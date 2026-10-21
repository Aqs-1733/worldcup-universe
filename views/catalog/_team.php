<?php
/** Most-capped listed player illustrates the team; no captain claim. */
$portrait=$model->featurePlayer;
?>
<a href="/teams/<?= e($model->slug) ?>" class="nation-card" data-media><span class="nation-code" aria-hidden="true"><?= e($model->code) ?></span><?php if($portrait?->photo_url): ?><img src="<?= e(safe_url($portrait->displayPhotoUrl)) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php endif ?><?php if($model->flag_url): ?><img class="nation-flag-fallback" src="<?= e(safe_url($model->flag_url)) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php endif ?><span class="nation-top"><?= e(['UEFA'=>'欧洲足联','CONMEBOL'=>'南美足联','CONCACAF'=>'中北美足联','CAF'=>'非洲足联','AFC'=>'亚洲足联','OFC'=>'大洋洲足联'][$model->confederation]??'国家队') ?> <span>小组 <?= e($model->group_name) ?></span></span><div class="nation-bottom"><h3><?= \app\services\TeamUi::label($model) ?><span>↗</span></h3><span class="meta">小组 <?= e($model->group_name) ?> · 26 人阵容</span></div></a>
