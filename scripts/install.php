<?php
/**
 * 文件作用：兼容旧说明的安装入口，实际调用 Yii2 的 app/install 命令。
 */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
exit($app->runAction('app/install'));
