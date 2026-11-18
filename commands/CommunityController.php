<?php
/** Console-only, idempotent community fixtures used in the demo environment. */
declare(strict_types=1);
namespace app\commands;
final class CommunityController extends \yii\console\Controller
{
    public function actionSeed(): int
    {
        $result=(new \app\services\CommunitySeedService())->seed();
        $this->stdout('PASS community fixtures '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n");
        return 0;
    }
}
