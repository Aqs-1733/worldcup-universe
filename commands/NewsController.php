<?php
/** Native console commands for scheduled and manual football-news synchronization. */
declare(strict_types=1);
namespace app\commands;

final class NewsController extends \yii\console\Controller
{
    public function actionConfigure(): int
    {
        $changed=(new \app\services\NewsImportService())->ensureFootballSources();
        $this->stdout("PASS configured current football RSS sources: $changed changed.\n");
        return 0;
    }

    public function actionCurate(): int
    {
        $generated=\Yii::$app->db->createCommand()->update('news_articles',['status'=>'draft'],['source_name'=>'赛事简报'])->execute();
        $domestic=(new \app\services\NewsImportService())->curateDomesticNonFootball();
        $this->stdout("PASS news curation: $generated generated briefs and $domestic reports outside the active football feeds moved to non-public drafts.\n");
        return 0;
    }

    public function actionTags(): int
    {
        $this->stdout('Tagged '.(new \app\services\NewsImportService())->backfillTags()." articles.\n");
        return 0;
    }

    public function actionSync(): int
    {
        $result=(new \app\services\NewsImportService())->sync();
        $this->stdout(gmdate(DATE_ATOM).' '.json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
        if(!empty($result['busy'])) { $this->stdout("SKIP another news import holds the lock.\n"); return 0; }
        $failed=array_column(array_filter($result['sources'],static fn(array $source): bool=>$source['status']==='failed'),'name');
        if($failed) { \Yii::error('News sources failed: '.implode(', ',$failed),'news'); }
        return count($result['sources'])-count($failed)>0?0:1;
    }

    public function actionWatch(int $interval=300,int $cycles=0): int
    {
        $interval=max(60,min(3600,$interval));
        $round=0;
        do {
            $result=(new \app\services\NewsImportService())->sync();
            $this->stdout(gmdate(DATE_ATOM).' '.json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
            $round++;
            if($cycles&&$round>=$cycles)break;
            sleep($interval);
        } while(true);
        return 0;
    }
}
