<?php
/** Native Yii2 ActiveRecord: match_prediction_features; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class MatchPredictionFeature extends Record
{
    public static function tableName(): string { return '{{%match_prediction_features}}'; }
    public function rules(): array
    {
        return [
            [['external_match_id'], 'required'],
            [['external_match_id'], 'string', 'max' => 100],
            [['external_match_id'], 'unique'],
            [['match_id'], 'default', 'value' => null],
            [['match_id'], 'integer'],
            [['home_team_id'], 'default', 'value' => null],
            [['home_team_id'], 'integer'],
            [['away_team_id'], 'default', 'value' => null],
            [['away_team_id'], 'integer'],
            [['feature_payload_json'], 'required'],
            [['feature_payload_json'], 'validateJson'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['match_id'], 'exist', 'targetClass' => MatchGame::class, 'targetAttribute' => ['match_id' => 'id']],
            [['home_team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['home_team_id' => 'id']],
            [['away_team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['away_team_id' => 'id']],
        ];
    }

}
