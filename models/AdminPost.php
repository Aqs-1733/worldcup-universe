<?php
/**
 * 文件作用：模型：admin_posts 表（后台发布的公告/资讯），含字段校验与关联。
 * 作者：蒋林瀞_2413507
 */
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
