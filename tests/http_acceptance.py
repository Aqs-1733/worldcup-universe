"""Real HTTP acceptance tests for the local Yii2/MySQL application."""
import json, os, re, sys, time, urllib.request, urllib.error, urllib.parse, http.cookiejar
from pathlib import Path
BASE=os.environ.get('WCU_TEST_URL','http://127.0.0.1:8088')
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar(); self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
 def req(self,path,data=None,method=None,json_body=False):
  headers={}
  if json_body: body=json.dumps(data).encode();headers['Content-Type']='application/json'
  elif data is not None: body=urllib.parse.urlencode(data,doseq=True).encode()
  else: body=None
  request=urllib.request.Request(BASE+path,body,headers,method=method)
  try:
   r=self.opener.open(request,timeout=30);return r.status,r.headers,r.read().decode('utf8','replace')
  except urllib.error.HTTPError as e:return e.code,e.headers,e.read().decode('utf8','replace')
 def session(self):return json.loads(self.req('/api/v1/session')[2])['data']
 def post(self,path,data=None,json_body=False,method='POST'):
  session=self.session(); data=dict(data or {});data[session['csrfParam']]=session['csrfToken'];return self.req(path,data,method,json_body)
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):return None
checks=[]
def ck(test,label):
 if not test:raise AssertionError(label)
 checks.append(label)
