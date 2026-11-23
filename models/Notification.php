<?php
/**
 * 文件作用：模型：notifications 表（回复通知），只属于接收者本人。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;
class Notification extends Record { public static function tableName(): string { return '{{%notifications}}'; } public function getThread() { return $this->hasOne(ForumThread::class,['id'=>'thread_id']); } }
