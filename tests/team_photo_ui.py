"""All 48 rendered team cards/details and served image bytes, not just manifest counters."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,ProxyHandler
import hashlib,json
ROOT=Path(__file__).resolve().parents[1]
opener=build_opener(ProxyHandler({}))
def get(path):return opener.open('http://127.0.0.1:8088'+path,timeout=30).read()
class Page(HTMLParser):
    def __init__(self):super().__init__();self.images=[];self.cards=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='img':self.images.append(a)
        if tag=='a' and 'squad-card' in a.get('class','').split():self.cards.append(a)
manifest=json.loads((ROOT/'public/assets/media/team-photos/manifest.json').read_text('utf-8'))
cards=[];covers=[]
for url in ['/teams','/teams?page=2']:
    p=Page();p.feed(get(url).decode('utf-8'));cards+=p.cards
    covers += [i['src'] for i in p.images if 'squad-photo' in i.get('class','').split()]
    assert 'flag-cover' not in {c for a in p.cards for c in a.get('class','').split()},url
assert len(cards)==len({a['href'] for a in cards})==48
assert set(covers)=={r['path'] for r in manifest['teams'].values()} and len(covers)==48
for a in cards:
    p=Page();p.feed(get(a['href']).decode('utf-8'))
    photo=[i['src'] for i in p.images if 'team-squad-hero' in i.get('class','').split()]
    assert len(photo)==1 and photo[0] in covers,a['href']
for code,r in manifest['teams'].items():
    served=get(r['path'])
    assert len(served)==r['bytes'] and hashlib.sha256(served).hexdigest()==r['sha256'],code
credits=get('/credits').decode('utf-8')
assert '48 支参赛球队' in credits and '官方来源 ↗' not in credits
print('PASS team photo UI: 48/48 unique list covers; 48/48 native detail covers; 48/48 HTTP photo hashes; no flag-only or individual-player covers.')
