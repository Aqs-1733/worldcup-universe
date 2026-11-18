<?php
/** Domestic official iframe plus only present local files. */
$this->title='赛事影像';
$localSelected=isset($selected['provider']) && $selected['provider']==='local';
?>
<?= $this->render('_masthead',['heading'=>'赛事影像','photo'=>'metlife','photoLabel'=>'纽约／新泽西体育场','class'=>'watch-cover']) ?>
<header class="edition-page-intro"><div><h1>场边和看台的声音。</h1><p>央视网的 2026 世界杯报道直接显示在本站页面内；本地片段只在文件已部署时出现。</p></div></header>
<?php if($selected && !$localSelected): ?>
<section class="cctv-player" aria-labelledby="cctv-player-title">
 <div class="cctv-player-head"><div><span><?= e($selected['provider']) ?> · <?= e($selected['type']) ?></span><h2 id="cctv-player-title"><?= e($selected['title']) ?></h2><p><?= e($selected['subtitle']) ?></p></div><b>站内播放</b></div>
 <div class="cctv-frame"><iframe src="<?= e(safe_url($selected['embed_url'])) ?>" title="<?= e($selected['title']) ?>" loading="eager" referrerpolicy="strict-origin-when-cross-origin" allow="autoplay; fullscreen" allowfullscreen></iframe></div>
</section>
<?php elseif($selected && $localSelected): ?>
  <?= $this->render('_video-shelf',['library'=>$library,'clip'=>$selected]) ?>
<?php else: ?>
  <section class="edition-video-empty"><span aria-hidden="true">▷</span><h2>影像正在整理</h2><p>当前没有可在本站直接播放的文件。</p></section>
<?php endif ?>
<?php if($official): ?><nav class="cctv-video-list" aria-label="央视网影像选择"><?php foreach($official as $item): ?><a href="/watch?clip=<?= e($item['slug']) ?>"<?= !$localSelected && $selected && $item['slug']===$selected['slug']?' aria-current="page"':'' ?>><span><?= e($item['type']) ?></span><b><?= e($item['title']) ?></b><small><?= e($item['subtitle']) ?></small></a><?php endforeach ?></nav><?php endif ?>
<?php if($library && !$localSelected): ?><section class="local-shelf-section"><h2>本地影像</h2><?= $this->render('_video-shelf',['library'=>$library,'clip'=>$library[0]]) ?></section><?php endif ?>
