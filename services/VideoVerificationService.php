<?php
/**
 * 文件作用：检查视频来源是否可以在站内播放。
 */
/**
 * No remote-media verifier remains in use. Entries in the public shelf are verified
 * by LocalVideoLibrary::safeFile() before they are rendered or streamed.
 */
declare(strict_types=1);
namespace app\services;
final class VideoVerificationService
{
    public function verify(\app\models\MatchVideo $video): array
    {
        if (!LocalVideoLibrary::safeFile((string)$video->provider_id)) { throw new \RuntimeException('本地视频文件不存在。'); }
        return ['author'=>$video->source_author,'title'=>$video->source_title,'thumbnail'=>$video->thumbnail_url,'requires_login'=>false];
    }
}
