<?php
/** Only photographed squads with explicit 2026 source evidence qualify as team covers. */
declare(strict_types=1);
namespace app\services;
final class TeamPhoto
{
 public static function all(): array
 {
  static $entries=null;
  if($entries!==null)return $entries;
  $file=ROOT_PATH.'/public/assets/media/team-photos/manifest.json';
  $raw=is_file($file)?json_decode(file_get_contents($file),true):[];$entries=[];
  foreach($raw['teams']??[] as $code=>$entry){$path=$entry['path']??'';if(($entry['year']??0)===2026&&($entry['status']??'')==='verified_2026_team_photo'&&preg_match('~^/assets/media/team-photos/[A-Z]{3}\.(jpg|png|webp)$~',$path)&&is_file(ROOT_PATH.'/public'.$path))$entries[$code]=$entry;}
  return $entries;
 }
 public static function get(?string $code): ?array{return self::all()[$code]??null;}
 public static function order(): \yii\db\Expression
 {
  $codes=array_keys(self::all());if(!$codes)return new \yii\db\Expression('id ASC');
  $quoted=array_map(static fn($code)=>\Yii::$app->db->quoteValue($code),$codes);
  $clear=array_keys(array_filter(self::all(),static fn($photo)=>empty($photo['watermarked'])));$clearQuoted=array_map(static fn($code)=>\Yii::$app->db->quoteValue($code),$clear);
  $clearCase=$clearQuoted?'WHEN code IN ('.implode(',',$clearQuoted).') THEN 0 ':'';
  return new \yii\db\Expression('CASE '.$clearCase.'WHEN code IN ('.implode(',',$quoted).') THEN 1 ELSE 2 END ASC, group_name ASC, id ASC');
 }
}
