"""Rendered team/flag coverage without a browser; no database writes."""
from html.parser import HTMLParser
from urllib.request import build_opener, ProxyHandler
import sys
class Node:
 def __init__(self,tag='',attrs=(),parent=None):self.tag=tag;self.a=dict(attrs);self.parent=parent;self.children=[]
 def cls(self,x):return x in self.a.get('class','').split()
 def all(self,p):return [c for c in self.children if p(c)]+[v for c in self.children for v in c.all(p)]
class Tree(HTMLParser):
 def __init__(self):super().__init__();self.root=Node();self.at=self.root
 def handle_starttag(self,t,a):
  n=Node(t,a,self.at);self.at.children.append(n)
  if t not in ['img','input','meta','link','br','hr','source','wbr']:self.at=n
 def handle_endtag(self,t):
  n=self.at
  while n.parent and n.tag!=t:n=n.parent
  if n.parent:self.at=n.parent
opener=build_opener(ProxyHandler({})); errors=[];checked=0
cases=[('/teams','nation-bottom',24,1),('/players','p-body',24,1),('/worldcup','fixture-row',104,2),('/worldcup','bracket-node',32,2),('/worldcup/standings','team-label',48,1)]
for route,cls,count,flags in cases:
 t=Tree();t.feed(opener.open('http://127.0.0.1:8088'+route).read().decode());nodes=t.root.all(lambda n:n.cls(cls))
 if len(nodes)<count:errors.append(f'{route}: expected >= {count} {cls}, got {len(nodes)}')
 for n in nodes:
  imgs=n.all(lambda i:i.tag=='img' and i.cls('team-flag'))
  if len(imgs)<flags:errors.append(f'{route}: {cls} missing flag');continue
  for img in imgs:
   u=img.a.get('src','');assert u.startswith('/assets/media/flags/') and img.a.get('alt','').endswith('国旗'),u
  checked+=1
if errors:print(f'FAIL flag coverage: {len(errors)} failures; {checked} containers verified');sys.exit(1)
print(f'PASS flag coverage: {checked} team containers; local flag images and Chinese alt text.')
