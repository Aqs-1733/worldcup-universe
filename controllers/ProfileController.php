<?php
/** Member-only preferences, followed entities, notifications and bookmarks. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{FanProfile,Team,Player,Notification,ForumThread,FanPreference};
use app\services\PreferenceService;
use Yii;
use yii\filters\AccessControl;
final class ProfileController extends BaseController
{
    public function behaviors(): array { return ['access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@']]]]]; }
    public function actionIndex()
    {
        $actor=$this->actor(); $model=FanProfile::findOne($actor->id)??new FanProfile();
        if (!Yii::$app->request->isPost && ($requested=Yii::$app->request->get('team')) && Team::findOne((int)$requested)) { $model->main_team_id=(int)$requested; }
        if (Yii::$app->request->isPost) { $model=(new PreferenceService())->save($actor,Yii::$app->request->post()); if (!$model->hasErrors()) { Yii::$app->session->setFlash('success','主队与球迷偏好已保存。'); return $this->refresh(); } }
        $ids=function($table,$field)use($actor) { return (new \yii\db\Query())->select($field)->from($table)->where(['user_id'=>$actor->id])->column(); };
        $favoritePlayerIds=$ids('user_favorite_players','player_id');
        $hot=Player::find()->andWhere(['not',['official_import_id'=>null]])->orderBy(['popularity_score'=>SORT_DESC])->limit(150)->all(); $hotIds=array_map(fn($p)=>(int)$p->id,$hot); $extras=array_diff($favoritePlayerIds,$hotIds);
        if ($extras) { $hot=array_merge($hot,Player::find()->andWhere(['not',['official_import_id'=>null]])->where(['id'=>$extras])->all()); }
        return $this->render('index',['model'=>$model,'teams'=>Team::find()->andWhere(['not',['official_import_id'=>null]])->orderBy(['name_original'=>SORT_ASC])->all(),'players'=>$hot,
            'favoriteTeams'=>$ids('user_favorite_teams','team_id'),'favoritePlayers'=>$ids('user_favorite_players','player_id'),'blockedTeams'=>$ids('user_blocked_teams','team_id'),
            'prefs'=>FanPreference::findOne(['user_id'=>$actor->id]),
            'bookmarks'=>ForumThread::find()->innerJoin('thread_bookmarks b','b.thread_id=forum_threads.id')->where(['b.user_id'=>$actor->id,'forum_threads.status'=>'published'])->limit(50)->all()]);
    }
    public function actionNotifications()
    {
        $actor=$this->actor(); if (Yii::$app->request->isPost) { Notification::updateAll(['read_at'=>date('Y-m-d H:i:s')],['user_id'=>$actor->id,'read_at'=>null]); return $this->refresh(); }
        return $this->render('notifications',['items'=>Notification::find()->with('thread')->where(['user_id'=>$actor->id])->orderBy(['id'=>SORT_DESC])->limit(100)->all()]);
    }
}
