<?php
/** Topic detail, Tabler reply panel and native Yii2 member actions. */
use yii\helpers\Html;
use app\services\TeamUi;
$this->title=$model->displayTitle;
$user=Yii::$app->user->identity;
$canEdit=$user&&((int)$user->id===(int)$model->author_id||$user->isAdmin());
$locked=$model->is_locked||$model->status!=='published';
?>
<a class="back-link" href="/forum">← 球迷论坛</a>
<div class="forum-layout community-detail community-scope">
 <div class="topic-detail-main">
  <article class="thread-article">
   <div class="topic-labels">
    <a class="topic-board" href="/forum?category=<?= (int)$model->category_id ?>"><?= e($model->category?->name) ?></a>
    <?php if($model->team): ?><a href="/teams/<?= e($model->team->slug) ?>"><?= TeamUi::label($model->team) ?></a><?php endif ?>
    <?php if($model->is_locked): ?><span class="topic-pill">已锁定</span><?php endif ?>
    <?php if($model->status!=='published'): ?><span class="topic-pill"><?= e($model->status) ?></span><?php endif ?>
    <?php if($model->isDemo): ?><span class="topic-pill demo-pill">示例帖</span><?php endif ?>
   </div>
   <h1><?= e($model->displayTitle) ?></h1>
   <div class="topic-author"><span class="avatar" aria-hidden="true"><?= e(mb_substr($model->author?->displayName??'球迷',0,1)) ?></span><div><b><?= e($model->author?->displayName??'球迷') ?></b><time datetime="<?= e($model->created_at) ?>"><?= e(date('m月d日 H:i',strtotime($model->created_at))) ?></time></div><span class="topic-author-badge">楼主</span></div>
   <div class="thread-body"><?= nl2br(e($model->body)) ?></div>
   <?php if($model->match_id): ?><a class="topic-linked-match" href="/matches/<?= (int)$model->match_id ?>"><?= TeamUi::label($model->match?->homeTeam) ?><b><?= e($model->match?->home_score??'—') ?> : <?= e($model->match?->away_score??'—') ?></b><?= TeamUi::label($model->match?->awayTeam) ?><span>比赛详情 ↗</span></a><?php endif ?>
   <div class="thread-actions">
    <?php if($user): ?>
     <?php foreach(['like'=>[$liked,'♡ 点赞','♥ 已赞'],'bookmark'=>[$bookmarked,'＋ 收藏','✓ 已收藏']] as $kind=>[$active,$off,$on]): ?><form method="post" action="/forum/<?= (int)$model->id ?>/<?= e($kind) ?>"><?= csrf_field() ?><input type="hidden" name="enabled" value="<?= $active?'0':'1' ?>"><button class="button outline"><?= e($active?$on:$off) ?></button></form><?php endforeach ?>
    <?php else: ?><a href="/login">登录点赞 / 收藏</a><?php endif ?>
    <?php if($canEdit): ?><a href="/forum/<?= (int)$model->id ?>/edit">编辑主题</a><form method="post" action="/forum/<?= (int)$model->id ?>/delete" data-confirm="确定删除这个主题？"><?= csrf_field() ?><button class="text-button">删除主题</button></form><?php endif ?>
   </div>
  </article>
  <section class="card topic-replies-panel" aria-labelledby="topic-replies-heading">
   <header class="card-header"><h2 id="topic-replies-heading">回复 <span class="reply-total"><?= (int)$provider->totalCount ?></span></h2><?php if(!$locked): ?><a href="#compose">参与讨论 ↘</a><?php endif ?></header>
   <?php if($provider->models): ?>
    <div class="list-group list-group-flush topic-reply-list">
     <?php foreach($provider->models as $index=>$r): ?>
      <article class="list-group-item topic-reply" id="reply-<?= (int)$r->id ?>">
       <span class="avatar reply-avatar" aria-hidden="true"><?= e(mb_substr($r->user?->displayName??'球迷',0,1)) ?></span>
       <div class="reply-content">
        <div class="reply-byline"><b><?= e($r->user?->displayName??'球迷') ?></b><?php if((int)$r->user_id===(int)$model->author_id): ?><span class="topic-author-badge">楼主</span><?php endif ?><a class="reply-permalink" href="#reply-<?= (int)$r->id ?>" aria-label="第 <?= (int)$provider->pagination->offset+$index+1 ?> 条回复">#<?= (int)$provider->pagination->offset+$index+1 ?></a></div>
        <?php if($r->parent_id): ?><a class="reply-quote-ref" href="#reply-<?= (int)$r->parent_id ?>">↳ 引用回复 #<?= (int)$r->parent_id ?></a><?php endif ?>
        <p class="reply-text"><?= nl2br(e($r->body)) ?></p>
        <div class="reply-bottom"><time datetime="<?= e($r->created_at) ?>"><?= e(date('m月d日 H:i',strtotime($r->created_at))) ?></time>
         <?php if($user): ?><div class="reply-tools">
          <?php if(!$locked): ?><a href="#compose" data-quote="<?= (int)$r->id ?>" data-quote-name="<?= e($r->user?->displayName??'球迷') ?>">回复</a><?php endif ?>
          <?php if((int)$user->id===(int)$r->user_id||$user->isAdmin()): ?><details class="reply-edit"><summary>编辑</summary><form action="/forum/replies/<?= (int)$r->id ?>/edit" method="post"><?= csrf_field() ?><label for="reply-edit-<?= (int)$r->id ?>">修改回复</label><textarea id="reply-edit-<?= (int)$r->id ?>" name="body" required minlength="2" maxlength="10000" rows="3"><?= e($r->body) ?></textarea><button class="button small">保存修改</button></form></details><form action="/forum/replies/<?= (int)$r->id ?>/delete" method="post" data-confirm="删除这条回复？"><?= csrf_field() ?><button class="text-button">删除</button></form><?php endif ?>
          <details class="reply-report"><summary>举报</summary><form action="/forum/<?= (int)$model->id ?>/report" method="post"><?= csrf_field() ?><input type="hidden" name="reply_id" value="<?= (int)$r->id ?>"><label for="reply-report-<?= (int)$r->id ?>">举报原因</label><input id="reply-report-<?= (int)$r->id ?>" name="reason" required minlength="3" maxlength="500"><button class="button small">提交</button></form></details>
         </div><?php endif ?>
        </div>
       </div>
      </article>
     <?php endforeach ?>
    </div>
    <?= yii\widgets\LinkPager::widget(['pagination'=>$provider->pagination]) ?>
   <?php else: ?>
    <div class="topic-reply-empty"><span class="reply-empty-art" aria-hidden="true"><svg viewBox="0 0 80 60" fill="none"><rect x="8" y="9" width="45" height="33" rx="9" fill="currentColor" opacity=".1"/><path d="M46 26h17a9 9 0 0 1 9 9v8a9 9 0 0 1-9 9h-3l-6 5v-5h-7a9 9 0 0 1-9-9v-8a9 9 0 0 1 8-9Z" fill="currentColor" opacity=".15"/><path d="M17 9h27a9 9 0 0 1 9 9v15a9 9 0 0 1-9 9H29l-9 7v-7h-3a9 9 0 0 1-9-9V18a9 9 0 0 1 9-9Z" stroke="currentColor" stroke-width="1.5"/><circle cx="22" cy="25" r="2" fill="currentColor"/><circle cx="31" cy="25" r="2" fill="currentColor"/><circle cx="40" cy="25" r="2" fill="currentColor"/></svg></span><p>还没有回复</p></div>
   <?php endif ?>
   <div class="card-footer topic-compose" id="compose">
    <?php if($locked): ?><div class="topic-compose-locked"><span aria-hidden="true">▣</span> 讨论已锁定</div>
    <?php elseif(!$user): ?><div class="topic-compose-guest"><span class="avatar" aria-hidden="true">＋</span><span>参与讨论</span><a href="/login" class="button">登录后回复 ↗</a></div>
    <?php else: ?><form method="post" action="/forum/<?= (int)$model->id ?>/reply" class="topic-reply-form">
     <?= csrf_field() ?><?= Html::errorSummary($replyModel,['class'=>'form-errors']) ?>
     <input type="hidden" name="parent_id" value="<?= e($replyModel->parent_id) ?>" data-parent-input>
     <div class="reply-quote-chip" data-quote-chip <?= $replyModel->parent_id?'':'hidden' ?>><span data-quote-caption>引用回复 #<?= (int)$replyModel->parent_id ?></span><button type="button" class="text-button" data-quote-cancel aria-label="取消引用">×</button></div>
     <div class="topic-composer-row"><span class="avatar" aria-hidden="true"><?= e(mb_substr($user->displayName,0,1)) ?></span><div><label for="topic-reply-body">写回复</label><textarea id="topic-reply-body" name="body" rows="4" minlength="2" maxlength="10000" placeholder="写下你的看法……" required><?= e($replyModel->body) ?></textarea></div></div>
     <div class="topic-compose-actions"><button class="button" type="submit">发布回复 ↗</button></div>
    </form><?php endif ?>
   </div>
  </section>
 </div>
 <aside class="forum-sidebar topic-detail-aside"><h3>主题操作</h3><a href="/forum/new" class="button">发起讨论 ↗</a><a class="topic-back-board" href="/forum?category=<?= (int)$model->category_id ?>">返回<?= e($model->category?->name) ?> →</a><?php if($user): ?><details class="report-panel"><summary>举报此主题</summary><form method="post" action="/forum/<?= (int)$model->id ?>/report"><?= csrf_field() ?><label>举报原因<textarea name="reason" required minlength="3" maxlength="500" rows="3"></textarea></label><button class="button small">提交举报</button></form></details><?php endif ?></aside>
</div>
