<?php
/** Native Yii2 ActiveRecord: source_files; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class SourceFile extends Record
{
    public static function tableName(): string { return '{{%source_files}}'; }
    public function rules(): array
    {
        return [
            [['dataset_key'], 'required'],
            [['dataset_key'], 'string', 'max' => 120],
            [['source_name'], 'required'],
            [['source_name'], 'string', 'max' => 160],
            [['source_path'], 'required'],
            [['source_path'], 'string', 'max' => 900],
            [['source_path'], 'unique'],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 900],
            [['file_type'], 'required'],
            [['file_type'], 'string', 'max' => 20],
            [['row_count'], 'integer'],
            [['sha256'], 'default', 'value' => null],
            [['sha256'], 'string', 'max' => 64],
        ];
    }

}
