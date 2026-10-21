<?php
/** Transactional forum use cases. Controllers never trust posted owner/status IDs. */
declare(strict_types=1);
namespace app\services;

use app\models\{User, ForumThread, ForumReply, ForumReport, Notification};
use Yii;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, BadRequestHttpException, TooManyRequestsHttpException};

final class ForumService
{
    public function readable(int $id, ?User $actor): ForumThread
    {
        $thread = ForumThread::find()->with(['author','team','category'])->where(['id'=>$id])->one();
        if (!$thread || $thread->status === 'deleted' || ($thread->status !== 'published' && (!$actor || (!$actor->isAdmin() && (int)$thread->author_id !== (int)$actor->id)))) { throw new NotFoundHttpException('主题不存在。'); }
        return $thread;
    }

    public function saveThread(User $actor, array $input, ?int $id = null): ForumThread
    {
        return Yii::$app->db->transaction(function () use ($actor,$input,$id) {
            $thread = $id ? $this->lockedThread($id) : new ForumThread();
            if ($id) { $this->assertOwner($actor,(int)$thread->author_id); if ($thread->status !== 'published' || ($thread->is_locked && !$actor->isAdmin())) { throw new ForbiddenHttpException('主题当前不可编辑。'); } }
            if (!$id && ForumThread::find()->where(['author_id'=>$actor->id])->andWhere(['>','created_at',date('Y-m-d H:i:s',time()-60)])->count() >= 5) { throw new TooManyRequestsHttpException('发帖过于频繁。'); }
            $thread->setAttributes(array_intersect_key($input, array_flip(['title','body','category_id','team_id','match_id'])));
            if (!$id) { $thread->author_id=$actor->id; $thread->status='published'; $thread->last_activity_at=date('Y-m-d H:i:s'); }
            if ($thread->save()) { AuditService::record($id?'thread.update':'thread.create','forum_threads',(int)$thread->id); $thread->refresh(); }
            return $thread;
        });
    }

    public function reply(User $actor, int $threadId, array $input): ForumReply
    {
        return Yii::$app->db->transaction(function () use ($actor,$threadId,$input) {
            $thread=$this->lockedThread($threadId);
            if ($thread->status!=='published' || $thread->is_locked) { throw new ForbiddenHttpException('主题已锁定或已隐藏。'); }
            if (ForumReply::find()->where(['user_id'=>$actor->id])->andWhere(['>','created_at',date('Y-m-d H:i:s',time()-60)])->count() >= 20) { throw new TooManyRequestsHttpException('回复过于频繁。'); }
            $reply = new ForumReply(); $reply->setAttributes(array_intersect_key($input,array_flip(['body','parent_id'])));
            $reply->thread_id=$thread->id; $reply->user_id=$actor->id; $reply->status='visible';
            $parent=null;
            if ($reply->parent_id) { $parent=ForumReply::findOne(['id'=>$reply->parent_id,'thread_id'=>$threadId,'status'=>'visible']); if (!$parent) { throw new BadRequestHttpException('引用回复不属于当前主题。'); } }
            if (!$reply->save()) { return $reply; }
            $thread->last_activity_at=date('Y-m-d H:i:s'); $thread->save(false);
            $recipients=array_unique([(int)$thread->author_id,(int)($parent->user_id ?? 0)]);
            foreach ($recipients as $recipient) { if ($recipient && $recipient !== (int)$actor->id) { Yii::$app->db->createCommand()->insert('notifications',['user_id'=>$recipient,'actor_id'=>$actor->id,'thread_id'=>$threadId,'type'=>'reply'])->execute(); } }
            AuditService::record('reply.create','forum_replies',(int)$reply->id);
            return $reply;
        });
    }

    public function remove(User $actor, string $type, int $id): void
    {
        Yii::$app->db->transaction(function () use ($actor,$type,$id) {
            $model=$type==='thread'?$this->lockedThread($id):ForumReply::findOne($id);
            if (!$model) { throw new NotFoundHttpException(); }
            $this->assertOwner($actor,(int)($type==='thread'?$model->author_id:$model->user_id));
            $model->status='deleted'; $model->save(false);
            AuditService::record($type.'.delete',$model::tableName(),$id);
        });
    }

