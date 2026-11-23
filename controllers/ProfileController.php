<?php
/**
 * 文件作用：个人中心控制器：主队与关注设置、回复通知、收藏的主题，只有登录用户能访问。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\controllers;
use app\models\{FanProfile,Team,Player,Notification,ForumThread,FanPreference};
use app\services\PreferenceService;
use Yii;
use yii\filters\AccessControl;
final class ProfileController extends BaseController
{
    // 整个控制器只允许已登录用户访问
    public function behaviors(): array { return ['access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@']]]]]; }
    public function actionIndex()
    {
        $actor=$this->actor(); $model=FanProfile::findOne($actor->id)??new FanProfile();
        // 从球队页点“设为主队”进来时，先把该球队预选上
        if (!Yii::$app->request->isPost && ($requested=Yii::$app->request->get('team')) && Team::findOne((int)$requested)) { $model->main_team_id=(int)$requested; }
        // 提交表单：主队、关注、屏蔽在服务层的一个事务里保存
        if (Yii::$app->request->isPost) { $model=(new PreferenceService())->save($actor,Yii::$app->request->post()); if (!$model->hasErrors()) { Yii::$app->session->setFlash('success','主队与球迷偏好已保存。'); return $this->refresh(); } }
        $ids=function($table,$field)use($actor) { return (new \yii\db\Query())->select($field)->from($table)->where(['user_id'=>$actor->id])->column(); };
        // 球员候选：热度前 150 名，再补上已关注但不在其中的球员
        $favoritePlayerIds=$ids('user_favorite_players','player_id');
        $hot=Player::find()->andWhere(['not',['official_import_id'=>null]])->orderBy(['popularity_score'=>SORT_DESC])->limit(150)->all(); $hotIds=array_map(fn($p)=>(int)$p->id,$hot); $extras=array_diff($favoritePlayerIds,$hotIds);
        if ($extras) { $hot=array_merge($hot,Player::find()->andWhere(['not',['official_import_id'=>null]])->where(['id'=>$extras])->all()); }
        return $this->render('index',['model'=>$model,'teams'=>Team::find()->andWhere(['not',['official_import_id'=>null]])->orderBy(['name_original'=>SORT_ASC])->all(),'players'=>$hot,
            'favoriteTeams'=>$ids('user_favorite_teams','team_id'),'favoritePlayers'=>$ids('user_favorite_players','player_id'),'blockedTeams'=>$ids('user_blocked_teams','team_id'),
            'prefs'=>FanPreference::findOne(['user_id'=>$actor->id]),
            'bookmarks'=>ForumThread::find()->innerJoin('thread_bookmarks b','b.thread_id=forum_threads.id')->where(['b.user_id'=>$actor->id,'forum_threads.status'=>'published'])->limit(50)->all()]);
    }
    // 回复通知：POST 时把自己的未读通知全部标记为已读
    public function actionNotifications()
    {
        $actor=$this->actor(); if (Yii::$app->request->isPost) { Notification::updateAll(['read_at'=>date('Y-m-d H:i:s')],['user_id'=>$actor->id,'read_at'=>null]); return $this->refresh(); }
        return $this->render('notifications',['items'=>Notification::find()->with('thread')->where(['user_id'=>$actor->id])->orderBy(['id'=>SORT_DESC])->limit(100)->all()]);
    }
}
