<?php
/**
 * 文件作用：模型：audit_logs 表（后台操作审计日志：谁、在什么时候、对哪条记录做了什么）。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;

class AuditLog extends Record
{
    public static function tableName(): string { return '{{%audit_logs}}'; }
    // 动作名必填；detail_json 必须是合法 JSON；user_id 必须是存在的用户
    public function rules(): array
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['action'], 'required'],
            [['action'], 'string', 'max' => 120],
            [['entity_type'], 'default', 'value' => null],
            [['entity_type'], 'string', 'max' => 80],
            [['entity_id'], 'default', 'value' => null],
            [['entity_id'], 'integer'],
            [['ip_address'], 'default', 'value' => null],
            [['ip_address'], 'string', 'max' => 64],
            [['detail_json'], 'default', 'value' => null],
            [['detail_json'], 'validateJson'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

}
