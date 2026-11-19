<?php
/** Backward-compatible cleanup command for retired generated community fixtures. */
declare(strict_types=1);
namespace app\commands;
final class DesignController extends \yii\console\Controller
{
    public function actionForum(): int
    {
        $result=(new \app\services\CommunitySeedService())->purgeDemoFixtures();
        $this->stdout('Community demo fixtures removed '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n");
        return 0;
    }
}
