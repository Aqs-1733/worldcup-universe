"""Export only public, verified release data; never users, secrets, sessions or test posts."""
from pathlib import Path
import argparse,os,subprocess,hashlib,re
root=Path(__file__).resolve().parents[1]
p=argparse.ArgumentParser();p.add_argument('--mysqldump',default='D:/XAMPP/mysql/bin/mysqldump.exe');a=p.parse_args()
e={}
for line in (root/'.env').read_text('utf-8-sig').splitlines():
 if '=' in line and not line.lstrip().startswith('#'):
  k,v=line.split('=',1);e[k]=v.strip().strip('"\'')
e.update({k:v for k,v in os.environ.items() if k.startswith('DB_')})
db=e.get('DB_DATABASE','worldcup_universe');assert re.fullmatch(r'[a-zA-Z0-9_]+',db)
child=os.environ.copy();child['MYSQL_PWD']=e.get('DB_PASSWORD','')
core='official_import_id IS NOT NULL'
tables=[('official_imports','id IN (SELECT DISTINCT official_import_id FROM teams WHERE '+core+')'),('venues','id IN (SELECT venue_id FROM matches WHERE '+core+')'),('teams',core),('players',core),('matches',core),('match_lineups',core),('standings',core),('match_progressions','is_verified=1'),('tags','1=1'),('news_sources','1=1'),('news_articles',"status='published' AND source_id IS NOT NULL"),('news_article_tags',"article_id IN (SELECT id FROM news_articles WHERE status='published' AND source_id IS NOT NULL)"),('country_profiles','1=1'),('team_members','is_visible=1'),('match_videos',"status='published' AND tournament_year=2026 AND verified_at IS NOT NULL")]
parts=[b'-- Public course snapshot; generated from verified 2026 FIFA data and publisher RSS.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n']
for table,where in tables:
 cmd=[a.mysqldump,'--host='+e.get('DB_HOST','127.0.0.1'),'--port='+e.get('DB_PORT','3306'),'--user='+e.get('DB_USERNAME','root'),'--default-character-set=utf8mb4','--skip-opt','--extended-insert','--complete-insert','--no-create-info','--skip-triggers','--skip-comments','--hex-blob','--single-transaction','--where='+where,db,table]
 r=subprocess.run(cmd,env=child,stdout=subprocess.PIPE,stderr=subprocess.PIPE,check=True);parts.append(r.stdout);print('EXPORTED',table,len(r.stdout))
parts.append(b'\nSET FOREIGN_KEY_CHECKS=1;\n');data=b'\n'.join(parts)
old=root/'data/sports-seed.sql';backup=root/'storage/audit/pre-release-sports-seed.sql'
if not backup.exists():backup.write_bytes(old.read_bytes())
data=data.replace(str(root/'storage/source_snapshots/official/20261021').replace('\\','/').encode(),b'data/official/20261021')
temp=root/'storage/audit/sports-seed.new.sql';temp.write_bytes(data);old.write_bytes(temp.read_bytes());print('PASS public seed',len(data),'bytes; SHA256',hashlib.sha256(data).hexdigest())
