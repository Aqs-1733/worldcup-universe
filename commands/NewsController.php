<?php
/** Native console command for scheduled or manual RSS synchronization. */
declare(strict_types=1);
namespace app\commands;
final class NewsController extends \yii\console\Controller
{
    public function actionCurate(): int
    {
        $n=\Yii::$app->db->createCommand()->update('news_articles',['status'=>'draft'],['source_name'=>'赛事简报'])->execute();
        \Yii::$app->db->createCommand()->update('news_articles',['status'=>'draft'],['and',['source_name'=>'Sky Sports Football'],['not like','source_url','https://www.skysports.com/football/%',false]])->execute();
        $this->stdout("PASS news curation: $n generated briefs retained as non-public drafts.\n");return 0;
    }
    public function actionConfigure(): int
    {
        $source=\app\models\NewsSource::findOne(['rss_url'=>'https://www.chinanews.com.cn/rss/sports.xml'])??new \app\models\NewsSource();
        $source->setAttributes(['name'=>'中国新闻网 · 体育','url'=>'https://www.chinanews.com.cn/sports/','rss_url'=>'https://www.chinanews.com.cn/rss/sports.xml','language_code'=>'zh-CN','trust_level'=>95,'is_enabled'=>1]);
        if(!$source->save())throw new \RuntimeException(json_encode($source->errors,JSON_UNESCAPED_UNICODE));
        $this->stdout("PASS configured Chinese publisher RSS.\n");return 0;
    }
    public function actionTags(): int { $this->stdout('Tagged '.(new \app\services\NewsImportService())->backfillTags()." articles.\n"); return 0; }
    public function actionSync(): int
    {
        $r=(new \app\services\NewsImportService())->sync(); $this->stdout(gmdate(DATE_ATOM).' '.json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
        if(!empty($r['busy'])) { $this->stdout("SKIP another news import holds the lock.\n"); return 0; }
        // Failed sources keep their message in news_sources.last_error; the next scheduled run retries them.
        $failed=array_column(array_filter($r['sources'],fn($s)=>$s['status']==='failed'),'name');
        if($failed) { \Yii::error('News sources failed: '.implode(', ',$failed),'news'); }
        $successful=count($r['sources'])-count($failed);
        return $successful>0?0:1;
    }
    public function actionWatch(int $interval=300,int $cycles=0): int
    {
        $interval=max(60,min(3600,$interval));$round=0;
        do { $r=(new \app\services\NewsImportService())->sync();$this->stdout(gmdate(DATE_ATOM).' '.json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");$round++;if($cycles&&$round>=$cycles)break;sleep($interval); } while(true);
        return 0;
    }
}
