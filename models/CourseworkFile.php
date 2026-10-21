<?php
/** Only regular files under data/team or data/personal can be downloaded. */
declare(strict_types=1);
namespace app\models;
class CourseworkFile extends Record
{
    public static function tableName(): string { return '{{%coursework_files}}'; }
    public function rules(): array { return [[['scope','title','storage_path'],'required'], ['scope','in','range'=>['team','personal']], ['title','string','max'=>200], ['storage_path','string','max'=>500], ['is_visible','boolean'], ['storage_path','validateStorage']]; }
    public function validateStorage(string $attribute): void { if (!preg_match('~^data/(team|personal)/[^\\\\]+$~u',$this->$attribute) || str_contains($this->$attribute,'..') || !str_starts_with($this->$attribute,'data/'.$this->scope.'/')) { $this->addError($attribute,'文件须位于对应课程交付目录。'); } }
    public function resolvedPath(): ?string
    {
        $base=realpath(\Yii::getAlias('@app/data/'.$this->scope)); $path=realpath(\Yii::getAlias('@app/'.$this->storage_path));
        if (!$base || !$path || !is_file($path) || !str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/')) { return null; }
        return $path;
    }
}
