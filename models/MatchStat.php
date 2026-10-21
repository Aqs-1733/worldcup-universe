<?php
/** Native Yii2 ActiveRecord: match_stats; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class MatchStat extends Record
{
    public function getTeam() { return $this->hasOne(Team::class, ['id'=>'team_id']); }
    public static function tableName(): string { return '{{%match_stats}}'; }
    public function rules(): array
    {
        return [
            [['match_id'], 'required'],
            [['match_id'], 'integer'],
            [['team_id'], 'default', 'value' => null],
            [['team_id'], 'integer'],
            [['possession'], 'default', 'value' => null],
            [['possession'], 'number'],
            [['shots'], 'default', 'value' => null],
            [['shots'], 'integer'],
            [['shots_on_target'], 'default', 'value' => null],
            [['shots_on_target'], 'integer'],
            [['corners'], 'default', 'value' => null],
            [['corners'], 'integer'],
            [['fouls'], 'default', 'value' => null],
            [['fouls'], 'integer'],
            [['yellow_cards'], 'default', 'value' => null],
            [['yellow_cards'], 'integer'],
            [['red_cards'], 'default', 'value' => null],
            [['red_cards'], 'integer'],
            [['expected_goals'], 'default', 'value' => null],
            [['expected_goals'], 'number'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['match_id'], 'exist', 'targetClass' => MatchGame::class, 'targetAttribute' => ['match_id' => 'id']],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }

}
