"""Real HTTP checks for the redesigned topic detail, including member/locked states."""
from pathlib import Path
from html.parser import HTMLParser
import http.cookiejar,json,os,re,sys,urllib.request,urllib.parse,urllib.error

ROOT=Path(__file__).resolve().parents[1]
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,req,fp,code,msg,headers,newurl):return None
class Client:
    def __init__(self):
        self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
    def req(self,path,data=None):
        body=None if data is None else urllib.parse.urlencode(data).encode()
        try:
            r=self.opener.open(urllib.request.Request('http://127.0.0.1:8088'+path,body),timeout=30)
            return r.status,r.headers,r.read().decode('utf-8')
        except urllib.error.HTTPError as e:return e.code,e.headers,e.read().decode('utf-8')
    def post(self,path,data):
        session=json.loads(self.req('/api/v1/session')[2])['data'];payload=dict(data)
        payload[session['csrfParam']]=session['csrfToken'];return self.req(path,payload)
class Nodes(HTMLParser):
    def __init__(self):super().__init__();self.inputs=[];self.classes=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs);self.classes.extend(a.get('class','').split())
        if tag=='input':self.inputs.append(a)
checks=[]
def ck(result,label):
    if not result:raise AssertionError(label)
    checks.append(label)
def parsed(html):p=Nodes();p.feed(html);return p

guest,member,admin=Client(),Client(),Client()
for route,has_replies in [('/forum/35',False),('/forum/31',True)]:
    r=guest.req(route);ck(r[0]==200,route+' HTTP 200');p=parsed(r[2])
    ck('topic-replies-panel' in p.classes,'detail reply card exists '+route)
    ck('empty' not in p.classes,'retired grey banner absent '+route)
    ck(('topic-reply' in p.classes)==has_replies,'actual reply/empty state '+route)
    ck('topic-compose-guest' in p.classes,'guest login footer '+route)

prefix='qa_'+os.urandom(4).hex()
(ROOT/'storage/audit/topic-prefix.txt').write_text(prefix,encoding='utf-8')
r=member.post('/register',{'RegisterForm[username]':prefix+'_a','RegisterForm[email]':prefix+'a@example.org','RegisterForm[password]':'QA-password-2026-test','RegisterForm[display_name]':'回复区验收'})
ck(r[0]==302,'temporary member registration')
r=member.post('/forum/new',{'title':'[测试] '+prefix+' 回复区','body':'只用于回复区回归测试，结束后精确清理。','category_id':1})
ck(r[0]==302,'create temporary topic');tid=int(re.search(r'/forum/(\d+)',r[1]['Location'])[1])
body=member.req('/forum/'+str(tid))[2];p=parsed(body)
ck('topic-reply-form' in p.classes,'member native reply form')
ck(any(i.get('name')=='parent_id' and i.get('type')=='hidden' for i in p.inputs),'quote ID hidden, not manual numeric field')
ck('data-quote-cancel' in body and 'data-quote-chip' in body,'visible cancellable quote component')
r=member.post(f'/forum/{tid}/reply',{'body':'[测试] <script>topic_test</script>'})
ck(r[0]==302,'persist real reply');rid=int(re.search(r'#reply-(\d+)',r[1]['Location'])[1])
body=member.req('/forum/'+str(tid))[2]
ck('&lt;script&gt;topic_test&lt;/script&gt;' in body and '<script>topic_test</script>' not in body,'reply text escaped')
ck(f'data-quote="{rid}"' in body and 'data-quote-name="回复区验收"' in body,'quote button identifies exact reply and author')
r=member.post(f'/forum/{tid}/reply',{'body':'[测试] 引用第一条回复','parent_id':rid})
ck(r[0]==302,'persist quoted reply')
body=guest.req('/forum/'+str(tid))[2]
ck(f'href="#reply-{rid}"' in body and 'reply-quote-ref' in body,'quoted reply links back')
r=member.post(f'/forum/{tid}/reply',{'body':'[测试] 不存在的引用','parent_id':999999999})
ck(r[0]==400,'invalid quote remains rejected by server')
r=member.post(f'/forum/{tid}/reply',{'body':'x','parent_id':rid})
ck(r[0]==422 and 'form-errors' in r[2] and 'topic-reply-form' in r[2],'short reply renders validation in new component')
env={}
for line in (ROOT/'.env').read_text('utf-8-sig').splitlines():
    if '=' in line and not line.startswith('#'):
        k,v=line.split('=',1);env[k]=v.strip().strip('"\'')
r=admin.post('/login',{'LoginForm[username]':env.get('ADMIN_USERNAME','admin'),'LoginForm[password]':env.get('ADMIN_PASSWORD','')})
ck(r[0]==302,'existing administrator session')
ck(admin.post('/admin/moderate',{'type':'thread','id':tid,'operation':'lock'})[0]==302,'lock disposable test topic')
for client,label in [(guest,'guest'),(member,'member')]:
    body=client.req('/forum/'+str(tid))[2];p=parsed(body)
    ck('topic-compose-locked' in p.classes and 'topic-reply-form' not in p.classes and 'topic-compose-guest' not in p.classes,'locked state, no misleading compose CTA '+label)
ck(member.post(f'/forum/{tid}/reply',{'body':'[测试] 锁定后回复'})[0]==403,'server still blocks locked replies')
if '--browser-fixture' in sys.argv:
    ck(admin.post('/admin/moderate',{'type':'thread','id':tid,'operation':'unlock'})[0]==302,'unlock temporary browser fixture')
report={'result':'PASS','count':len(checks),'checks':checks,'prefix':prefix,'topic_id':tid,'reply_id':rid}
(ROOT/'storage/audit/topic-tests.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(f'PASS {len(checks)} topic reply checks; guest/member/quoted/invalid/locked states; prefix={prefix}')
