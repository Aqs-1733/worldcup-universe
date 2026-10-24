<?php
/** An official video ID, never arbitrary iframe HTML or an extracted stream URL. */
declare(strict_types=1);
namespace app\models;
final class MatchVideo extends Record
{
    public static function tableName(): string { return '{{%match_videos}}'; }
    public function rules(): array
    {
        return [[['slug','title','tournament_year','stage','home_name','away_name','home_code','away_code','provider_id','source_url','source_author','source_title'],'required'],
            ['slug','match','pattern'=>'/^[a-z0-9-]{3,140}$/'],['slug','unique'],[['title'],'string','max'=>220],['source_title','string','max'=>280],
            ['tournament_year','integer','min'=>2026,'max'=>2026],[['stage'],'string','max'=>60],[['home_name','away_name','source_author'],'string','max'=>80],
            [['home_code','away_code'],'match','pattern'=>'/^[A-Z]{3}$/'],['provider','in','range'=>['youtube','rtve']],['provider_id','validateProviderId'],
            ['source_url','url'],[['source_url','thumbnail_url'],'string','max'=>800],['source_url','validateOfficialSource'],['thumbnail_url','validateThumbnail'],
            ['video_type','in','range'=>['full_match','highlights']],['status','in','range'=>['draft','published','hidden']],['verified_at','safe']];
    }
    public function validateOfficialSource(string $field): void
    {
        $parts=parse_url($this->$field);parse_str($parts['query']??'',$query);
        $valid=$this->provider==='rtve'?($this->source_author==='RTVE'&&($parts['host']??'')==='www.rtve.es'&&str_ends_with($parts['path']??'','/'.$this->provider_id.'/')&&str_starts_with($parts['path']??'','/play/videos/copa-mundial-de-la-fifa-2026/')):($this->source_author==='FIFA'&&in_array($parts['host']??'',['www.youtube.com','youtube.com'],true)&&($parts['path']??'')==='/watch'&&($query['v']??null)===$this->provider_id);
        if(($parts['scheme']??'')!=='https'||!$valid) {$this->addError($field,'请使用已核验的官方完整比赛来源。');}
    }
    public function validateProviderId(string $field): void {if(!preg_match($this->provider==='rtve'?'/^\d{8}$/':'/^[a-zA-Z0-9_-]{11}$/',(string)$this->$field)){$this->addError($field,'官方视频编号无效。');}}
    public function validateThumbnail(string $field): void { if($this->$field&&!preg_match('~^https://(?:i\.ytimg\.com/vi/[A-Za-z0-9_-]{11}/[A-Za-z0-9_-]+\.jpg|img\.rtve\.es/imagenes/[a-zA-Z0-9/_-]+\.jpg)$~',$this->$field)) {$this->addError($field,'缩略图地址无效。');} }
    public function beforeSave($insert): bool
    {
        if(!parent::beforeSave($insert))return false;
        if($this->status==='published'&&($insert||array_intersect(array_keys($this->getDirtyAttributes()),['provider','provider_id','source_url','tournament_year','status','video_type']))) {
            try{$verified=(new \app\services\VideoVerificationService())->verify($this);$this->source_author=$verified['author'];$this->source_title=$verified['title'];$this->thumbnail_url=$verified['thumbnail'];$this->verified_at=date('Y-m-d H:i:s');}
            catch(\Throwable $e){$this->addError('source_url',$e->getMessage());return false;}
        }
        return true;
    }
    public function getEmbedUrl(): string { return $this->provider==='rtve'?'https://www.rtve.es/drmn/embed/video/'.rawurlencode($this->provider_id).'/':'https://www.youtube-nocookie.com/embed/'.rawurlencode($this->provider_id).'?rel=0&playsinline=1'; }
}
