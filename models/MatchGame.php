<?php
/** Native Yii2 ActiveRecord: matches; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class MatchGame extends Record
{
    public static function tableName(): string { return '{{%matches}}'; }
    public function rules(): array
    {
        return [
            [['external_id'], 'default', 'value' => null],
            [['external_id'], 'string', 'max' => 100],
            [['external_id'], 'unique'],
            [['stage'], 'required'],
            [['stage'], 'string', 'max' => 80],
            [['group_name'], 'default', 'value' => null],
            [['group_name'], 'string', 'max' => 20],
            [['home_team_id'], 'default', 'value' => null],
            [['home_team_id'], 'integer'],
            [['away_team_id'], 'default', 'value' => null],
            [['away_team_id'], 'integer'],
            [['home_team_name'], 'default', 'value' => null],
            [['home_team_name'], 'string', 'max' => 160],
            [['away_team_name'], 'default', 'value' => null],
            [['away_team_name'], 'string', 'max' => 160],
            [['home_score'], 'default', 'value' => null],
            [['home_score'], 'integer'],
            [['away_score'], 'default', 'value' => null],
            [['away_score'], 'integer'],
            [['home_penalty_score'], 'default', 'value' => null],
            [['home_penalty_score'], 'integer'],
            [['away_penalty_score'], 'default', 'value' => null],
            [['away_penalty_score'], 'integer'],
            [['status'], 'in', 'range' => ['scheduled','live','finished','postponed','cancelled','unknown']],
            [['starts_at'], 'default', 'value' => null],
            [['starts_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['venue_id'], 'default', 'value' => null],
            [['venue_id'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['home_team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['home_team_id' => 'id']],
            [['away_team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['away_team_id' => 'id']],
            [['venue_id'], 'exist', 'targetClass' => Venue::class, 'targetAttribute' => ['venue_id' => 'id']],
        ];
    }
    public function getHomeTeam() { return $this->hasOne(Team::class, ['id'=>'home_team_id']); }
    public function getAwayTeam() { return $this->hasOne(Team::class, ['id'=>'away_team_id']); }
    public function getVenue() { return $this->hasOne(Venue::class, ['id'=>'venue_id']); }
    public function getStats() { return $this->hasMany(MatchStat::class, ['match_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
    public function getEvents() { return $this->hasMany(MatchEvent::class, ['match_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
    public function getLineups() { return $this->hasMany(MatchLineup::class, ['match_id'=>'id'])->andWhere(['not',['official_import_id'=>null]]); }
}
