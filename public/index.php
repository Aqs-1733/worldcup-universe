<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$yiiBootstrap = dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
if (!is_file($vendorAutoload) || !is_file($yiiBootstrap)) {
    http_response_code(500);
    echo 'Yii2 vendor dependencies are missing. Run: D:\\XAMPP\\php\\php.exe -d extension=zip $env:TEMP\\composer.phar install';
    exit;
}

require $vendorAutoload;
require $yiiBootstrap;
require dirname(__DIR__) . '/app/bootstrap.php';
header('X-Backend: Yii2');

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config))->run();
