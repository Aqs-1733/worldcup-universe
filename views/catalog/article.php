<?php
/**
 * 文件作用：视图：新闻详情页，内容全部转义输出。
 * 作者：梁靖泽_2412852
 */
$this->title=$model->title;
?>
<a class="back-link" href="/news">← 新闻中心</a><article class="article prose"><span class="eyebrow"><?= e($model->source_name) ?> / <?= e($model->published_at?:'时间待确认') ?></span><div class="story-teams"><?php foreach($model->relatedTeams as $team): ?><a href="/teams/<?= e($team->slug) ?>"><?= \app\services\TeamUi::label($team) ?></a><?php endforeach ?></div><h1><?= e($model->title) ?></h1><p class="lead"><?= e($model->summary_cn?:$model->summary_original) ?></p><?php $body=$model->content_cn?:$model->content_original;if($body&&$body!==($model->summary_cn?:$model->summary_original)): ?><div class="article-body"><?= nl2br(e($body)) ?></div><?php endif ?><p><a href="<?= e(safe_url($model->source_url)) ?>" rel="noreferrer noopener" target="_blank">阅读原始来源 ↗</a></p></article>
<?= $this->render('_comments',['items'=>$comments,'type'=>'news','id'=>$model->id]) ?>
