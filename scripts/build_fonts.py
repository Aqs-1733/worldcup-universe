"""Build project-local webfonts from the original OFL sources (optional design task)."""
from pathlib import Path
import sys,re,urllib.request
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'storage/design-assets/python-libs'))
from fontTools import subset
from fontTools.ttLib import TTFont

chars=set(''.join(p.read_text('utf-8',errors='ignore') for folder in ['views','models','controllers','services','config'] for p in (ROOT/folder).rglob('*.php'))+(ROOT/'data/sports-seed.sql').read_text('utf-8',errors='ignore'))
chars.update(chr(c) for c in range(32,592))
chars.update('世界杯宇宙参赛球队球员名单主办城市墨西哥温哥华纽约阿兹特克体育场本站赛事图集照片来源小组决赛首发替补国家地球探索球迷论坛个人中心完整比赛回放')
css=(ROOT/'storage/design-assets/google-fonts.css').read_text('utf-8')+(ROOT/'storage/design-assets/fonts-display.css').read_text('utf-8')
fonts=[('WCU Sans',ROOT/'storage/design-assets/NotoSansSC-variable.ttf','wcu-sans.woff2','100 900')]
for block in re.findall(r'@font-face\s*\{(.*?)\}',css,re.S):
    family=re.search(r"font-family: '([^']+)'",block)[1];weight=re.search(r'font-weight: ([0-9]+)',block)[1]
    if family=='Noto Sans SC':continue
    url=re.search(r'src: url\(([^)]+)\)',block)[1]
    name={'Anton':'anton','ZCOOL QingKe HuangYou':'qingke','Mulish':'mulish'}[family]+'-'+weight
    file=ROOT/'storage/design-assets'/(name+'.ttf')
    if not file.exists():file.write_bytes(urllib.request.urlopen(url,timeout=60).read())
    fonts.append((family,file,'wcu-'+name+'.woff2',weight))
faces=[]
for family,source,target,weight in fonts:
    font=TTFont(source);options=subset.Options();options.flavor='woff2';options.name_IDs=['*'];sub=subset.Subsetter(options=options);sub.populate(text=''.join(chars));sub.subset(font);font.flavor='woff2'
    if family=='WCU Sans':
        for n in font['name'].names:
            if n.nameID in [1,3,4,6,16]:n.string=('WCUSans' if n.nameID==6 else 'WCU Sans').encode(n.getEncoding(),errors='replace')
    dest=ROOT/'public/assets/fonts'/target;font.save(dest)
    print(target,dest.stat().st_size,flush=True)
    faces.append("@font-face{font-family:'"+family+"';font-style:normal;font-weight:"+weight+";font-display:swap;src:url('/assets/fonts/"+target+"') format('woff2')}")
(ROOT/'public/assets/fonts/fonts.css').write_text('\n'.join(faces),'utf-8')
