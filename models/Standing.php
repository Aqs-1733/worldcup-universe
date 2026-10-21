<?php
/** Native Yii2 ActiveRecord: standings; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Standing extends Record
{
    public static function tableName(): string { return '{{%standings}}'; }
    public function rules(): array
    {
        return [
            [['team_id'], 'required'],
            [['team_id'], 'integer'],
            [['group_name'], 'required'],
            [['group_name'], 'string', 'max' => 20],
            [['played'], 'integer'],
            [['won'], 'integer'],
            [['drawn'], 'integer'],
            [['lost'], 'integer'],
            [['goals_for'], 'integer'],
            [['goals_against'], 'integer'],
            [['goal_difference'], 'integer'],
            [['points'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }
    public function getTeam() { return $this->hasOne(Team::class, ['id'=>'team_id']); }
}
