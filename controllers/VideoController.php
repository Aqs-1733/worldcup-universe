<?php
/** Full-match archive and an inline official player; years stay explicit. */
declare(strict_types=1);
namespace app\controllers;
use app\models\MatchVideo;
use yii\data\ActiveDataProvider;
use Yii;
final class VideoController extends BaseController
{
    public function actionIndex(?string $slug=null)
    {
        $q=MatchVideo::find()->where(['status'=>'published','video_type'=>'full_match','tournament_year'=>2026]);
        $year=Yii::$app->request->get('year');if($year!==null&&$year!=='') {if(!ctype_digit((string)$year)){throw new \yii\web\BadRequestHttpException();}$q->andWhere(['tournament_year'=>(int)$year]);}
        $q->orderBy(['tournament_year'=>SORT_DESC,'id'=>SORT_ASC]);
        $selected=$slug?MatchVideo::findOne(['slug'=>$slug,'status'=>'published','video_type'=>'full_match','tournament_year'=>2026]):(clone $q)->one();
        if($slug&&!$selected) {throw new \yii\web\NotFoundHttpException();}
        return $this->render('/catalog/videos',['provider'=>new ActiveDataProvider(['query'=>$q,'pagination'=>['pageSize'=>12],'sort'=>false]),'selected'=>$selected,'years'=>MatchVideo::find()->select('tournament_year')->where(['status'=>'published'])->distinct()->orderBy(['tournament_year'=>SORT_DESC])->column()]);
    }
}
