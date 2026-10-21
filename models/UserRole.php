<?php
/** Native Yii2 ActiveRecord: user_roles; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class UserRole extends Record
{
    public static function tableName(): string { return '{{%user_roles}}'; }
    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['role_id'], 'required'],
            [['role_id'], 'integer'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['role_id'], 'exist', 'targetClass' => Role::class, 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

}
