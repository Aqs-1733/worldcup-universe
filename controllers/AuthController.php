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
        $model=new LoginForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->login()) { return $this->goBack('/profile'); }
        $model->password=''; return $this->render('login',['model'=>$model]);
    }
    public function actionRegister()
    {
        if (!Yii::$app->user->isGuest) { return $this->redirect('/profile'); }
        $model=new RegisterForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && ($user=$model->register())) { Yii::$app->user->login($user); return $this->redirect('/profile'); }
        $model->password=''; return $this->render('register',['model'=>$model]);
    }
    public function actionLogout() { $this->posted(); Yii::$app->user->logout(); return $this->goHome(); }
}
