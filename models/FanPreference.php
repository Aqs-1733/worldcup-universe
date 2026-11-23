<?php
/**
 * 文件作用：模型：fan_preferences 表（球迷偏好设置）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class FanPreference extends Record
{
    public static function tableName(): string { return '{{%fan_preferences}}'; }
    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['user_id'], 'unique'],
            [['push_match'], 'integer'],
            [['push_tactics'], 'integer'],
            [['push_team'], 'integer'],
            [['push_fanmade'], 'integer'],
            [['push_entertainment'], 'integer'],
            [['preferred_language'], 'string', 'max' => 20],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

}
