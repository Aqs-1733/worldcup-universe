<?php
/** Native Yii2 ActiveRecord: tournament_stages; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class TournamentStage extends Record
{
    public static function tableName(): string { return '{{%tournament_stages}}'; }
    public function rules(): array
    {
        return [
            [['external_id'], 'default', 'value' => null],
            [['external_id'], 'string', 'max' => 80],
            [['external_id'], 'unique'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 120],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 120],
            [['stage_order'], 'default', 'value' => null],
            [['stage_order'], 'integer'],
            [['is_knockout'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

}
