<?php
/** Notifications are selected and marked read only for the current member. */
$this->title='回复通知';
?>
<section class="page-heading"><h1>站内通知</h1></section><a href="/profile">← 个人中心</a><form method="post" class="section"><?= csrf_field() ?><button class="button">全部标记已读</button></form><?php foreach($items as $item): ?><article class="reply"><span class="badge"><?= $item->read_at?'已读':'未读' ?></span><time class="meta"><?= e($item->created_at) ?></time><?php if($item->thread&&$item->thread->status==='published'): ?><p>你参与的讨论有了新回复：<a href="/forum/<?= (int)$item->thread_id ?>"><?= e($item->thread->title) ?> ↗</a></p><?php else: ?><p>相关讨论当前未公开。</p><?php endif ?></article><?php endforeach ?><?php if(!$items): ?><div class="empty">暂时没有新消息。</div><?php endif ?>
