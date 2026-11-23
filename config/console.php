<?php
/**
 * 文件作用：Yii2 控制台应用配置：安装、迁移、审计、同步等命令。
 */
declare(strict_types=1);
return [
    'id'=>'worldcup-console','basePath'=>dirname(__DIR__),'vendorPath'=>dirname(__DIR__).'/vendor',
    'runtimePath'=>dirname(__DIR__).'/storage/runtime','controllerNamespace'=>'app\\commands',
    'bootstrap'=>['log'],
    'components'=>['db'=>require __DIR__.'/db.php','cache'=>['class'=>yii\caching\FileCache::class],
        'log'=>['targets'=>[['class'=>yii\log\FileTarget::class,'levels'=>['error','warning'],'logVars'=>[],'logFile'=>dirname(__DIR__).'/storage/logs/console.log']]]],
    'controllerMap'=>['migrate'=>['class'=>yii\console\controllers\MigrateController::class,'migrationPath'=>'@app/migrations']],
];
