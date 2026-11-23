<?php
/**
 * 文件作用：模型：team_members 表（项目团队成员：姓名、学号、角色）。
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
            [['photo_url'], 'default', 'value' => null],
            [['photo_url'], 'string', 'max' => 500],
            [['sort_order'], 'integer'],
            [['is_visible'], 'integer'],
        ];
    }

}
