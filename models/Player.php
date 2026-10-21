<?php
/** Native Yii2 ActiveRecord: players; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Player extends Record
{
    public static function tableName(): string { return '{{%players}}'; }
    public function rules(): array
    {
        return [
            [['team_id'], 'default', 'value' => null],
            [['team_id'], 'integer'],
            [['fifa_id'], 'default', 'value' => null],
            [['fifa_id'], 'string', 'max' => 80],
            [['fifa_id'], 'unique'],
            [['slug'], 'required'],
            [['slug'], 'string', 'max' => 160],
            [['slug'], 'unique'],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 160],
            [['name_original'], 'required'],
            [['name_original'], 'string', 'max' => 220],
            [['position'], 'default', 'value' => null],
            [['position'], 'string', 'max' => 40],
            [['shirt_number'], 'default', 'value' => null],
            [['shirt_number'], 'integer'],
            [['birth_date'], 'default', 'value' => null],
            [['birth_date'], 'datetime', 'format' => 'php:Y-m-d'],
            [['age'], 'default', 'value' => null],
            [['age'], 'integer'],
            [['club'], 'default', 'value' => null],
            [['club'], 'string', 'max' => 180],
            [['caps'], 'default', 'value' => null],
            [['caps'], 'integer'],
            [['goals'], 'default', 'value' => null],
            [['goals'], 'integer'],
            [['height_cm'], 'default', 'value' => null],
            [['height_cm'], 'integer'],
            [['photo_url'], 'default', 'value' => null],
            [['photo_url'], 'string', 'max' => 800],
            [['popularity_score'], 'number'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }
    public function getTeam() { return $this->hasOne(Team::class, ['id'=>'team_id']); }
    public function getStatistics() { return $this->hasMany(PlayerStatistic::class, ['player_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
    public function getName(): string { return $this->name_cn ?: $this->name_original; }
    public function getDisplayPhotoUrl(): string { return \app\services\PortraitAsset::url($this->photo_url); }
}
