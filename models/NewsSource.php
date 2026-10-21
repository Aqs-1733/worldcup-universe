<?php
/** Native Yii2 ActiveRecord: news_sources; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class NewsSource extends Record
{
    public static function tableName(): string { return '{{%news_sources}}'; }
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 120],
            [['url'], 'required'],
            [['url'], 'string', 'max' => 800],
            [['rss_url'], 'default', 'value' => null],
            [['rss_url'], 'string', 'max' => 800],
            [['language_code'], 'string', 'max' => 20],
            [['trust_level'], 'integer'],
            [['is_enabled'], 'integer'],
            [['last_synced_at'], 'default', 'value' => null],
            [['last_synced_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['last_error'], 'default', 'value' => null],
            [['last_error'], 'string'],
        ];
    }

}
