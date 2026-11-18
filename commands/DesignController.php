<?php
/** Backward-compatible command that installs the current community demo fixtures. */
declare(strict_types=1);
namespace app\commands;
final class DesignController extends \yii\console\Controller
{
    public function actionForum(): int
    {
        $result=(new \app\services\CommunitySeedService())->seed();
        $this->stdout('PASS community fixtures '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n");
        return 0;
    }
}
