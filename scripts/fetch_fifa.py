"""Collect public FIFA responses and the official squad PDF; never writes the DB.

Usage: python scripts/fetch_fifa.py [--resume DIR]
Dependencies: pdfplumber. A failed or incomplete collection is not publishable.
"""
import argparse
import datetime as dt
import hashlib
import json
import logging
from pathlib import Path
import re
import time
import unicodedata
import urllib.request
import urllib.parse
import pdfplumber

logging.getLogger('pdfminer').setLevel(logging.ERROR)
ROOT = Path(__file__).resolve().parents[1]
API = 'https://api.fifa.com/api/v3'
PDF = 'https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf'
CALENDAR = API + '/calendar/matches?idCompetition=17&idSeason=285023&language=en&count=500'

def localized(value):
    return next((x['Description'] for x in value or [] if x.get('Locale', '').startswith('en')), '')

def normalized(value):
    return re.sub('[^a-z0-9]', '', unicodedata.normalize('NFKD', value).encode('ascii', 'ignore').decode().lower())

class Collector:
    def __init__(self, folder):
        self.folder = folder
        folder.mkdir(parents=True, exist_ok=True)
        self.records = []
        self.last_request = 0

    def get(self, url, filename):
        parsed = urllib.parse.urlsplit(url)
        if parsed.scheme != 'https' or parsed.hostname not in {'api.fifa.com', 'fdp.fifa.org'}:
            raise ValueError('Source host is not in the official allowlist')
        path = self.folder / filename
        meta = path.with_suffix(path.suffix + '.meta.json')
        if path.exists() and meta.exists():
            record = json.loads(meta.read_text('utf-8'))
            if record['url'] != url or record['sha256'] != hashlib.sha256(path.read_bytes()).hexdigest():
                raise ValueError('Cached source identity/hash mismatch: ' + filename)
        else:
            time.sleep(max(0, 1 - (time.monotonic() - self.last_request)))
            for attempt in range(3):
                try:
                    self.last_request = time.monotonic()
                    req = urllib.request.Request(url, headers={'User-Agent': 'WorldCupUniverse-Coursework/2.0 (public data research)', 'Accept': 'application/json,application/pdf'})
                    with urllib.request.urlopen(req, timeout=45) as res:
                        if urllib.parse.urlsplit(res.url).hostname not in {'api.fifa.com', 'fdp.fifa.org'}:
                            raise ValueError('Unexpected redirect host')
                        payload = res.read(12 * 1024 * 1024 + 1)
                        if len(payload) > 12 * 1024 * 1024:
                            raise ValueError('Response exceeds 12 MB')
                        record = {'url': url, 'file': filename, 'retrieved_at': dt.datetime.now(dt.timezone.utc).isoformat(), 'http_status': res.status, 'content_type': res.headers.get_content_type(), 'sha256': hashlib.sha256(payload).hexdigest(), 'bytes': len(payload)}
                    path.write_bytes(payload)
                    meta.write_text(json.dumps(record, indent=2), 'utf-8')
                    break
                except Exception:
                    if attempt == 2:
                        raise
                    time.sleep(2 ** (attempt + 1))
        self.records.append(record)
        return path

    def json(self, url, filename):
        return json.loads(self.get(url, filename).read_text('utf-8-sig'))

def parse_squads(path):
    teams = []
    with pdfplumber.open(path) as pdf:
        if len(pdf.pages) != 48:
            raise ValueError('Expected 48 official squad pages')
        for page_no, p in enumerate(pdf.pages, 1):
            text = p.extract_text()
            heading = re.search(r'^(.+?) \(([A-Z]{3})\)$', text, re.M)
            if not heading:
                raise ValueError('Missing squad heading on page ' + str(page_no))
            players = []
            for line in text.splitlines():
                row = re.match(r'^(\d+) (GK|DF|MF|FW) (.+?) (\d{2}/\d{2}/\d{4})(?: (.*?))? (\d+) (\d+) (\d+)$', line)
                if not row:
                    continue
                number, position, names, dob, club, height, caps, goals = row.groups()
                players.append({'shirt_number': int(number), 'position': position, 'listed_names': names.replace('\x00',''), 'birth_date': dt.datetime.strptime(dob, '%d/%m/%Y').date().isoformat(), 'club': club or None, 'height_cm': int(height), 'caps': int(caps), 'goals': int(goals), 'pdf_page': page_no})
            if len(players) != 26 or {p['shirt_number'] for p in players} != set(range(1, 27)):
                raise ValueError('Squad row parsing failed on page ' + str(page_no))
            teams.append({'name': heading[1], 'code': heading[2], 'players': players, 'pdf_page': page_no})
    if len({t['code'] for t in teams}) != 48 or sum(len(t['players']) for t in teams) != 1248:
        raise ValueError('Squad completeness check failed')
    return teams

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--resume', type=Path)
    args = parser.parse_args()
    folder = args.resume or ROOT / 'storage/source_snapshots/official' / dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
    collector = Collector(folder.resolve())
    calendar = collector.json(CALENDAR, 'matches.json')
    matches = calendar.get('Results', [])
    if calendar.get('ContinuationToken') or len(matches) != 104 or len({m['IdMatch'] for m in matches}) != 104 or {int(m['MatchNumber']) for m in matches} != set(range(1, 105)):
        raise ValueError('Calendar incomplete; refusing partial publication')
    if any(m['IdCompetition'] != '17' or m['IdSeason'] != '285023' for m in matches):
        raise ValueError('Unexpected competition/season')
    squads = parse_squads(collector.get(PDF, 'squads.pdf'))
    team_refs = {s['IdTeam']: s for m in matches for s in (m['Home'], m['Away']) if s}
    if len(team_refs) != 48 or {s['IdCountry'] for s in team_refs.values()} != {t['code'] for t in squads}:
        raise ValueError('Calendar and squad team sets disagree')
    teams = {}
    for id_team in sorted(team_refs):
        teams[id_team] = collector.json(API + '/teams/' + id_team + '?language=en', 'team-' + id_team + '.json')
        if teams[id_team].get('IdTeam') != id_team:
            raise ValueError('Team identifier mismatch')
    lives = []
    for idx, m in enumerate(matches, 1):
        url = f"{API}/live/football/17/285023/{m['IdStage']}/{m['IdMatch']}?language=en"
        live = collector.json(url, 'live-' + m['IdMatch'] + '.json')
        if live.get('IdMatch') != m['IdMatch']:
            raise ValueError('Live identifier mismatch')
        lives.append(live)
        print(f"FIFA details {idx}/104", flush=True)
    output = {'schema_version': 1, 'competition_id': '17', 'season_id': '285023', 'squads': squads, 'teams': teams, 'matches': matches, 'live': lives, 'sources': collector.records}
    (folder / 'normalized.json').write_text(json.dumps(output, ensure_ascii=False, indent=2), 'utf-8')
    manifest = {'complete': True, 'teams': 48, 'players': 1248, 'matches': 104, 'match_details': len(lives), 'sources': collector.records, 'normalized_sha256': hashlib.sha256((folder / 'normalized.json').read_bytes()).hexdigest()}
    (folder / 'manifest.json').write_text(json.dumps(manifest, indent=2), 'utf-8')
    print('PASS FIFA collection: 48 teams / 1248 players / 104 matches / 104 details', flush=True)
    print(folder.resolve(), flush=True)

if __name__ == '__main__':
    main()
