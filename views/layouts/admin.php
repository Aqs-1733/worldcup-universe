<?php
/** Separate administrative layout; all pages remain protected by AccessControl. */
use yii\helpers\Html;
$registry=require Yii::getAlias('@app/config/v2-tables.php');
?>
<?php $this->beginPage() ?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?= Html::csrfMetaTags() ?><title>WorldCup Universe 管理台</title><link rel="stylesheet" href="/assets/css/v2-tokens.css"><link rel="stylesheet" href="/assets/css/v2-site.css"><link rel="stylesheet" href="/assets/css/light-theme.css?v=<?= filemtime(ROOT_PATH.'/public/assets/css/light-theme.css') ?>"><script src="/assets/js/v2-app.js" defer></script><?php $this->head() ?></head><body class="admin-body"><?php $this->beginBody() ?>
<aside class="admin-sidebar"><a class="brand" href="/admin">W/26 管理台</a><a href="/">↗ 返回前台</a><a href="/admin">概览与审核</a><a href="/admin/users">用户状态</a><a href="/admin/audit">操作审计</a><details open><summary>内容与赛事</summary><?php foreach($registry as $key=>[$label,$class]): ?><a href="/admin/<?= e($key) ?>"><?= e($label) ?></a><?php endforeach ?></details></aside>
<main class="admin-main"><div class="admin-top"><b>WorldCup Universe / 数据管理</b><span><?= e(Yii::$app->user->identity->displayName) ?></span><form action="/logout" method="post"><?= csrf_field() ?><button>退出</button></form></div>
<?php foreach(Yii::$app->session->getAllFlashes() as $kind=>$message): ?><div class="flash <?= e($kind) ?>" role="status"><?= e($message) ?></div><?php endforeach ?><?= $content ?></main>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
