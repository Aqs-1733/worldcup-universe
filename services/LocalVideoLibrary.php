<?php
/**
 * /watch renders only verified CCTV.com pages or local files in this deployment.
 * Local shelf entries are listed only after their file or approved uploaded asset exists.
 */
declare(strict_types=1);
namespace app\services;
final class LocalVideoLibrary
{
    public const DIR=ROOT_PATH.'/public/assets/media/videos';
    private const EXT=['mp4','webm','m4v'];

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $config=is_file(ROOT_PATH.'/config/local-videos.php') ? require ROOT_PATH.'/config/local-videos.php' : [];
        $items=[]; $claimed=['hero-loop.mp4'=>true];
        foreach ($config as $entry) {
            $item=$this->normalize($entry);
            if ($item!==null) { $items[]=$item; $claimed[$item['file']]=true; }
        }
        foreach (is_dir(self::DIR) ? scandir(self::DIR) : [] as $file) {
            if (isset($claimed[$file]) || !in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)),self::EXT,true)) { continue; }
            $item=$this->normalize(['slug'=>'file-'.substr(sha1($file),0,10),'title'=>str_replace(['_','-'],' ',pathinfo($file,PATHINFO_FILENAME)),'type'=>'moment','file'=>$file]);
            if ($item!==null) { $items[]=$item; }
        }
        foreach(\app\models\MediaAsset::find()->where(['status'=>'approved'])->andWhere(['like','path','/uploads/%',false])->andWhere(['<>','asset_key','home-hero-video'])->orderBy(['id'=>SORT_ASC])->all() as $asset) {
            if (!MediaUploadService::isVideo($asset->path)) { continue; }
            $item=$this->normalize(['slug'=>'asset-'.$asset->asset_key,'title'=>$asset->alt_text,'type'=>'moment','url'=>$asset->path,'credit'=>trim($asset->credit.' · '.$asset->license_name,' · '),'source_url'=>$asset->source_url]);
            if ($item!==null) { $items[]=$item; }
        }
        return $items;
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $item) { if ($item['slug']===$slug) { return $item; } }
        return null;
    }

    public static function safeFile(string $name): ?string
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/',$name) || !in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),self::EXT,true)) { return null; }
        $directory=realpath(self::DIR); $path=$directory ? realpath($directory.'/'.$name) : false;
        return $path && is_file($path) && str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$directory).'/') ? $path : null;
    }

    /** @return array<string,mixed>|null */
    private function normalize(array $entry): ?array
    {
        $file=(string)($entry['file']??''); $local=$file!=='' ? self::safeFile($file) : null;
        $uploaded=isset($entry['url']) && MediaUploadService::managedPath((string)$entry['url']) ? (string)$entry['url'] : null;
        if (!$local && !$uploaded) { return null; }
        $poster=$entry['poster']??null;
        if (!$poster && $file!=='') {
            foreach (['jpg','png','webp'] as $extension) {
                $candidate=pathinfo($file,PATHINFO_FILENAME).'.'.$extension;
                if (is_file(self::DIR.'/'.$candidate)) { $poster='/assets/media/videos/'.$candidate; break; }
            }
        }
        return [
            'slug'=>(string)$entry['slug'],'title'=>(string)$entry['title'],'credit'=>$entry['credit']??null,'source_url'=>$entry['source_url']??null,
            'subtitle'=>(string)($entry['subtitle']??''),'type'=>(string)($entry['type']??'moment'),'match_id'=>$entry['match_id']??null,
            'file'=>$file,'src'=>$local ? '/stream/'.rawurlencode($file) : $uploaded,'poster'=>$poster?:'/assets/media/stadium-hero-v2.png','provider'=>'local',
        ];
    }
}
