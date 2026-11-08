<?php
/** Personal-use video shelf: local files in public/assets/media/videos plus optional Bilibili/YouTube embeds. */
declare(strict_types=1);
namespace app\services;
final class LocalVideoLibrary
{
    public const DIR=ROOT_PATH.'/public/assets/media/videos';
    private const EXT=['mp4','webm','m4v'];

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $config=is_file(ROOT_PATH.'/config/local-videos.php')?require ROOT_PATH.'/config/local-videos.php':[];
        $items=[];$claimed=['hero-loop.mp4'=>true];
        foreach($config as $entry){$item=$this->normalize($entry);$items[]=$item;if(!empty($entry['file']))$claimed[$entry['file']]=true;}
        // Any other file dropped into the folder shows up automatically, titled by its file name.
        foreach(is_dir(self::DIR)?scandir(self::DIR):[] as $file){
            if(isset($claimed[$file])||!in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)),self::EXT,true))continue;
            $items[]=$this->normalize(['slug'=>'file-'.substr(sha1($file),0,10),'title'=>str_replace(['_','-'],' ',pathinfo($file,PATHINFO_FILENAME)),'type'=>'moment','file'=>$file]);
        }
        // Videos an administrator uploaded and approved under 媒体资产 (home-hero-video is the home page loop, not a shelf item).
        foreach(\app\models\MediaAsset::find()->where(['status'=>'approved'])->andWhere(['like','path','/uploads/%',false])->andWhere(['<>','asset_key','home-hero-video'])->orderBy(['id'=>SORT_ASC])->all() as $asset){
            if(!MediaUploadService::isVideo($asset->path))continue;
            $items[]=$this->normalize(['slug'=>'asset-'.$asset->asset_key,'title'=>$asset->alt_text,'type'=>'moment','url'=>$asset->path,'credit'=>trim($asset->credit.' · '.$asset->license_name,' ·'),'source_url'=>$asset->source_url]);
        }
        return $items;
    }
    public function find(string $slug): ?array { foreach($this->all() as $item){if($item['slug']===$slug)return $item;} return null; }
    public static function safeFile(string $name): ?string
    {
        if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/',$name)||!in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)),self::EXT,true))return null;
        $path=realpath(self::DIR.'/'.$name);
        return $path&&is_file($path)&&str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',realpath(self::DIR)).'/')?$path:null;
    }
    private function normalize(array $e): array
    {
        $file=$e['file']??null;$local=$file?self::safeFile($file):null;
        $uploaded=isset($e['url'])&&MediaUploadService::managedPath((string)$e['url'])?(string)$e['url']:null;
        $poster=$e['poster']??null;
        if(!$poster&&$file){foreach(['jpg','png','webp'] as $ext){$p=pathinfo($file,PATHINFO_FILENAME).'.'.$ext;if(is_file(self::DIR.'/'.$p)){$poster='/assets/media/videos/'.$p;break;}}}
        $bilibili=isset($e['bilibili'])&&preg_match('/^BV[0-9A-Za-z]{10}$/',(string)$e['bilibili'])?$e['bilibili']:null;
        $youtube=isset($e['youtube'])&&preg_match('/^[A-Za-z0-9_-]{11}$/',(string)$e['youtube'])?$e['youtube']:null;
        $provider=$local||$uploaded?'local':($bilibili?'bilibili':($youtube?'youtube':'missing'));
        // config/video-playback.php records YouTube videos whose embedded player was tested and does not play: those show the
        // cover with a link to the official page instead of an iframe that would only show an error.
        $playback=$provider==='youtube'&&is_file(ROOT_PATH.'/config/video-playback.php')?((require ROOT_PATH.'/config/video-playback.php')['youtube:'.$youtube]??[]):[];
        $external=!empty($playback['external']);
        return [
            'slug'=>$e['slug'],'title'=>$e['title'],'credit'=>$e['credit']??null,'commons'=>!empty($e['commons']),'subtitle'=>$e['subtitle']??'','type'=>$e['type']??'highlights','match_id'=>$e['match_id']??null,
            'file'=>$file,'src'=>$local?'/stream/'.rawurlencode($file):$uploaded,'poster'=>$poster?:'/assets/media/stadium-hero-v2.png',
            'provider'=>$provider,'bilibili'=>$bilibili,'youtube'=>$youtube,'source_url'=>$e['source_url']??($youtube?'https://www.youtube.com/watch?v='.$youtube:null),
            'embed'=>match($provider){'bilibili'=>'https://player.bilibili.com/player.html?bvid='.$bilibili.'&autoplay=1&high_quality=1&danmaku=0','youtube'=>$external?null:'https://www.youtube.com/embed/'.$youtube.'?autoplay=1&rel=0&playsinline=1',default=>null},
            'external'=>$external,'notice'=>$playback['notice']??null,
        ];
    }
}
