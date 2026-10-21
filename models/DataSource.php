<?php
/** Native Yii2 ActiveRecord: data_sources; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class DataSource extends Record
{
    public static function tableName(): string { return '{{%data_sources}}'; }
    public function rules(): array
    {
        return [
            [['source_key'], 'required'],
            [['source_key'], 'string', 'max' => 80],
            [['source_key'], 'unique'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 120],
            [['url'], 'required'],
            [['url'], 'string', 'max' => 800],
            [['source_type'], 'in', 'range' => ['official','api','wiki','rss','manual']],
            [['trust_level'], 'integer'],
            [['last_synced_at'], 'default', 'value' => null],
            [['last_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['last_status'], 'default', 'value' => null],
            [['last_status'], 'string', 'max' => 40],
            [['last_error'], 'default', 'value' => null],
            [['last_error'], 'string'],
        ];
    }

}
