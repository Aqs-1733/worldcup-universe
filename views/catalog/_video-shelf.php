<?php
/** Personal-use cinema: local files play in a custom player; embeds load only after a click. */
if(!$library||!$clip)return;
$badge=['local'=>'本地','bilibili'=>'B站','youtube'=>'YouTube','missing'=>'待添加'];
$type=['highlights'=>'精彩集锦','full_match'=>'完整回放','moment'=>'高光时刻'];
?>
<section class="cinema" id="cinema" aria-labelledby="cinema-title">
 <h2 id="cinema-title" class="visually-hidden">影像馆</h2>
 <div class="cinema-body">
  <div class="cp" data-cinema data-provider="<?= e($clip['provider']) ?>" <?= $clip['embed']?'data-embed="'.e($clip['embed']).'"':'' ?>>
   <?php if($clip['provider']==='local'): ?>
    <video class="cp-video" src="<?= e($clip['src']) ?>" poster="<?= e($clip['poster']) ?>" preload="metadata" playsinline data-cp-video></video>
    <button type="button" class="cp-big" data-cp-toggle aria-label="播放"><span aria-hidden="true"></span></button>
    <div class="cp-bar" data-cp-bar>
     <button type="button" class="cp-btn" data-cp-toggle aria-label="播放/暂停"><span class="cp-icon-play" aria-hidden="true"></span></button>
     <span class="cp-time" data-cp-time>0:00 / 0:00</span>
     <input class="cp-seek" type="range" min="0" max="1000" value="0" step="1" data-cp-seek aria-label="播放进度">
     <button type="button" class="cp-btn" data-cp-mute aria-label="静音">🔊</button>
     <button type="button" class="cp-btn" data-cp-full aria-label="全屏">⛶</button>
    </div>
   <?php else: ?>
    <img class="cp-poster" src="<?= e($clip['poster']) ?>" alt="">
    <div class="cp-cover">
     <span class="cp-badge cp-badge-<?= e($clip['provider']) ?>"><?= e($badge[$clip['provider']]) ?></span>
     <h3><?= e($clip['title']) ?></h3><p><?= e($clip['subtitle']) ?></p>
     <?php if($clip['embed']): ?><button type="button" class="cp-big cp-big-inline" data-cp-embed aria-label="加载播放器"><span aria-hidden="true"></span></button>
     <?php elseif($clip['commons']): ?><p class="cp-missing">这是一段开放授权视频，运行 <code>php scripts/fetch-open-videos.php</code> 下载后即可在这里播放。</p><?php else: ?><p class="cp-missing">把视频文件放到 <code>public/assets/media/videos/<?= e($clip['file']?:'你的文件.mp4') ?></code>，刷新即可在这里播放。</p><?php endif ?>
    </div>
   <?php endif ?>
  </div>
  <ol class="cinema-list">
   <?php foreach($library as $item): ?><li><a class="cinema-item<?= $item['slug']===$clip['slug']?' is-active':'' ?>" href="/watch?clip=<?= e($item['slug']) ?>#cinema" <?= $item['slug']===$clip['slug']?'aria-current="true"':'' ?>><span class="cinema-thumb"><img src="<?= e($item['poster']) ?>" alt="" loading="lazy"><i aria-hidden="true">▶</i></span><span class="cinema-meta"><?php if($item['provider']!=='local'): ?><span class="cp-badge cp-badge-<?= e($item['provider']) ?>"><?= e($badge[$item['provider']]) ?></span><?php endif ?><b><?= e($item['title']) ?></b><?php if($item['subtitle']): ?><small><?= e($item['subtitle']) ?></small><?php endif ?></span></a></li><?php endforeach ?>
  </ol>
 </div>
 <div class="cinema-notes">
  <p class="cinema-status" data-cp-status role="status"><?php if($clip['provider']==='youtube'): ?>YouTube 嵌入需要能访问 YouTube 的网络；若播放器显示“此视频不能观看”，是版权方禁止站外嵌入，<a href="<?= e($clip['source_url']) ?>" target="_blank" rel="noopener noreferrer">在 YouTube 打开 ↗</a>，或改用本地文件。<?php elseif($clip['provider']==='bilibili'): ?><?php elseif($clip['provider']==='local'): ?><?php if($clip['credit']): ?>视频：<a href="<?= e($clip['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($clip['credit']) ?> ↗</a><?php endif ?><?php endif ?></p>
  <details class="cinema-help"><summary>添加视频</summary><ol><li>把 <code>.mp4</code> 文件放进 <code>public/assets/media/videos/</code>。文件名用英文、数字或短横线，例如 <code>semi-final-1.mp4</code>。</li><li>想换封面，就放一张同名的 <code>.jpg</code>（如 <code>semi-final-1.jpg</code>）。</li><li>刷新本页：新文件会自动出现在右侧列表。要改中文标题，在 <code>config/local-videos.php</code> 里加一条。</li><li>首页背景视频：命名为 <code>hero-loop.mp4</code>（建议 10–20 秒、无声、1080p 以内），首页第二幕会自动换成循环视频。</li><li>B 站视频：在配置里填 <code>'bilibili'=>'BV1xxxxxxxxx'</code>，没有本地文件时就用 B 站播放器。</li></ol></details>
 </div>
</section>
