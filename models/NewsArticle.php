<?php
/** Native Yii2 ActiveRecord: news_articles; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class NewsArticle extends Record
{
    public $tagIds=[];
    public static function tableName(): string { return '{{%news_articles}}'; }
    public function rules(): array
    {
        return [
            ['tagIds','each','rule'=>['integer','min'=>1]],
            ['tagIds','each','rule'=>['exist','targetClass'=>Tag::class,'targetAttribute'=>'id']],
            [['source_id'], 'default', 'value' => null],
            [['source_id'], 'integer'],
            [['source_name'], 'required'],
            [['source_name'], 'string', 'max' => 120],
            [['source_url'], 'required'],
            [['source_url'], 'string', 'max' => 900],
            [['source_url'], 'unique'],
            [['title_cn'], 'default', 'value' => null],
            [['title_cn'], 'string', 'max' => 280],
            [['title_original'], 'required'],
            [['title_original'], 'string', 'max' => 280],
            [['summary_cn'], 'default', 'value' => null],
            [['summary_cn'], 'string'],
            [['summary_original'], 'default', 'value' => null],
            [['summary_original'], 'string'],
            [['content_cn'], 'default', 'value' => null],
            [['content_cn'], 'string'],
            [['content_original'], 'default', 'value' => null],
            [['content_original'], 'string'],
            [['language_code'], 'string', 'max' => 20],
            [['published_at'], 'default', 'value' => null],
            [['published_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['credibility_score'], 'integer'],
            [['translation_status'], 'in', 'range' => ['none','pending','translated','failed']],
            [['raw_payload_json'], 'default', 'value' => null],
            [['raw_payload_json'], 'validateJson'],
            [['source_id'], 'exist', 'targetClass' => NewsSource::class, 'targetAttribute' => ['source_id' => 'id']],
            [['status'], 'in', 'range' => ['draft','published','hidden']],
        ];
    }
    public function getTags() { return $this->hasMany(Tag::class, ['id'=>'tag_id'])->viaTable('news_article_tags', ['article_id'=>'id']); }
    public function getTitle(): string { return $this->title_cn ?: $this->title_original; }
    public function getImageUrl(): string
    {
        $raw=is_array($this->raw_payload_json)?$this->raw_payload_json:json_decode($this->raw_payload_json??'{}',true);
        return (new \app\services\NewsImportService())->imageUrl((string)($raw['image']??''));
    }
    public function getNewsType(): string
    {
        $slugs=array_map(static fn($t)=>$t->slug,$this->tags);
        foreach(['tactics'=>'战术分析','team'=>'球队动态','fan'=>'球迷现场','entertainment'=>'场外花絮','worldcup'=>'世界杯'] as $slug=>$label) {if(in_array($slug,$slugs,true))return $label;}
        return '赛事报道';
    }
    public function getRelatedTeams(): array
    {
        static $teams=null;
        $teams??=Team::find()->where(['not',['official_import_id'=>null]])->with('featurePlayer')->all();
        $raw=is_array($this->raw_payload_json)?$this->raw_payload_json:json_decode($this->raw_payload_json??'{}',true);
        $ids=$raw['team_ids']??[];$text=$this->title.' '.($this->summary_cn?:$this->summary_original);
        return array_values(array_filter($teams,static function($team) use($ids,$text) {
            return in_array((int)$team->id,$ids,true)||($team->name_cn&&mb_stripos($text,$team->name_cn)!==false)||preg_match('/\b'.preg_quote($team->name_original,'/').'\b/iu',$text);
        }));
    }
}
