<?php
/** Verify an official publisher's complete 2026 video metadata, never extract media streams. */
declare(strict_types=1);
namespace app\services;
use app\models\MatchVideo;
final class VideoVerificationService
{
    public function verify(MatchVideo $video): array
    {
        if((int)$video->tournament_year!==2026)throw new \RuntimeException('仅收录 2026 年世界杯。');
        $highlights=$video->video_type==='highlights';
        if($video->provider==='rtve') {
            if(!preg_match('/^\d{8}$/',$video->provider_id))throw new \RuntimeException('官方视频编号无效。');
            $d=$this->json('https://www.rtve.es/api/videos/'.$video->provider_id.'.json');$v=$d['page']['items'][0]??[];
            $typeValid=$highlights?in_array($v['type']['name']??'',['Resumen','Resumen deportivo'],true)&&($v['duration']??0)>0:($v['type']['name']??'')==='Completo'&&($v['duration']??0)>=3600000;
            if(($v['id']??'')!==$video->provider_id||!$typeValid||($v['pubState']['code']??'')!=='ENPUB'||!str_contains($v['mainTopic']??'','2026')||($v['htmlUrl']??'')!==$video->source_url)throw new \RuntimeException('官方元数据未确认对应类型的 2026 世界杯视频。');
            return ['author'=>'RTVE','title'=>$v['longTitle']??$v['title'],'thumbnail'=>$v['thumbnail']??null,'requires_login'=>(bool)($v['requireLogged']??false),'duration_seconds'=>(int)round(($v['duration']??0)/1000)];
        }
        if($video->provider==='youtube') {
            $d=$this->json('https://www.youtube.com/oembed?format=json&url='.rawurlencode($video->source_url));
            $pattern=$highlights?'/highlights|resumen/i':'/full match/i';
            if(($d['author_name']??'')!=='FIFA'||!preg_match($pattern,$d['title']??'')||!str_contains($d['title']??'','2026'))throw new \RuntimeException('官方频道未确认对应类型的 2026 世界杯视频。');
            return ['author'=>'FIFA','title'=>$d['title'],'thumbnail'=>$d['thumbnail_url']??null,'requires_login'=>false];
        }
        throw new \RuntimeException('视频来源无效。');
    }
    private function json(string $url): array
    {
        $curl=curl_init($url);$body='';curl_setopt_array($curl,[CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'WorldCupUniverse-CourseProject/2.0',CURLOPT_WRITEFUNCTION=>function($c,$b)use(&$body){if(strlen($body)+strlen($b)>2097152)return 0;$body.=$b;return strlen($b);}]);$ok=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);if(!$ok||$status!==200)throw new \RuntimeException('官方视频元数据获取失败，请稍后重试。');return json_decode($body,true,512,JSON_THROW_ON_ERROR);
    }
}
