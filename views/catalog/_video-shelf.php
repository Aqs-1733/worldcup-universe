<?php
/**
 * 文件作用：局部视图：视频列表与 HTML5 播放器。
 */
if(!$library||!$clip)return;
$type=['highlights'=>'精彩片段','full_match'=>'完整比赛','moment'=>'现场影像'];
?>
<section class="cinema" id="cinema" aria-labelledby="cinema-title">
 <h2 id="cinema-title" class="visually-hidden">赛事影像</h2>
 <div class="cinema-body">
  <div class="cp" data-cinema data-provider="local">
   <video class="cp-video" src="<?= e($clip['src']) ?>" poster="<?= e($clip['poster']) ?>" preload="metadata" playsinline data-cp-video></video>
   <button type="button" class="cp-big" data-cp-toggle aria-label="播放"><span aria-hidden="true"></span></button>
   <div class="cp-bar" data-cp-bar>
    <button type="button" class="cp-btn" data-cp-toggle aria-label="播放或暂停"><span class="cp-icon-play" aria-hidden="true"></span></button>
    <span class="cp-time" data-cp-time>0:00 / 0:00</span>
    <input class="cp-seek" type="range" min="0" max="1000" value="0" step="1" data-cp-seek aria-label="播放进度">
    <button type="button" class="cp-btn" data-cp-mute aria-label="静音">🔊</button>
    <button type="button" class="cp-btn" data-cp-full aria-label="全屏">⛶</button>
   </div>
  </div>
  <ol class="cinema-list">
   <?php foreach($library as $item): ?><li><a class="cinema-item<?= $item['slug']===$clip['slug']?' is-active':'' ?>" href="/watch?clip=<?= e($item['slug']) ?>#cinema" <?= $item['slug']===$clip['slug']?'aria-current="true"':'' ?>><span class="cinema-thumb"><img src="<?= e($item['poster']) ?>" alt="" loading="lazy"><i aria-hidden="true">▶</i></span><span class="cinema-meta"><span class="cp-badge cp-badge-local">本地播放</span><b><?= e($item['title']) ?></b><?php if($item['subtitle']): ?><small><?= e($item['subtitle']) ?></small><?php endif ?></span></a></li><?php endforeach ?>
  </ol>
 </div>
 <div class="cinema-notes"><p class="cinema-status" data-cp-status role="status"><?= e($type[$clip['type']]??'赛事影像') ?> · <?= e($clip['title']) ?><?php if($clip['credit']): ?> · 来源：<a href="<?= e(safe_url($clip['source_url'])) ?>" target="_blank" rel="noopener noreferrer"><?= e($clip['credit']) ?> ↗</a><?php endif ?></p></div>
</section>
