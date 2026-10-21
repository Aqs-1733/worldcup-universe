"""Curated official 2026 men's World Cup team photographs; never substitute portraits.

Only explicit association sources with 2026 World Cup/team-photo evidence qualify.
The 48-team manifest keeps missing images null. Publisher response bytes stay unchanged.
Uses cached source text for reproducibility; --refresh re-fetches those five pages.
"""
from pathlib import Path
from datetime import datetime, timezone, timedelta
from html.parser import HTMLParser
import argparse
import hashlib
import html
import io
import json
import re
import time
import urllib.parse
import urllib.request
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'public/assets/media/team-photos'
EVIDENCE = OUT / 'evidence'
HOSTS = {'www.dfb.de','assets.dfb.de','res.cloudinary.com','www.fotball.no','dam.fotball.no','fcf.com.co','cbf.media','cdn-cbf-asset-bank-watermarked.yapoli.com','cdn-cbf-asset-bank-thumbnail.yapoli.com','www.englandfootball.com','cdn.englandfootball.com'}
SOURCES = {
    'GER': {'source_url':'https://www.dfb.de/news/das-ist-das-offizielle-mannschaftsfoto-fuer-die-wm-2026','date':'2026-06-10','caption':'德国足协发布的2026世界杯官方球队合照，包含球员及教练工作人员。','caption_evidence':'Das ist das offizielle Mannschaftsfoto für die WM 2026','markers':['original_mannschaftsfoto_wm26.jpg','10.06.2026','WM 2026'],'credit':'GES Sportfoto/DFB'},
    'NOR': {'source_url':'https://www.fotball.no/landslag/norge-a-herrer/2026/her-er-var-mesterskapsside/','date':'2026-06-15','caption':'挪威足协2026世界杯专题文章配发的国家队正式合照。','caption_evidence':'offisielt lagbilde VM-troppen Greensboro','markers':['offisielt lagbilde VM-troppen Greensboro','15.06.2026','VM 2026'],'credit':'Bildbyrån'},
    'COL': {'source_url':'https://fcf.com.co/category/galeria-camino-al-mundial/','date':'2026-07-03','caption':'2026世界杯哥伦比亚对加纳赛前，哥伦比亚球队合影。','caption_evidence':'Colombia players pose for a team photograph ... FIFA World Cup 2026','markers':['Colombia players pose for a team photograph','FIFA World Cup 2026','July 03, 2026','1.-Colombia-vs-Ghana-3-de-julio'],'credit':'Julian Finney - FIFA/FIFA via Getty Images'},
    'BRA': {'source_url':'https://cbf.media/en/p/foto-oficial-copa-2026-masculino-01062026/a/34f9dc8a-2049-46fc-b036-771df00820fe','date':'2026-06-01','caption':'巴西足协媒体库发布的2026世界杯官方球队合照；保留官方公开预览的水印。','caption_evidence':'Foto oficial - Copa 2026','markers':['Foto oficial - Copa 2026','01062026_foto_oficial_copa_2026'],'credit':'CBF Media Center; public watermarked rendition','watermarked':True},
    'ENG': {'source_url':'https://www.englandfootball.com/articles/2026/Jun/12/england-mens-senior-team-marks-and-spencers-squad-photo-20261206','date':'2026-06-12','caption':'英格兰足协发布的2026世界杯官方全队合照，球队穿着正式出征服装。','caption_evidence':"England men's squad wear M&S for official squad photo",'markers':['Team-photo-1440-20261106','World Cup','2026'],'credit':'England Football / The FA / M&S'},
}


class PageImages(HTMLParser):
    def __init__(self):
        super().__init__(); self.images=[]; self.og_image=''
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='img':self.images.append(a)
        if tag=='meta' and a.get('property')=='og:image':self.og_image=a.get('content','')


def sha(data):return hashlib.sha256(data).hexdigest()


def get(url, limit):
    parsed=urllib.parse.urlsplit(url)
    if parsed.scheme!='https' or parsed.hostname not in HOSTS:raise ValueError('Unapproved official-source host')
    request=urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0 (WorldCupUniverse course-project source audit)'})
    error=''
    for attempt in range(3):
        try:
            time.sleep(.5)
            with urllib.request.urlopen(request,timeout=30) as response:
                if urllib.parse.urlsplit(response.geturl()).hostname not in HOSTS:raise ValueError('Unapproved redirect host')
                if response.status!=200:raise ValueError('HTTP '+str(response.status))
                data=response.read(limit+1)
            if len(data)>limit:raise ValueError('Response exceeds byte limit')
            return data
        except (OSError,ValueError) as exc:
            error=str(exc)
            if attempt<2:time.sleep(1+attempt)
    raise RuntimeError(error)


