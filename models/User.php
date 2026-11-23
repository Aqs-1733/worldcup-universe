<?php
/**
 * 文件作用：模型：users 表（用户账号）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class User extends UserIdentity
{
    public static function tableName(): string { return '{{%users}}'; }
    public function rules(): array
    {
        return [
            [['username'], 'required'],
            [['username'], 'string', 'max' => 40],
            [['username'], 'unique'],
            [['email'], 'required'],
            [['email'], 'string', 'max' => 120],
            [['email'], 'unique'],
            [['display_name'], 'default', 'value' => null],
            [['display_name'], 'string', 'max' => 80],
            [['avatar_url'], 'default', 'value' => null],
            [['avatar_url'], 'string', 'max' => 500],
            [['survey_completed_at'], 'default', 'value' => null],
            [['survey_completed_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['survey_skipped_at'], 'default', 'value' => null],
            [['survey_skipped_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

}
