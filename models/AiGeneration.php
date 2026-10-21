<?php
/** Native Yii2 ActiveRecord: ai_generations; field validation and relational integrity. */
declare(strict_types=1);
namespace app\models;

class AiGeneration extends Record
{
    public static function tableName(): string { return '{{%ai_generations}}'; }
    public function rules(): array
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['generation_type'], 'in', 'range' => ['chat','image','vision']],
            [['prompt'], 'required'],
            [['prompt'], 'string'],
            [['tone'], 'default', 'value' => null],
            [['tone'], 'string', 'max' => 80],
            [['target_team'], 'default', 'value' => null],
            [['target_team'], 'string', 'max' => 120],
            [['response_text'], 'default', 'value' => null],
            [['response_text'], 'string'],
            [['image_url'], 'default', 'value' => null],
            [['image_url'], 'string', 'max' => 900],
            [['model_name'], 'default', 'value' => null],
            [['model_name'], 'string', 'max' => 160],
            [['status'], 'in', 'range' => ['success','failed']],
            [['error_message'], 'default', 'value' => null],
            [['error_message'], 'string'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

}
