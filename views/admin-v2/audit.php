<?php
/** Read-only audit trail; no edit/delete action is exposed. */
?>
<h1>操作审计</h1><p>日志记录操作与变更字段，不记录密码或外部 API 密钥。</p><div class="table-scroll"><table><thead><tr><th>时间</th><th>用户</th><th>操作</th><th>对象</th><th>详情</th></tr></thead><tbody><?php foreach($provider->models as $m): ?><tr><td><?= e($m->created_at) ?></td><td><?= e($m->user_id??'CLI') ?></td><td><?= e($m->action) ?></td><td><?= e($m->entity_type) ?> #<?= e($m->entity_id) ?></td><td><code><?= e($m->detail_json) ?></code></td></tr><?php endforeach ?></tbody></table></div><?= yii\widgets\LinkPager::widget(['pagination'=>$provider->pagination]) ?>
