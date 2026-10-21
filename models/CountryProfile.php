<?php
/** Native Yii2 ActiveRecord: country_profiles; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class CountryProfile extends Record
{
    public static function tableName(): string { return '{{%country_profiles}}'; }
    public function rules(): array
    {
        return [
            [['country_code'], 'required'],
            [['country_code'], 'string', 'max' => 8],
            [['country_code'], 'unique'],
            [['country_name_cn'], 'default', 'value' => null],
            [['country_name_cn'], 'string', 'max' => 120],
            [['country_name_original'], 'required'],
            [['country_name_original'], 'string', 'max' => 160],
            [['capital'], 'default', 'value' => null],
            [['capital'], 'string', 'max' => 180],
            [['region'], 'default', 'value' => null],
            [['region'], 'string', 'max' => 120],
            [['subregion'], 'default', 'value' => null],
            [['subregion'], 'string', 'max' => 120],
            [['languages'], 'default', 'value' => null],
            [['languages'], 'string', 'max' => 300],
            [['currencies'], 'default', 'value' => null],
            [['currencies'], 'string', 'max' => 300],
            [['population'], 'default', 'value' => null],
            [['population'], 'integer'],
            [['area_km2'], 'default', 'value' => null],
            [['area_km2'], 'number'],
            [['latitude'], 'default', 'value' => null],
            [['latitude'], 'number'],
            [['longitude'], 'default', 'value' => null],
            [['longitude'], 'number'],
            [['map_url'], 'default', 'value' => null],
            [['map_url'], 'string', 'max' => 900],
            [['fifa_team_count'], 'integer'],
            [['travel_summary'], 'default', 'value' => null],
            [['travel_summary'], 'string'],
            [['culture_summary'], 'default', 'value' => null],
            [['culture_summary'], 'string'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 900],
            [['source_synced_at'], 'default', 'value' => null],
            [['source_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

}
