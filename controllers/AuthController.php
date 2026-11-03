<?php
/** Native Yii2 authentication controller and validated form models. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{LoginForm,RegisterForm};
use Yii;
final class AuthController extends BaseController
{
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) { return $this->redirect('/profile'); }
        $model=new LoginForm(); $insecure=$this->insecure($model,'password');
        if (!$insecure && Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->login()) { return $this->goBack('/profile'); }
        $model->password=''; return $this->render('login',['model'=>$model,'insecure'=>$insecure]);
    }
    public function actionRegister()
    {
        if (!Yii::$app->user->isGuest) { return $this->redirect('/profile'); }
        $model=new RegisterForm(); $insecure=$this->insecure($model,'password');
        if (!$insecure && Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && ($user=$model->register())) { Yii::$app->user->login($user); return $this->redirect('/profile'); }
        $model->password=''; return $this->render('register',['model'=>$model,'insecure'=>$insecure]);
    }
    /**
     * Production never accepts passwords over plain HTTP from the network (first deploy before the certificate,
     * or a stray http:// link). Loopback (an SSH tunnel) still works; ALLOW_INSECURE_LOGIN=1 is an explicit override.
     */
    private function insecure(\yii\base\Model $model,string $field): bool
    {
        $request=Yii::$app->request;
        if (env('APP_ENV')!=='production' || $request->isSecureConnection || in_array($request->userIP,['127.0.0.1','::1'],true) || (string)env('ALLOW_INSECURE_LOGIN','')==='1') { return false; }
        $model->addError($field,'当前连接未加密（HTTP），已停止接收密码。请改用 https:// 地址访问本站后再登录或注册。');
        if ($request->isPost) { Yii::$app->response->statusCode=403; }
        return true;
    }
    public function actionLogout() { $this->posted(); Yii::$app->user->logout(); return $this->goHome(); }
}
