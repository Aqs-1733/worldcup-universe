"""Real HTTP acceptance tests for administrator uploads, access control and front-end display.

Run against a server backed by a dedicated test database only, from the project root on the same machine
(the script inspects and removes the files it uploads):
  WCU_TEST_URL=http://127.0.0.1:8098 ADMIN_USERNAME=admin ADMIN_PASSWORD=... python tests/upload_acceptance.py
"""
import json, os, re, struct, sys, urllib.error, urllib.parse, urllib.request, http.cookiejar, zlib
from pathlib import Path
sys.stdout.reconfigure(encoding="utf-8")

BASE = os.environ.get('WCU_TEST_URL', 'http://127.0.0.1:8098')
ROOT = Path(__file__).resolve().parent.parent


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

    def req(self, path, body=None, headers=None, method=None):
        request = urllib.request.Request(BASE + path, body, headers or {}, method=method)
        try:
            r = self.opener.open(request, timeout=60)
            return r.status, r.headers, r.read()
        except urllib.error.HTTPError as e:
            return e.code, e.headers, e.read()

    def text(self, path):
        s, h, b = self.req(path)
        return s, h, b.decode('utf8', 'replace')

    def csrf(self):
        data = json.loads(self.text('/api/v1/session')[2])['data']
        return data['csrfParam'], data['csrfToken']

    def form(self, path, fields, files=None, csrf=True):
        fields = dict(fields)
        if csrf:
            k, v = self.csrf()
            fields[k] = v
        boundary = '----wcu' + os.urandom(8).hex()
        parts = []
        for k, v in fields.items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
        for k, (name, data, ctype) in (files or {}).items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + data + b'\r\n')
        parts.append(f'--{boundary}--\r\n'.encode())
        s, h, b = self.req(path, b''.join(parts), {'Content-Type': 'multipart/form-data; boundary=' + boundary}, 'POST')
        return s, h, b.decode('utf8', 'replace')


def png(width=3, height=2, rgb=(200, 30, 40)):
    raw = b''.join(b'\x00' + bytes(rgb) * width for _ in range(height))
    chunk = lambda t, d: struct.pack('>I', len(d)) + t + d + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(raw)) + chunk(b'IEND', b'')


PDF = b'%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n'
checks = []
created = []  # (table, id)


def ck(test, label):
    if not test:
        raise AssertionError(label)
    checks.append(label)


def upload_count():
    base = ROOT / 'public' / 'uploads'
    return sum(1 for p in base.rglob('*') if p.is_file() and p.name != '.gitkeep') if base.exists() else 0


def uploads_in(html):
    return set(re.findall(r'/uploads/\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]+', html))


def new_id(admin, table, marker):
    html = admin.text(f'/admin/{table}?q=' + urllib.parse.quote(marker))[2]
    ids = re.findall(r'/admin/' + table + r'/edit\?id=(\d+)', html)
    ck(ids, f'{table} record "{marker}" listed in admin')
    return int(ids[0])


