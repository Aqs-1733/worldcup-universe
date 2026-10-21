<?php
/** Media usage requires a recorded source, licence and approval. */
declare(strict_types=1);
namespace app\models;
class MediaAsset extends Record
{
    public static function tableName(): string { return '{{%media_assets}}'; }
    public function rules(): array { return [[['asset_key','path','alt_text','source_url','license_name'],'required'], ['asset_key','match','pattern'=>'/^[a-z0-9-]+$/'], ['asset_key','string','max'=>80], ['asset_key','unique'], [['path','source_url'],'string','max'=>800], [['alt_text','credit'],'string','max'=>300], ['license_name','string','max'=>120], ['status','in','range'=>['pending','approved','rejected']], ['source_url','url'], ['path','validatePath']]; }
    public function validatePath(string $field): void { if (!str_starts_with($this->$field,'/assets/media/') && !preg_match('~^https://~i',$this->$field)) { $this->addError($field,'使用 /assets/media/ 本地路径或 HTTPS 图片地址。'); } }
}
