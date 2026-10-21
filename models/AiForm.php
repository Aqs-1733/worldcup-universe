<?php
/** Validated optional AI request; image generation requires explicit opt-in. */
declare(strict_types=1);
namespace app\models;
use yii\base\Model;
final class AiForm extends Model
{
    public $prompt=''; public $tone='中立、清楚'; public $target_team=''; public $generate_image=false; public $image_prompt=''; public $image;
    public function rules(): array { return [[['prompt','tone','target_team','image_prompt'],'trim'], ['prompt','required','on'=>'chat'], ['prompt','string','max'=>4000], ['tone','string','max'=>80], ['target_team','string','max'=>120], ['generate_image','boolean'], ['image_prompt','string','max'=>300], ['image','file','extensions'=>['jpg','jpeg','png','webp'],'mimeTypes'=>['image/jpeg','image/png','image/webp'],'maxSize'=>5*1024*1024,'skipOnEmpty'=>false,'on'=>'upload']]; }
}