    public function editReply(User $actor,int $id,string $body): ForumReply
    {
        return Yii::$app->db->transaction(function()use($actor,$id,$body) {
            $reply=ForumReply::findOne($id); if(!$reply || $reply->status!=='visible') { throw new NotFoundHttpException(); }
            $this->assertOwner($actor,(int)$reply->user_id); $thread=$this->lockedThread((int)$reply->thread_id);
            if($thread->status!=='published' || ($thread->is_locked && !$actor->isAdmin())) { throw new ForbiddenHttpException('主题当前不可编辑。'); }
            $reply->body=$body; if($reply->save()) { AuditService::record('reply.update','forum_replies',$id); } return $reply;
        });
    }

    public function setCollection(User $actor, int $id, string $kind, bool $enabled): void
    {
        $this->readable($id,$actor);
        $table = match($kind) { 'like'=>'thread_likes','bookmark'=>'thread_bookmarks',default=>throw new BadRequestHttpException() };
        if ($enabled) { Yii::$app->db->createCommand()->upsert($table,['thread_id'=>$id,'user_id'=>$actor->id],false)->execute(); }
        else { Yii::$app->db->createCommand()->delete($table,['thread_id'=>$id,'user_id'=>$actor->id])->execute(); }
    }

    public function report(User $actor,int $id,array $input): ForumReport
    {
        $this->readable($id,$actor);
        $replyId=empty($input['reply_id'])?null:filter_var($input['reply_id'],FILTER_VALIDATE_INT);
        if ($replyId !== null && (!$replyId || !ForumReply::find()->where(['id'=>$replyId,'thread_id'=>$id,'status'=>'visible'])->exists())) { throw new BadRequestHttpException('举报对象不匹配。'); }
        if (ForumReport::find()->where(['reporter_id'=>$actor->id,'thread_id'=>$id,'reply_id'=>$replyId,'status'=>'open'])->exists()) { throw new BadRequestHttpException('该内容已提交举报。'); }
        $report = new ForumReport(['reason'=>$input['reason']??'']);
        $report->reporter_id=$actor->id; $report->thread_id=$id; $report->reply_id=$replyId; $report->status='open'; $report->save();
        return $report;
    }

    public function moderate(User $actor,string $type,int $id,string $operation): void
    {
        if (!$actor->isAdmin()) { throw new ForbiddenHttpException(); }
        Yii::$app->db->transaction(function () use ($actor,$type,$id,$operation) {
            $class=match($type) {'thread'=>ForumThread::class,'reply'=>ForumReply::class,'report'=>ForumReport::class,default=>throw new BadRequestHttpException()};
            $model=$class::findOne($id); if (!$model) { throw new NotFoundHttpException(); }
            if ($type==='thread' && in_array($operation,['lock','unlock','pin','unpin'],true)) { $field=in_array($operation,['lock','unlock'],true)?'is_locked':'is_pinned'; $model->$field=in_array($operation,['lock','pin'],true)?1:0; }
            else { $model->status=match($operation) {'hide'=>'hidden','publish'=>$type==='reply'?'visible':'published','resolve'=>'resolved','dismiss'=>'dismissed',default=>throw new BadRequestHttpException()};
                if ($type==='report') { if (!in_array($operation,['resolve','dismiss'],true)) { throw new BadRequestHttpException(); } $model->reviewed_by=$actor->id; }
                elseif (!in_array($operation,['hide','publish'],true)) { throw new BadRequestHttpException(); }
            }
            $model->save(false); AuditService::record('moderation.'.$operation,$model::tableName(),$id);
        });
    }

    private function lockedThread(int $id): ForumThread
    {
        $row=Yii::$app->db->createCommand('SELECT * FROM forum_threads WHERE id=:id FOR UPDATE',[':id'=>$id])->queryOne();
        if (!$row) { throw new NotFoundHttpException(); }
        $thread=new ForumThread(); ForumThread::populateRecord($thread,$row); return $thread;
    }
    private function assertOwner(User $actor,int $owner): void { if ((int)$actor->id!==$owner && !$actor->isAdmin()) { throw new ForbiddenHttpException('仅作者或管理员可操作。'); } }
}
