<?php
/**
 * 文件作用：控制台命令 design/*：兼容旧命令名，清理已停用的示例论坛内容。
 * 作者：蒋林瀞_2413507
 */
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
