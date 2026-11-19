<?php
/** Persistent site-health checks for the public content that needs real data. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$config=require ROOT_PATH.'/config/web.php';
$config['components']['request']['scriptFile']=ROOT_PATH.'/public/index.php';
$config['components']['request']['scriptUrl']='/index.php';
$config['components']['request']['cookieValidationKey']=str_repeat('site-health-key',8);
new yii\web\Application($config);
use app\models\{CountryProfile,ForumReply,ForumThread,MatchVideo,NewsArticle,NewsSource,User,Venue};
$checks=[];
function health(bool $condition,string $message): void { global $checks; if(!$condition) { throw new RuntimeException($message); } $checks[]=$message; }
$videos=require ROOT_PATH.'/config/cctv-videos.php';
health(count($videos)>=2,'two official CCTV video entries configured');
foreach($videos as $video) { health(parse_url($video['embed_url'],PHP_URL_HOST)==='tv.cctv.com','video entry uses CCTV official host'); }
health(MatchVideo::find()->where(['<>','provider','local'])->count()===0,'no third-party remote video record remains');
$watchView=(string)file_get_contents(ROOT_PATH.'/views/catalog/videos.php');
health(!str_contains(strtolower($watchView),'youtube')&&!str_contains(strtolower($watchView),'rtve'),'watch view contains no unavailable provider');
$members=['late-whistle'=>'补时别眨眼','offside-run'=>'越位也要冲','post-please'=>'球门柱保佑','group-math'=>'小组赛数学家','stand-seven'=>'看台第七码','night-match'=>'夜猫子看球'];
foreach($members as $username=>$displayName) {
    $user=User::findOne(['username'=>$username]);
    health($user!==null&&$user->display_name===$displayName,'community user '.$username.' exists');
    health($user!==null&&is_file(ROOT_PATH.'/public'.$user->avatar_url),'community avatar '.$username.' exists');
}
health(ForumThread::find()->where(['status'=>'published'])->andWhere(['author_id'=>User::find()->select('id')->where(['username'=>array_keys($members)])])->count()>=6,'seeded community discussions are published');
health(ForumReply::find()->where(['user_id'=>User::find()->select('id')->where(['username'=>array_keys($members)])])->count()>=10,'seeded community replies are visible');
$chinanews=NewsSource::findOne(['rss_url'=>'https://www.chinanews.com.cn/rss/sports.xml']);
health($chinanews!==null&&(int)$chinanews->is_enabled===1,'Chinese news RSS source enabled');
health($chinanews!==null&&$chinanews->last_synced_at!==null,'Chinese news RSS has completed a refresh');
health($chinanews!==null&&NewsArticle::find()->where(['source_id'=>$chinanews->id])->count()>0,'Chinese news has visible imported articles');
$timer=(string)file_get_contents(ROOT_PATH.'/deploy/systemd/wcu-news-sync.timer');
$service=(string)file_get_contents(ROOT_PATH.'/deploy/systemd/wcu-news-sync.service.template');
health(str_contains($timer,'OnCalendar=*:0/10')&&str_contains($timer,'Persistent=true'),'production timer refreshes news every ten minutes');
health(str_contains($service,'yii news/sync')&&str_contains($service,'flock'),'production news job avoids concurrent syncs');
health(CountryProfile::find()->count()===48,'all forty-eight World Cup country records are available');
$stories=require ROOT_PATH.'/config/venue-stories.php';
health(count($stories)===16,'all sixteen host venues have an introduction');
health(Venue::find()->where(['name_original'=>array_keys($stories)])->count()===16,'venue introductions match stored host venues');
echo 'PASS '.count($checks)." site-health checks.\n";
foreach($checks as $check) { echo ' - '.$check."\n"; }
