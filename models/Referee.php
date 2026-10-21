<?php
/** Native Yii2 ActiveRecord: referees; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Referee extends Record
{
    public static function tableName(): string { return '{{%referees}}'; }
    public function rules(): array
    {
        return [
            [['external_id'], 'default', 'value' => null],
            [['external_id'], 'string', 'max' => 80],
            [['external_id'], 'unique'],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 160],
            [['name_original'], 'required'],
            [['name_original'], 'string', 'max' => 220],
            [['country_code'], 'default', 'value' => null],
            [['country_code'], 'string', 'max' => 3],
            [['avg_cards_per_game'], 'default', 'value' => null],
            [['avg_cards_per_game'], 'number'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

}
