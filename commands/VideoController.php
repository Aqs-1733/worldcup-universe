<?php
/**
 * 文件作用：控制台命令 video/*：维护比赛视频目录，不写入第三方播放地址。
 */
declare(strict_types=1);
namespace app\commands;
final class VideoController extends \yii\console\Controller
{
    /** Removes legacy remote publishers so /watch cannot expose an unavailable player. */
    public function actionPurgeExternal(): int
    {
        $deleted=\Yii::$app->db->createCommand()->delete('match_videos',['<>','provider','local'])->execute();
        $this->stdout("PASS removed {$deleted} remote video catalogue entries.\n");
        return 0;
    }
}