def image_url(code, text):
    p=PageImages();p.feed(text)
    if code=='GER':
        m=re.search(r'https://assets\.dfb\.de/uploads/[\w/]+/original_mannschaftsfoto_wm26\.jpg(?:%3F|\?)\d+',text)
        if not m:raise ValueError('Official team-photo source URL missing')
        return html.unescape(urllib.parse.unquote(m.group()))
    if code=='NOR':return next(x['src'] for x in p.images if x.get('alt')=='offisielt lagbilde VM-troppen Greensboro')
    if code=='COL':
        for x in p.images:
            if '1.-Colombia-vs-Ghana-3-de-julio' in x.get('data-src',''):
                return x['data-srcset'].split(',')[-1].strip().split(' ')[0]
        raise ValueError('Caption-associated Colombia image missing')
    if code=='BRA':return next(x['src'] for x in p.images if x.get('src','').startswith('https://cdn-cbf-asset-bank-watermarked.yapoli.com/'))
    if code=='ENG':
        if 'Team-photo-1440-20261106' not in p.og_image:raise ValueError('Official squad photo OG field missing')
        return p.og_image
    raise ValueError('No curated selector')


def run(refresh=False):
    EVIDENCE.mkdir(parents=True,exist_ok=True)
    review_file=OUT/'visual-review.json'
    reviews=json.loads(review_file.read_text('utf-8')) if review_file.exists() else {}
    team_files=sorted((ROOT/'data/official/20261021').glob('team-*.json'))
    teams={}
    for f in team_files:
        item=json.loads(f.read_text('utf-8'))
        if 'Abbreviation' not in item:continue  # Adjacent HTTP metadata files are not teams.
        code=item['Abbreviation']
        teams[code]={'team_code':code,'year':2026,'path':None,'source_url':None,'image_url':None,'caption':None,'sha256':None,'bytes':None,'status':'missing_verified_2026_team_photo','reason':'No verified same-year official team-photo source collected in this bounded pass.'}
    for code,source in SOURCES.items():
        try:
            page=EVIDENCE/(code+'.source.txt')
            if refresh or not page.exists():page.write_bytes(get(source['source_url'],6*1024*1024))
            raw=page.read_bytes();text=html.unescape(raw.decode('utf-8','replace'))
            for marker in source['markers']:
                if marker not in text:raise ValueError('Source evidence marker missing: '+marker)
            url=image_url(code,text)
            data=get(url,15*1024*1024)
            with Image.open(io.BytesIO(data)) as image:
                fmt=image.format;width,height=image.size
                if fmt not in ['JPEG','PNG','WEBP'] or width<700 or height<300:raise ValueError('Not an adequate official team photograph')
            suffix={'JPEG':'jpg','PNG':'png','WEBP':'webp'}[fmt];dest=OUT/(code+'.'+suffix);dest.write_bytes(data)
            assert dest.read_bytes()==data
            reviewed=reviews.get(code,{})
            teams[code]={'team_code':code,'year':2026,'path':'/assets/media/team-photos/'+dest.name,'source_url':source['source_url'],'image_url':url,'caption':source['caption'],'caption_evidence':source['caption_evidence'],'year_evidence':'Official association page/photo caption explicitly ties this squad photograph to the 2026 World Cup; see required_markers and source date.','date':source['date'],'date_kind':'official source article/album entry publication date','credit':source['credit'],'sha256':sha(data),'bytes':len(data),'width':width,'height':height,'mime':Image.MIME[fmt],'watermarked':source.get('watermarked',False),'retrieved_at':datetime.now(timezone.utc).isoformat(),'source_evidence':{'path':str(page.relative_to(ROOT)).replace('\\','/'),'sha256':sha(raw),'bytes':len(raw),'required_markers':source['markers']},'status':'verified_2026_team_photo' if reviewed.get('sha256')==sha(data) else 'source_verified_pending_visual_review','visual_review':reviewed if reviewed.get('sha256')==sha(data) else None,'pixel_changes':'none; exact published response bytes retained','license':'Official source credit retained; no independent open/publication licence asserted.'}
            print('CACHED',code,len(data),width,height,flush=True)
        except Exception as exc:
            teams[code]['reason']=str(exc);teams[code]['source_url']=source['source_url'];print('MISSING',code,str(exc),flush=True)
    manifest={'year':2026,'collected_on':datetime.now(timezone(timedelta(hours=8))).date().isoformat(),'collection_timezone':'Asia/Shanghai','scope':'Official same-year men\'s World Cup team/squad group photographs only. Missing teams remain null; never substitute individual portraits, qualifiers from other years, or generated images.','teams':dict(sorted(teams.items())),'coverage':{'total_teams':len(teams),'downloaded':sum(bool(v['path']) for v in teams.values()),'missing':sum(not v['path'] for v in teams.values())},'notes':['Source text is retained as .txt evidence, not executable HTML.','A source/page title saying 2026 alone is insufficient; each image selector must be tied to explicit team/squad-photo evidence.','Photo pixels are not edited. Publisher crop/watermark/rendition is preserved.']}
    file=OUT/'manifest.json';file.write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n','utf-8')
    for code,row in json.loads(file.read_text('utf-8'))['teams'].items():
        if row['path']:
            data=(ROOT/'public'/row['path'].lstrip('/')).read_bytes();assert sha(data)==row['sha256'] and len(data)==row['bytes']
    print('REOPENED manifest:',manifest['coverage'],flush=True)


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--refresh',action='store_true');run(parser.parse_args().refresh)
