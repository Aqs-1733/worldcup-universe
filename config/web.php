<?php
/** Yii2 web composition root. Every public/admin business route is native MVC. */
declare(strict_types=1);

$runtime=dirname(__DIR__).'/storage/runtime';
foreach ([$runtime,$runtime.'/sessions',$runtime.'/cache'] as $dir) { if (!is_dir($dir)) { mkdir($dir,0775,true); } }
$key=(string)env('YII_COOKIE_VALIDATION_KEY','');
if (strlen($key)<32) {
    $keyFile=$runtime.'/cookie-key';
    if (!is_file($keyFile)) { file_put_contents($keyFile,bin2hex(random_bytes(32)),LOCK_EX); }
    $key=trim((string)file_get_contents($keyFile));
}
return [
    'id'=>'worldcup-universe-yii2','name'=>'WorldCup Universe','basePath'=>dirname(__DIR__),
    'vendorPath'=>dirname(__DIR__).'/vendor','runtimePath'=>$runtime,
    'controllerNamespace'=>'app\\controllers','language'=>'zh-CN','timeZone'=>'Asia/Shanghai',
    'defaultRoute'=>'site/index','bootstrap'=>['log'],
    'components'=>[
        'request'=>['cookieValidationKey'=>$key,'enableCsrfValidation'=>true,'parsers'=>['application/json'=>yii\web\JsonParser::class]],
        'response'=>['charset'=>'UTF-8'],
        'session'=>['name'=>'WCUSESSID','savePath'=>$runtime.'/sessions','cookieParams'=>['httponly'=>true,'samesite'=>'Lax']],
        'user'=>['identityClass'=>app\models\User::class,'enableAutoLogin'=>true,'loginUrl'=>['auth/login'],'identityCookie'=>['name'=>'_wcu_identity','httpOnly'=>true,'sameSite'=>'Lax']],
        'cache'=>['class'=>yii\caching\FileCache::class,'cachePath'=>$runtime.'/cache'],
        'db'=>require __DIR__.'/db.php',
        'errorHandler'=>['errorAction'=>'site/error'],
        'urlManager'=>['enablePrettyUrl'=>true,'showScriptName'=>false,'rules'=>[
            ''=>'site/index','teams'=>'team/index','teams/<id:[a-zA-Z0-9-]+>'=>'team/view',
            'players'=>'player/index','players/<id:[a-zA-Z0-9-]+>'=>'player/view',
            'worldcup'=>'match/index','worldcup/standings'=>'match/standings','stats'=>'match/stats','matches/<id:\d+>'=>'match/view',
            'news'=>'news/index','news/refresh'=>'news/refresh','news/status'=>'news/status','news/<id:\d+>'=>'news/view',
            'countries'=>'country/index','search'=>'site/search','credits'=>'site/credits',
            'watch'=>'video/index','watch/<slug:[a-z0-9-]+>'=>'video/index',
            'fan-space'=>'forum/index','forum'=>'forum/index','forum/new'=>'forum/create',
            'forum/<id:\d+>'=>'forum/view','forum/<id:\d+>/<action:edit|delete|reply|like|bookmark|report>'=>'forum/<action>',
            'forum/replies/<id:\d+>/delete'=>'forum/delete-reply',
            'forum/replies/<id:\d+>/edit'=>'forum/edit-reply',
            'profile'=>'profile/index','profile/notifications'=>'profile/notifications','onboarding'=>'profile/index',
            'login'=>'auth/login','register'=>'auth/register','logout'=>'auth/logout',
            'commentary'=>'ai/index','vision'=>'ai/vision',
            'deliverables'=>'site/deliverables','downloads/<id:\d+>'=>'site/download',
            'comments'=>'site/comment',
            'admin'=>'admin/index','admin/users'=>'admin/users','admin/users/<id:\d+>/status'=>'admin/user-status',
            'admin/moderate'=>'admin/moderate','admin/audit'=>'admin/audit',
            'admin/<table:[a-z_]+>'=>'admin/table',
            'admin/<table:[a-z_]+>/<action:create|edit|delete|save>'=>'admin/<action>',
            'api/v1/session'=>'api/session', 'api/v1/<action:teams|players|matches|standings|stats|news|countries|home|forum|bracket>'=>'api/<action>',
            'api/v1/teams/<id:[a-zA-Z0-9-]+>'=>'api/team','api/v1/players/<id:[a-zA-Z0-9-]+>'=>'api/player',
            'api/v1/matches/<id:\d+>'=>'api/match','api/v1/forum/<id:\d+>'=>'api/thread',
            'api/v1/profile'=>'api/profile',
        ]],
        'log'=>['targets'=>[['class'=>yii\log\FileTarget::class,'levels'=>['error','warning'],'logVars'=>[],'logFile'=>dirname(__DIR__).'/storage/logs/yii2.log']]],
    ],
];
