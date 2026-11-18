<?php
/** Idempotent, clearly named community fixtures for a populated local/demo environment. */
declare(strict_types=1);
namespace app\services;
use app\models\{FanProfile,ForumCategory,ForumReply,ForumThread,Team,User};
use Yii;
final class CommunitySeedService
{
    /** @return array<string,int> */
    public function seed(): array
    {
        return Yii::$app->db->transaction(function(): array {
            $result=['users'=>0,'threads'=>0,'replies'=>0,'likes'=>0,'hidden_legacy'=>0];
            $result['hidden_legacy']=Yii::$app->db->createCommand()->update('forum_threads',['status'=>'hidden','is_pinned'=>0],['or',['like','title','【示例】%',false],['title'=>'论坛使用说明']])->execute();
            $members=[
                'late-whistle'=>['email'=>'late-whistle@worldcup.local','name'=>'补时别眨眼','avatar'=>'/assets/avatars/补时别眨眼.svg','team'=>'argentina','bio'=>'看球主打一个补时也不走。'],
                'offside-run'=>['email'=>'offside-run@worldcup.local','name'=>'越位也要冲','avatar'=>'/assets/avatars/越位也要冲.svg','team'=>'spain','bio'=>'进球先别庆祝，等边裁。'],
                'post-please'=>['email'=>'post-please@worldcup.local','name'=>'球门柱保佑','avatar'=>'/assets/avatars/球门柱保佑.svg','team'=>'japan','bio'=>'冷门爱好者，主打一个不按套路。'],
                'group-math'=>['email'=>'group-math@worldcup.local','name'=>'小组赛数学家','avatar'=>'/assets/avatars/小组赛数学家.svg','team'=>'germany','bio'=>'积分表一出来就开始算。'],
                'stand-seven'=>['email'=>'stand-seven@worldcup.local','name'=>'看台第七码','avatar'=>'/assets/avatars/看台第七码.svg','team'=>'mexico','bio'=>'场馆攻略和看台氛围收集者。'],
                'night-match'=>['email'=>'night-match@worldcup.local','name'=>'夜猫子看球','avatar'=>'/assets/avatars/夜猫子看球.svg','team'=>'portugal','bio'=>'凌晨开球也会准时出现。'],
            ];
            $users=[];
            foreach($members as $username=>$member) {
                $user=User::findOne(['username'=>$username]);
                $team=Team::findOne(['slug'=>$member['team']]);
                if(!$team) { throw new \RuntimeException('缺少球队：'.$member['team']); }
                if(!$user) {
                    $user=new User(['username'=>$username,'email'=>$member['email'],'display_name'=>$member['name'],'avatar_url'=>$member['avatar']]);
                    $user->password_hash=password_hash('WorldCupDemo2026!',PASSWORD_DEFAULT);
                    if(!$user->save()) { throw new \RuntimeException(json_encode($user->errors,JSON_UNESCAPED_UNICODE)); }
                    $result['users']++;
                } elseif($user->avatar_url!==$member['avatar'] || $user->display_name!==$member['name']) {
                    $user->display_name=$member['name'];$user->avatar_url=$member['avatar'];$user->save(false);
                }
                $profile=FanProfile::findOne(['user_id'=>$user->id])??new FanProfile(['user_id'=>$user->id]);
                $profile->main_team_id=$team->id;$profile->bio=$member['bio'];
                if(!$profile->save()) { throw new \RuntimeException(json_encode($profile->errors,JSON_UNESCAPED_UNICODE)); }
                $users[$username]=$user;
            }
            $categories=[];foreach(ForumCategory::find()->all() as $category){$categories[$category->slug]=$category;}
            $forum=new ForumService();$threads=[];
            $topics=[
                ['late-whistle','worldcup','argentina','阿根廷这十分钟后场出球，我先暂停呼吸','被逼抢的那段我连弹幕都不敢开。好在最后几脚处理还是稳住了，大家觉得这套中场该怎么换人？'],
                ['offside-run','tactics','spain','西班牙的中场跑动到底谁在关灯后加班','控球看着很丝滑，回追也没掉线。这个强度踢到淘汰赛会不会有点吃体能？'],
                ['post-please','home-team','japan','日本队边路一启动，感觉小组赛要有节目','别急着喊爆冷，先看对手怎么压边后卫。不过这速度确实看得人想把进度条拉回去。'],
                ['stand-seven','fan-works','mexico','第一次去墨西哥城看球，座位和进场有什么坑','准备把开球前两小时留给进场和找座位，有去过大场馆的朋友可以说说真实体验。'],
                ['group-math','worldcup','germany','小组赛刚开两轮，出线公式已经写满草稿纸','我知道现在算有点早，但净胜球和交手成绩一摆出来，手已经自己动了。'],
                ['night-match','home-team','portugal','凌晨场集合：别剧透，只聊看球感受','想开一个不甩比分截图的聊天楼。开场前来报到，赛后再聊谁的心率先过线。'],
            ];
            foreach($topics as [$author,$category,$team,$title,$body]) {
                $thread=ForumThread::find()->where(['title'=>$title])->one();
                if(!$thread) {
                    $thread=$forum->saveThread($users[$author],['title'=>$title,'body'=>$body,'category_id'=>$categories[$category]->id,'team_id'=>Team::findOne(['slug'=>$team])->id]);
                    if($thread->hasErrors()) { throw new \RuntimeException(json_encode($thread->errors,JSON_UNESCAPED_UNICODE)); }
                    $result['threads']++;
                }
                $threads[$title]=$thread;
            }
            $replies=[
                ['阿根廷这十分钟后场出球，我先暂停呼吸','offside-run','同感，后腰一回头我手里的薯片都不香了。'],
                ['阿根廷这十分钟后场出球，我先暂停呼吸','group-math','这种局面先把第二落点拿住，比硬传更靠谱。'],
                ['西班牙的中场跑动到底谁在关灯后加班','night-match','看完数据再说，但肉眼看覆盖面积已经拉满。'],
                ['西班牙的中场跑动到底谁在关灯后加班','late-whistle','下一场要是还是这个跑法，我先把咖啡备上。'],
                ['日本队边路一启动，感觉小组赛要有节目','post-please','我押一个边路传中制造点球，先放这儿。'],
                ['第一次去墨西哥城看球，座位和进场有什么坑','night-match','同蹲攻略，散场交通和安检时间也很想知道。'],
                ['第一次去墨西哥城看球，座位和进场有什么坑','group-math','带好充电宝，比赛日导航真的很耗电。'],
                ['小组赛刚开两轮，出线公式已经写满草稿纸','late-whistle','你先算，我负责把截图存好，之后回来对答案。'],
                ['凌晨场集合：别剧透，只聊看球感受','stand-seven','已就位，今晚谁先困谁请喝冰美式。'],
                ['凌晨场集合：别剧透，只聊看球感受','offside-run','不剧透党集合，比分图请晚十分钟再发。'],
            ];
            foreach($replies as [$title,$author,$body]) {
                $thread=$threads[$title];
                if(!ForumReply::find()->where(['thread_id'=>$thread->id,'user_id'=>$users[$author]->id,'body'=>$body])->exists()) {
                    $reply=$forum->reply($users[$author],(int)$thread->id,['body'=>$body]);
                    if($reply->hasErrors()) { throw new \RuntimeException(json_encode($reply->errors,JSON_UNESCAPED_UNICODE)); }
                    $result['replies']++;
                }
            }
            foreach([['late-whistle','西班牙的中场跑动到底谁在关灯后加班'],['offside-run','凌晨场集合：别剧透，只聊看球感受'],['night-match','阿根廷这十分钟后场出球，我先暂停呼吸'],['group-math','第一次去墨西哥城看球，座位和进场有什么坑']] as [$username,$title]) {
                $before=(int)Yii::$app->db->createCommand('SELECT COUNT(*) FROM thread_likes WHERE user_id=:user AND thread_id=:thread',[':user'=>$users[$username]->id,':thread'=>$threads[$title]->id])->queryScalar();
                $forum->setCollection($users[$username],(int)$threads[$title]->id,'like',true);
                $result['likes']+=1-$before;
            }
            return $result;
        });
    }
}
