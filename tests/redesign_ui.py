"""Actual HTTP/asset checks for the six screenshot-driven redesign requests."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,ProxyHandler
from urllib.error import HTTPError
import hashlib,json

ROOT=Path(__file__).resolve().parents[1]
opener=build_opener(ProxyHandler({}))
def get(path):
    return opener.open('http://127.0.0.1:8088'+path,timeout=30).read().decode('utf-8')
class Nodes(HTMLParser):
    def __init__(self):
        super().__init__();self.links=[];self.match_ids=[];self.edges=[];self.parents=[];self.images=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='a':self.links.append(a)
        if tag=='img':self.images.append(a)
        if tag=='details':
            if 'data-match' in a:
                mid=int(a['data-match']);self.match_ids.append(mid)
                parent=next((x for x in reversed(self.parents) if x is not None),None)
                if parent:self.edges.append((mid,parent))
                self.parents.append(mid)
            else:self.parents.append(None)
    def handle_endtag(self,tag):
        if tag=='details' and self.parents:self.parents.pop()

forum=get('/forum')
assert 'community-topic-list' in forum and forum.count('示例帖')>=6
assert 'topic-panel' in forum and 'community-aside' in forum and 'footer-inner' in forum
tree=get('/worldcup');p=Nodes();p.feed(tree)
assert len(p.match_ids)==len(set(p.match_ids))==32
source=json.loads(get('/api/v1/bracket'))['data']
expected={(int(e['from_match_id']),int(e['to_match_id'])) for e in source if e['outcome']=='winner'}
assert set(p.edges)==expected,(set(p.edges)^expected)
assert 'bracket-scroll' not in tree and 'bracket-canvas' not in tree
assert len([i for i in p.images if 'team-flag' in i.get('class','').split()])>=64
countries=get('/countries');p=Nodes();p.feed(countries)
country_links=[a['href'] for a in p.links if 'country-tile' in a.get('class','').split()]
assert len(set(country_links))==48
for href in country_links:
    body=get(href)
    assert 'country-profile-head' in body and 'country-fact-list' in body and '世界杯国家队' in body,href
try:get('/countries/zz');raise AssertionError('Unknown country did not return 404')
except HTTPError as e:assert e.code==404
teams=get('/teams')
assert '/portraits/' not in teams and 'team-hero-player' not in get('/teams/brazil')
manifest=json.loads((ROOT/'public/assets/media/team-photos/manifest.json').read_text('utf-8'))
verified=0
for code,entry in manifest['teams'].items():
    if entry.get('path'):
        assert entry['year']==2026 and entry['status']=='verified_2026_team_photo'
        data=(ROOT/'public'/entry['path'].lstrip('/')).read_bytes()
        assert len(data)==entry['bytes'] and hashlib.sha256(data).hexdigest()==entry['sha256']
        assert entry['source_url'].startswith('https://') and entry['caption_evidence']
        verified+=1
    else:assert entry['status']=='missing_verified_2026_team_photo'
assert verified==48
comments=get('/teams/brazil')
assert 'comment-card' in comments and 'comment-compose' in comments and '还没有已审核的留言' not in comments
print('PASS redesign: 6 labelled example topics; 32 unique expandable matches / 30 verified winner edges; 48 working country details; 48 hashed 2026 squad photos; no individual team covers; new comment and footer components.')
