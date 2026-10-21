<?php
/** Full install and migrate down/up in an explicitly dedicated restore database. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
if(!str_starts_with((string)env('DB_DATABASE'),'wcu_restore_')) { fwrite(STDERR,"Use a dedicated DB_DATABASE beginning wcu_restore_.\n");exit(1); }
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
if($app->runAction('app/install')!==0) {exit(1);}
$before=[];foreach(['teams','players','matches'] as $table) { $before[$table]=(int)(new yii\db\Query())->from($table)->count(); }
if ($before!==['teams'=>48,'players'=>1248,'matches'=>104]) { throw new RuntimeException('Bundled sports restore count mismatch'); }
$password=$app->db->createCommand('SELECT password_hash FROM users ORDER BY id LIMIT 1')->queryScalar();
if($app->runAction('migrate/down',[5,'interactive'=>false])!==0) {exit(1);}
$app->db->schema->refresh();
if(count($app->db->schema->getTableNames())!==35) { throw new RuntimeException('Expected 34 original tables plus migration history after rollback'); }
foreach($before as $table=>$count) { if((int)(new yii\db\Query())->from($table)->count()!==$count) { throw new RuntimeException('Original sports records changed'); } }
if($app->db->createCommand('SELECT password_hash FROM users ORDER BY id LIMIT 1')->queryScalar()!==$password) { throw new RuntimeException('Original credentials changed'); }
echo "ROLLBACK PASS: original 34 tables, sports records and password hash preserved.\n";
if($app->runAction('migrate/up',['interactive'=>false])!==0) {exit(1);}
$app->db->schema->refresh();
if(count($app->db->schema->getTableNames())!==48) { throw new RuntimeException('Expected 48 tables after migration reapply'); }
foreach($before as $table=>$count) { if((int)(new yii\db\Query())->from($table)->count()!==$count) { throw new RuntimeException('Reapply changed original data'); } }
if($app->runAction('fifa/import',[ROOT_PATH.'/data/official/20261021'])!==0) {exit(1);}
echo 'PASS schema roundtrip: '.json_encode($before)."; upgraded tables=48; restored original tables=34.\n";
