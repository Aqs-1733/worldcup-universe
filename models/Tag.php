<?php
/**
 * 文件作用：模型：tags 表（标签）。
 * 作者：梁靖泽_2412852
 */
declare(strict_types=1);
namespace app\models;

class Tag extends Record
{
    public static function tableName(): string { return '{{%tags}}'; }
    public function rules(): array
    {
        return [
            [['slug'], 'required'],
            [['slug'], 'string', 'max' => 80],
            [['slug'], 'unique'],
            [['name_cn'], 'required'],
            [['name_cn'], 'string', 'max' => 80],
            [['color'], 'string', 'max' => 20],
        ];
    }

}
