<?php
/** Deferred official iframe. The page never claims a cross-origin video has played. */
$this->title='比赛影像';$full=$type==='full_match';
$playback=require ROOT_PATH.'/config/video-playback.php';
$access=$selected?($playback[$selected->provider.':'.$selected->provider_id]??[]):[];
$external=$access['external']??false;
$alternative=$selected&&$full?\app\models\MatchVideo::find()->where(['status'=>'published','video_type'=>'highlights','tournament_year'=>$selected->tournament_year,'home_code'=>$selected->home_code,'away_code'=>$selected->away_code,'stage'=>$selected->stage])->andWhere(['not',['verified_at'=>null]])->one():null;
?>
<header class="edition-page-intro"><div><h1>让精彩，再发生一次。</h1></div></header>
<?= $this->render('_competition-nav',['active'=>'videos']) ?>
<?= $this->render('_video-shelf',['library'=>$library??[],'clip'=>$clip??null]) ?>
<details class="official-replays"<?= str_starts_with(Yii::$app->request->pathInfo,'watch/')||Yii::$app->request->get('type')?' open':'' ?>><summary>官方完整回放 · RTVE</summary>
<nav class="edition-video-tabs" aria-label="视频类型"><a href="/watch?type=full_match" <?= $full?'aria-current="page"':'' ?>>完整回放<span>FULL MATCH</span></a><a href="/watch?type=highlights" <?= !$full?'aria-current="page"':'' ?>>精彩集锦<span>HIGHLIGHTS</span></a></nav>
<?php if($selected): ?>
<section class="edition-cinema" data-official-player data-embed="<?= e($selected->embedUrl) ?>" data-provider="<?= e($selected->provider) ?>">
 <div class="edition-cinema-poster" data-video-screen>
  <?php if($selected->thumbnail_url): ?><img src="<?= e(safe_url($selected->thumbnail_url)) ?>" alt="" referrerpolicy="no-referrer"><?php endif ?>
  <div class="edition-video-cover"><span class="edition-kicker"><?= e(strtoupper($selected->source_author)) ?> / <?= $full?'完整比赛':'精彩集锦' ?></span><h2><?= e($selected->home_name) ?><span>对</span><?= e($selected->away_name) ?></h2><p><?= e($selected->stage) ?> · <?= (int)$selected->tournament_year ?> 年世界杯</p><?php if($external): ?><a class="edition-primary" href="<?= e(safe_url($selected->source_url)) ?>" target="_blank" rel="noopener noreferrer">在 FIFA 官方频道播放 ↗</a><?php else: ?><button type="button" data-video-load><span aria-hidden="true">▶</span>加载官方播放器</button><?php endif ?></div>
 </div>
 <div class="edition-video-access"><span class="edition-video-provider"><?= e($selected->source_author) ?> 官方来源</span><span><?= $selected->provider==='rtve'?'可能需要官方账号登录，且受所在地区播放权限制。':'播放权限与可用地区由官方频道决定。' ?></span><a href="<?= e(safe_url($selected->source_url)) ?>" target="_blank" rel="noopener noreferrer">前往官方观看 ↗</a></div>
 <p class="edition-video-status" data-video-status role="status"><?= e($access['notice']??'点击加载后，播放由官方播放器提供。本站不托管视频。') ?></p>
 <?php if($alternative): ?><p><a class="edition-inline-link" href="/watch/<?= e($alternative->slug) ?>">完整回放受限？观看同场 FIFA 官方集锦 ↗</a></p><?php endif ?>
 <details class="edition-video-help" data-video-help><summary>无法播放？查看可用方式</summary><div><p>若播放器提示地区限制、需要登录或视频不可用，请在官方页面确认访问权限。本站无法解除官方播放限制。</p><?php if(!$external): ?><button type="button" data-video-retry>重新加载播放器</button><?php endif ?><a href="<?= e(safe_url($selected->source_url)) ?>" target="_blank" rel="noopener noreferrer">打开官方页面 ↗</a></div></details>
 <div class="edition-video-source"><span>已核验：<?= e($selected->source_author) ?> · <?= e($selected->source_title) ?></span><span>核验时间 <?= e($selected->verified_at) ?> · 元数据核验不代表当前地区可播放</span></div>
</section>
<?php else: ?><section class="edition-video-empty"><span aria-hidden="true">▷</span><h2><?= $full?'暂未收录官方完整回放':'精彩集锦，等待官方收录。' ?></h2><p>当前数据库没有经核验、可发布的<?= $full?'完整回放':'官方精彩集锦' ?>。你可以先回看完整比赛，或查看比赛档案。</p><a class="edition-inline-link" href="<?= $full?'/worldcup':'/watch?type=full_match' ?>"><?= $full?'查看比赛档案':'查看完整回放' ?> ↗</a></section><?php endif ?>
<?php if(count($provider->models)>1): ?><section class="section"><div class="section-head"><h2><?= $full?'完整回放目录':'精彩集锦目录' ?></h2></div><div class="video-grid"><?php foreach($provider->models as $video): ?><a class="video-card" href="/watch/<?= e($video->slug) ?>"><div class="video-thumbnail"><?php if($video->thumbnail_url): ?><img src="<?= e(safe_url($video->thumbnail_url)) ?>" alt="" loading="lazy"><?php endif ?><span class="play-mark">▶</span></div><h3><?= e($video->title) ?></h3></a><?php endforeach ?></div><?= yii\widgets\LinkPager::widget(['pagination'=>$provider->pagination]) ?></section><?php endif ?>
</details>
