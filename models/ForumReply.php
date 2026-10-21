<?php
/** Reply record; same-thread parent validation also runs in the write service. */
declare(strict_types=1);
namespace app\models;
class ForumReply extends Record
{
    public static function tableName(): string { return '{{%forum_replies}}'; }
    public function rules(): array { return [['body','trim'], ['body','required'], ['body','string','min'=>2,'max'=>10000], ['parent_id','default','value'=>null], ['parent_id','integer']]; }
    public function getUser() { return $this->hasOne(User::class,['id'=>'user_id']); }
    public function getThread() { return $this->hasOne(ForumThread::class,['id'=>'thread_id']); }
}
