<?php
/** Forum HTTP workflow: authentication, CSRF and service-level ownership checks. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{ForumThread,ForumReply,ForumCategory,Team,MatchGame};
use app\services\ForumService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
final class ForumController extends BaseController
{
    public function behaviors(): array { return ['access'=>['class'=>AccessControl::class,'except'=>['index','view'],'rules'=>[['allow'=>true,'roles'=>['@']]]]]; }
    public function actionIndex()
    {
        $q=ForumThread::find()->alias('t')->select(['t.*','reply_count'=>new \yii\db\Expression("(SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id=t.id AND r.status='visible')"),'like_count'=>new \yii\db\Expression('(SELECT COUNT(*) FROM thread_likes l WHERE l.thread_id=t.id)')])->with(['author.profile.mainTeam','team','category'])->where(['t.status'=>'published']);
        $q->andFilterWhere(['t.category_id'=>Yii::$app->request->get('category'),'t.team_id'=>Yii::$app->request->get('team_id')]);
        if ($search=Yii::$app->request->get('q')) { $q->andWhere(['like','t.title',mb_substr((string)$search,0,160)]); }
        if (Yii::$app->request->get('mine')==='1') { $q->andWhere(['t.author_id'=>$this->actor()->id]); }
        if (Yii::$app->request->get('feed')==='main') { $q->andWhere(['t.team_id'=>$this->actor()->profile?->main_team_id?:0]); }
        return $this->render('index',['provider'=>new ActiveDataProvider(['query'=>$q->orderBy(['t.is_pinned'=>SORT_DESC,'t.last_activity_at'=>SORT_DESC,'t.id'=>SORT_DESC]),'pagination'=>['pageSize'=>20],'sort'=>false]),'categories'=>ForumCategory::find()->orderBy('sort_order')->all(),'teams'=>Team::find()->andWhere(['not',['official_import_id'=>null]])->orderBy('name_original')->all()]);
    }
    public function actionView(int $id)
    {
        $thread=(new ForumService())->readable($id,Yii::$app->user->identity);
        return $this->showThread($thread);
    }
    public function actionCreate() { return $this->editThread(null); }
    public function actionEdit(int $id) { return $this->editThread($id); }
    public function actionDelete(int $id) { $this->posted(); (new ForumService())->remove($this->actor(),'thread',$id); return $this->redirect('/forum'); }
    public function actionDeleteReply(int $id) { $this->posted(); $reply=ForumReply::findOne($id); if (!$reply) { throw new \yii\web\NotFoundHttpException(); } (new ForumService())->remove($this->actor(),'reply',$id); return $this->redirect('/forum/'.$reply->thread_id); }
    public function actionEditReply(int $id)
    {
        $this->posted(); $reply=(new ForumService())->editReply($this->actor(),$id,(string)Yii::$app->request->post('body',''));
        Yii::$app->session->setFlash($reply->hasErrors()?'error':'success',$reply->hasErrors()?implode(' ',$reply->getFirstErrors()):'回复已更新。'); return $this->redirect('/forum/'.$reply->thread_id.'#reply-'.$id);
    }
    public function actionReply(int $id)
    {
        $this->posted(); $reply=(new ForumService())->reply($this->actor(),$id,Yii::$app->request->post());
        if ($reply->hasErrors()) { Yii::$app->response->statusCode=422; return $this->showThread((new ForumService())->readable($id,$this->actor()),$reply); }
        return $this->redirect('/forum/'.$id.'#reply-'.$reply->id);
    }
    public function actionLike(int $id) { return $this->collection($id,'like'); }
    public function actionBookmark(int $id) { return $this->collection($id,'bookmark'); }
    public function actionReport(int $id)
    {
        $this->posted(); $report=(new ForumService())->report($this->actor(),$id,Yii::$app->request->post());
        Yii::$app->session->setFlash($report->hasErrors()?'error':'success',$report->hasErrors()?implode(' ',$report->getFirstErrors()):'举报已提交，等待管理员处理。'); return $this->redirect('/forum/'.$id);
    }
    private function collection(int $id,string $kind) { $this->posted(); (new ForumService())->setCollection($this->actor(),$id,$kind,Yii::$app->request->post('enabled')==='1'); return $this->redirect('/forum/'.$id); }
    private function editThread(?int $id)
    {
        $actor=$this->actor(); $model=$id?(new ForumService())->readable($id,$actor):new ForumThread();
        if ($id && (int)$model->author_id!==(int)$actor->id && !$actor->isAdmin()) { throw new \yii\web\ForbiddenHttpException(); }
        if (Yii::$app->request->isPost) { $model=(new ForumService())->saveThread($actor,Yii::$app->request->post(),$id); if (!$model->hasErrors()) { return $this->redirect('/forum/'.$model->id); } Yii::$app->response->statusCode=422; }
        return $this->render('edit',['model'=>$model,'categories'=>ForumCategory::find()->orderBy('sort_order')->all(),'teams'=>Team::find()->andWhere(['not',['official_import_id'=>null]])->orderBy('name_original')->all(),'matches'=>MatchGame::find()->with(['homeTeam','awayTeam'])->where(['not',['official_import_id'=>null]])->orderBy(['starts_at'=>SORT_DESC])->limit(150)->all()]);
    }
    private function showThread(ForumThread $thread,?ForumReply $reply=null)
    {
        $uid=Yii::$app->user->id;
        return $this->render('view',['model'=>$thread,'liked'=>$uid?\app\models\ThreadLike::find()->where(['thread_id'=>$thread->id,'user_id'=>$uid])->exists():false,'bookmarked'=>$uid?\app\models\ThreadBookmark::find()->where(['thread_id'=>$thread->id,'user_id'=>$uid])->exists():false,'replyModel'=>$reply??new ForumReply(),'provider'=>new ActiveDataProvider(['query'=>ForumReply::find()->with('user')->where(['thread_id'=>$thread->id,'status'=>'visible'])->orderBy('id'),'pagination'=>['pageSize'=>30],'sort'=>false])]);
    }
}
