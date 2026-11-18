<?php
/** No third-party playback URLs are seeded into the public catalogue. */
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
