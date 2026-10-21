<?php
/** Official-source provenance is additive; legacy snapshots remain quarantined. */
declare(strict_types=1);
use yii\db\Migration;
final class m261021_000004_official_sources extends Migration
{
    public function up()
    {
        $this->createTable('official_imports',[
            'id'=>$this->bigPrimaryKey()->unsigned(),'provider'=>$this->string(40)->notNull(),
            'manifest_sha256'=>$this->char(64)->notNull()->unique(),'snapshot_path'=>$this->string(500)->notNull(),
            'manifest_json'=>$this->json()->notNull(),'report_json'=>$this->json(),
            'imported_at'=>$this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ],'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Official FIFA ingestion evidence"');
        foreach(['teams','players','matches','standings','match_lineups','match_stats','match_events','player_statistics'] as $table) {
            $this->addColumn($table,'official_import_id',$this->bigInteger()->unsigned()->comment('Null = unverified legacy data, never public'));
            $this->addForeignKey('fk_'.$table.'_official',$table,'official_import_id','official_imports','id','RESTRICT');
        }
        $this->addColumn('matches','match_number',$this->integer());
        $this->addColumn('matches','winner_fifa_id',$this->string(80));
        $this->addColumn('matches','placeholder_home',$this->string(80));
        $this->addColumn('matches','placeholder_away',$this->string(80));
    }
    public function down()
    {
        foreach(['match_number','winner_fifa_id','placeholder_home','placeholder_away'] as $column) { $this->dropColumn('matches',$column); }
        foreach(['teams','players','matches','standings','match_lineups','match_stats','match_events','player_statistics'] as $table) {
            $this->dropForeignKey('fk_'.$table.'_official',$table);$this->dropColumn($table,'official_import_id');
        }
        $this->dropTable('official_imports');
    }
}
