<?php
/** Register the credited venue photograph and member write-rate indexes. */
declare(strict_types=1);
use yii\db\Migration;
final class m261021_000002_presentation_and_indexes extends Migration
{
    public function safeUp()
    {
        $this->createIndex('idx_thread_author_time','forum_threads',['author_id','created_at']);
        $this->createIndex('idx_reply_user_time','forum_replies',['user_id','created_at']);
        $this->insert('media_assets',[
            'asset_key'=>'home-hero','path'=>'/assets/media/photos/display/azteca.jpg',
            'alt_text'=>'阿兹特克体育场全景',
            'source_url'=>'https://commons.wikimedia.org/wiki/File:Panor%C3%A1mica_Estadio_Azteca.jpg',
            'license_name'=>'CC BY-SA 4.0','credit'=>'Luicheto; MediaWiki 1280px preview; CSS crop and overlay','status'=>'approved',
        ]);
        foreach(['team'=>'data/team/README.md','personal'=>'data/personal/README.md'] as $scope=>$path) {
            $this->insert('coursework_files',['scope'=>$scope,'title'=>$scope==='team'?'团队课程交付说明（开发版）':'个人作业收集说明','storage_path'=>$path,'is_visible'=>1]);
        }
    }
    public function safeDown()
    {
        // InnoDB can replace an implicit FK index with our composite index.
        // Restore a single-column FK index before dropping the composite one.
        foreach(['forum_replies'=>['user_id','idx_reply_user_fk'],'forum_threads'=>['author_id','idx_thread_author_fk']] as $table=>[$column,$index]) {
            $exists=$this->db->createCommand('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND INDEX_NAME=:i',[':t'=>$table,':i'=>$index])->queryScalar();
            if(!$exists) { $this->createIndex($index,$table,[$column]); }
        }
        $this->delete('coursework_files',['storage_path'=>['data/team/README.md','data/personal/README.md']]);
        $this->delete('media_assets',['asset_key'=>'home-hero']);
        $this->dropIndex('idx_reply_user_time','forum_replies');
        $this->dropIndex('idx_thread_author_time','forum_threads');
    }
}
