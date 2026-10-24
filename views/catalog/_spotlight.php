<?php
/** Server-rendered player chapters, progressively enhanced with manual tab controls. */
$showcase=array_slice($players,0,3);$presentation=require ROOT_PATH.'/config/presentation.php';$instance=$instance??'featured';
?>
<?php if($showcase): ?><section class="spotlight edition-spotlight" data-player-spotlight aria-label="主推球员" style="--spotlight-duration:<?= (int)$presentation['motion']['spotlightDuration'] ?>ms">
 <header class="edition-spotlight-heading"><span>PLAYER FOCUS</span><span>不止一个名字，是一个时代。</span></header>
 <?php foreach($showcase as $i=>$p): ?>
 <article id="<?= e($instance) ?>-player-<?= $i ?>" class="spotlight-slide <?= $i===0?'is-active':'' ?>" data-player-panel role="tabpanel" aria-labelledby="<?= e($instance) ?>-tab-<?= $i ?>" <?= $i?'hidden':'' ?>>
  <span class="edition-player-watermark" aria-hidden="true"><?= e($p->team?->code) ?></span>
  <div class="spotlight-copy"><span class="edition-player-nationality"><?= \app\services\TeamUi::label($p->team) ?><span>/ <?= e(['GK'=>'守门员','DF'=>'后卫','MF'=>'中场','FW'=>'前锋'][$p->position]??$p->position) ?></span></span><h2><?php foreach(explode('·',$p->name,2) as $namePart): ?><span><?= e($namePart) ?></span><?php endforeach ?></h2><p class="edition-player-original"><?= e($p->name_original) ?></p><p class="edition-player-club"><?= e($p->club?:'俱乐部资料待补充') ?></p><dl class="edition-player-facts"><div><dt>国家队出场</dt><dd><?= e($p->caps??'—') ?></dd></div><div><dt>国家队进球</dt><dd><?= e($p->goals??'—') ?></dd></div><div><dt>球衣号码</dt><dd><?= e($p->shirt_number??'—') ?></dd></div></dl><a class="edition-player-link" href="/players/<?= e($p->slug) ?>">阅读球员档案 <span>↗</span></a><small class="edition-stat-note">数据来自已采集的官方阵容快照</small></div>
  <?php if($p->photo_url): ?><div class="edition-player-figure"><span class="edition-shirt-number" aria-hidden="true"><?= e($p->shirt_number) ?></span><img class="spotlight-photo" src="<?= e(safe_url($p->displayPhotoUrl)) ?>" alt="<?= e($p->name_original) ?> · FIFA 官方照片" <?= $i?'loading="lazy"':'fetchpriority="high"' ?> referrerpolicy="no-referrer"></div><?php endif ?>
 </article><?php endforeach ?>
 <div class="spotlight-tabs" role="tablist" aria-label="切换主推球员"><?php foreach($showcase as $i=>$p): ?><button type="button" id="<?= e($instance) ?>-tab-<?= $i ?>" role="tab" aria-selected="<?= $i===0?'true':'false' ?>" aria-controls="<?= e($instance) ?>-player-<?= $i ?>" tabindex="<?= $i===0?'0':'-1' ?>" data-player-tab><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span><span><?= e($p->name) ?><small><?= e($p->team?->name) ?></small></span><span aria-hidden="true">↗</span></button><?php endforeach ?></div>
</section><?php endif ?>
