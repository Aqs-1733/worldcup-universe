<?php
/** Moderation report with a bounded, plain-text reason. */
declare(strict_types=1);
namespace app\models;
class ForumReport extends Record
{
    public static function tableName(): string { return '{{%forum_reports}}'; }
    public function rules(): array { return [['reason','trim'], ['reason','required'], ['reason','string','min'=>3,'max'=>500]]; }
}
