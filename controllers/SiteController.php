<?php
/** WorldCup Universe: controllers/SiteController.php; course project source. */

declare(strict_types=1);

namespace app\controllers;

use app\models\{Team,Player,Comment,NewsArticle,MatchGame,CourseworkFile};
use app\services\{CatalogService,AuditService};
use Yii;

final class SiteController extends BaseController
{
    public function actionIndex(): string { return $this->render('index',(new CatalogService())->home()); }
    public function actionCredits(): string { return $this->render('credits'); }
    public function actionSearch(): string
    {
        $filters=['q'=>Yii::$app->request->get('q','')]; $service=new CatalogService();
        return $this->render('search',['q'=>$filters['q'],'teams'=>$filters['q']?$service->teams($filters)->getModels():[],'players'=>$filters['q']?$service->players($filters)->getModels():[],'news'=>$filters['q']?$service->news($filters)->getModels():[]]);
    }
    public function actionDeliverables(): string { return $this->render('deliverables',['files'=>CourseworkFile::find()->where(['is_visible'=>1])->orderBy(['scope'=>SORT_ASC,'id'=>SORT_ASC])->all()]); }
    public function actionDownload(int $id)
    {
        $file=CourseworkFile::findOne(['id'=>$id,'is_visible'=>1]);
        if (!$file || !($path=$file->resolvedPath())) { throw new \yii\web\NotFoundHttpException('文件尚未发布。'); }
        return Yii::$app->response->sendFile($path,basename($path),['inline'=>false]);
    }
    public function actionComment()
    {
        $this->posted(); $actor=$this->actor(); $input=Yii::$app->request->post();
        if(Comment::find()->where(['user_id'=>$actor->id])->andWhere(['>','created_at',date('Y-m-d H:i:s',time()-60)])->count()>=10) { throw new \yii\web\TooManyRequestsHttpException('留言过于频繁，请稍后再试。'); }
        $types=['team'=>Team::class,'player'=>Player::class,'match'=>MatchGame::class,'news'=>NewsArticle::class];
        $type=$input['entity_type']??'site'; $id=empty($input['entity_id'])?null:(int)$input['entity_id'];
        if ($type!=='site' && (!isset($types[$type]) || !$id || !$types[$type]::findOne($id))) { throw new \yii\web\BadRequestHttpException('留言对象不存在。'); }
        if ($type==='news' && NewsArticle::findOne($id)->status!=='published') { throw new \yii\web\NotFoundHttpException(); }
        $model=new Comment(['body'=>$input['body']??'']); $model->user_id=$actor->id; $model->entity_type=$type; $model->entity_id=$type==='site'?null:$id; $model->status='pending';
        if ($model->save()) { AuditService::record('comment.create','comments',(int)$model->id); Yii::$app->session->setFlash('success','留言已提交审核。'); }
        else { Yii::$app->session->setFlash('error',implode(' ',$model->getFirstErrors())); }
        return $this->redirect(match($type) {'team'=>'/teams/'.$id,'player'=>'/players/'.$id,'match'=>'/matches/'.$id,'news'=>'/news/'.$id,default=>'/forum'});
    }
    public function actionError(): mixed
    {
        $exception=Yii::$app->errorHandler->exception;
        $status=$exception instanceof \yii\web\HttpException?$exception->statusCode:500;
        Yii::$app->response->statusCode=$status;
        if (Yii::$app->response->format===\yii\web\Response::FORMAT_JSON) { return ['error'=>['status'=>$status,'message'=>$status>=500?'服务器处理请求失败。':($exception?->getMessage()?:'请求失败。')]]; }
        return $this->render('error',['status'=>$status,'message'=>$status>=500?'服务器处理请求时发生错误，请稍后重试。':($exception?->getMessage()?:'页面不存在。')]);
    }
}
