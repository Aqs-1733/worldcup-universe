<?php
/** Native Yii2 ActiveRecord: venues; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Venue extends Record
{
    public static function tableName(): string { return '{{%venues}}'; }
    public function rules(): array
    {
        return [
            [['fifa_id'], 'default', 'value' => null],
            [['fifa_id'], 'string', 'max' => 80],
            [['fifa_id'], 'unique'],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 160],
            [['name_original'], 'required'],
            [['name_original'], 'string', 'max' => 220],
            [['city_cn'], 'default', 'value' => null],
            [['city_cn'], 'string', 'max' => 120],
            [['city_original'], 'default', 'value' => null],
            [['city_original'], 'string', 'max' => 160],
            [['country_code'], 'default', 'value' => null],
            [['country_code'], 'string', 'max' => 2],
            [['capacity'], 'default', 'value' => null],
            [['capacity'], 'integer'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
        ];
    }

}
