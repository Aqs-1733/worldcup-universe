<?php
/** Native Yii2 ActiveRecord: source_records; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class SourceRecord extends Record
{
    public static function tableName(): string { return '{{%source_records}}'; }
    public function rules(): array
    {
        return [
            [['source_file_id'], 'required'],
            [['source_file_id'], 'integer'],
            [['row_number'], 'required'],
            [['row_number'], 'integer'],
            [['entity_key'], 'default', 'value' => null],
            [['entity_key'], 'string', 'max' => 160],
            [['record_json'], 'required'],
            [['record_json'], 'validateJson'],
            [['source_file_id'], 'exist', 'targetClass' => SourceFile::class, 'targetAttribute' => ['source_file_id' => 'id']],
        ];
    }

}
