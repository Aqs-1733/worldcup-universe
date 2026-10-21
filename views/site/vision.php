<?php
/** Private image intake is clearly separated from unconfigured model analysis. */
use yii\helpers\Html;
$this->title='图像工作室';
?>
<section class="page-heading"><h1>图像工作室</h1><p>上传 JPEG、PNG 或 WebP，最大 5 MB。图片仅本人和管理员可读取。</p></section><p class="meta">当前提供图片接收与私有预览；视觉分析模型尚未接入，上传不产生虚构分析。</p><form method="post" enctype="multipart/form-data" class="form-panel prose"><?= csrf_field() ?><?= Html::errorSummary($model,['class'=>'form-errors']) ?><label>选择图片<input type="file" name="AiForm[image]" accept="image/jpeg,image/png,image/webp" required></label><button class="button">保存图片 →</button></form><?php if($record): ?><p class="flash success"><?= e($record->response_text) ?></p><img class="studio-image" src="<?= e(yii\helpers\Url::to(['ai/upload','id'=>$record->id])) ?>" alt="用户上传图片的私有预览"><?php endif ?>
