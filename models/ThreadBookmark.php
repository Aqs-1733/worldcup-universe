<?php
/**
 * 文件作用：模型：thread_bookmarks 表（主题收藏），每人每个主题只能收藏一次。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;
class ThreadBookmark extends Record { public static function tableName(): string { return '{{%thread_bookmarks}}'; } }
