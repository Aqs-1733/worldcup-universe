<?php
/** Optional local cache of unchanged official portrait bytes; the database keeps its source URL. */
declare(strict_types=1);
namespace app\services;
final class PortraitAsset
{
    public static function url(?string $source): string
    {
        if(!$source)return '';
        static $manifest=null;
        $manifest??=is_file(ROOT_PATH.'/public/assets/media/portraits/manifest.json')?(json_decode(file_get_contents(ROOT_PATH.'/public/assets/media/portraits/manifest.json'),true)?:[]):[];
        $entry=$manifest[hash('sha256',$source)]??null;$path=$entry['path']??'';
        if(($entry['source_url']??null)===$source&&preg_match('~^/assets/media/portraits/[a-f0-9]{64}\.(png|jpg|webp)$~',$path)&&is_file(ROOT_PATH.'/public'.$path))return $path;
        return $source;
    }
}
