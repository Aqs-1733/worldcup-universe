<?php
/** Native Yii2 ActiveRecord: match_events; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class MatchEvent extends Record
{
    public function getPlayer() { return $this->hasOne(Player::class, ['id'=>'player_id']); }
    public static function tableName(): string { return '{{%match_events}}'; }
    public function rules(): array
    {
        return [
            [['external_event_id'], 'default', 'value' => null],
            [['external_event_id'], 'string', 'max' => 100],
            [['external_event_id'], 'unique'],
            [['match_id'], 'default', 'value' => null],
            [['match_id'], 'integer'],
            [['minute'], 'default', 'value' => null],
            [['minute'], 'integer'],
            [['event_type'], 'required'],
            [['event_type'], 'string', 'max' => 80],
            [['team_id'], 'default', 'value' => null],
            [['team_id'], 'integer'],
            [['player_id'], 'default', 'value' => null],
            [['player_id'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['raw_payload_json'], 'default', 'value' => null],
            [['raw_payload_json'], 'validateJson'],
            [['match_id'], 'exist', 'targetClass' => MatchGame::class, 'targetAttribute' => ['match_id' => 'id']],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
            [['player_id'], 'exist', 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
        ];
    }

}
