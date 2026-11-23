<?php
/**
 * 文件作用：模型：match_videos 表（比赛视频，后台保留的记录类型）。
 */
declare(strict_types=1);
namespace app\models;
final class MatchVideo extends Record
{
    public static function tableName(): string { return '{{%match_videos}}'; }
    public function rules(): array
    {
        return [
            [['slug','title','tournament_year','stage','home_name','away_name','home_code','away_code','provider_id','source_author','source_title'],'required'],
            ['slug','match','pattern'=>'/^[a-z0-9-]{3,140}$/'],['slug','unique'],[['title'],'string','max'=>220],['source_title','string','max'=>280],
            ['tournament_year','integer','min'=>2026,'max'=>2026],[['stage'],'string','max'=>60],[['home_name','away_name','source_author'],'string','max'=>80],
            [['home_code','away_code'],'match','pattern'=>'/^[A-Z]{3}$/'],['provider','in','range'=>['local']],
            ['provider_id','match','pattern'=>'/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/'],
            [['source_url','thumbnail_url'],'string','max'=>800],['video_type','in','range'=>['full_match','highlights','moment']],['status','in','range'=>['draft','published','hidden']],['verified_at','safe'],
        ];
    }
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) { return false; }
        if ($this->status==='published' && !\app\services\LocalVideoLibrary::safeFile((string)$this->provider_id)) {
            $this->addError('provider_id','本地视频文件不存在。'); return false;
        }
        if ($this->status==='published') { $this->verified_at=date('Y-m-d H:i:s'); }
        return true;
    }
    public function getEmbedUrl(): string { return ''; }
}
