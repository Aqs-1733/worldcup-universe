<?php
/** Native Yii2 ActiveRecord: data_quality_checks; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class DataQualityCheck extends Record
{
    public static function tableName(): string { return '{{%data_quality_checks}}'; }
    public function rules(): array
    {
        return [
            [['check_key'], 'required'],
            [['check_key'], 'string', 'max' => 120],
            [['check_key'], 'unique'],
            [['label'], 'required'],
            [['label'], 'string', 'max' => 200],
            [['expected_value'], 'default', 'value' => null],
            [['expected_value'], 'string', 'max' => 120],
            [['actual_value'], 'default', 'value' => null],
            [['actual_value'], 'string', 'max' => 120],
            [['status'], 'in', 'range' => ['pass','warn','fail']],
            [['detail'], 'default', 'value' => null],
            [['detail'], 'string'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 900],
        ];
    }

}
