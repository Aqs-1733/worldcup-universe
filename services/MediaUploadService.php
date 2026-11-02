<?php
/** Administrator uploads: allowlisted type, sniffed MIME, size cap, random name, fixed storage roots. */
declare(strict_types=1);
namespace app\services;
use Yii;
use yii\web\UploadedFile;
final class MediaUploadService
{
    /** Public media is served by the web server from public/uploads; nothing there is ever executed. */
    public const PUBLIC_URL='/uploads';
    /** Coursework is stored under data/<scope>/uploads and only leaves through SiteController::actionDownload. */
    public const COURSEWORK_DIR='uploads';
    private const KINDS=[
        'image'=>['ext'=>['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],'gif'=>['image/gif']],'env'=>'UPLOAD_MAX_IMAGE_MB','mb'=>10],
        'video'=>['ext'=>['mp4'=>['video/mp4','application/mp4'],'m4v'=>['video/mp4','video/x-m4v'],'webm'=>['video/webm','audio/webm']],'env'=>'UPLOAD_MAX_VIDEO_MB','mb'=>200],
        'document'=>['ext'=>[
            'pdf'=>['application/pdf'],'doc'=>['application/msword','application/x-ole-storage','application/CDFV2'],'xls'=>['application/vnd.ms-excel','application/x-ole-storage','application/CDFV2'],'ppt'=>['application/vnd.ms-powerpoint','application/x-ole-storage','application/CDFV2'],
            'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip','application/octet-stream'],
            'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip','application/octet-stream'],
            'pptx'=>['application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip','application/octet-stream'],
            'zip'=>['application/zip','application/x-zip-compressed'],'rar'=>['application/x-rar','application/vnd.rar','application/x-rar-compressed'],'7z'=>['application/x-7z-compressed'],
            'md'=>['text/plain','text/markdown','text/x-markdown'],'txt'=>['text/plain'],'mp4'=>['video/mp4','application/mp4'],
        ],'env'=>'UPLOAD_MAX_DOCUMENT_MB','mb'=>100],
    ];
    /** Segments that a web server might hand to an interpreter, wherever they appear in the client name. */
    private const DANGEROUS='/\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|asp|aspx|jsp|exe|bat|cmd|com|htaccess|html?|svg|xhtml|js|mjs)(\.|$)/i';

    public static function publicDir(): string { return ROOT_PATH.'/public'.self::PUBLIC_URL; }

    /** HTML accept attribute for a field. */
    public static function accept(array $kinds): string
    {
        $out=[]; foreach($kinds as $kind) { foreach(array_keys(self::kind($kind)['ext']) as $ext) { $out[]='.'.$ext; } }
        return implode(',',array_unique($out));
    }

    public static function limitBytes(string $kind): int
    {
        $spec=self::kind($kind); $mb=(int)env($spec['env'],$spec['mb']);
        return max(1,min(2048,$mb))*1048576;
    }

    /**
     * Check one upload against the allowed kinds without moving it.
     * @return array{kind:string,ext:string,mime:string}
     */
    public function inspect(UploadedFile $file,array $kinds): array
    {
        if($file->error!==UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(match($file->error) {UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'文件超过服务器允许的上传大小。',UPLOAD_ERR_PARTIAL=>'文件只上传了一部分，请重试。',UPLOAD_ERR_NO_FILE=>'没有选择文件。',default=>'服务器未能接收上传文件。'});
        }
        $name=(string)$file->name; $tmp=(string)$file->tempName;
        if($name===''||preg_match('/[\x00-\x1f\\\\\/]/',$name)||preg_match(self::DANGEROUS,$name)) { throw new \InvalidArgumentException('文件名包含不允许的扩展名或字符。'); }
        if(!is_file($tmp)) { throw new \InvalidArgumentException('上传临时文件不存在。'); }
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $kind=null; foreach($kinds as $candidate) { if(isset(self::kind($candidate)['ext'][$ext])) { $kind=$candidate; break; } }
        if(!$kind) { throw new \InvalidArgumentException('不支持的文件类型：.'.($ext?:'无扩展名').'。允许：'.self::accept($kinds)); }
        $size=(int)filesize($tmp);
        if($size<=0) { throw new \InvalidArgumentException('文件为空。'); }
        if($size>self::limitBytes($kind)) { throw new \InvalidArgumentException('文件过大，'.$kind.' 类文件上限 '.(self::limitBytes($kind)/1048576).' MB。'); }
        $mime=$this->sniff($tmp,$kind);
        if(!in_array($mime,self::kind($kind)['ext'][$ext],true)) { throw new \InvalidArgumentException('文件内容（'.$mime.'）与扩展名 .'.$ext.' 不一致。'); }
        if($kind==='image') { $this->assertImage($tmp,$mime); }
        return ['kind'=>$kind,'ext'=>$ext==='jpeg'?'jpg':$ext,'mime'=>$mime];
    }

