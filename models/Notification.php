<?php
/** User-owned reply notifications. */
declare(strict_types=1);
namespace app\models;
class Notification extends Record { public static function tableName(): string { return '{{%notifications}}'; } public function getThread() { return $this->hasOne(ForumThread::class,['id'=>'thread_id']); } }
