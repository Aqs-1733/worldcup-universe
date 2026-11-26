<?php
/**
 * 文件作用：模型：team_members 表（项目团队成员资料）。
 */
declare(strict_types=1);
namespace app\models;

class TeamMember extends Record
{
    public static function tableName(): string { return '{{%team_members}}'; }
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 80],
            [['student_no'], 'default', 'value' => null],
            [['student_no'], 'string', 'max' => 50],
            [['role_name'], 'required'],
            [['role_name'], 'string', 'max' => 80],
            [['bio'], 'default', 'value' => null],
            [['bio'], 'string'],
            [['self_introduction'], 'filter', 'filter' => static fn($value) => is_string($value) ? trim($value) : $value],
            [['self_introduction'], 'default', 'value' => null],
            [['self_introduction'], 'string', 'max' => 500],
            [['photo_url'], 'default', 'value' => null],
            [['photo_url'], 'string', 'max' => 500],
            [['sort_order'], 'integer'],
            [['is_visible'], 'integer'],
        ];
    }
    public function attributeLabels(): array
    {
        return [
            'bio' => '学院及成员信息',
            'self_introduction' => '自我介绍',
            'photo_url' => '成员头像',
        ] + parent::attributeLabels();
    }
}
