"""Cache publisher-generated Commons 1280px previews; retain untouched originals and attribution."""
from pathlib import Path
import json,urllib.request,time,hashlib
root=Path(__file__).resolve().parents[1];lib=json.loads((root/'public/assets/media/photos/library.json').read_text('utf-8'));out=root/'public/assets/media/photos/display';out.mkdir(exist_ok=True);manifest={}
for p in lib:
 original=p['original_url'];assert original.startswith('https://upload.wikimedia.org/wikipedia/commons/')
 rest=original.split('/commons/',1)[1];filename=rest.rsplit('/',1)[1];url='https://upload.wikimedia.org/wikipedia/commons/thumb/'+rest+'/1280px-'+filename;path=out/(p['key']+'.jpg')
 try:
  if not path.exists():
   time.sleep(1)
   with urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0'}),timeout=25) as r:b=r.read(6*1024*1024)
   if not b.startswith(b'\xff\xd8'):raise ValueError('Not a JPEG preview')
   path.write_bytes(b)
  b=path.read_bytes();manifest[p['key']]={'source_url':p['source_url'],'preview_url':url,'path':'/assets/media/photos/display/'+path.name,'bytes':len(b),'sha256':hashlib.sha256(b).hexdigest(),'changes':'Publisher-generated 1280px preview; original is retained separately.'};print('PREVIEW',p['key'],len(b),flush=True)
 except Exception as e:print('PREVIEW UNAVAILABLE',p['key'],type(e).__name__,flush=True)
(out/'manifest.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2),'utf-8');print('PASS previews',len(manifest))
