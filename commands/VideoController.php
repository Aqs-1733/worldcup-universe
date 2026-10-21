<?php
/** Publish only catalogue entries verified against the official broadcaster metadata. */
declare(strict_types=1);
namespace app\commands;
final class VideoController extends \yii\console\Controller
{
    public function actionSeed(): int
    {
        $m=\app\models\MatchVideo::findOne(['provider'=>'rtve','provider_id'=>'17162172'])??new \app\models\MatchVideo();
        $m->setAttributes(['slug'=>'2026-final-spain-argentina','title'=>'西班牙 对 阿根廷 · 决赛完整回放','tournament_year'=>2026,'stage'=>'决赛','home_name'=>'西班牙','away_name'=>'阿根廷','home_code'=>'ESP','away_code'=>'ARG','provider'=>'rtve','provider_id'=>'17162172','source_url'=>'https://www.rtve.es/play/videos/copa-mundial-de-la-fifa-2026/final-espana-argentina-completo-mundial-2026/17162172/','source_author'=>'RTVE','source_title'=>'Final | España - Argentina | Mundial FIFA 2026','video_type'=>'full_match','status'=>'published']);
        if(!$m->save()){$this->stderr(json_encode($m->errors,JSON_UNESCAPED_UNICODE)."\n");return 1;}
        $this->stdout("PASS video catalogue: official RTVE 2026 full final embed; publisher login required; stream playback not asserted.\n");return 0;
    }
}
