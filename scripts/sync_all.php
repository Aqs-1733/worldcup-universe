<?php
/**
 * 文件作用：兼容旧说明的入口：同步官方赛事数据和新闻。
 * 作者：梁靖泽_2412852
 */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
if($app->runAction('fifa/import',[$argv[1]??ROOT_PATH.'/data/official/20261021'])!==0) {exit(1);}
exit($app->runAction('news/sync'));
