<?php
/** Private bookmark, unique per member/topic. */
declare(strict_types=1);
namespace app\models;
class ThreadBookmark extends Record { public static function tableName(): string { return '{{%thread_bookmarks}}'; } }
