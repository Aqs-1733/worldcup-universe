<?php
/** Curated official full-match embeds, distinct from the 2026 match dataset. */
declare(strict_types=1);
use yii\db\Migration;
final class m261021_000005_match_videos extends Migration
{
    public function up()
    {
        $this->createTable('match_videos',[
            'id'=>$this->bigPrimaryKey()->unsigned(),'slug'=>$this->string(140)->notNull()->unique(),
            'title'=>$this->string(220)->notNull(),'tournament_year'=>$this->integer()->notNull(),
            'stage'=>$this->string(60)->notNull(),'home_name'=>$this->string(80)->notNull(),'away_name'=>$this->string(80)->notNull(),
            'home_code'=>$this->char(3)->notNull(),'away_code'=>$this->char(3)->notNull(),
            'provider'=>$this->string(20)->notNull()->defaultValue('youtube'),'provider_id'=>$this->string(80)->notNull(),
            'source_url'=>$this->string(800)->notNull(),'source_author'=>$this->string(80)->notNull(),
            'source_title'=>$this->string(280)->notNull(),'thumbnail_url'=>$this->string(800),
            'video_type'=>$this->string(20)->notNull()->defaultValue('full_match'),'status'=>$this->string(20)->notNull()->defaultValue('draft'),
            'verified_at'=>$this->dateTime(),'created_at'=>$this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'=>$this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Official full-match video catalogue"');
        $this->createIndex('idx_match_video_catalogue','match_videos',['status','tournament_year','video_type']);
    }
    public function down() { $this->dropTable('match_videos'); }
}
