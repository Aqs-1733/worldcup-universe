<?php
/**
 * 文件作用：模型：roles 表（角色）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class Role extends Record
{
    public static function tableName(): string { return '{{%roles}}'; }
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['name'], 'unique'],
            [['label'], 'required'],
            [['label'], 'string', 'max' => 100],
        ];
    }

}
