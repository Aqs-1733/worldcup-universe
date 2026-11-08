<?php
/** Separate administrative layout; all pages remain protected by AccessControl. */
use yii\helpers\Html;
$registry=require Yii::getAlias('@app/config/v2-tables.php');
$nav=require Yii::getAlias('@app/config/admin-nav.php');
// Current section from the URL: admin, admin/users, admin/audit, admin/<table>[/create|/edit].
$parts=explode('/',trim(Yii::$app->request->pathInfo,'/'));
$active=($parts[0]??'')==='admin'?($parts[1]??''):'';
$overview=[''=>'概览与审核','users'=>'用户状态','audit'=>'操作审计'];
$counts=[];foreach($registry as $key=>[$label,$class]){$counts[$key]=(int)$class::find()->count();}
// Tables missing from the groups still get a place, so the whitelist and the menu never drift apart.
$grouped=array_merge(...array_map(static fn($g)=>$g['tables'],$nav['groups']));
$rest=array_values(array_diff(array_keys($registry),$grouped));
if($rest){$nav['groups'][]=['label'=>'其他','tables'=>$rest];}
$crumbGroup='总览';$crumbLabel=$overview[$active]??null;
foreach($nav['groups'] as $g){if(in_array($active,$g['tables'],true)){$crumbGroup=$g['label'];$crumbLabel=$registry[$active][0]??$active;}}
$sub=$parts[2]??'';if($crumbLabel&&$sub==='create'){$crumbLabel.=' · 新增';}elseif($crumbLabel&&$sub==='edit'){$crumbLabel.=' · 编辑';}
$link=static function(string $key,string $label,?int $count) use($active): string{
 $href=$key===''?'/admin':'/admin/'.$key;$on=$key===$active;
 return '<a href="'.e($href).'"'.($on?' class="is-active" aria-current="page"':'').'><span>'.e($label).'</span>'.($count!==null?'<small'.($count===0?' class="is-zero"':'').'>'.$count.'</small>':'').'</a>';
};
$css=static fn(string $f)=>'/assets/css/'.$f.'?v='.filemtime(ROOT_PATH.'/public/assets/css/'.$f);
?>
<?php $this->beginPage() ?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?= Html::csrfMetaTags() ?><title><?= e(($crumbLabel?$crumbLabel.' · ':'').'WorldCup Universe 管理台') ?></title><link rel="stylesheet" href="/assets/css/v2-tokens.css"><link rel="stylesheet" href="/assets/css/v2-site.css"><link rel="stylesheet" href="<?= $css('light-theme.css') ?>"><link rel="stylesheet" href="<?= $css('admin-media.css') ?>"><link rel="stylesheet" href="<?= $css('admin-nav.css') ?>"><script src="/assets/js/v2-app.js" defer></script><?php $this->head() ?></head><body class="admin-body"><?php $this->beginBody() ?>
<aside class="admin-sidebar" data-admin-nav aria-label="后台导航">
 <a class="brand" href="/admin">W/26 管理台</a>
 <a class="admin-back" href="/">↗ 返回前台</a>
 <div class="admin-nav-group is-static"><span class="admin-nav-title">总览</span><?php foreach($overview as $key=>$label): ?><?= $link($key,$label,null) ?><?php endforeach ?></div>
 <?php foreach($nav['groups'] as $g): $reserved=!empty($g['reserved']);$has=in_array($active,$g['tables'],true); ?>
 <details class="admin-nav-group<?= $reserved?' is-reserved':'' ?>"<?= !$reserved||$has?' open':'' ?>>
  <summary class="admin-nav-title"><?= e($g['label']) ?></summary>
  <?php if($reserved): ?><p class="admin-nav-note">课程数据库设计中的预留表，目前没有导入程序，前台也不读取；可手动维护。</p><?php endif ?>
  <?php foreach($g['tables'] as $key): if(!isset($registry[$key]))continue; ?><?= $link($key,$registry[$key][0],$counts[$key]) ?><?php endforeach ?>
 </details>
 <?php endforeach ?>
</aside>
<main class="admin-main"><div class="admin-top"><nav class="admin-crumb" aria-label="当前位置"><a href="/admin">管理台</a><span><?= e($crumbGroup) ?></span><?php if($crumbLabel): ?><b><?= e($crumbLabel) ?></b><?php endif ?></nav><span class="admin-user"><?= e(Yii::$app->user->identity->displayName) ?></span><form action="/logout" method="post"><?= csrf_field() ?><button>退出</button></form></div>
<?php foreach(Yii::$app->session->getAllFlashes() as $kind=>$message): ?><div class="flash <?= e($kind) ?>" role="status"><?= e($message) ?></div><?php endforeach ?><?= $content ?></main>
<script>
/* Keep the sidebar where it was between pages, and bring the current item into view if it is hidden. */
(()=>{const s=document.querySelector('[data-admin-nav]');if(!s)return;const k='wcu-admin-nav-top';
 try{const v=sessionStorage.getItem(k);if(v!==null)s.scrollTop=+v;}catch(e){}
 const a=s.querySelector('[aria-current=page]');
 if(a&&s.scrollHeight>s.clientHeight){const r=a.getBoundingClientRect(),b=s.getBoundingClientRect();if(r.top<b.top+60||r.bottom>b.bottom-20)s.scrollTop+=r.top-b.top-(b.height-r.height)/2;}
 addEventListener('pagehide',()=>{try{sessionStorage.setItem(k,String(s.scrollTop));}catch(e){}});
})();
</script>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
