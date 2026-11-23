<?php
/**
 * 文件作用：控制台命令 community/*：清理旧版本遗留的演示论坛数据。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\commands;
final class CommunityController extends \yii\console\Controller
{
    /** Compatibility entry point: removes the old fixtures instead of creating accounts or posts. */
    public function actionSeed(): int
    {
        $result=(new \app\services\CommunitySeedService())->purgeDemoFixtures();
        $this->stdout('Community demo fixtures removed '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n");
        return 0;
    }
    /** Clear all local course-demo forum topics and non-administrator accounts. */
    public function actionClearAll(): int
    {
        $result=(new \app\services\CommunitySeedService())->clearAllDemoCommunity();
        $this->stdout('Community demo data cleared '.json_encode($result,JSON_UNESCAPED_UNICODE)."\n");
        return 0;
    }
}
