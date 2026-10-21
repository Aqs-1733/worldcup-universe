<?php
/** Shared Yii2 model behaviour, never a parallel PDO repository. */
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

abstract class Record extends ActiveRecord
{
    public function beforeValidate(): bool
    {
        foreach(static::getTableSchema()->columns as $field=>$column) {
            if($this->$field==='' && !$column->allowNull && $column->defaultValue!==null) { $this->$field=$column->defaultValue; }
        }
        return parent::beforeValidate();
    }
    public function attributeLabels(): array
    {
        return ['name_cn'=>'中文名称','name_original'=>'原名','slug'=>'网址标识','team_id'=>'球队 ID','player_id'=>'球员 ID','match_id'=>'比赛 ID','title_cn'=>'中文标题','title_original'=>'原文标题','source_name'=>'来源名称','source_url'=>'来源地址','status'=>'状态','body'=>'正文','name'=>'名称','title'=>'标题','position'=>'场上位置','shirt_number'=>'球衣号码','photo_url'=>'肖像地址','flag_url'=>'国旗地址','country_code'=>'国家代码','starts_at'=>'开球时间','stage'=>'轮次','home_team_id'=>'主队 ID','away_team_id'=>'客队 ID','home_score'=>'主队比分','away_score'=>'客队比分','summary_cn'=>'中文摘要','content_cn'=>'中文正文','published_at'=>'发布时间','is_visible'=>'前台可见','sort_order'=>'排序','student_no'=>'学号','role_name'=>'项目角色','bio'=>'简介','coach_name'=>'主教练','world_ranking'=>'世界排名','license_name'=>'素材许可','credit'=>'署名','alt_text'=>'替代文字','storage_path'=>'存储路径'];
    }
    public function fields(): array { return array_diff(parent::fields(), ['raw_payload_json', 'feature_payload_json']); }
    public function validateJson(string $attribute): void
    {
        $value=$this->$attribute;
        if($value===null||$value==='') { return; }
        if($value instanceof \yii\db\JsonExpression) { $value=$value->getValue(); }
        elseif(is_string($value)) { $value=json_decode($value,true);if(json_last_error()!==JSON_ERROR_NONE) {$this->addError($attribute,'请输入有效 JSON。');return;} }
        try { json_encode($value,JSON_THROW_ON_ERROR);$this->$attribute=new \yii\db\JsonExpression($value); }
        catch(\JsonException) { $this->addError($attribute,'请输入有效 JSON。'); }
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) { return false; }
        // Manual edits to factual fields must not retain FIFA verification.
        // Chinese display translations and URL aliases are application metadata.
        if ($this->hasAttribute('official_import_id') && $this->official_import_id) {
            $factual=array_diff(array_keys($this->getDirtyAttributes()),['name_cn','slug','updated_at']);
            if($factual) { $this->official_import_id=null; }
        }
        if ($this->hasAttribute('updated_at')) { $this->updated_at = new Expression('CURRENT_TIMESTAMP'); }
        return true;
    }
}
