<?php
/** Native common controller; framework handles request, CSRF, session and views. */
declare(strict_types=1);
namespace app\controllers;
use Yii;
use app\models\User;
use yii\web\{Controller,UnauthorizedHttpException};
abstract class BaseController extends Controller
{
    public function beforeAction($action): bool
    {
        if($action->id!=='error') { foreach(Yii::$app->request->queryParams as $value) { if(is_array($value) || is_object($value)) { throw new \yii\web\BadRequestHttpException('查询参数格式错误。'); } } }
        Yii::$app->response->headers->set('X-Content-Type-Options','nosniff');
        Yii::$app->response->headers->set('Referrer-Policy','strict-origin-when-cross-origin');
        Yii::$app->response->headers->set('X-Frame-Options','SAMEORIGIN');
        return parent::beforeAction($action);
    }
    protected function actor(): User { if (Yii::$app->user->isGuest) { throw new UnauthorizedHttpException('请先登录。'); } return Yii::$app->user->identity; }
    protected function posted(): void { if (!Yii::$app->request->isPost) { throw new \yii\web\MethodNotAllowedHttpException('请使用 POST 请求。'); } }
}
