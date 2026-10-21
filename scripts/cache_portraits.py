"""Cache selected official FIFA portrait bytes unchanged.

Input: read-only Yii2 export in storage/design-assets/portrait-sources.json.
Selection: 48 Team::getFeaturePlayer() rows plus caps DESC LIMIT 24; URLs dedupe.
Output: public/assets/media/portraits/manifest.json keyed by SHA256(source_url).
Only HTTPS digitalhub.fifa.com/api.fifa.com; 2 request starts/sec; 3 retries.
No image decoding, resizing, pixel edits, generated imagery or background removal.
"""
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
import argparse
import hashlib
import json
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'public/assets/media/portraits'
HOSTS = {'digitalhub.fifa.com', 'api.fifa.com'}
LIMIT = 10 * 1024 * 1024
LOCK = threading.Lock()
LAST_START = 0.0


def allowed(url):
    p = urllib.parse.urlsplit(url)
    return p.scheme == 'https' and p.hostname in HOSTS and p.port in (None, 443) and not p.username and not p.password


class OfficialRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, newurl):
        if not allowed(newurl):
            raise ValueError('Redirect is not an allowed official HTTPS host')
        return super().redirect_request(request, fp, code, message, headers, newurl)


def rate_limit():
    global LAST_START
    with LOCK:
        delay = 0.5 - (time.monotonic() - LAST_START)
        if delay > 0:
            time.sleep(delay)
        LAST_START = time.monotonic()


def image_type(data):
    if data.startswith(b'\x89PNG\r\n\x1a\n'):
        return 'png', 'image/png'
    if data.startswith(b'\xff\xd8\xff') and data.endswith(b'\xff\xd9'):
        return 'jpg', 'image/jpeg'
    if len(data) >= 12 and data[:4] == b'RIFF' and data[8:12] == b'WEBP':
        return 'webp', 'image/webp'
    raise ValueError('Response bytes are not PNG, JPEG or WebP')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def fetch(url, selections, old):
    key = digest(url.encode('utf-8'))
    if not allowed(url):
        raise ValueError('Source is not an allowed official HTTPS host')
    existing = old.get(key)
    if existing and existing.get('source_url') == url:
        file = ROOT / 'public' / existing['path'].lstrip('/')
        if file.parent == OUT and file.is_file():
            data = file.read_bytes()
            if len(data) <= LIMIT and digest(data) == existing.get('sha256'):
                _, mime = image_type(data)
                if mime == existing.get('mime'):
                    existing['selections'] = selections
                    return key, existing
    error = ''
    for attempt in range(4):
        try:
            rate_limit()
            opener = urllib.request.build_opener(OfficialRedirect())
            request = urllib.request.Request(url, headers={'User-Agent': 'WorldCupUniverse-CourseProject/2.0'})
            with opener.open(request, timeout=30) as response:
                if response.status != 200:
                    raise ValueError('HTTP ' + str(response.status))
                if not allowed(response.geturl()):
                    raise ValueError('Unexpected final response host')
                size = response.headers.get('Content-Length')
                if size and int(size) > LIMIT:
                    raise ValueError('Response exceeds 10 MiB limit')
                data = response.read(LIMIT + 1)
                if len(data) > LIMIT:
                    raise ValueError('Response exceeds 10 MiB limit')
            extension, mime = image_type(data)
            file = OUT / (key + '.' + extension)
            file.write_bytes(data)
            reopened = file.read_bytes()
            if reopened != data:
                raise ValueError('Written file differs from downloaded bytes')
            return key, {
                'source_url': url,
                'path': '/assets/media/portraits/' + file.name,
                'sha256': digest(reopened),
                'bytes': len(reopened),
                'mime': mime,
                'retrieved_at': datetime.now(timezone.utc).isoformat(),
                'selections': selections,
            }
        except (OSError, ValueError, urllib.error.URLError) as exc:
            error = str(exc)
            if attempt < 3:
                time.sleep(1 + attempt)
    raise RuntimeError(error)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source-list', type=Path, default=ROOT / 'storage/design-assets/portrait-sources.json')
    args = parser.parse_args()
    payload = json.loads(args.source_list.read_text('utf-8-sig'))
    OUT.mkdir(parents=True, exist_ok=True)
    manifest_file = OUT / 'manifest.json'
    old = json.loads(manifest_file.read_text('utf-8')) if manifest_file.exists() else {}
    urls = {}
    failures = list(payload.get('missing', []))
    for row in payload['selections']:
        url = row.get('source_url')
        if not url:
            failures.append(dict(row, error='Missing portrait source URL'))
            continue
        urls.setdefault(url, []).append({k: v for k, v in row.items() if k != 'source_url'})
    manifest = {}
    with ThreadPoolExecutor(max_workers=3) as pool:
        jobs = {pool.submit(fetch, url, rows, old): url for url, rows in urls.items()}
        for job in as_completed(jobs):
            url = jobs[job]
            try:
                key, record = job.result()
                manifest[key] = record
                print('CACHED', len(manifest), '/', len(urls), record['bytes'], key[:12], flush=True)
            except Exception as exc:
                failures.append({'source_url': url, 'error': str(exc), 'selections': urls[url]})
                print('MISSING', url, str(exc), flush=True)
    manifest = dict(sorted(manifest.items()))
    manifest_file.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n', 'utf-8')
    (OUT / 'failures.json').write_text(json.dumps(failures, ensure_ascii=False, indent=2) + '\n', 'utf-8')
    total_bytes = 0
    for key, record in json.loads(manifest_file.read_text('utf-8')).items():
        file = ROOT / 'public' / record['path'].lstrip('/')
        data = file.read_bytes()
        if key != digest(record['source_url'].encode('utf-8')) or digest(data) != record['sha256'] or len(data) != record['bytes']:
            raise RuntimeError('Reopened cache verification failed: ' + file.name)
        _, mime = image_type(data)
        if mime != record['mime']:
            raise RuntimeError('Reopened MIME verification failed: ' + file.name)
        total_bytes += len(data)
    coverage = {'selection_rows': len(payload['selections']), 'unique_urls': len(urls), 'cached_files': len(manifest), 'failures': len(failures), 'bytes': total_bytes, 'scope': payload.get('selection_rule'), 'pixel_changes': 'none; original response bytes preserved'}
    (OUT / 'coverage.json').write_text(json.dumps(coverage, ensure_ascii=False, indent=2) + '\n', 'utf-8')
    print('REOPENED', len(manifest), 'original files;', len(failures), 'missing;', total_bytes, 'bytes.', flush=True)
    return 0 if not failures else 1


if __name__ == '__main__':
    raise SystemExit(main())
