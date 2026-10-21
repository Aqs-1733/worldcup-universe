<?php
/** Native Yii2 ActiveRecord: player_statistics; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class PlayerStatistic extends Record
{
    public function getPlayer() { return $this->hasOne(Player::class, ['id'=>'player_id']); }
    public static function tableName(): string { return '{{%player_statistics}}'; }
    public function rules(): array
    {
        return [
            [['player_id'], 'default', 'value' => null],
            [['player_id'], 'integer'],
            [['external_player_id'], 'default', 'value' => null],
            [['external_player_id'], 'string', 'max' => 80],
            [['external_player_id'], 'unique'],
            [['matches_played'], 'default', 'value' => null],
            [['matches_played'], 'integer'],
            [['matches_started'], 'default', 'value' => null],
            [['matches_started'], 'integer'],
            [['minutes_played'], 'default', 'value' => null],
            [['minutes_played'], 'integer'],
            [['goals'], 'default', 'value' => null],
            [['goals'], 'integer'],
            [['assists'], 'default', 'value' => null],
            [['assists'], 'integer'],
            [['shots'], 'default', 'value' => null],
            [['shots'], 'integer'],
            [['shots_on_target'], 'default', 'value' => null],
            [['shots_on_target'], 'integer'],
            [['yellow_cards'], 'default', 'value' => null],
            [['yellow_cards'], 'integer'],
            [['red_cards'], 'default', 'value' => null],
            [['red_cards'], 'integer'],
            [['penalty_goals'], 'default', 'value' => null],
            [['penalty_goals'], 'integer'],
            [['own_goals'], 'default', 'value' => null],
            [['own_goals'], 'integer'],
            [['clean_sheets'], 'default', 'value' => null],
            [['clean_sheets'], 'integer'],
            [['saves'], 'default', 'value' => null],
            [['saves'], 'integer'],
            [['goals_conceded'], 'default', 'value' => null],
            [['goals_conceded'], 'integer'],
            [['average_rating'], 'default', 'value' => null],
            [['average_rating'], 'number'],
            [['data_source'], 'default', 'value' => null],
            [['data_source'], 'string', 'max' => 160],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['raw_payload_json'], 'default', 'value' => null],
            [['raw_payload_json'], 'validateJson'],
            [['player_id'], 'exist', 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
        ];
    }

}
