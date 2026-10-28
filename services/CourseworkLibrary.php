<?php
/** Coursework downloads: files registered in the admin "课程交付" table plus any file dropped into data/team or data/personal. */
declare(strict_types=1);
namespace app\services;
use app\models\CourseworkFile;
final class CourseworkLibrary
{
    public const SCOPES=['team'=>'团队作业','personal'=>'个人作业'];
    private const EXT=['pdf','doc','docx','ppt','pptx','zip','rar','7z','md','txt','mp4','xlsx'];

    /** @return array<string,list<array{title:string,url:string,ext:string,size:int,updated:int}>> */
    public function grouped(): array
    {
        $out=array_fill_keys(array_keys(self::SCOPES),[]);$seen=[];
        foreach(CourseworkFile::find()->where(['is_visible'=>1])->orderBy(['id'=>SORT_ASC])->all() as $file){
            $path=$file->resolvedPath();if(!$path||!self::allowed(basename($path)))continue;
            $seen[str_replace('\\','/',$path)]=true;
            $out[$file->scope][]=$this->item($file->title,'/downloads/'.(int)$file->id,$path);
        }
        foreach(array_keys(self::SCOPES) as $scope){
            $dir=ROOT_PATH.'/data/'.$scope;
            foreach(is_dir($dir)?scandir($dir):[] as $name){
                $path=$dir.'/'.$name;
                if(!self::allowed($name)||!is_file($path)||isset($seen[str_replace('\\','/',realpath($path))]))continue;
                $out[$scope][]=$this->item(pathinfo($name,PATHINFO_FILENAME),'/downloads/'.$scope.'/'.rawurlencode($name),$path);
            }
        }
        return $out;
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
        return $base&&$path&&is_file($path)&&str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/')?$path:null;
    }
    private function item(string $title,string $url,string $path): array
    {
        return ['title'=>$title,'url'=>$url,'ext'=>strtoupper(pathinfo($path,PATHINFO_EXTENSION)),'size'=>(int)filesize($path),'updated'=>(int)filemtime($path)];
    }
}
