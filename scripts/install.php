<?php
/** Backward-compatible CLI entry now delegates to the native Yii2 installer. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
exit($app->runAction('app/install'));
