"""The competition centre owns standings; its old independent URL only redirects."""
from html.parser import HTMLParser
from urllib.request import build_opener,ProxyHandler,HTTPRedirectHandler
from urllib.error import HTTPError
class Page(HTMLParser):
 def __init__(self):super().__init__();self.main=False;self.tabs=False;self.links=[];self.tablinks=[];self.current=None;self.groups=0
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='nav':self.main=a.get('id')=='navigation';self.tabs='competition-nav' in a.get('class','').split()
  if tag=='a':
   self.current={'href':a.get('href'),'active':a.get('aria-current'),'text':''}
   if self.main:self.links.append(self.current)
   if self.tabs:self.tablinks.append(self.current)
  if tag=='details' and 'data-group' in a:self.groups+=1
 def handle_data(self,data):
  if self.current is not None:self.current['text']+=data
 def handle_endtag(self,tag):
  if tag=='nav':self.main=self.tabs=False
  if tag=='a':self.current=None
class NoRedirect(HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs):return None
base='http://127.0.0.1:8088';opener=build_opener(ProxyHandler({}))
for path in ['/worldcup','/worldcup/standings','/teams','/players','/news','/forum','/countries']:
 p=Page();p.feed(opener.open(base+path,timeout=30).read().decode('utf-8'))
 assert len(p.links)==6,(path,p.links)
 assert not any(x['text'].strip()=='数据' or x['href']=='/stats' for x in p.links),path
 if path.startswith('/worldcup'):
  assert [x['text'].strip() for x in p.links if x['active']]==['赛事'],path
  entries=[x for x in p.tablinks if x['text'].strip()=='小组积分'];assert len(entries)==1 and entries[0]['href']=='/worldcup/standings'
 if path=='/worldcup/standings':assert p.groups==12 and entries[0]['active']=='page'
try:build_opener(ProxyHandler({}),NoRedirect()).open(base+'/stats');raise AssertionError('Legacy URL did not redirect')
except HTTPError as e:assert e.code==301 and e.headers['Location']==base+'/worldcup/standings',(e.code,dict(e.headers))
print('PASS navigation: 6 primary items; one standings entry; /stats -> 301 /worldcup/standings; 12 groups.')
