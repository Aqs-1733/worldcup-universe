<?php
/** Bounded RSS parser and current publisher-feed checks; no test writes. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
new yii\console\Application(require ROOT_PATH.'/config/console.php');

$service=new app\services\NewsImportService();
$xml='<rss xmlns:media="http://search.yahoo.com/mrss/"><channel><item><title>Team news</title><link>https://www.bbc.co.uk/sport/football/test</link><description>&lt;b&gt;Real excerpt&lt;/b&gt;</description><media:thumbnail url="https://ichef.bbci.co.uk/sample.jpg"/><category>Football</category></item><item><title>Unsafe</title><link>javascript:alert(1)</link></item></channel></rss>';
$parsed=$service->parse($xml);
if(count($parsed)!==1||$parsed[0]['image']!=='https://ichef.bbci.co.uk/sample.jpg'||$parsed[0]['summary']!=='Real excerpt'||$parsed[0]['categories']!==['Football'])throw new RuntimeException('RSS namespace and metadata regression');
if($service->imageUrl('http://ichef.bbci.co.uk/x.jpg')||$service->imageUrl('https://127.0.0.1/x.jpg')||$service->imageUrl('javascript:alert(1)'))throw new RuntimeException('Image scheme/host validation regression');
$large=$service->parse('<rss xmlns:media="http://search.yahoo.com/mrss/"><channel><item><title>Photo sizes</title><link>https://www.theguardian.com/football/example</link><media:content width="140" url="https://i.guim.co.uk/small.jpg"/><media:content width="700" url="https://i.guim.co.uk/large.jpg"/></item></channel></rss>');
if($large[0]['image']!=='https://i.guim.co.uk/large.jpg')throw new RuntimeException('Largest publisher photo not selected');
if(app\models\NewsArticle::find()->where(['status'=>'published','source_name'=>'赛事简报'])->exists())throw new RuntimeException('Generated briefs still public');

$source=app\models\NewsSource::findOne(['rss_url'=>'https://www.chinanews.com.cn/rss/sports.xml']);
if(!$source||(int)$source->is_enabled!==1||$source->last_synced_at===null||$source->last_error!==null)throw new RuntimeException('Current Chinese sports publisher is unavailable');
$footballRss=['https://feeds.bbci.co.uk/sport/football/rss.xml','https://www.espn.com/espn/rss/soccer/news'];
$healthy=0;
foreach($footballRss as $rss){$football=app\models\NewsSource::findOne(['rss_url'=>$rss]);if(!$football||(int)$football->is_enabled!==1)throw new RuntimeException('Football publisher is not configured');if($football->last_synced_at!==null&&$football->last_error===null)$healthy++;}
if($healthy<2)throw new RuntimeException('Too few current football publishers are available');
$articles=app\models\NewsArticle::find()->where(['status'=>'published'])->andWhere(['not',['source_id'=>null]])->all();
if(!$articles)throw new RuntimeException('Current football publishers have no imported articles');
$enabledSourceIds=app\models\NewsSource::find()->select('id')->where(['is_enabled'=>1])->column();
$footballHeadline='/足球|世界杯|国足|男足|女足|欧冠|欧联|英超|西甲|意甲|德甲|中超|梅西|C罗|巴萨|皇马|曼联|切尔西|阿森纳|利物浦/u';
foreach($articles as $article){if(!in_array((int)$article->source_id,$enabledSourceIds,true))throw new RuntimeException('Retired publisher article is still public');if($article->language_code==='zh-CN'&&!preg_match($footballHeadline,$article->title_original))throw new RuntimeException('Non-football domestic article is still public');$raw=is_array($article->raw_payload_json)?$article->raw_payload_json:json_decode($article->raw_payload_json??'{}',true);if(($raw['kind']??'')==='publisher_rss'&&empty($raw['feed_sha256']))throw new RuntimeException('Missing feed hash');}
$importer=(string)file_get_contents(ROOT_PATH.'/services/NewsImportService.php');
$strictItemFilter="isFootballText(\$item['title'])";
if(!str_contains($importer,$strictItemFilter)||!str_contains($importer,'isFootballText($article->title_original)'))throw new RuntimeException('Domestic football filter is not headline-only');

$controller=(string)file_get_contents(ROOT_PATH.'/controllers/NewsController.php');
$view=(string)file_get_contents(ROOT_PATH.'/views/catalog/news.php');
$live=(string)file_get_contents(ROOT_PATH.'/public/assets/js/news-live.js');
if(!str_contains($controller,'public function actionRefresh()')||preg_match('/public function actionRefresh\(\).*?isAdmin/s',$controller))throw new RuntimeException('Refresh endpoint remains administrator-only');
if(!str_contains($view,'method="post" action="/news/refresh"')||!str_contains($view,'刷新新闻 ↻'))throw new RuntimeException('News page has no refresh form');
if(!str_contains($live,'data.revision')||!str_contains($live,"cache: 'no-store'"))throw new RuntimeException('News live status does not observe refreshed records');
echo "PASS football news refresh: current football sources are enabled, non-football domestic reports are hidden, and live status observes source revisions.\n";
