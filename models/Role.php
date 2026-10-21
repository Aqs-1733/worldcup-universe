<?php
/** Native Yii2 ActiveRecord: roles; field validation and relational integrity. */
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
