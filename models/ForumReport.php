<?php
/**
 * 文件作用：模型：forum_reports 表（举报记录），举报理由为纯文本并限制长度。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;
class ForumReport extends Record
{
    public static function tableName(): string { return '{{%forum_reports}}'; }
    public function rules(): array { return [['reason','trim'], ['reason','required'], ['reason','string','min'=>3,'max'=>500]]; }
}
