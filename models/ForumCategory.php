<?php
/**
 * 文件作用：模型：forum_categories 表（论坛板块）。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;
class ForumCategory extends Record
{
    public static function tableName(): string { return '{{%forum_categories}}'; }
    public function rules(): array { return [[['name','slug'],'required'], ['slug','match','pattern'=>'/^[a-z0-9-]+$/'], ['slug','unique'], ['slug','string','max'=>60], ['name','string','max'=>80], ['description','string','max'=>300], ['sort_order','integer']]; }
}