    /** Store a public image/video; returns its site-relative URL such as /uploads/2026/11/<32 hex>.jpg. */
    public function storePublic(UploadedFile $file,array $kinds): string
    {
        $info=$this->inspect($file,$kinds);
        $sub=date('Y').'/'.date('m'); $dir=self::publicDir().'/'.$sub;
        $name=$this->move($file,$dir,$info['ext']);
        return self::PUBLIC_URL.'/'.$sub.'/'.$name;
    }

    /** Store a coursework document; returns its storage_path such as data/team/uploads/<32 hex>.pdf. */
    public function storeCoursework(UploadedFile $file,string $scope): string
    {
        if(!isset(CourseworkLibrary::SCOPES[$scope])) { throw new \InvalidArgumentException('课程交付范围无效。'); }
        $info=$this->inspect($file,['document']);
        if(!in_array($info['ext'],CourseworkLibrary::EXT,true)) { throw new \InvalidArgumentException('课程交付不支持该文件类型。'); }
        $name=$this->move($file,ROOT_PATH.'/data/'.$scope.'/'.self::COURSEWORK_DIR,$info['ext']);
        return 'data/'.$scope.'/'.self::COURSEWORK_DIR.'/'.$name;
    }

    /** Remove a file this service created, after its record was replaced or deleted. Other paths are ignored. */
    public function discard(?string $value): bool
    {
        $path=self::managedPath((string)$value);
        return $path!==null && @unlink($path);
    }

    /** Absolute path of a file created by this service, or null for anything else (seeded assets, remote URLs, traversal). */
    public static function managedPath(string $value): ?string
    {
        if(preg_match('~^/uploads/(\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]{2,4})$~',$value,$m)) { $base=self::publicDir(); $relative=$m[1]; }
        elseif(preg_match('~^data/(team|personal)/uploads/([a-f0-9]{32}\.[a-z0-9]{2,4})$~',$value,$m)) { $base=ROOT_PATH.'/data/'.$m[1].'/'.self::COURSEWORK_DIR; $relative=$m[2]; }
        else { return null; }
        $root=realpath($base); $path=realpath($base.'/'.$relative);
        return $root&&$path&&is_file($path)&&str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$root).'/')?$path:null;
    }

    public static function isVideo(string $url): bool { return (bool)preg_match('~\.(mp4|m4v|webm)$~i',$url); }

    private static function kind(string $kind): array { if(!isset(self::KINDS[$kind])) { throw new \InvalidArgumentException('Unknown upload kind '.$kind); } return self::KINDS[$kind]; }

    private function sniff(string $tmp,string $kind): string
    {
        if(class_exists(\finfo::class)) { return (string)(new \finfo(FILEINFO_MIME_TYPE))->file($tmp); }
        // Without ext-fileinfo only images can be identified reliably (getimagesize reads the header itself).
        if($kind==='image') { $info=@getimagesize($tmp); if($info) { return (string)$info['mime']; } }
        throw new \InvalidArgumentException('服务器未启用 PHP fileinfo 扩展，无法校验该类型文件。');
    }

    private function assertImage(string $tmp,string $mime): void
    {
        $info=@getimagesize($tmp);
        if(!$info || $info['mime']!==$mime) { throw new \InvalidArgumentException('图片内容无法识别。'); }
        if($info[0]<1 || $info[1]<1 || $info[0]*$info[1]>40000000) { throw new \InvalidArgumentException('图片尺寸无效或超过 4000 万像素。'); }
        // Reject polyglots: an image that also carries a script tag is never legitimate media.
        $bytes=(string)file_get_contents($tmp);
        if(preg_match('/<\?php|<\?=|<script[\s>]/i',$bytes)) { throw new \InvalidArgumentException('图片中包含脚本内容，已拒绝。'); }
    }

    private function move(UploadedFile $file,string $dir,string $ext): string
    {
        if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) { throw new \RuntimeException('上传目录不可写：'.$dir); }
        $name=bin2hex(random_bytes(16)).'.'.$ext; $target=$dir.'/'.$name;
        // HTTP uploads must come through move_uploaded_file; console tools pass already-local files.
        $ok=Yii::$app instanceof \yii\web\Application ? $file->saveAs($target) : copy($file->tempName,$target);
        if(!$ok) { throw new \RuntimeException('文件保存失败，请检查目录权限。'); }
        @chmod($target,0644);
        return $name;
    }
}
