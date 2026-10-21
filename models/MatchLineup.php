<?php
/** Native Yii2 ActiveRecord: match_lineups; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class MatchLineup extends Record
{
    public function getPlayer() { return $this->hasOne(Player::class, ['id'=>'player_id']); }
    public function getOfficialPlayerName(): string { $raw=is_array($this->raw_payload_json)?$this->raw_payload_json:json_decode($this->raw_payload_json??'{}',true);return $this->player?->name??($raw['PlayerName'][0]['Description']??'官方阵容球员'); }
    public static function tableName(): string { return '{{%match_lineups}}'; }
    public function rules(): array
    {
        return [
            [['external_lineup_id'], 'default', 'value' => null],
            [['external_lineup_id'], 'string', 'max' => 100],
            [['external_lineup_id'], 'unique'],
            [['match_id'], 'default', 'value' => null],
            [['match_id'], 'integer'],
            [['player_id'], 'default', 'value' => null],
            [['player_id'], 'integer'],
            [['team_id'], 'default', 'value' => null],
            [['team_id'], 'integer'],
            [['is_starting_xi'], 'integer'],
            [['tactical_position'], 'default', 'value' => null],
            [['tactical_position'], 'string', 'max' => 40],
            [['minutes_played'], 'default', 'value' => null],
            [['minutes_played'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['raw_payload_json'], 'default', 'value' => null],
            [['raw_payload_json'], 'validateJson'],
            [['match_id'], 'exist', 'targetClass' => MatchGame::class, 'targetAttribute' => ['match_id' => 'id']],
            [['player_id'], 'exist', 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }

}
