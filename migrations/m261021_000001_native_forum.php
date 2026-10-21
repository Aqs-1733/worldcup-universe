<?php
/** Additive migration: preserve all 34 existing business tables and records. */
declare(strict_types=1);

use yii\db\Migration;

final class m261021_000001_native_forum extends Migration
{
    public function up()
    {
        $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $id = fn() => $this->bigInteger()->unsigned();
        $this->addColumn('users', 'auth_key', $this->string(64)->notNull()->defaultValue('')->comment('Yii2 identity key'));
        $this->addColumn('users', 'status', $this->string(16)->notNull()->defaultValue('active')->comment('active/disabled'));
        $this->addColumn('news_articles', 'status', $this->string(16)->notNull()->defaultValue('published')->comment('draft/published/hidden'));
        $this->createTable('fan_profiles', [
            'user_id' => $id()->notNull(), 'main_team_id' => $id(), 'bio' => $this->string(500),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'PRIMARY KEY(user_id)',
        ], $options);
        $this->addForeignKey('fk_fan_profile_user', 'fan_profiles', 'user_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('fk_fan_profile_team', 'fan_profiles', 'main_team_id', 'teams', 'id', 'SET NULL');
        $this->createTable('forum_categories', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'slug' => $this->string(60)->notNull()->unique(),
            'name' => $this->string(80)->notNull(), 'description' => $this->string(300),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ], $options);
        $this->createTable('forum_threads', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'author_id' => $id()->notNull(),
            'category_id' => $id()->notNull(), 'team_id' => $id(), 'match_id' => $id(),
            'title' => $this->string(160)->notNull(), 'body' => $this->text()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('published'),
            'is_locked' => $this->boolean()->notNull()->defaultValue(false),
            'is_pinned' => $this->boolean()->notNull()->defaultValue(false),
            'views' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'last_activity_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        foreach (['author_id' => 'users', 'category_id' => 'forum_categories', 'team_id' => 'teams', 'match_id' => 'matches'] as $column => $table) {
            $this->addForeignKey('fk_thread_' . $column, 'forum_threads', $column, $table, 'id', in_array($column, ['team_id', 'match_id']) ? 'SET NULL' : 'RESTRICT');
        }
        $this->createIndex('idx_thread_feed', 'forum_threads', ['status', 'is_pinned', 'last_activity_at']);
        $this->createIndex('idx_thread_team', 'forum_threads', ['team_id', 'status']);
        $this->createTable('forum_replies', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'thread_id' => $id()->notNull(),
            'user_id' => $id()->notNull(), 'parent_id' => $id(), 'body' => $this->text()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('visible'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_reply_thread', 'forum_replies', 'thread_id', 'forum_threads', 'id', 'CASCADE');
        $this->addForeignKey('fk_reply_user', 'forum_replies', 'user_id', 'users', 'id', 'RESTRICT');
        $this->addForeignKey('fk_reply_parent', 'forum_replies', 'parent_id', 'forum_replies', 'id', 'SET NULL');
        $this->createIndex('idx_reply_thread_status', 'forum_replies', ['thread_id', 'status', 'id']);
        foreach (['thread_likes', 'thread_bookmarks'] as $table) {
            $this->createTable($table, ['thread_id' => $id()->notNull(), 'user_id' => $id()->notNull(), 'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'), 'PRIMARY KEY(thread_id,user_id)'], $options);
            $this->addForeignKey('fk_' . $table . '_thread', $table, 'thread_id', 'forum_threads', 'id', 'CASCADE');
            $this->addForeignKey('fk_' . $table . '_user', $table, 'user_id', 'users', 'id', 'CASCADE');
        }
        $this->createTable('forum_reports', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'reporter_id' => $id()->notNull(), 'thread_id' => $id()->notNull(),
            'reply_id' => $id(), 'reason' => $this->string(500)->notNull(), 'status' => $this->string(16)->notNull()->defaultValue('open'),
            'reviewed_by' => $id(), 'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        foreach (['reporter_id' => 'users', 'thread_id' => 'forum_threads', 'reply_id' => 'forum_replies', 'reviewed_by' => 'users'] as $c => $t) {
            $this->addForeignKey('fk_report_' . $c, 'forum_reports', $c, $t, 'id', in_array($c, ['reply_id', 'reviewed_by']) ? 'SET NULL' : 'CASCADE');
        }
        $this->createTable('notifications', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'user_id' => $id()->notNull(), 'actor_id' => $id(),
            'thread_id' => $id()->notNull(), 'type' => $this->string(32)->notNull(), 'read_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_notice_user', 'notifications', 'user_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('fk_notice_actor', 'notifications', 'actor_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey('fk_notice_thread', 'notifications', 'thread_id', 'forum_threads', 'id', 'CASCADE');
        $this->createIndex('idx_notice_unread', 'notifications', ['user_id', 'read_at']);
        $this->createTable('match_progressions', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'from_match_id' => $id()->notNull(), 'to_match_id' => $id()->notNull(),
            'outcome' => $this->string(16)->notNull()->defaultValue('winner'), 'slot' => $this->string(8)->notNull(),
            'source_url' => $this->string(800)->notNull(), 'is_verified' => $this->boolean()->notNull()->defaultValue(false),
            'UNIQUE(to_match_id,slot)', 'UNIQUE(from_match_id,outcome)',
        ], $options);
        foreach (['from_match_id', 'to_match_id'] as $c) { $this->addForeignKey('fk_progress_' . $c, 'match_progressions', $c, 'matches', 'id', 'CASCADE'); }
        $this->createTable('media_assets', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'asset_key' => $this->string(80)->notNull()->unique(),
            'path' => $this->string(800)->notNull(), 'alt_text' => $this->string(300)->notNull(),
            'source_url' => $this->string(800)->notNull(), 'license_name' => $this->string(120)->notNull(),
            'credit' => $this->string(300), 'status' => $this->string(16)->notNull()->defaultValue('pending'),
        ], $options);
        $this->createTable('coursework_files', [
            'id' => $this->bigPrimaryKey()->unsigned(), 'scope' => $this->string(16)->notNull(),
            'title' => $this->string(200)->notNull(), 'storage_path' => $this->string(500)->notNull(),
            'is_visible' => $this->boolean()->notNull()->defaultValue(false),
        ], $options);
        $this->batchInsert('forum_categories', ['slug', 'name', 'description', 'sort_order'], [
            ['worldcup', '世界杯广场', '赛事焦点与观赛讨论', 1], ['tactics', '战术研究室', '阵型、技战术与数据分析', 2],
            ['home-team', '主队看台', '属于你的球队讨论区', 3], ['fan-works', '球迷创作', '原创、观赛日记与文化交流', 4],
        ]);
    }

    public function down()
    {
        foreach (['coursework_files', 'media_assets', 'match_progressions', 'notifications', 'forum_reports', 'thread_bookmarks', 'thread_likes', 'forum_replies', 'forum_threads', 'forum_categories', 'fan_profiles'] as $table) { $this->dropTable($table); }
        $this->dropColumn('news_articles', 'status');
        $this->dropColumn('users', 'status');
        $this->dropColumn('users', 'auth_key');
    }
}
