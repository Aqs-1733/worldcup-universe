<?php
/** Native editorial list/detail with draft protection and published tags. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{NewsArticle,Tag,Comment};
use app\services\CatalogService;
use Yii;
use yii\web\NotFoundHttpException;
final class NewsController extends BaseController
{
    public function actionIndex() { return $this->render('/catalog/news',['provider'=>(new CatalogService())->news(Yii::$app->request->get()),'tags'=>Tag::find()->where(['slug'=>['match','tactics','team','fan','entertainment','worldcup']])->orderBy('id')->all(),'sources'=>\app\models\NewsSource::find()->where(['is_enabled'=>1])->all()]); }
    public function actionStatus() { return $this->asJson(['count'=>(int)NewsArticle::find()->where(['status'=>'published'])->andWhere(['<>','source_name','赛事简报'])->count(),'latest'=>(int)NewsArticle::find()->where(['status'=>'published'])->max('id'),'updated'=>\app\models\NewsSource::find()->max('last_synced_at')]); }
    public function actionView(int $id)
    {
        $model=NewsArticle::findOne(['id'=>$id,'status'=>'published']); if (!$model) { throw new NotFoundHttpException(); }
        return $this->render('/catalog/article',['model'=>$model,'comments'=>Comment::find()->with('user')->where(['entity_type'=>'news','entity_id'=>$id,'status'=>'visible'])->orderBy(['id'=>SORT_DESC])->limit(50)->all()]);
    }
    public function actionRefresh()
    {
        $this->posted(); if (!$this->actor()->isAdmin()) { throw new \yii\web\ForbiddenHttpException(); }
        if(!Yii::$app->cache->add('news-sync-lock',1,90)) { throw new \yii\web\TooManyRequestsHttpException('新闻同步正在进行或刚刚完成。'); }
        $result=(new \app\services\NewsImportService())->sync();
        Yii::$app->session->setFlash('success','本次新增 '.$result['saved'].' 条新闻。来源同步状态可在后台查看。');
        return $this->redirect('/news');
    }
}
