<?php
/** Explicit design examples, attributed to the site administrator rather than fake fans. */
declare(strict_types=1);
namespace app\commands;
use app\models\{User,Team,ForumThread,ForumCategory,ForumReply,MatchGame};
use app\services\ForumService;
final class DesignController extends \yii\console\Controller
{
 public function actionForum(): int
 {
  $author=User::findOne(['username'=>env('ADMIN_USERNAME','admin')]);if(!$author||!$author->isAdmin())throw new \RuntimeException('Existing administrator required.');
  $specs=[
   ['决赛这场球，你最想回看哪一段？','ESP','worldcup','如果只选十分钟重看，你会选开场、下半场，还是最后阶段？把你想回看的时间段写下来，也可以说说那次攻防为什么让你印象深刻。'],
   ['西班牙与阿根廷，哪一组对位值得复盘？','ARG','tactics','先不聊最后的比分，想看看大家会怎样分析中场的接应和两侧的空间。欢迎结合比赛画面，把你注意到的跑位或防守选择讲清楚。'],
   ['你今年支持哪支球队？','POR','home-team','我的主队已经选好了。你是一直支持同一支球队，还是因为某位球员开始关注？也欢迎聊聊家里人和朋友都支持谁。'],
   ['48支球队里，你最喜欢哪套球衣？','JPN','fan-works','主场、客场都可以。配色、队徽、号码字体，你最看重哪一点？如果有自己画的球衣草图，也欢迎放到球迷创作板块交流。'],
   ['从小组赛到淘汰赛，哪场比赛值得再看？','BRA','worldcup','给新球迷留一份回看清单吧。可以推荐一场，也可以只提一个让你记住的瞬间；尽量说明你为什么会选它。'],
   ['北美三国的比赛场馆，你最想去哪座？','GER','fan-works','你更想看球场建筑、看台氛围，还是顺便在主办城市逛一逛？我先把场馆目录翻了一遍，想听听大家会怎么选。'],
  ];$created=0;$replies=0;
  foreach($specs as [$title,$code,$slug,$body]){
   $full='【示例】'.$title;$existing=ForumThread::findOne(['title'=>$full,'author_id'=>$author->id]);if($existing)continue;
   $category=ForumCategory::findOne(['slug'=>$slug])??ForumCategory::find()->orderBy('id')->one();$team=Team::findOne(['code'=>$code]);
   // Six examples intentionally bypass only the human posting rate, not validation or permissions.
   $thread=new ForumThread(['title'=>$full,'body'=>$body,'category_id'=>$category->id,'team_id'=>$team?->id]);$thread->author_id=$author->id;$thread->status='published';$thread->last_activity_at=date('Y-m-d H:i:s');
   if($code==='ESP'||$code==='ARG')$thread->match_id=MatchGame::find()->select('id')->where(['stage'=>'决赛'])->scalar()?:null;
   if(!$thread->save())throw new \RuntimeException(json_encode($thread->errors,JSON_UNESCAPED_UNICODE));$created++;
   if($code==='ESP')foreach(['示例回复：我会先回看下半场的换人，再对比前后的站位。','示例回复：也可以把同一次进攻的前两脚传球一起看，别只截最后一下。'] as $text){$reply=(new ForumService())->reply($author,(int)$thread->id,['body'=>$text]);if($reply->hasErrors())throw new \RuntimeException('Example reply validation failed');$replies++;}
  }
  $this->stdout("PASS forum examples: $created administrator-authored, labelled topics; $replies labelled replies; no fabricated fan accounts or likes.\n");return 0;
 }
}
