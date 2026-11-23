<?php
/**
 * 文件作用：比赛回放控制器：/watch 页面，嵌入央视网官方页面和本地视频。
 */
declare(strict_types=1);
namespace app\controllers;
use Yii;
final class VideoController extends BaseController
{
    public function actionIndex(?string $slug=null)
    {
        $official=is_file(ROOT_PATH.'/config/cctv-videos.php') ? require ROOT_PATH.'/config/cctv-videos.php' : [];
        $library=(new \app\services\LocalVideoLibrary())->all();
        $clipSlug=(string)Yii::$app->request->get('clip',$slug??'');
        $selected=null;
        foreach ($official as $item) { if ($item['slug']===$clipSlug || (!$clipSlug && $selected===null)) { $selected=$item; if ($clipSlug) { break; } } }
        if (!$selected && $clipSlug) { $selected=(new \app\services\LocalVideoLibrary())->find($clipSlug); }
        if ($clipSlug && !$selected) { throw new \yii\web\NotFoundHttpException('影像不存在或尚未部署。'); }
        if (!$selected && $library) { $selected=$library[0]; }
        return $this->render('/catalog/videos',['official'=>$official,'library'=>$library,'selected'=>$selected]);
    }

    /** Streams a local shelf file with HTTP Range support for seeking. */
    public function actionStream(string $name)
    {
        $path=\app\services\LocalVideoLibrary::safeFile($name);
        if (!$path) { throw new \yii\web\NotFoundHttpException('视频文件不存在。'); }
        $mime=['mp4'=>'video/mp4','m4v'=>'video/mp4','webm'=>'video/webm'][strtolower(pathinfo($path,PATHINFO_EXTENSION))];
        Yii::$app->response->headers->set('Accept-Ranges','bytes')->set('Cache-Control','private, max-age=3600');
        return Yii::$app->response->sendFile($path,basename($path),['mimeType'=>$mime,'inline'=>true]);
    }
}
