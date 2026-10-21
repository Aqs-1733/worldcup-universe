<?php
/** Legacy command URL retained, but sports ingestion now exclusively uses official FIFA evidence. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
$folder=$argv[1]??ROOT_PATH.'/data/official/20261021';
exit($app->runAction('fifa/import',[$folder]));
