<?php
/** Native Yii2 ActiveRecord: audit_logs; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class AuditLog extends Record
{
    public static function tableName(): string { return '{{%audit_logs}}'; }
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
