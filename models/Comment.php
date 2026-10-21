<?php
/** Native Yii2 ActiveRecord: comments; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Comment extends Record
{
    public static function tableName(): string { return '{{%comments}}'; }
    public function rules(): array
    {
        return [
            [['body'], 'string', 'min' => 2, 'max' => 1000],
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['entity_type'], 'in', 'range' => ['team','player','match','news','site']],
            [['entity_id'], 'default', 'value' => null],
            [['entity_id'], 'integer'],
            [['body'], 'required'],
            [['body'], 'string'],
            [['status'], 'in', 'range' => ['visible','hidden','pending']],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id'=>'user_id']); }
}
