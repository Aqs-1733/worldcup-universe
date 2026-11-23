<?php
/**
 * 文件作用：后台侧边栏分组配置；每个表名都必须在 config/v2-tables.php 白名单中。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);

return [
    'groups' => [
        ['label' => '赛事数据', 'tables' => ['teams', 'players', 'matches', 'standings', 'match_lineups', 'match_progressions', 'venues', 'country_profiles']],
        ['label' => '影像与媒体', 'tables' => ['match_videos', 'media_assets']],
        ['label' => '新闻', 'tables' => ['news_articles', 'news_sources', 'tags']],
        ['label' => '社区与互动', 'tables' => ['forum_categories', 'forum_threads', 'forum_replies', 'forum_reports', 'comments', 'admin_posts']],
        ['label' => '项目与课程', 'tables' => ['team_members', 'coursework_files']],
        // Course schema tables that no importer fills yet; the public site does not read them.
        ['label' => '预留数据表（暂无数据）', 'tables' => ['match_stats', 'player_statistics', 'match_events', 'tournament_stages', 'coaches', 'referees'], 'reserved' => true],
    ],
];
