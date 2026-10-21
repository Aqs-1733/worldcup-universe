<?php
/** WorldCup Universe: explicit model registry for native Yii2 administration. */
declare(strict_types=1);

return [
    'teams' => ['球队', app\models\Team::class],
    'players' => ['球员', app\models\Player::class],
    'matches' => ['比赛', app\models\MatchGame::class],
    'standings' => ['积分榜', app\models\Standing::class],
    'match_stats' => ['比赛统计', app\models\MatchStat::class],
    'player_statistics' => ['球员统计', app\models\PlayerStatistic::class],
    'match_events' => ['比赛事件', app\models\MatchEvent::class],
    'match_lineups' => ['比赛阵容', app\models\MatchLineup::class],
    'tournament_stages' => ['赛事阶段', app\models\TournamentStage::class],
    'venues' => ['场馆', app\models\Venue::class],
    'coaches' => ['教练', app\models\Coach::class],
    'referees' => ['裁判', app\models\Referee::class],
    'country_profiles' => ['国家档案', app\models\CountryProfile::class],
    'news_articles' => ['新闻', app\models\NewsArticle::class],
    'news_sources' => ['新闻来源', app\models\NewsSource::class],
    'tags' => ['新闻标签', app\models\Tag::class],
    'team_members' => ['项目团队', app\models\TeamMember::class],
    'admin_posts' => ['公告', app\models\AdminPost::class],
    'comments' => ['业务留言', app\models\Comment::class],
    'forum_categories' => ['论坛板块', app\models\ForumCategory::class],
    'forum_threads' => ['论坛主题', app\models\ForumThread::class],
    'forum_replies' => ['论坛回复', app\models\ForumReply::class],
    'forum_reports' => ['论坛举报', app\models\ForumReport::class],
    'media_assets' => ['媒体资产', app\models\MediaAsset::class],
    'coursework_files' => ['课程交付', app\models\CourseworkFile::class],
    'match_progressions' => ['经核验的晋级关系', app\models\MatchProgression::class],
    'match_videos' => ['官方比赛回放', app\models\MatchVideo::class],
];
