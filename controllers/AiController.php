<?php
/** Optional AI integration stays behind native MVC and explicit user actions. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{AiForm,AiGeneration};
use App\Services\ArkClient;
use Yii;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
final class AiController extends BaseController
{
    public function behaviors(): array { return ['access'=>['class'=>AccessControl::class,'except'=>['index','vision'],'rules'=>[['allow'=>true,'roles'=>['@']]]]]; }
    public function actionIndex()
    {
        $model=new AiForm(['scenario'=>'chat']); $ark=new ArkClient(); $result=null; $image=null;
        if(Yii::$app->request->isPost) {
            $actor=$this->actor(); $this->limit((int)$actor->id);
            if($model->load(Yii::$app->request->post()) && $model->validate()) {
                if(!$ark->textReady()) { $model->addError('prompt','文本模型尚未配置。'); }
                else {
                    try { $result=$ark->chat($model->prompt,$model->tone,$model->target_team?:null,60,2000); $this->record('chat',$model->prompt,$result); }
                    catch(\Throwable $e) { Yii::warning($e->getMessage(),'ai'); $model->addError('prompt','模型请求未完成，请稍后重试。'); $this->record('chat',$model->prompt,null,'failed'); }
                    if($model->generate_image) { try { $image=$ark->image($model->image_prompt?:$model->prompt); $this->record('image',$model->image_prompt?:$model->prompt,null,'success',$image['url']??null); } catch(\Throwable $e) { $model->addError('image_prompt','图片生成未完成。'); $this->record('image',$model->prompt,null,'failed'); } }
                }
            }
        }
        return $this->render('/site/ai',['model'=>$model,'configured'=>$ark->textReady(),'result'=>$result,'image'=>$image]);
    }
    public function actionVision()
    {
        $model=new AiForm(['scenario'=>'upload']); $record=null;
        if(Yii::$app->request->isPost) {
            $this->actor(); $model->image=UploadedFile::getInstance($model,'image');
            if($model->validate()) {
                $info=@getimagesize($model->image->tempName);
                if(!$info || $info[0]*$info[1]>40000000) { $model->addError('image','图片尺寸无效或过大。'); }
                else { $name=bin2hex(random_bytes(16)).'.'.$model->image->extension; $dir=Yii::getAlias('@app/storage/uploads'); if(!is_dir($dir)) { mkdir($dir,0775,true); }
                    if($model->image->saveAs($dir.'/'.$name)) { $record=new AiGeneration(['generation_type'=>'vision','prompt'=>'upload:'.$name,'response_text'=>'图片已接收；尚未运行视觉模型分析。','status'=>'success']); $record->user_id=$this->actor()->id; $record->save(false); }
                    else { $model->addError('image','图片保存失败。'); }
                }
            }
        }
        return $this->render('/site/vision',['model'=>$model,'record'=>$record]);
    }
    public function actionUpload(int $id)
    {
        $actor=$this->actor(); $record=AiGeneration::findOne(['id'=>$id,'generation_type'=>'vision']);
        if(!$record || ((int)$record->user_id!==(int)$actor->id && !$actor->isAdmin()) || !preg_match('/^upload:([a-f0-9]{32}\.(jpg|jpeg|png|webp))$/',$record->prompt,$m)) { throw new \yii\web\NotFoundHttpException(); }
        $path=Yii::getAlias('@app/storage/uploads/'.$m[1]); if(!is_file($path)) { throw new \yii\web\NotFoundHttpException(); }
        return Yii::$app->response->sendFile($path,null,['inline'=>true]);
    }
    private function limit(int $id): void { if(AiGeneration::find()->where(['user_id'=>$id])->andWhere(['>','created_at',date('Y-m-d H:i:s',time()-60)])->count()>=5) { throw new \yii\web\TooManyRequestsHttpException('请稍后再试。'); } }
    private function record(string $kind,string $prompt,?string $response,string $status='success',?string $image=null): void { $m=new AiGeneration(['generation_type'=>$kind,'prompt'=>$prompt,'response_text'=>$response,'status'=>$status,'image_url'=>$image]); $m->user_id=$this->actor()->id; $m->save(false); }
}
