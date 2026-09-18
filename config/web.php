<?php

declare(strict_types=1);

return [
    'id' => 'worldcup-universe-yii2',
    'name' => app_name(),
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'runtimePath' => dirname(__DIR__) . '/storage/runtime',
    'controllerNamespace' => 'app\\controllers',
    'language' => 'zh-CN',
    'timeZone' => (string) env('APP_TIMEZONE', 'Asia/Shanghai'),
    'defaultRoute' => 'site/home',
    'components' => [
        'request' => [
            'cookieValidationKey' => (string) env('YII_COOKIE_VALIDATION_KEY', hash('sha256', app_name() . ROOT_PATH)),
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'charset' => 'UTF-8',
        ],
        'db' => require __DIR__ . '/db.php',
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/home',
                'teams' => 'site/teams',
                'teams/<id:[^/]+>' => 'site/team',
                'players' => 'site/players',
                'players/<id:[^/]+>' => 'site/player',
                'worldcup' => 'site/worldcup',
                'matches/<id:\\d+>' => 'site/match',
                'news' => 'site/news',
                'news/refresh' => 'site/refresh-news',
                'news/<id:\\d+>' => 'site/news-show',
                'countries' => 'site/countries',
                'search' => 'site/search',
                'fan-space' => 'site/fan-space',
                'onboarding' => 'site/onboarding',
                'commentary' => 'site/commentary',
                'vision' => 'site/vision',
                'api/news/refresh' => 'site/refresh-news-api',
                'login' => 'auth/login',
                'register' => 'auth/register',
                'logout' => 'auth/logout',
                'admin' => 'admin/dashboard',
                'admin/<table:[a-z_]+>' => 'admin/table',
                'admin/<table:[a-z_]+>/save' => 'admin/save',
                'admin/<table:[a-z_]+>/delete' => 'admin/delete',
            ],
        ],
        'log' => [
            'traceLevel' => (int) env('YII_TRACE_LEVEL', 0),
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => dirname(__DIR__) . '/storage/logs/yii2.log',
                ],
            ],
        ],
    ],
];
