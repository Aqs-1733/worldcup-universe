<?php
/** Upload storage maintenance: readiness check and dry-run-first cleanup of files no record points to. */
declare(strict_types=1);
namespace app\commands;
use app\services\MediaUploadService;
use Yii;
use yii\console\Controller;
final class MediaController extends Controller
{
    /** @var bool delete orphaned files instead of only listing them */
    public $apply=false;
    public function options($actionID): array { return $actionID==='prune'?['apply']:[]; }

    /** Verifies PHP extensions, upload limits and writable storage directories. */
    public function actionCheck(): int
    {
        $ok=true;
        foreach(['fileinfo','pdo_mysql','mbstring','curl','simplexml'] as $ext) { $has=extension_loaded($ext); $ok=$ok&&$has; $this->stdout(($has?'PASS':'FAIL')." php extension $ext\n"); }
        $this->stdout('INFO upload_max_filesize='.ini_get('upload_max_filesize').' post_max_size='.ini_get('post_max_size')." (CLI values; PHP-FPM uses its pool settings)\n");
        foreach([MediaUploadService::publicDir(),ROOT_PATH.'/data/team/'.MediaUploadService::COURSEWORK_DIR,ROOT_PATH.'/data/personal/'.MediaUploadService::COURSEWORK_DIR,ROOT_PATH.'/storage/runtime',ROOT_PATH.'/storage/logs'] as $dir) {
            if(!is_dir($dir)) { @mkdir($dir,0775,true); }
            $writable=is_dir($dir)&&is_writable($dir); $ok=$ok&&$writable; $this->stdout(($writable?'PASS':'FAIL')." writable $dir\n");
        }
        return $ok?0:1;
    }

    /**
     * Lists upload files that no database record references; --apply deletes them. Files younger than one hour are skipped.
     * Every text column of every table is searched (not only the upload fields), so a path an administrator pasted
     * into an article body or another URL field still counts as used. audit_logs only records history and is ignored.
     */
    public function actionPrune(): int
    {
        $referenced=[]; $db=Yii::$app->db; $db->schema->refresh();
        // A wrong DB_DATABASE (or a half-restored one) would make every file look unused: refuse instead of guessing.
        foreach(['media_assets','coursework_files','players','team_members'] as $required) {
            if($db->schema->getTableSchema($required)===null) { $this->stderr("Table $required not found in {$db->dsn}; nothing done.\n"); return 1; }
        }
        // An installed site always has the built-in media slots and the squad data; empty tables mean a wrong or blank database.
        foreach(['media_assets','players'] as $required) {
            if(!(new \yii\db\Query())->from($required)->exists($db)) { $this->stderr("Table $required is empty in {$db->dsn}; refusing to judge which uploads are used.\n"); return 1; }
        }
        if($this->apply && is_file(ROOT_PATH.'/storage/runtime/maintenance.flag')) { $this->stderr("Maintenance mode is on (restore in progress); nothing deleted.\n"); return 1; }
        foreach($db->schema->getTableSchemas() as $table) {
            if(in_array($table->name,['audit_logs','migration'],true)) { continue; }
            foreach($table->columns as $column) {
                if(!in_array($column->type,['string','text','json','char'],true)) { continue; }
                foreach((new \yii\db\Query())->select($column->name)->from($table->name)->where(['like',$column->name,'uploads'])->column() as $value) {
                    preg_match_all('~/uploads/\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]{2,4}|data/(?:team|personal)/uploads/[a-f0-9]{32}\.[a-z0-9]{2,4}~',str_replace('\\/','/',(string)$value),$m);
                    foreach($m[0] as $path) { $referenced[$path]=true; }
                }
            }
        }
        $candidates=[];
        $public=MediaUploadService::publicDir();
        if(is_dir($public)) { foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($public,\FilesystemIterator::SKIP_DOTS)) as $file) { $candidates[]=MediaUploadService::PUBLIC_URL.'/'.ltrim(str_replace('\\','/',substr($file->getPathname(),strlen($public))),'/'); } }
        foreach(['team','personal'] as $scope) { foreach(glob(ROOT_PATH.'/data/'.$scope.'/'.MediaUploadService::COURSEWORK_DIR.'/*')?:[] as $path) { $candidates[]='data/'.$scope.'/'.MediaUploadService::COURSEWORK_DIR.'/'.basename($path); } }
        $orphans=[];
        foreach($candidates as $value) {
            $path=MediaUploadService::managedPath($value);
            if(!$path) { continue; }
            if(isset($referenced[$value]) || filemtime($path)>time()-3600) { continue; }
            $orphans[$value]=$path;
        }
        foreach($orphans as $value=>$path) {
            $this->stdout(($this->apply?'DELETE ':'ORPHAN ').$value."\n");
            if($this->apply) { unlink($path); }
        }
        $this->stdout(sprintf("%d referenced, %d orphaned%s.\n",count($referenced),count($orphans),$this->apply||!$orphans?'':' (dry run; add --apply to delete)'));
        return 0;
    }
}
