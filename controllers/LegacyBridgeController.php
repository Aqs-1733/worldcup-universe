<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use App\Core\View;

abstract class LegacyBridgeController extends Controller
{
    public $enableCsrfValidation = false;
    public $layout = false;

    protected function legacy(object $controller, string $method, mixed ...$params): string
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        $content = $controller->{$method}(...$params);
        $status = http_response_code();
        if (is_int($status) && $status >= 400) {
            Yii::$app->response->statusCode = $status;
        }
        return (string) $content;
    }

    public function actionError(): string
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception && property_exists($exception, 'statusCode')) {
            Yii::$app->response->statusCode = (int) $exception->statusCode;
        }
        if ($exception && (int) Yii::$app->response->statusCode >= 500) {
            return View::render('errors/runtime', ['title' => '运行环境未就绪', 'error' => $exception]);
        }
        Yii::$app->response->statusCode = 404;
        return View::render('errors/404', ['title' => '页面不存在']);
    }
}
