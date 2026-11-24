<?php
/**
 * 文件作用：后台视图：课程交付的新增/编辑表单。类型、所属成员用下拉选择，标题默认取上传文件名，存储位置由类型自动决定。
 * 作者：蒋林瀞_2413507
 */
use yii\helpers\Html;
use app\services\MediaUploadService;
$isNew=$model->isNewRecord;
$scope=$model->scope?:'personal';
$path=(string)$model->storage_path;
$fromRepo=$path!==''&&!str_contains($path,'/uploads/'); // 随 GitHub 仓库提交、部署时自动登记的文件
?>
<a href="/admin/<?= e($table) ?>">← <?= e($label) ?></a><h1><?= $isNew?'上传':'编辑' ?>课程作业</h1>
<form method="post" class="form-panel admin-form" enctype="multipart/form-data" data-coursework-form>
<?= csrf_field() ?><?= Html::errorSummary($model,['class'=>'form-errors']) ?>
<div class="form-grid">
 <label>作业类型<small>团队作业保存到 data/team，个人作业保存到 data/personal</small>
  <?php if($isNew): ?><select name="Record[scope]" data-scope>
   <option value="personal" <?= $scope==='personal'?'selected':'' ?>>个人作业</option>
   <option value="team" <?= $scope==='team'?'selected':'' ?>>团队作业</option>
  </select><?php else: ?><input value="<?= $scope==='team'?'团队作业':'个人作业' ?>" disabled><small>已保存的记录不能改类型；放错了请删除后重新上传。</small><?php endif ?>
 </label>
 <label data-member-field <?= $scope==='team'?'hidden':'' ?>>所属成员<small>个人作业按学号显示在团队页对应成员名下</small>
  <select name="member" data-member>
   <option value="">请选择成员</option>
   <?php foreach($members as $m): ?><option value="<?= e($m->student_no) ?>" data-name="<?= e($m->name) ?>" <?= str_contains((string)$model->title,(string)$m->student_no)?'selected':'' ?>><?= e($m->name) ?> · <?= e($m->student_no) ?></option><?php endforeach ?>
  </select>
 </label>
 <label class="form-wide">文件<small>支持 pdf、doc(x)、ppt(x)、zip、rar、7z、mp4 等；上限 <?= (int)(MediaUploadService::limitBytes('document')/1048576) ?> MB</small>
  <span class="media-upload">
   <?php if(!$isNew): ?><a class="media-current" href="/admin/coursework_files/file?id=<?= (int)$model->id ?>">下载当前文件 ↓</a><small>保存位置：<?= e($path) ?>（自动生成，不需要填写）</small><small><?= $fromRepo?'此文件随 GitHub 仓库提交，部署时自动登记；如需替换请更新仓库里的文件。':'选择新文件即可替换当前文件。' ?></small><?php endif ?>
   <?php if($isNew||!$fromRepo): ?><input type="file" name="upload[storage_path]" accept="<?= e(MediaUploadService::accept(['document'])) ?>" data-file><?php endif ?>
  </span>
 </label>
 <label class="form-wide">标题<small>选择文件后自动填入文件名，可以修改；个人作业建议写成“作业1(学号_姓名)”，没写学号时保存会自动补上</small>
  <input name="Record[title]" value="<?= e($model->title) ?>" maxlength="200" placeholder="例如：作业1(2412096_王麒萱)" data-title>
 </label>
 <label class="choice coursework-visible"><input type="hidden" name="Record[is_visible]" value="0"><input type="checkbox" name="Record[is_visible]" value="1" <?= ($isNew||$model->is_visible)?'checked':'' ?>> 在团队页（前台）显示并提供下载</label>
</div>
<button class="button"><?= $isNew?'上传并保存 →':'保存修改 →' ?></button>
</form>
<script>
// 小交互：切换类型时显示/隐藏“所属成员”；选择文件后自动把文件名填进标题（用户改过标题就不再覆盖）
(function(){
 var form=document.querySelector('[data-coursework-form]');if(!form)return;
 var scope=form.querySelector('[data-scope]'),member=form.querySelector('[data-member-field]'),file=form.querySelector('[data-file]'),title=form.querySelector('[data-title]');
 var touched=title.value!=='';
 title.addEventListener('input',function(){touched=title.value!=='';});
 if(scope)scope.addEventListener('change',function(){member.hidden=scope.value==='team';});
 if(file)file.addEventListener('change',function(){var f=file.files[0];if(f&&!touched){title.value=f.name.replace(/\.[^.]+$/,'');}});
})();
</script>
