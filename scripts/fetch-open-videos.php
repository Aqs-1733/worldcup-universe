<?php
/**
 * Downloads the openly licensed clips listed in config/local-videos.php (entries with a 'commons' title)
 * from Wikimedia Commons into public/assets/media/videos/, plus a poster frame for each.
 * Usage: php scripts/fetch-open-videos.php [--force]
 */
declare(strict_types=1);
const ROOT_PATH=__DIR__.'/..';
$dir=ROOT_PATH.'/public/assets/media/videos';
$force=in_array('--force',$argv,true);
$entries=array_filter(require ROOT_PATH.'/config/local-videos.php',static fn($e)=>!empty($e['commons'])&&!empty($e['file']));
if(!$entries){echo "No Commons entries in config/local-videos.php\n";exit(0);}
$get=static function(string $url,?string $to=null){
    $ch=curl_init($url);$fh=$to?fopen($to.'.part','wb'):null;
    curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>true,CURLOPT_USERAGENT=>'WorldCupUniverse-CourseProject/2.0 (coursework; open-licence media fetch)',CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>900,CURLOPT_FAILONERROR=>true]+(PHP_OS_FAMILY==='Windows'&&defined('CURLSSLOPT_NATIVE_CA')?[CURLOPT_SSL_OPTIONS=>CURLSSLOPT_NATIVE_CA]:[])+($fh?[CURLOPT_FILE=>$fh]:[CURLOPT_RETURNTRANSFER=>true]));
    $body=curl_exec($ch);$err=curl_error($ch);curl_close($ch);
    if($fh){fclose($fh);if($err){@unlink($to.'.part');throw new RuntimeException($err);}rename($to.'.part',$to);return true;}
    if($body===false)throw new RuntimeException($err);return $body;
};
$titles=implode('|',array_map(static fn($e)=>$e['commons'],$entries));
$api='https://commons.wikimedia.org/w/api.php?'.http_build_query(['action'=>'query','format'=>'json','titles'=>$titles,'prop'=>'videoinfo','viprop'=>'url|derivatives','viurlwidth'=>1280]);
$pages=json_decode($get($api),true)['query']['pages']??[];
$byTitle=[];foreach($pages as $p){$byTitle[str_replace('_',' ',$p['title'])]=$p['videoinfo'][0]??null;}
foreach($entries as $e){
    $info=$byTitle[str_replace('_',' ',$e['commons'])]??null;
    if(!$info){echo "! not found on Commons: {$e['commons']}\n";continue;}
    $target=$dir.'/'.$e['file'];
    $want=(int)($e['commons_height']??720);
    $webm=array_values(array_filter($info['derivatives']??[],static fn($d)=>str_starts_with($d['type']??'','video/webm')&&!empty($d['height'])));
    usort($webm,static fn($a,$b)=>abs($a['height']-$want)<=>abs($b['height']-$want)?:str_contains($b['src'],'transcoded')<=>str_contains($a['src'],'transcoded'));
    $src=$webm[0]['src']??$info['url'];
    if($force||!is_file($target)){echo "↓ {$e['file']} ({$webm[0]['height']}p) …\n";$get($src,$target);echo '  '.round(filesize($target)/1048576,1)." MB\n";}
    else echo "= {$e['file']} already present\n";
    $poster=$dir.'/'.pathinfo($e['file'],PATHINFO_FILENAME).'.jpg';
    if(!empty($info['thumburl'])&&($force||!is_file($poster))){$get($info['thumburl'],$poster);echo "  poster saved\n";}
}
echo "Done. Refresh /watch to see the clips.\n";
