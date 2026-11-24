<?php
/**
 * 文件作用：课程作业下载：列出 data/team、data/personal 下的文件和后台登记的作业，并校验下载路径。
 */
declare(strict_types=1);
namespace app\services;
use app\models\CourseworkFile;
final class CourseworkLibrary
{
    public const SCOPES=['team'=>'团队作业','personal'=>'个人作业'];
    public const EXT=['pdf','doc','docx','ppt','pptx','zip','rar','7z','md','txt','mp4','xlsx'];

    /** @return array<string,list<array{title:string,url:string,ext:string,size:int,updated:int,student:string}>> */
    public function grouped(): array
    {
        $out=array_fill_keys(array_keys(self::SCOPES),[]);$seen=self::hidden();
        foreach(CourseworkFile::find()->where(['is_visible'=>1])->orderBy(['id'=>SORT_ASC])->all() as $file){
            $path=$file->resolvedPath();if(!$path||!self::allowed(basename($path)))continue;
            $seen[str_replace('\\','/',$path)]=true;
            $out[$file->scope][]=$this->item($file->title,'/downloads/'.(int)$file->id,$path);
        }
        foreach(array_keys(self::SCOPES) as $scope){
            $dir=ROOT_PATH.'/data/'.$scope;
            foreach(is_dir($dir)?scandir($dir):[] as $name){
                $path=$dir.'/'.$name;
                if(!self::allowed($name)||!is_file($path)||isset($seen[str_replace('\\','/',realpath($path))])||self::isRemoved($scope,$name))continue;
                $out[$scope][]=$this->item(pathinfo($name,PATHINFO_FILENAME),'/downloads/'.$scope.'/'.rawurlencode($name),$path);
            }
        }
        return $out;
    }
    /**
     * 部署时调用：把 data/team、data/personal 目录里（随 GitHub 提交的）还没登记的作业文件登记到“课程交付”表，
     * 并删除指向这两个目录、但文件已经不存在的登记。后台上传的文件（uploads/ 子目录）和管理员设置的“前台可见”不会被改动。
     * @return array{added:int,removed:int}
     */
    public static function syncFolder(): array
    {
        $added=0;$removed=0;
        foreach(array_keys(self::SCOPES) as $scope){
            // 1. 文件已从仓库删除（或不是作业文件，如 README.md）的登记：删除
            foreach(CourseworkFile::find()->where(['scope'=>$scope])->all() as $file){
                $name=substr((string)$file->storage_path,strlen('data/'.$scope.'/'));
                if(str_starts_with((string)$file->storage_path,'data/'.$scope.'/uploads/')||str_contains($name,'/'))continue;
                if(!self::allowed($name)||!is_file(ROOT_PATH.'/data/'.$scope.'/'.$name)){$file->delete();$removed++;}
            }
            // 2. 目录里有、表里没有的文件：新增登记，标题用文件名，默认前台可见
            $dir=ROOT_PATH.'/data/'.$scope;
            foreach(is_dir($dir)?scandir($dir):[] as $name){
                if(!self::allowed($name)||!is_file($dir.'/'.$name)||self::isRemoved($scope,$name))continue;
                $path='data/'.$scope.'/'.$name;
                if(CourseworkFile::find()->where(['storage_path'=>$path])->exists())continue;
                $file=new CourseworkFile(['scope'=>$scope,'title'=>mb_substr(pathinfo($name,PATHINFO_FILENAME),0,200),'storage_path'=>$path,'is_visible'=>1]);
                if(!$file->save())throw new \RuntimeException($path.': '.json_encode($file->errors,JSON_UNESCAPED_UNICODE));
                $added++;
            }
        }
        return ['added'=>$added,'removed'=>$removed];
    }

    /**
     * 后台删除课程交付后调用，让删除真正生效：
     * - 后台上传的文件（uploads/ 子目录）：连同文件一起删除；
     * - 随 GitHub 仓库提交的文件：服务器上不能改仓库（否则自动更新会拒绝），所以把文件名和内容哈希记到
     *   data/<scope>/.removed.json，团队页、下载链接和部署同步都会跳过它；仓库里换成新版本（内容不同）时会重新出现。
     */
    public static function remove(CourseworkFile $file): void
    {
        $path=(string)$file->storage_path;
        if(str_starts_with($path,'data/'.$file->scope.'/uploads/')){(new MediaUploadService())->discard($path);return;}
        $name=basename($path);$full=ROOT_PATH.'/data/'.$file->scope.'/'.$name;
        if(!isset(self::SCOPES[$file->scope])||!is_file($full))return;
        $list=self::removedList($file->scope);$list[$name]=hash_file('sha256',$full);
        file_put_contents(self::removedFile($file->scope),json_encode($list,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);
    }
    /** 这个仓库文件是否已在后台删除（文件内容和删除时一样） */
    public static function isRemoved(string $scope,string $name): bool
    {
        $hash=self::removedList($scope)[$name]??null;$full=ROOT_PATH.'/data/'.$scope.'/'.$name;
        return $hash!==null&&is_file($full)&&hash_file('sha256',$full)===$hash;
    }
    private static function removedFile(string $scope): string { return ROOT_PATH.'/data/'.$scope.'/.removed.json'; }
    private static function removedList(string $scope): array
    {
        $f=self::removedFile($scope);
        $list=is_file($f)?json_decode((string)file_get_contents($f),true):[];
        return is_array($list)?$list:[];
    }
    public static function allowed(string $name): bool
    {
        // Folder READMEs are instructions for the team, not coursework.
        return $name!==''&&$name[0]!=='.'&&strcasecmp($name,'README.md')!==0&&!str_contains($name,'..')&&!preg_match('~[\\\\/]~',$name)&&in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),self::EXT,true);
    }
    public static function resolve(string $scope,string $name): ?string
    {
        if(!isset(self::SCOPES[$scope])||!self::allowed($name))return null;
        $base=realpath(ROOT_PATH.'/data/'.$scope);$path=realpath(ROOT_PATH.'/data/'.$scope.'/'.$name);
        if(!$base||!$path||!is_file($path)||!str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/'))return null;
        if(self::isRemoved($scope,basename($path)))return null;
        // A file the administrator registered and then hid stays private even though it sits in the folder.
        return isset(self::hidden()[str_replace('\\','/',$path)])?null:$path;
    }
    /** Real paths of registered coursework files whose record is not visible. */
    private static function hidden(): array
    {
        $out=[];
        foreach(CourseworkFile::find()->where(['is_visible'=>0])->all() as $file){if($path=$file->resolvedPath())$out[str_replace('\\','/',$path)]=true;}
        return $out;
    }
    private function item(string $title,string $url,string $path): array
    {
        // 从标题或文件名里找 7 位学号，团队页据此把个人作业归到对应成员名下
        $student=preg_match('/(?<!\d)(\d{7})(?!\d)/',$title.' '.basename($path),$m)?$m[1]:'';
        return ['title'=>$title,'url'=>$url,'ext'=>strtoupper(pathinfo($path,PATHINFO_EXTENSION)),'size'=>(int)filesize($path),'updated'=>(int)filemtime($path),'student'=>$student];
    }
}
