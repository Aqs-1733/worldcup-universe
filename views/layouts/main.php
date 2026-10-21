<?php
/** Shared responsive Yii2 frontend layout and semantic navigation. */
use yii\helpers\Html;
$current='/'.Yii::$app->request->pathInfo;
$nav=['/worldcup'=>'赛事','/teams'=>'球队','/players'=>'球员','/news'=>'新闻','/forum'=>'球迷论坛','/countries'=>'探索'];
if(str_starts_with($current,'/watch')||$current==='/stats'){$current='/worldcup';}
$user=Yii::$app->user->identity;
$asset=static fn(string $path):string=>e($path.'?v='.filemtime(ROOT_PATH.'/public'.$path));
?>
<?php $this->beginPage() ?>
<!doctype html><html lang="zh-CN"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?= Html::csrfMetaTags() ?><title><?= e(($this->title?:'世界杯宇宙').' · 世界杯宇宙') ?></title>
<meta name="description" content="世界杯宇宙：赛事、球队、球员、数据与属于球迷的主队看台。">
<link rel="stylesheet" href="<?= $asset('/assets/vendor/arena/css/vendor/bootstrap.min.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/css/v2-tokens.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/css/v2-site.css') ?>">
<link rel="stylesheet" href="<?= $asset('/assets/fonts/fonts.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/vendor/arena/css/arena-adapted.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/css/arena-integration.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/vendor/tabler/components.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/vendor/multiverse/gallery.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/css/matchday.css') ?>"><script src="<?= $asset('/assets/js/v3-cinematic.js') ?>" defer></script><script src="<?= $asset('/assets/js/team-picker.js') ?>" defer></script><script src="<?= $asset('/assets/js/player-interaction.js') ?>" defer></script><script src="<?= $asset('/assets/js/gallery.js') ?>" defer></script><script src="<?= $asset('/assets/js/group-accordion.js') ?>" defer></script><script src="<?= $asset('/assets/js/news-live.js') ?>" defer></script>
<link rel="stylesheet" href="<?= $asset('/assets/vendor/tabler/community.css') ?>"><link rel="stylesheet" href="<?= $asset('/assets/css/refinement.css') ?>"><script src="<?= $asset('/assets/js/bracket-tree.js') ?>" defer></script><script src="<?= $asset('/assets/js/country-directory.js') ?>" defer></script>
<script src="<?= $asset('/assets/js/v2-app.js') ?>" defer></script>
<?php if($current==='/countries'): ?><script src="<?= $asset('/assets/vendor/globe.gl-2.46.2.min.js') ?>" defer></script><script src="<?= $asset('/assets/js/globe-3d.js') ?>" defer></script><?php endif ?>
<?php $this->head() ?></head><body><?php $this->beginBody() ?>
<a class="skip-link" href="#main">跳到内容</a>
<div class="arena-topline"><span>2026 世界杯</span><span>加拿大 · 墨西哥 · 美国</span></div>
<header class="site-header" id="site-header"><div class="nav-inner shell"><a class="brand" href="/"><span class="wcu-crest" aria-hidden="true">W</span><span class="brand-name">世界杯<span class="brand-fc">宇宙</span><small>赛事 · 球队 · 球员 · 球迷论坛</small></span></a><button class="menu-button navbar-toggler" data-menu-toggle aria-expanded="false" aria-controls="navigation" aria-label="打开导航菜单">☰</button><nav id="navigation" class="navigation" aria-label="主导航"><ul class="nav-menu"><?php foreach($nav as $path=>$label): ?><li><a href="<?= e($path) ?>" <?= str_starts_with($current,$path)?'aria-current="page"':'' ?>><?= e($label) ?></a></li><?php endforeach ?></ul></nav><div class="account-nav"><?php if($user): ?><a href="/profile"><?= e($user->displayName) ?></a><?php if($user->isAdmin()): ?><a href="/admin">管理台</a><?php endif ?><form method="post" action="/logout"><?= csrf_field() ?><button class="text-button">退出</button></form><?php else: ?><a href="/login">登录</a><a class="btn btn-scarlet" href="/register">注册</a><?php endif ?></div></div></header>
<?php foreach(Yii::$app->session->getAllFlashes() as $kind=>$message): ?><div class="flash <?= e($kind) ?>" role="status"><?= e($message) ?></div><?php endforeach ?>
<main id="main" class="page-shell"><?= $content ?></main>
<footer class="site-footer" id="site-footer"><div class="footer-inner"><div><a class="footer-brand" href="/">世界杯<br><i>宇宙</i></a><p>世界杯赛事资料与球迷交流平台。</p></div><div><b>网站导航</b><a href="/worldcup">赛程与晋级</a><a href="/forum">球迷论坛</a><a href="/profile">选择我的主队</a><a href="/profile/notifications">回复通知</a></div><div><b>关于本站</b><a href="/#our-team">项目团队</a><a href="/deliverables">课程交付与下载</a><a href="/commentary">智能解说</a><a href="/vision">图像工作室</a><a href="/credits">素材与模板</a></div><div class="footer-bottom"><span>© <?= date('Y') ?> 世界杯宇宙 · 课程项目 / 非官方赛事网站</span><span>页面组件： <a href="https://uicookies.com/downloads/arena/" target="_blank" rel="noopener noreferrer">Arena / uiCookies</a> · <a href="/credits">Multiverse / Tabler / 摄影署名</a></span></div></div></footer>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
