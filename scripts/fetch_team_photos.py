"""Verify or re-fetch the complete 48-team 2026 World Cup photograph catalogue.

Default: offline byte/dimension/year/caption/evidence/visual-review verification.
--refresh re-fetches the exact published image_url for all teams (or --team CODE).
Changed responses are staged for a new human visual review, never auto-published.
The manifest is never rebuilt from a five-team list or overwritten with fallbacks.
"""
from pathlib import Path
from datetime import datetime, timezone
import argparse, hashlib, html, io, json, re, time, urllib.parse, urllib.request
from PIL import Image

ROOT=Path(__file__).resolve().parents[1]
CATALOG=ROOT/'public/assets/media/team-photos/manifest.json'
HOSTS={'assets.dfb.de','res.cloudinary.com','dam.fotball.no','fcf.com.co','cdn-cbf-asset-bank-watermarked.yapoli.com','cdn-cbf-asset-bank-thumbnail.yapoli.com','cdn.englandfootball.com','media.gettyimages.com','thumb.wikimedia.org','upload.wikimedia.org'}
def sha(data):return hashlib.sha256(data).hexdigest()
def within(path):
    path=path.resolve()
    if not path.is_relative_to(ROOT):raise ValueError('Path escapes repository')
    return path

def evidence_text(raw):
    text=raw.decode('utf-8','replace')
    # Evidence may be original HTML or a saved source/API metadata excerpt.
    try:text+='\n'+json.dumps(json.loads(text),ensure_ascii=False)
    except ValueError:pass
    return html.unescape(text)

def get(url):
    if urllib.parse.urlsplit(url).scheme!='https' or urllib.parse.urlsplit(url).hostname not in HOSTS:raise ValueError('Unapproved image host')
    req=urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0 (WorldCupUniverse source verification)'})
    for attempt in range(3):
        try:
            with urllib.request.urlopen(req,timeout=40) as response:
                if response.status!=200 or urllib.parse.urlsplit(response.geturl()).hostname not in HOSTS:raise ValueError('Unexpected image response')
                data=response.read(16*1024*1024+1)
            if len(data)>16*1024*1024:raise ValueError('Image exceeds size limit')
            return data
        except OSError:
            if attempt==2:raise
            time.sleep(attempt+1)

def verify(manifest_path,refresh=False,team=None):
    manifest=json.loads(manifest_path.read_text('utf-8'))
    codes=set()
    for f in (ROOT/'data/official/20261021').glob('team-*.json'):
        obj=json.loads(f.read_text('utf-8'))
        if 'Abbreviation' in obj:codes.add(obj['Abbreviation'])
    assert len(codes)==48 and set(manifest['teams'])==codes,'FIFA team coverage mismatch'
    if team and team not in codes:raise ValueError('Unknown FIFA team code')
    verified=0;errors=[];network=[];hashes=set()
    for code,row in sorted(manifest['teams'].items()):
        if not row.get('path'):
            errors.append(code+': missing photograph');continue
        try:
            assert row['team_code']==code and row['year']==2026 and row['status']=='verified_2026_team_photo','unverified year/status/team'
            assert re.fullmatch(r'/assets/media/team-photos/'+code+r'\.(jpg|png|webp)',row['path']),'unexpected photo path'
            data=within(ROOT/'public'/row['path'].lstrip('/')).read_bytes()
            assert sha(data)==row['sha256'] and len(data)==row['bytes'],'photo byte hash/size mismatch'
            assert sha(data) not in hashes,'same photo reused for another team'
            hashes.add(sha(data))
            with Image.open(io.BytesIO(data)) as im:
                assert im.size==(row['width'],row['height']) and im.width>=700 and im.height>=300,'dimensions mismatch'
                assert im.format in {'JPEG','PNG','WEBP'},'not a photograph format'
                im.verify()
            assert row['visual_review']['sha256']==row['sha256'] and row['visual_review']['observation'],'missing exact-file visual review'
            assert re.fullmatch(r'2026-\d{2}-\d{2}',row['date'] or ''),'missing 2026 source date'
            assert row['caption_evidence'] and row['year_evidence'] and row['credit'],'missing provenance fields'
            assert row['source_url'].startswith('https://') and row['image_url'].startswith('https://'),'source URL missing'
            ev=row['source_evidence'];raw=within(ROOT/ev['path']).read_bytes();text=evidence_text(raw)
            assert sha(raw)==ev['sha256'] and len(raw)==ev['bytes'],'source snapshot mismatch'
            markers=ev.get('required_markers',[])
            assert markers,'source evidence markers absent'
            for marker in markers:assert html.unescape(marker) in text,'source marker missing: '+marker[:80]
            verified+=1
            if refresh and (team is None or code==team):
                incoming=get(row['image_url']);digest=sha(incoming)
                if incoming!=data:
                    stage=ROOT/'storage/design-assets/photo-refresh';stage.mkdir(parents=True,exist_ok=True)
                    dest=stage/(code+'-'+digest[:12]+Path(row['path']).suffix);dest.write_bytes(incoming)
                    errors.append(code+': changed response staged, visual review required; active photograph unchanged')
                else:print('REFETCH MATCH',code,len(incoming),flush=True)
                network.append({'team_code':code,'sha256':digest,'bytes':len(incoming),'matches_published':incoming==data,'checked_at':datetime.now(timezone.utc).isoformat()})
                time.sleep(.25)
        except (AssertionError,KeyError,ValueError,OSError) as exc:errors.append(code+': '+str(exc))
    if refresh:
        log=ROOT/'storage/audit/team-photo-refetch.json';log.parent.mkdir(parents=True,exist_ok=True)
        log.write_text(json.dumps(network,indent=2)+'\n','utf-8')
    if errors:
        print(f'FAIL team photos: {verified}/48 verified; {len(errors)} errors')
        for error in errors:print('  '+error)
        return 1
    assert manifest['coverage']=={'total_teams':48,'downloaded':48,'missing':0},'manifest coverage counters stale'
    print('PASS team photos: 48/48 distinct 2026 squad/group photographs; bytes, dimensions, caption evidence and visual-review hashes verified.')
    return 0

if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--manifest',type=Path,default=CATALOG);p.add_argument('--refresh',action='store_true');p.add_argument('--team')
    args=p.parse_args();raise SystemExit(verify(within(args.manifest),args.refresh,args.team))