guest, user, admin = Client(), Client(), Client()
prefix = 'qa_up_' + os.urandom(3).hex()
stray = ROOT / 'data' / 'team' / f'{prefix}_hidden.pdf'
try:
    # --- access control ------------------------------------------------------------------------------------------
    s = guest.req('/admin')[0]
    ck(s in (302, 403), f'guest is kept out of /admin (HTTP {s})')
    ck(user.form('/register', {'RegisterForm[username]': prefix, 'RegisterForm[email]': prefix + '@example.org', 'RegisterForm[password]': 'QA-password-2026-test', 'RegisterForm[display_name]': 'QA'})[0] == 302, 'ordinary user registered')
    ck(user.req('/admin')[0] == 403, 'ordinary user receives 403 on /admin')
    ck(user.form('/admin/team_members/save', {'Record[name]': prefix, 'Record[role_name]': 'x'}, {'upload[photo_url]': ('a.png', png(), 'image/png')})[0] == 403, 'ordinary user cannot post admin uploads')
    ck(admin.form('/login', {'LoginForm[username]': os.environ.get('ADMIN_USERNAME', 'admin'), 'LoginForm[password]': os.environ['ADMIN_PASSWORD']})[0] == 302, 'administrator login')
    before = upload_count()
    ck(admin.form('/admin/team_members/save', {'Record[name]': prefix, 'Record[role_name]': 'QA'}, {'upload[photo_url]': ('a.png', png(), 'image/png')}, csrf=False)[0] == 400, 'admin upload without CSRF token rejected (400)')
    ck(upload_count() == before, 'rejected CSRF request stored no file')

    # --- validation ----------------------------------------------------------------------------------------------
    bad = {
        'PHP code renamed to .jpg': ('shell.jpg', b'<?php system($_GET["c"]); ?>', 'image/jpeg'),
        'double extension x.php.png': ('x.php.png', png(), 'image/png'),
        'SVG image': ('logo.svg', b'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'image/svg+xml'),
        'PNG carrying a PHP tag': ('poly.png', png() + b'<?php echo 1; ?>', 'image/png'),
        'PNG content named .jpg': ('mismatch.jpg', png(), 'image/jpeg'),
        'executable extension .phtml': ('a.phtml', b'<?php ?>', 'application/octet-stream'),
    }
    for label, f in bad.items():
        s, _, body = admin.form('/admin/team_members/save', {'Record[name]': prefix + '_bad', 'Record[role_name]': 'QA', 'Record[sort_order]': '99', 'Record[is_visible]': '0'}, {'upload[photo_url]': f})
        ck(s == 422 and 'form-errors' in body, f'rejected: {label} (HTTP {s})')
    ck(upload_count() == before, 'rejected uploads left no file in public/uploads')
    s, _, body = admin.form('/admin/coursework_files/save', {'Record[scope]': 'team', 'Record[title]': prefix + ' traversal', 'Record[storage_path]': 'data/team/../../.env', 'Record[is_visible]': '1'})
    ck(s == 422, 'coursework storage_path with ../ rejected')

    # --- team member photo: upload, front-end display, replace -----------------------------------------------------
    s, h, _ = admin.form('/admin/team_members/save', {'Record[name]': prefix, 'Record[role_name]': 'QA 成员', 'Record[student_no]': prefix, 'Record[sort_order]': '98', 'Record[is_visible]': '1'}, {'upload[photo_url]': ('member.png', png(4, 4, (10, 120, 200)), 'image/png')})
    ck(s == 302, 'team member created with uploaded photo')
    member = new_id(admin, 'team_members', prefix); created.append(('team_members', member))
    edit = admin.text(f'/admin/team_members/edit?id={member}')[2]
    first = sorted(uploads_in(edit))
    ck(len(first) == 1 and 'media-preview' in edit, 'admin edit form previews the stored photo')
    s, h, data = guest.req(first[0])
    ck(s == 200 and h.get('Content-Type', '').startswith('image/png') and data[:8] == b'\x89PNG\r\n\x1a\n', 'uploaded photo is publicly served as image/png')
    ck(re.fullmatch(r'/uploads/\d{4}/\d{2}/[a-f0-9]{32}\.png', first[0]) is not None, 'stored under a random 32-hex name')
    ck(first[0] in guest.text('/team')[2], '/team shows the uploaded member photo')
    ck(first[0] in guest.text('/')[2], 'home page member card shows the uploaded photo')
    s, _, _ = admin.form('/admin/team_members/save', {'id': str(member), 'Record[name]': prefix, 'Record[role_name]': 'QA 成员', 'Record[student_no]': prefix, 'Record[sort_order]': '98', 'Record[is_visible]': '1'}, {'upload[photo_url]': ('member2.png', png(5, 5, (0, 160, 60)), 'image/png')})
    second = sorted(uploads_in(admin.text(f'/admin/team_members/edit?id={member}')[2]))
    ck(s == 302 and len(second) == 1 and second != first, 'photo replaced by a second upload')
    ck(second[0] in guest.text('/team')[2] and first[0] not in guest.text('/team')[2], '/team shows the replacement immediately')

    # --- home page media slot -------------------------------------------------------------------------------------
    stadium = new_id(admin, 'media_assets', 'home-stadium')
    form = admin.text(f'/admin/media_assets/edit?id={stadium}')[2]
    original = re.search(r'name="Record\[path\]" value="([^"]+)"', form).group(1)
    fields = {f'Record[{k}]': v for k, v in re.findall(r'name="Record\[(\w+)\]" value="([^"]*)"', form)}
    fields.update({'id': str(stadium), 'Record[status]': 'approved'})
    s, _, _ = admin.form('/admin/media_assets/save', fields, {'upload[path]': ('stadium.png', png(8, 4, (20, 20, 90)), 'image/png')})
    hero = sorted(uploads_in(admin.text(f'/admin/media_assets/edit?id={stadium}')[2]))
    ck(s == 302 and hero and hero[0] in guest.text('/')[2], 'home stadium image follows the 媒体资产 upload')
    fields['Record[path]'] = original
    ck(admin.form('/admin/media_assets/save', fields)[0] == 302 and original in guest.text('/')[2], 'home stadium image restored to the built-in file')

    # --- news cover -----------------------------------------------------------------------------------------------
    news = json.loads(guest.text('/api/v1/news')[2])['data']
    if news:
        article = news[0]['id']
        s, _, _ = admin.form(f'/admin/news_articles/save', {'id': str(article)}, {'upload[image]': ('cover.png', png(6, 4, (240, 200, 0)), 'image/png')})
        cover = sorted(uploads_in(admin.text(f'/admin/news_articles/edit?id={article}')[2]))
        ck(s == 302 and cover and cover[0] in guest.text(f'/news/{article}')[2] + guest.text('/news')[2], 'news cover upload appears on the public news pages')
    else:
        print('SKIP news cover: no published articles in this database')

    # --- coursework: private until published ----------------------------------------------------------------------
    s, _, _ = admin.form('/admin/coursework_files/save', {'Record[scope]': 'team', 'Record[title]': prefix + ' 部署文档', 'Record[storage_path]': '', 'Record[is_visible]': '0'}, {'upload[storage_path]': ('deploy.pdf', PDF, 'application/pdf')})
    ck(s == 302, 'coursework PDF uploaded as hidden')
    doc = new_id(admin, 'coursework_files', prefix + ' 部署文档'); created.append(('coursework_files', doc))
    stored = re.search(r'data/team/uploads/[a-f0-9]{32}\.pdf', admin.text(f'/admin/coursework_files/edit?id={doc}')[2]).group(0)
    ck((ROOT / stored).is_file(), 'coursework file stored privately under data/team/uploads')
    ck(guest.req(f'/downloads/{doc}')[0] == 404, 'hidden coursework not downloadable by visitors')
    ck(prefix + ' 部署文档' not in guest.text('/team')[2], 'hidden coursework not listed on /team')
    ck(guest.req('/downloads/team/' + Path(stored).name)[0] == 404, 'uploads/ subfolder not reachable through the folder route')
    s, h, data = admin.req(f'/admin/coursework_files/file?id={doc}')
    ck(s == 200 and data.startswith(b'%PDF'), 'administrator can download the hidden file')
    ck(user.req(f'/admin/coursework_files/file?id={doc}')[0] == 403, 'ordinary user cannot use the admin download')
    s, _, _ = admin.form('/admin/coursework_files/save', {'id': str(doc), 'Record[scope]': 'team', 'Record[title]': prefix + ' 部署文档', 'Record[storage_path]': stored, 'Record[is_visible]': '1'})
    s2, h, data = guest.req(f'/downloads/{doc}')
    ck(s == 302 and s2 == 200 and data.startswith(b'%PDF'), 'published coursework downloadable')
    ck(urllib.parse.quote(prefix) in h.get('Content-Disposition', '') or prefix in h.get('Content-Disposition', ''), 'download is named after the registered title')
    ck(prefix + ' 部署文档' in guest.text('/team')[2], 'published coursework listed on /team')
    stray.write_bytes(PDF)
    s, _, _ = admin.form('/admin/coursework_files/save', {'Record[scope]': 'team', 'Record[title]': prefix + ' stray', 'Record[storage_path]': f'data/team/{stray.name}', 'Record[is_visible]': '0'})
    created.append(('coursework_files', new_id(admin, 'coursework_files', prefix + ' stray')))
    ck(guest.req('/downloads/team/' + stray.name)[0] == 404, 'hidden record also blocks the dropped-in file route')

    print('\n'.join('  PASS ' + c for c in checks))
    print(f'PASS upload acceptance: {len(checks)} checks.')
finally:
    for table, rid in created:
        admin.form(f'/admin/{table}/delete', {'id': str(rid)})
    stray.unlink(missing_ok=True)