def status(r,expected,label):ck(r[0]==expected,f'{label}: HTTP {r[0]} (expected {expected})')
a,b,admin,guest=Client(),Client(),Client(),Client()
prefix='qa_'+os.urandom(4).hex();Path('storage/audit/http-prefix.txt').write_text(prefix)
try:
 for route in ['/','/teams','/teams/argentina','/players','/players/1','/worldcup','/worldcup/standings','/watch','/credits','/matches/1','/news','/countries','/forum','/fan-space','/deliverables','/login','/register','/search?q=France','/commentary','/vision']:
  status(guest.req(route),200,'public '+route)
 for route in ['/api/v1/home','/api/v1/teams','/api/v1/teams/argentina','/api/v1/players','/api/v1/players/1','/api/v1/matches','/api/v1/matches/1','/api/v1/standings','/api/v1/stats','/api/v1/news','/api/v1/countries','/api/v1/forum','/api/v1/bracket']:
  r=guest.req(route);status(r,200,route);ck('data' in json.loads(r[2]),route+' JSON envelope')
 r=guest.req('/watch');ck('https://www.rtve.es/drmn/embed/video/17162172/' in r[2] and '需官方账号登录' in r[2],'2026 official iframe and publisher access state visible')
 r=guest.req('/stats');status(r,301,'old standalone data URL redirects');ck(r[1]['Location'].endswith('/worldcup/standings'),'canonical competition standings URL')
 status(guest.req('/teams/no-such-team'),404,'missing team')
 status(guest.req('/teams?q[]=bad'),400,'query shape validation')
 status(guest.req('/logout'),405,'GET cannot log out')
 status(guest.req('/login',{'LoginForm[username]':'nobody','LoginForm[password]':'bad'}),400,'missing CSRF rejected')
 status(guest.req('/admin'),302,'guest admin access redirects to login')
 for c,suffix in [(a,'a'),(b,'b')]:
  status(c.post('/register',{'RegisterForm[username]':prefix+'_'+suffix,'RegisterForm[email]':prefix+suffix+'@example.org','RegisterForm[password]':'QA-password-2026-test','RegisterForm[display_name]':'验收球迷 '+suffix}),302,'register '+suffix)
  ck(c.session()['user']['username']==prefix+'_'+suffix,'session bound to registered member')
 status(a.req('/admin'),403,'member admin access forbidden')
 teams=json.loads(a.req('/api/v1/teams')[2])['data'];team=teams[0]['id']
 status(a.post('/profile',{'main_team_id':team,'bio':'验收简介','replace_preferences':'1'}),302,'main team save')
 ck(json.loads(a.req('/api/v1/profile')[2])['data']['profile']['main_team_id']==team,'main team persists after reload')
 categories=json.loads(a.req('/api/v1/forum')[2]) # category 1 is seeded migration
 payload={'title':'[测试] '+prefix+' 主队讨论','body':'HTTP 回归用例：<script>alert(1)</script> 只应显示文本。','category_id':1,'team_id':team,'author_id':999,'is_pinned':1,'status':'hidden'}
 r=a.post('/api/v1/forum',payload,True);status(r,201,'JSON create thread'); thread=json.loads(r[2])['data'];tid=thread['id'];ck(thread['status']=='published' and thread['author_id']==a.session()['user']['id'] and not thread['is_pinned'],'JSON cannot forge author or moderation state')
 r=guest.req('/forum/'+str(tid));status(r,200,'published thread visible');ck('&lt;script&gt;alert(1)&lt;/script&gt;' in r[2] and '<script>alert(1)</script>' not in r[2],'forum escapes script markup')
 status(b.post(f'/forum/{tid}/edit',payload),403,'nonowner cannot edit through HTTP')
 status(b.post(f'/forum/{tid}/delete'),403,'nonowner cannot delete through HTTP')
 r=b.post(f'/forum/{tid}/reply',{'body':'[测试] 支持主队，理性讨论。','user_id':999});status(r,302,'reply persisted');rid=int(re.search(r'#reply-(\d+)',r[1]['Location'])[1])
 status(a.post(f'/forum/replies/{rid}/delete'),403,'reply owner check')
 status(b.post(f'/forum/replies/{rid}/edit',{'body':'[测试] 回复编辑验证'}),302,'owner can edit own reply')
 ck(json.loads(a.req('/api/v1/profile')[2])['data']['unread']>=1,'reply notification delivered')
 status(a.post('/profile/notifications'),302,'owner marks notifications read');ck(json.loads(a.req('/api/v1/profile')[2])['data']['unread']==0,'read state persists')
 for kind in ['like','bookmark']:
  status(a.post(f'/forum/{tid}/{kind}',{'enabled':'1'}),302,kind+' on');status(a.post(f'/forum/{tid}/{kind}',{'enabled':'1'}),302,kind+' idempotent retry')
 status(b.post(f'/forum/{tid}/report',{'reason':'[测试] 管理员审核流程'}),302,'report submitted')
 env={}
 for line in Path('.env').read_text(encoding='utf-8-sig').splitlines():
  if '=' in line and not line.startswith('#'):
   k,v=line.split('=',1);env[k]=v.strip().strip('"\'')
 status(admin.post('/login',{'LoginForm[username]':os.environ.get('ADMIN_USERNAME',env.get('ADMIN_USERNAME','admin')),'LoginForm[password]':os.environ.get('ADMIN_PASSWORD',env.get('ADMIN_PASSWORD',''))}),302,'administrator login')
 status(admin.req('/admin'),200,'native admin dashboard')
 for table in ['teams','players','matches','news_articles','forum_threads','forum_replies','forum_reports','match_videos','media_assets','coursework_files']:
  status(admin.req('/admin/'+table),200,'admin '+table)
 status(admin.req('/admin/users'),200,'user management');status(admin.req('/admin/audit'),200,'audit log')
 status(admin.req('/admin/no_such_table'),404,'admin table whitelist')
 status(admin.post('/admin/moderate',{'type':'thread','id':tid,'operation':'lock'}),302,'admin locks thread')
 status(b.post(f'/forum/{tid}/reply',{'body':'[测试] 锁定后不得回复'}),403,'locked thread blocks replies over HTTP')
 status(admin.post('/admin/moderate',{'type':'thread','id':tid,'operation':'hide'}),302,'admin hides thread')
 status(guest.req('/forum/'+str(tid)),404,'guest cannot read hidden thread')
 status(admin.post('/admin/moderate',{'type':'thread','id':tid,'operation':'publish'}),302,'admin restores thread')
 r=admin.post('/admin/news_articles/create',{'Record[source_name]':'QA TEST','Record[source_url]':'https://example.org/'+prefix,'Record[title_original]':'[测试] '+prefix+' 新闻 CRUD','Record[summary_original]':'仅用于验证数据库写入','Record[language_code]':'zh-CN','Record[status]':'published','tag_ids[]':[1]});status(r,302,'native news create')
 r=guest.req('/api/v1/news?tag=match&q='+prefix);news=json.loads(r[2])['data'];ck(len(news)==1,'created article visible through public Yii2 API');nid=news[0]['id']
 status(admin.post('/admin/news_articles/edit?id='+str(nid),{'Record[status]':'draft'}),302,'news update draft')
 status(guest.req('/news/'+str(nid)),404,'draft article not public')
 status(admin.post('/admin/news_articles/delete',{'id':nid}),302,'news delete')
 status(guest.req('/news/'+str(nid)),404,'deleted article remains unavailable')
 status(admin.req('/downloads/1'),200,'registered coursework download')
 uid=b.session()['user']['id'];status(admin.post(f'/admin/users/{uid}/status',{'status':'disabled'}),302,'admin disables member')
 ck(b.session()['user'] is None,'disabled member session invalidated on next request')
 status(a.post('/logout'),302,'POST logout');ck(a.session()['user'] is None,'logout clears identity')
 status(guest.req('/api/v1/teams',{},'POST'),405,'read-only API rejects POST before action execution')
 status(guest.post('/api/v1/teams'),405,'read-only API rejects valid-CSRF POST')
 print(f'PASS {len(checks)} HTTP checks; prefix={prefix}')
 Path('storage/audit/http-results.json').write_text(json.dumps({'result':'PASS','checks':checks,'count':len(checks),'prefix':prefix},ensure_ascii=False,indent=2),encoding='utf8')
 for c in checks:print('  PASS '+c)
except Exception as e:
 print('FAIL',str(e));Path('storage/audit/http-failure.txt').write_text(str(e),encoding='utf8');sys.exit(1)
