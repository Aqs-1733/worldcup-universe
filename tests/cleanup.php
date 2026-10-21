<?php
/** Delete only explicitly identified disposable HTTP test users and their content. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
$prefix=$argv[1]??'';
if(!preg_match('/^qa_[a-f0-9]{8}$/',$prefix)) { fwrite(STDERR,"Expected an exact qa_XXXXXXXX test prefix.\n");exit(1); }
$users=app\models\User::find()->where(['username'=>[$prefix.'_a',$prefix.'_b']])->all();
foreach($users as $user) { if(!in_array($user->email,[$prefix.'a@example.org',$prefix.'b@example.org'],true)) { throw new RuntimeException('Test identity marker mismatch'); } }
$app->db->transaction(function()use($users) {
 foreach($users as $user) { $threads=app\models\ForumThread::find()->where(['author_id'=>$user->id])->all();foreach($threads as $thread) { $thread->delete(); } }
 foreach($users as $user) { app\models\ForumReply::deleteAll(['user_id'=>$user->id]); $user->delete(); }
});
echo 'CLEANED '.count($users).' test members; prefix='.$prefix."\n";
