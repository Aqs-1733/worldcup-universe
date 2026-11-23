<?php
/**
 * 文件作用：视图：球队列表页，支持按名称搜索和按足联筛选。
 * 作者：崔迪_2412439
 */
$this->title='国家队';
?>
<?= $this->render('_masthead',['heading'=>'参赛球队','photo'=>'azteca','photoLabel'=>'阿兹特克体育场','number'=>'48','class'=>'teams-cover']) ?>
<?php /* 筛选表单：按球队名称和所属足联，GET 提交 */ ?>
<form class="filter-bar" method="get"><label>球队名称<input name="q" value="<?= e(Yii::$app->request->get('q','')) ?>" placeholder="中文名或原名"></label><label>足联<select name="confederation"><option value="">全部足联</option><?php foreach(['UEFA','CONMEBOL','CONCACAF','CAF','AFC','OFC'] as $c): ?><option value="<?= e($c) ?>" <?= Yii::$app->request->get('confederation')===$c?'selected':'' ?>><?= e(['UEFA'=>'欧洲足联','CONMEBOL'=>'南美足联','CONCACAF'=>'中北美及加勒比足联','CAF'=>'非洲足联','AFC'=>'亚洲足联','OFC'=>'大洋洲足联'][$c]) ?></option><?php endforeach ?></select></label><button class="button">筛选 →</button></form>
<?php /* 球队卡片列表 + 分页 */ ?>
<p class="meta"><?= (int)$provider->totalCount ?> 支参赛队伍</p><div class="nations-grid edition-teams-directory"><?php foreach($provider->models as $t): ?><?= $this->render('_team',['model'=>$t]) ?><?php endforeach ?></div><?php if(!$provider->models): ?><div class="empty">没有符合筛选的球队。</div><?php endif ?><?= yii\widgets\LinkPager::widget(['pagination'=>$provider->pagination]) ?>
