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
        $poster=$e['poster']??null;
        if(!$poster&&$file){foreach(['jpg','png','webp'] as $ext){$p=pathinfo($file,PATHINFO_FILENAME).'.'.$ext;if(is_file(self::DIR.'/'.$p)){$poster='/assets/media/videos/'.$p;break;}}}
        $bilibili=isset($e['bilibili'])&&preg_match('/^BV[0-9A-Za-z]{10}$/',(string)$e['bilibili'])?$e['bilibili']:null;
        $youtube=isset($e['youtube'])&&preg_match('/^[A-Za-z0-9_-]{11}$/',(string)$e['youtube'])?$e['youtube']:null;
        $provider=$local?'local':($bilibili?'bilibili':($youtube?'youtube':'missing'));
        return [
            'slug'=>$e['slug'],'title'=>$e['title'],'subtitle'=>$e['subtitle']??'','type'=>$e['type']??'highlights','match_id'=>$e['match_id']??null,
            'file'=>$file,'src'=>$local?'/stream/'.rawurlencode($file):null,'poster'=>$poster?:'/assets/media/stadium-hero-v2.png',
            'provider'=>$provider,'bilibili'=>$bilibili,'youtube'=>$youtube,'source_url'=>$e['source_url']??($youtube?'https://www.youtube.com/watch?v='.$youtube:null),
            'embed'=>match($provider){'bilibili'=>'https://player.bilibili.com/player.html?bvid='.$bilibili.'&autoplay=1&high_quality=1&danmaku=0','youtube'=>'https://www.youtube-nocookie.com/embed/'.$youtube.'?autoplay=1&rel=0&playsinline=1&modestbranding=1',default=>null},
        ];
    }
}
