<?php
/** Native Yii2 ActiveRecord: coaches; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class Coach extends Record
{
    public static function tableName(): string { return '{{%coaches}}'; }
    public function rules(): array
    {
        return [
            [['team_id'], 'required'],
            [['team_id'], 'integer'],
            [['name_cn'], 'default', 'value' => null],
            [['name_cn'], 'string', 'max' => 160],
            [['name_original'], 'required'],
            [['name_original'], 'string', 'max' => 220],
            [['nationality'], 'default', 'value' => null],
            [['nationality'], 'string', 'max' => 120],
            [['source_url'], 'default', 'value' => null],
            [['source_url'], 'string', 'max' => 800],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }

}
