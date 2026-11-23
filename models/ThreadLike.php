<?php
/**
 * 文件作用：模型：thread_likes 表（主题点赞），每人每个主题只能点赞一次。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;
class ThreadLike extends Record { public static function tableName(): string { return '{{%thread_likes}}'; } }
