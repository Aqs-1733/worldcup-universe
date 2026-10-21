<?php
/** Topic record. Public input is narrowed further by ForumService. */
declare(strict_types=1);
namespace app\models;
class ForumThread extends Record
{
    public $reply_count=0;
    public $like_count=0;
    public static function tableName(): string { return '{{%forum_threads}}'; }
    public function rules(): array
    {
        return [[['title','body'],'trim'], [['title','body','category_id'],'required'], ['title','string','min'=>3,'max'=>160], ['body','string','min'=>5,'max'=>20000], [['team_id','match_id'],'default','value'=>null], [['category_id','team_id','match_id'],'integer'], ['category_id','exist','targetClass'=>ForumCategory::class,'targetAttribute'=>'id'], ['team_id','exist','targetClass'=>Team::class,'targetAttribute'=>'id'], ['match_id','exist','targetClass'=>MatchGame::class,'targetAttribute'=>'id']];
    }
    public function getAuthor() { return $this->hasOne(User::class,['id'=>'author_id']); }
    public function getCategory() { return $this->hasOne(ForumCategory::class,['id'=>'category_id']); }
    public function getTeam() { return $this->hasOne(Team::class,['id'=>'team_id']); }
    public function getMatch() { return $this->hasOne(MatchGame::class,['id'=>'match_id']); }
    public function getReplies() { return $this->hasMany(ForumReply::class,['thread_id'=>'id'])->where(['status'=>'visible']); }
    public function getLikes() { return $this->hasMany(ThreadLike::class,['thread_id'=>'id']); }
    public function getBookmarks() { return $this->hasMany(ThreadBookmark::class,['thread_id'=>'id']); }
}
