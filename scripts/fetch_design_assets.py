"""Collect attributed, high-resolution venue/city photographs from Commons.

Run once when curating the design library, not on page requests. Original image
bytes and API licensing metadata are preserved; publication uses reviewed files.
"""
import hashlib, html, json, re, time, urllib.parse, urllib.request
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'storage/design-assets'
OUT.mkdir(parents=True,exist_ok=True)
MEDIA=ROOT/'public/assets/media/photos'
MEDIA.mkdir(parents=True,exist_ok=True)
queries={
 'azteca':('阿兹特克体育场','Panorámica Estadio Azteca.jpg'),
 'metlife':('纽约／新泽西体育场','MetLife Stadium soccer panorama'),
 'bc-place':('温哥华卑诗体育场','BC Place soccer interior'),
 'sofi':('洛杉矶体育场','SoFi Stadium interior panorama'),
 'lumen':('西雅图体育场','Lumen Field soccer panorama'),
 'bmo':('多伦多体育场','BMO Field soccer panorama'),
 'dallas':('达拉斯体育场','AT&T Stadium interior panorama'),
 'vancouver':('温哥华','Vancouver-Panorama.jpg'),
 'mexico-city':('墨西哥城','Mexico City Zocalo panorama'),
 'new-york':('纽约','Manhattan skyline blue hour panorama'),
}
HEADERS={'User-Agent':'WorldCupUniverse-Coursework/2.0 (attributed educational venue gallery)'}
def get(url):
 for attempt in range(3):
  try:
   with urllib.request.urlopen(urllib.request.Request(url,headers=HEADERS),timeout=60) as r:
    return r.read(35*1024*1024)
  except Exception:
   if attempt==2:raise
   time.sleep(2**(attempt+1))
def api(params):
 time.sleep(1)
 return json.loads(get('https://commons.wikimedia.org/w/api.php?'+urllib.parse.urlencode({'format':'json','action':'query',**params})))
def clean(s):return html.unescape(re.sub('<[^>]*>','',s)).strip()

library=[]
for key,(label,query) in queries.items():
 cache=OUT/(key+'-candidates.json')
 if cache.exists():result=json.loads(cache.read_text('utf-8'))
 else:
  result=api({'generator':'search','gsrsearch':query,'gsrnamespace':'6','gsrlimit':'5','prop':'imageinfo','iiprop':'url|size|extmetadata'})
  cache.write_text(json.dumps(result,ensure_ascii=False,indent=2),'utf-8')
 candidates=[]
 for page in result.get('query',{}).get('pages',{}).values():
  if not page.get('imageinfo'):continue
  i=page['imageinfo'][0];e=i.get('extmetadata',{});license=e.get('LicenseShortName',{}).get('value','')
  if i.get('width',0)<1600 or i.get('size',0)>30*1024*1024 or not i['url'].lower().split('?')[0].endswith(('.jpg','.jpeg')):continue
  if not any(x in license.lower() for x in ['cc by','cc0','public domain']):continue
  candidates.append((page.get('index',99),page,i,e,license))
 if not candidates:
  print('NO LICENSED CANDIDATE',key,flush=True);continue
 _,page,i,e,license=sorted(candidates,key=lambda v:v[0])[0]
 url=i['url'].split('?')[0];path=MEDIA/(key+'.jpg')
 if not path.exists():path.write_bytes(get(url))
 record={'key':key,'label':label,'path':'/assets/media/photos/'+path.name,'source_url':i['descriptionurl'],'original_url':url,'title':page['title'],'author':clean(e.get('Artist',{}).get('value','')),'license':license,'license_url':e.get('LicenseUrl',{}).get('value',''),'photographed_at':clean(e.get('DateTimeOriginal',{}).get('value','')),'width':i['width'],'height':i['height'],'sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'changes':'No file edits; CSS crop/overlay only.'}
 library.append(record)
 (MEDIA/'library.json').write_text(json.dumps(library,ensure_ascii=False,indent=2),'utf-8')
 print('PHOTO',key,record['width'],record['height'],record['title'],flush=True)
print('Collected',len(library),'photographs.',flush=True)
