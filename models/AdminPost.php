<?php
/** Native Yii2 ActiveRecord: admin_posts; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class AdminPost extends Record
{
    public static function tableName(): string { return '{{%admin_posts}}'; }
    public function rules(): array
    {
        return [
            [['author_id'], 'default', 'value' => null],
            [['author_id'], 'integer'],
            [['title'], 'required'],
            [['title'], 'string', 'max' => 180],
            [['body'], 'required'],
            [['body'], 'string'],
            [['post_type'], 'in', 'range' => ['announcement','team','personal','match']],
            [['status'], 'in', 'range' => ['draft','published']],
            [['published_at'], 'default', 'value' => null],
            [['published_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['author_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['author_id' => 'id']],
        ];
    }
    public function getAuthor() { return $this->hasOne(User::class, ['id'=>'author_id']); }
}
