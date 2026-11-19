<?php
/** Removes bundled demo accounts and fabricated forum content; it never creates pseudo-user posts. */
declare(strict_types=1);
namespace app\services;
use app\models\{ForumReply,ForumThread,User};
use Yii;
final class CommunitySeedService
{
    /** @var list<string> */
    private const DEMO_USERNAMES=['late-whistle','offside-run','post-please','group-math','stand-seven','night-match'];

    /** Remove only the former bundled demo accounts and their generated content. @return array<string,int> */
    public function purgeDemoFixtures(): array
    {
        return Yii::$app->db->transaction(function(): array {
            $ids=User::find()->select('id')->where(['username'=>self::DEMO_USERNAMES])->column();
            $replyCount=$ids?ForumReply::deleteAll(['user_id'=>$ids]):0;
            $threadCount=$ids?ForumThread::deleteAll(['author_id'=>$ids]):0;
            $legacyCount=ForumThread::deleteAll(['or',['like','title','【示例】%',false],['title'=>'论坛使用说明']]);
            $userCount=$ids?User::deleteAll(['id'=>$ids]):0;
            return ['demo_users'=>$userCount,'demo_threads'=>$threadCount,'demo_replies'=>$replyCount,'legacy_threads'=>$legacyCount];
        });
    }

    /** Clear local course-demo community data while preserving the administrator account. @return array<string,int> */
    public function clearAllDemoCommunity(): array
    {
        return Yii::$app->db->transaction(function(): array {
            $threadCount=(int)ForumThread::find()->count();
            $replyCount=(int)ForumReply::find()->count();
            ForumThread::deleteAll(); // dependent replies, reactions, reports and thread notifications use database cascades.
            $userCount=User::deleteAll(['<>','username',(string)env('ADMIN_USERNAME','admin')]);
            return ['users_removed'=>$userCount,'threads_removed'=>$threadCount,'replies_removed'=>$replyCount];
        });
    }
}
