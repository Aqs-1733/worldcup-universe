<?php
/** Database-backed service regression; all test content is rolled back. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$config=require ROOT_PATH.'/config/web.php';
$config['components']['request']['scriptFile']=ROOT_PATH.'/public/index.php';
$config['components']['request']['scriptUrl']='/index.php';
$config['components']['request']['cookieValidationKey']=str_repeat('test-key',8);
$app=new yii\web\Application($config);
use app\models\{User,RegisterForm,Team,Player,ForumThread,ForumReply,ForumCategory,ThreadLike,ThreadBookmark,ForumReport,Notification,MediaAsset,MatchProgression,MatchGame,CourseworkFile};
use app\services\{ForumService,PreferenceService,CatalogService};
$checks=[];
function check(bool $condition,string $label): void { global $checks; if(!$condition) { throw new RuntimeException($label); } $checks[]=$label; }
function rejects(callable $fn,string $class,string $label): void { try { $fn(); } catch(Throwable $e) { check($e instanceof $class,$label.' ('.get_class($e).')'); return; } throw new RuntimeException('Expected rejection: '.$label); }
$tx=$app->db->beginTransaction();
try {
    $team=Team::find()->one(); $player=Player::find()->one(); check($team!==null && $player!==null,'existing sports data available');
    $reg=new RegisterForm(['username'=>'bad name','email'=>'invalid','password'=>'short']); check(!$reg->validate(),'registration rejects invalid credentials');
    $suffix=bin2hex(random_bytes(4)); $reg=new RegisterForm(['username'=>'test_'.$suffix,'email'=>$suffix.'@example.org','password'=>'Passphrase-2026-test','display_name'=>'测试球迷']);
    $a=$reg->register(); check($a instanceof User,'native registration writes User and FanProfile');
    $reg2=new RegisterForm(['username'=>'other_'.$suffix,'email'=>'b'.$suffix.'@example.org','password'=>'Passphrase-2026-test']); $b=$reg2->register(); check($b instanceof User,'second member registered');
    check(!$a->isAdmin(),'registration grants no administrator role');
    check(!isset($a->toArray()['password_hash'])&&!isset($a->toArray()['auth_key']),'identity serialization omits secrets');
    $dupe=new RegisterForm(['username'=>$a->username,'email'=>$a->email,'password'=>'Passphrase-2026-test']); check(!$dupe->validate(),'username/email uniqueness validated');
    $pref=new PreferenceService(); $p=$pref->save($a,['main_team_id'=>$team->id,'teams'=>[],'players'=>[$player->id],'blocked_teams'=>[],'bio'=>'我的主队']); check(!$p->hasErrors()&&(int)$p->main_team_id===(int)$team->id,'main team and preferences saved transactionally');
    $bad=$pref->save($a,['main_team_id'=>$team->id,'blocked_teams'=>[$team->id]]); check($bad->hasErrors(),'blocked team cannot overlap main/followed team');
    rejects(fn()=>$pref->save($a,['teams'=>[999999999]]),yii\web\BadRequestHttpException::class,'preference rejects nonexistent team');
    $cat=ForumCategory::find()->one(); $service=new ForumService();
    $invalid=$service->saveThread($a,['title'=>'x','body'=>'y','category_id'=>$cat->id]); check($invalid->hasErrors(),'thread title/body validation rejects short content');
    $thread=$service->saveThread($a,['title'=>'测试主题与主队讨论','body'=>'这是用于验证后端事务的测试主题。','category_id'=>$cat->id,'team_id'=>$team->id,'author_id'=>$b->id,'is_pinned'=>1,'status'=>'hidden']);
    check(!$thread->hasErrors()&&(int)$thread->author_id===(int)$a->id&&$thread->status==='published'&&!$thread->is_pinned,'public mass assignment cannot forge author/moderation fields');
    rejects(fn()=>$service->saveThread($b,['title'=>'冒充修改','body'=>'其他人的内容不应被修改','category_id'=>$cat->id],(int)$thread->id),yii\web\ForbiddenHttpException::class,'nonowner cannot edit thread');
    rejects(fn()=>$service->remove($b,'thread',(int)$thread->id),yii\web\ForbiddenHttpException::class,'nonowner cannot delete thread');
    $reply=$service->reply($b,(int)$thread->id,['body'=>'支持主队，理性讨论。','user_id'=>$a->id]); check(!$reply->hasErrors()&&(int)$reply->user_id===(int)$b->id,'reply author bound to authenticated actor');
    check(Notification::find()->where(['user_id'=>$a->id,'thread_id'=>$thread->id])->count()==1,'reply creates owner notification');
    $other=$service->saveThread($b,['title'=>'另外一个测试主题','body'=>'用于验证不能跨主题引用回复。','category_id'=>$cat->id]);
    rejects(fn()=>$service->reply($a,(int)$other->id,['body'=>'错误的跨主题引用','parent_id'=>$reply->id]),yii\web\BadRequestHttpException::class,'cross-thread parent reply rejected');
    $service->setCollection($a,(int)$thread->id,'like',true); $service->setCollection($a,(int)$thread->id,'like',true); check(ThreadLike::find()->where(['thread_id'=>$thread->id,'user_id'=>$a->id])->count()==1,'like is idempotent under unique composite key');
    $service->setCollection($a,(int)$thread->id,'like',false); check(ThreadLike::find()->where(['thread_id'=>$thread->id,'user_id'=>$a->id])->count()==0,'unlike is persisted');
    $service->setCollection($b,(int)$thread->id,'bookmark',true); check(ThreadBookmark::find()->where(['thread_id'=>$thread->id,'user_id'=>$b->id])->count()==1,'bookmark ownership persisted');
    $report=$service->report($b,(int)$thread->id,['reason'=>'内容需要核查']); check(!$report->hasErrors(),'member report accepted');
    rejects(fn()=>$service->report($b,(int)$thread->id,['reason'=>'重复举报']),yii\web\BadRequestHttpException::class,'duplicate open report rejected');
    rejects(fn()=>$service->moderate($b,'thread',(int)$thread->id,'lock'),yii\web\ForbiddenHttpException::class,'member cannot moderate');
    $admin=User::find()->joinWith('roles')->where(['roles.name'=>'admin'])->one(); check($admin!==null,'existing administrator identity is preserved');
    $service->moderate($admin,'thread',(int)$thread->id,'lock');
    rejects(fn()=>$service->reply($b,(int)$thread->id,['body'=>'锁定后禁止新增回复']),yii\web\ForbiddenHttpException::class,'locked thread rejects replies');
    $service->moderate($admin,'thread',(int)$thread->id,'hide');
    rejects(fn()=>$service->readable((int)$thread->id,$b),yii\web\NotFoundHttpException::class,'hidden thread unavailable to other members');
    check($service->readable((int)$thread->id,$a)->id==$thread->id,'author can inspect own hidden topic');
    $service->moderate($admin,'report',(int)$report->id,'resolve'); $report->refresh(); check($report->status==='resolved'&&(int)$report->reviewed_by===(int)$admin->id,'moderation stores reviewer and resolution');
    $m=new MediaAsset(['asset_key'=>'bad','path'=>'javascript:alert(1)','alt_text'=>'x','source_url'=>'https://example.org','license_name'=>'test','status'=>'approved']); check(!$m->validate(),'media rejects non-HTTPS/nonasset source path');
    $file=new CourseworkFile(['scope'=>'team','title'=>'x','storage_path'=>'data/team/../../.env','is_visible'=>1]); check(!$file->validate(),'download traversal rejected by input validator');
    $games=MatchGame::find()->orderBy('starts_at')->limit(2)->all(); $edge=new MatchProgression(['from_match_id'=>$games[0]->id,'to_match_id'=>$games[0]->id,'outcome'=>'winner','slot'=>'home','source_url'=>'https://example.org/source']); check(!$edge->validate(),'bracket self-cycle rejected');
    check(count(array_filter((new CatalogService())->bracket(),fn($e)=>(int)$e['is_verified']!==1))===0,'bracket exposes verified edges only');
    check((new CatalogService())->teams(['q'=>$team->name_original])->totalCount>=1,'real Yii2 search query returns existing team');
    $parser=new app\services\SqlFileService();
    $statements=$parser->statements("-- comment\nINSERT INTO x VALUES ('a;b'); /* comment; */ SELECT 'it''s;ok'; SELECT 1;");
    check(count($statements)===3,'SQL restore parser preserves quoted semicolons and doubled quotes');
    rejects(fn()=>$parser->statements("SELECT 'unclosed"),RuntimeException::class,'malformed SQL rejected');
    $rss=(new app\services\NewsImportService())->parse('<rss><channel><item><title>Football</title><link>https://example.org/story</link><description><![CDATA[<b>Facts</b> &amp; context]]></description><pubDate>Wed, 21 Oct 2026 12:00:00 GMT</pubDate></item><item><title>bad</title><link>javascript:alert(1)</link></item></channel></rss>');
    check(count($rss)===1&&$rss[0]['summary']==='Facts & context','RSS parser strips markup and ignores unsafe link schemes');
    rejects(fn()=>(new app\services\NewsImportService())->parse('not XML'),RuntimeException::class,'invalid RSS rejected');
    check(safe_url('javascript:alert(1)')==='#'&&safe_url('//other-host')==='#','public links reject script and protocol-relative URLs');
    $patch=$pref->save($a,['bio'=>'局部更新']);
    check((int)$patch->main_team_id===(int)$team->id&&app\models\UserFavoritePlayer::find()->where(['user_id'=>$a->id,'player_id'=>$player->id])->exists(),'partial preference update preserves main team and followed players');
    $service->moderate($admin,'thread',(int)$thread->id,'publish');$service->moderate($admin,'thread',(int)$thread->id,'unlock');
    rejects(fn()=>$service->editReply($a,(int)$reply->id,'非作者不能改别人的回复'),yii\web\ForbiddenHttpException::class,'reply edit ownership enforced in service');
    $edited=$service->editReply($b,(int)$reply->id,'这是作者修改后的回复。');check(!$edited->hasErrors()&&$edited->body==='这是作者修改后的回复。','reply author can edit own unlocked reply');
    $invalidTag=new app\models\NewsArticle(['source_name'=>'test','source_url'=>'https://example.org/test','title_original'=>'test','tagIds'=>[999999999]]);check(!$invalidTag->validate(),'admin news tags require existing tag records');
    $tx->rollBack(); echo 'PASS '.count($checks)." database/service checks; all test writes rolled back.\n";
    foreach($checks as $check) { echo '  PASS '.$check."\n"; }
    exit(0);
} catch(Throwable $e) { if($tx->isActive) { $tx->rollBack(); } fwrite(STDERR,'FAIL '.$e->getMessage()."\n".$e->getTraceAsString()."\n"); exit(1); }
