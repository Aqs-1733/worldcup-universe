<?php
/**
 * 文件作用：模型：teams 表（球队：中英文名、代码、所属足联、国旗、FIFA 排名），并关联球员和小组积分。
 * 作者：崔迪_2412439
 */
declare(strict_types=1);
namespace app\models;

class Team extends Record
{
    public static function tableName(): string { return '{{%teams}}'; }
    // 字段校验规则：slug 和原名必填，其余字段可以为空
    public function rules(): array
    {
        return [
            [['fifa_id'], 'default', 'value' => null],
            [['fifa_id'], 'string', 'max' => 80],
            [['fifa_id'], 'unique'],
            [['slug'], 'required'],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'unique'],
            [['code'], 'default', 'value' => null],
            [['code'], 'string', 'max' => 8],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 120],
            [['name_original'], 'required'],
            [['name_original'], 'string', 'max' => 160],
            [['country_code'], 'default', 'value' => null],
            [['country_code'], 'string', 'max' => 8],
            [['flag_emoji'], 'default', 'value' => null],
            [['flag_emoji'], 'string', 'max' => 16],
            [['flag_url'], 'default', 'value' => null],
            [['flag_url'], 'string', 'max' => 800],
            [['confederation'], 'default', 'value' => null],
            [['confederation'], 'string', 'max' => 40],
            [['group_name'], 'default', 'value' => null],
            [['group_name'], 'string', 'max' => 20],
            [['coach_name'], 'default', 'value' => null],
            [['coach_name'], 'string', 'max' => 160],
            [['world_ranking'], 'default', 'value' => null],
            [['world_ranking'], 'integer'],
            [['profile'], 'default', 'value' => null],
            [['profile'], 'string'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }
    // 关联：本队球员（只取官方导入的数据）
    public function getPlayers() { return $this->hasMany(Player::class, ['team_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
    // 关联：出场次数最多的球员，作为球队卡片上的代表球员
    public function getFeaturePlayer() { return $this->hasOne(Player::class, ['team_id'=>'id'])->andWhere(['not',['official_import_id'=>null]])->orderBy(['caps'=>SORT_DESC]); }
    // 关联：小组积分
    public function getStandings() { return $this->hasMany(Standing::class, ['team_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
    // 显示名称：优先用中文名
    public function getName(): string { return $this->name_cn ?: $this->name_original; }
}
