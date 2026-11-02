<?php
/** Model-schema form: safe attributes, enum choices, browser uploads with preview, and validation feedback. */
use yii\helpers\Html;
use app\services\MediaUploadService;
$schema=$model::getTableSchema();
$media=$media??[];
$preview=static function(?string $url): string {
 $src=safe_url($url); if(!$url||$src==='#') { return ''; }
 if(MediaUploadService::isVideo($src)) { return '<video class="media-preview" src="'.e($src).'" controls preload="metadata"></video>'; }
 return '<a href="'.e($src).'" target="_blank" rel="noopener"><img class="media-preview" src="'.e($src).'" alt="当前文件预览" loading="lazy" referrerpolicy="no-referrer"></a>';
};
$upload=static function(string $field,?string $current) use($media,$model,$preview): string {
 if(!isset($media[$field])) { return ''; }
 $coursework=$media[$field]===['coursework']; $kinds=$coursework?['document']:$media[$field];
 $limits=implode(' / ',array_map(static fn($k)=>$k.' '.(MediaUploadService::limitBytes($k)/1048576).' MB',$kinds));
 $html='<span class="media-upload">';
 if($current) { $html.=$coursework?($model->isNewRecord?'':'<a class="media-current" href="/admin/coursework_files/file?id='.(int)$model->id.'">下载当前文件 ↓</a>'):$preview($current); }
 $html.='<span class="media-preview-new" data-media-preview hidden></span>';
 $html.='<input type="file" name="upload['.e($field).']" accept="'.e(MediaUploadService::accept($kinds)).'" data-media-input>';
 return $html.'<small>'.($current?'选择新文件即可替换当前文件；':'').'上传优先于手填地址。上限：'.e($limits).'</small></span>';
};
?>
<a href="/admin/<?= e($table) ?>">← <?= e($label) ?></a><h1><?= $model->isNewRecord?'新增':'编辑' ?><?= e($label) ?></h1><form method="post" class="form-panel admin-form"<?= $media?' enctype="multipart/form-data"':'' ?>><?= csrf_field() ?><?= Html::errorSummary($model,['class'=>'form-errors']) ?><div class="form-grid"><?php foreach($model->safeAttributes() as $field): ?><?php $column=$schema->columns[$field]??null; if(!$column) {continue;} $value=$model->$field; ?><label><?= e($model->getAttributeLabel($field)) ?><small><?= e($column->comment?:$field) ?><?= $column->allowNull?' · 可空':'' ?></small><?php if($column->enumValues): ?><select name="Record[<?= e($field) ?>]"><?php foreach($column->enumValues as $v): ?><option <?= $value===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select><?php elseif(in_array($field,['status'],true)&&$table==='news_articles'): ?><select name="Record[status]"><?php foreach(['draft','published','hidden'] as $v): ?><option <?= $value===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select><?php elseif(in_array($column->type,['text','json'],true)||str_contains($field,'body')||($column->size??0)>1000): ?><textarea name="Record[<?= e($field) ?>]" rows="5"><?= e(is_string($value)?$value:($value===null?'':json_encode($value,JSON_UNESCAPED_UNICODE))) ?></textarea><?php else: ?><input name="Record[<?= e($field) ?>]" value="<?= e($value) ?>" <?= $column->size?'maxlength="'.(int)$column->size.'"':'' ?> placeholder="<?= in_array($column->type,['datetime','timestamp'],true)?'YYYY-MM-DD HH:MM:SS':e($field) ?>"><?php endif ?><?= $upload($field,is_string($value)?$value:null) ?></label><?php endforeach ?></div><?php if(isset($media['image'])&&$model instanceof app\models\NewsArticle): ?><fieldset><legend>新闻封面图片</legend><label>封面<small>写入 raw_payload_json.image，新闻列表与详情读取同一字段</small><?= $upload('image',$model->imageUrl?:null) ?></label></fieldset><?php endif ?><?php if($tags): ?><fieldset><legend>新闻标签</legend><div class="choice-grid"><?php foreach($tags as $tag): ?><label class="choice"><input type="checkbox" name="tag_ids[]" value="<?= (int)$tag->id ?>" <?= in_array($tag->id,$model->tagIds)?'checked':'' ?>><?= e($tag->name_cn) ?></label><?php endforeach ?></div></fieldset><?php endif ?><button class="button">保存到 MySQL →</button><p class="meta">保存后前台读取同一数据库。ID、创建时间等不可通过表单覆盖。</p></form>
<?php if($media): ?><script>document.querySelectorAll('[data-media-input]').forEach(function(input){input.addEventListener('change',function(){var box=input.parentNode.querySelector('[data-media-preview]'),file=input.files[0],el;box.textContent='';box.hidden=!file;if(!file){return;}if(/^image\//.test(file.type)){el=document.createElement('img');}else if(/^video\//.test(file.type)){el=document.createElement('video');el.controls=true;}else{el=document.createElement('span');el.textContent=file.name;}if(el.tagName!=='SPAN'){el.src=URL.createObjectURL(file);el.className='media-preview';}box.appendChild(el);var size=document.createElement('small');size.textContent='待上传：'+file.name+' · '+(file.size/1048576).toFixed(2)+' MB';box.appendChild(size);});});</script><?php endif ?>
