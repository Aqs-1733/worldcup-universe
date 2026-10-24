<?php
/** Official full replays and highlights stay separate; playback is owned by the publisher. */
declare(strict_types=1);
namespace app\controllers;
use app\models\MatchVideo;
use yii\data\ActiveDataProvider;
use Yii;
final class VideoController extends BaseController
{
 public function actionIndex(?string $slug=null)
 {
  $type=(string)Yii::$app->request->get('type','full_match');
  if(!in_array($type,['full_match','highlights'],true))throw new \yii\web\BadRequestHttpException('视频类型无效。');
  $selected=$slug?MatchVideo::findOne(['slug'=>$slug,'status'=>'published','tournament_year'=>2026]):null;
  if($slug&&!$selected)throw new \yii\web\NotFoundHttpException();
  if($selected)$type=$selected->video_type;
  $q=MatchVideo::find()->where(['status'=>'published','video_type'=>$type,'tournament_year'=>2026])->orderBy(['id'=>SORT_ASC]);
  $year=Yii::$app->request->get('year');
  if($year!==null&&$year!==''){if(!ctype_digit((string)$year))throw new \yii\web\BadRequestHttpException();$q->andWhere(['tournament_year'=>(int)$year]);}
  $selected??=(clone $q)->one();
  $library=(new \app\services\LocalVideoLibrary())->all();
  $clipSlug=(string)Yii::$app->request->get('clip','');
  $clip=null;foreach($library as $item){if($item['slug']===$clipSlug){$clip=$item;break;}}
  if(!$clip){foreach($library as $item){if($item['provider']==='local'){$clip=$item;break;}}}
  $clip??=$library[0]??null;
  return $this->render('/catalog/videos',['provider'=>new ActiveDataProvider(['query'=>$q,'pagination'=>['pageSize'=>12],'sort'=>false]),'selected'=>$selected,'type'=>$type,'years'=>[2026],'library'=>$library,'clip'=>$clip]);
 }
 /** Streams a file from the local video shelf with HTTP Range support so seeking works on the PHP dev server too. */
 public function actionStream(string $name)
 {
  $path=\app\services\LocalVideoLibrary::safeFile($name);
  if(!$path)throw new \yii\web\NotFoundHttpException('视频文件不存在。');
  $mime=['mp4'=>'video/mp4','m4v'=>'video/mp4','webm'=>'video/webm'][strtolower(pathinfo($path,PATHINFO_EXTENSION))];
  Yii::$app->response->headers->set('Accept-Ranges','bytes')->set('Cache-Control','private, max-age=3600');
  return Yii::$app->response->sendFile($path,basename($path),['mimeType'=>$mime,'inline'=>true]);
 }
}
