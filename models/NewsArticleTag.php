<?php
/** Native Yii2 ActiveRecord: news_article_tags; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class NewsArticleTag extends Record
{
    public static function tableName(): string { return '{{%news_article_tags}}'; }
    public function rules(): array
    {
        return [
            [['article_id'], 'required'],
            [['article_id'], 'integer'],
            [['tag_id'], 'required'],
            [['tag_id'], 'integer'],
            [['article_id'], 'exist', 'targetClass' => NewsArticle::class, 'targetAttribute' => ['article_id' => 'id']],
            [['tag_id'], 'exist', 'targetClass' => Tag::class, 'targetAttribute' => ['tag_id' => 'id']],
        ];
    }

}
