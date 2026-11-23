<?php
/**
 * 文件作用：模型：forum_threads 表（论坛主题），含标题、正文校验以及作者、板块、球队关联。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\models;
class ForumThread extends Record
{
    // 列表查询时附带算出的回复数、点赞数
    public $reply_count=0;
    public $like_count=0;
    public static function tableName(): string { return '{{%forum_threads}}'; }
    // 标题 3~160 字、正文 5~20000 字；板块必填，球队和比赛可选但必须存在
    public function rules(): array
    {
        return [[['title','body'],'trim'], [['title','body','category_id'],'required'], ['title','string','min'=>3,'max'=>160], ['body','string','min'=>5,'max'=>20000], [['team_id','match_id'],'default','value'=>null], [['category_id','team_id','match_id'],'integer'], ['category_id','exist','targetClass'=>ForumCategory::class,'targetAttribute'=>'id'], ['team_id','exist','targetClass'=>Team::class,'targetAttribute'=>'id'], ['match_id','exist','targetClass'=>MatchGame::class,'targetAttribute'=>'id']];
    }
    // 标题以【示例】开头的是示例主题
    public function getIsDemo(): bool { return str_starts_with($this->title,'【示例】'); }
    public function getDisplayTitle(): string { return $this->isDemo?mb_substr($this->title,4):$this->title; }
    // 关联：作者、板块、球队、比赛、回复、点赞、收藏
    public function getAuthor() { return $this->hasOne(User::class,['id'=>'author_id']); }
    public function getCategory() { return $this->hasOne(ForumCategory::class,['id'=>'category_id']); }
    public function getTeam() { return $this->hasOne(Team::class,['id'=>'team_id']); }
    public function getMatch() { return $this->hasOne(MatchGame::class,['id'=>'match_id']); }
    public function getReplies() { return $this->hasMany(ForumReply::class,['thread_id'=>'id'])->where(['status'=>'visible']); }
    public function getLikes() { return $this->hasMany(ThreadLike::class,['thread_id'=>'id']); }
    public function getBookmarks() { return $this->hasMany(ThreadBookmark::class,['thread_id'=>'id']); }
}
