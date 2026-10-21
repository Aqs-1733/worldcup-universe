<?php
/** Course schema documentation: annotate only tables without existing comments. */
declare(strict_types=1);
use yii\db\Migration;
final class m261021_000003_table_comments extends Migration
{
    private function labels(): array
    {
        return ['users'=>'用户与Yii2身份','roles'=>'系统角色','user_roles'=>'用户角色关联','teams'=>'世界杯球队','players'=>'球队球员','matches'=>'世界杯比赛快照','standings'=>'小组积分快照','match_stats'=>'比赛统计快照','player_statistics'=>'球员赛事统计','match_lineups'=>'比赛阵容','match_events'=>'比赛事件','country_profiles'=>'国家探索资料','team_members'=>'课程项目团队成员','fan_profiles'=>'单一主队与球迷简介','forum_categories'=>'球迷论坛板块','forum_threads'=>'球迷论坛主题','forum_replies'=>'球迷主题回复','forum_reports'=>'球迷内容举报审核','thread_likes'=>'主题点赞唯一关联','thread_bookmarks'=>'用户私人主题收藏','notifications'=>'用户回复通知','match_progressions'=>'具有来源的显式晋级关系','media_assets'=>'媒体来源许可登记','coursework_files'=>'团队个人作业下载登记','audit_logs'=>'只读操作审计','news_articles'=>'有来源的新闻内容','news_sources'=>'新闻RSS来源','comments'=>'业务页面审核留言'];
    }
    public function up()
    {
        foreach($this->db->schema->getTableNames() as $table) {
            if($table==='migration') {continue;}
            $comment='WCU: '.($this->labels()[$table]??$table.'业务数据');
            $current=$this->db->createCommand('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table',[':table'=>$table])->queryScalar();
            if($current==='') { $this->execute('ALTER TABLE '.$this->db->quoteTableName($table).' COMMENT='.$this->db->quoteValue($comment)); }
        }
    }
    public function down()
    {
        foreach($this->db->schema->getTableNames() as $table) {
            if($table==='migration') {continue;}
            $expected='WCU: '.($this->labels()[$table]??$table.'业务数据');
            $current=$this->db->createCommand('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table',[':table'=>$table])->queryScalar();
            if($current===$expected) { $this->execute('ALTER TABLE '.$this->db->quoteTableName($table)." COMMENT=''"); }
        }
    }
}
