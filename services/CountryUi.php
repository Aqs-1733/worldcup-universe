<?php
/** Chinese presentation labels; original country-source values remain untouched. */
declare(strict_types=1);
namespace app\services;
final class CountryUi
{
 public static function name(array $country): string{return ($country['linked_teams'][0]['name_cn']??null)?:($country['country_name_cn']?:$country['country_name_original']);}
 public static function region(array $country): string{return ['Africa'=>'非洲','Americas'=>'美洲','Asia'=>'亚洲','Europe'=>'欧洲','Oceania'=>'大洋洲'][$country['region']]??$country['region'];}
 public static function capital(array $country): string
 {
  $labels=['DZ'=>'阿尔及尔','AR'=>'布宜诺斯艾利斯','AU'=>'堪培拉','AT'=>'维也纳','BE'=>'布鲁塞尔','BA'=>'萨拉热窝','BR'=>'巴西利亚','CA'=>'渥太华','CV'=>'普拉亚','CO'=>'波哥大','HR'=>'萨格勒布','CW'=>'威廉斯塔德','CZ'=>'布拉格','CD'=>'金沙萨','EC'=>'基多','EG'=>'开罗','GB-ENG'=>'伦敦','FR'=>'巴黎','DE'=>'柏林','GH'=>'阿克拉','HT'=>'太子港','IR'=>'德黑兰','IQ'=>'巴格达','CI'=>'亚穆苏克罗','JP'=>'东京','JO'=>'安曼','MX'=>'墨西哥城','MA'=>'拉巴特','NL'=>'阿姆斯特丹','NZ'=>'惠灵顿','NO'=>'奥斯陆','PA'=>'巴拿马城','PY'=>'亚松森','PT'=>'里斯本','QA'=>'多哈','SA'=>'利雅得','GB-SCT'=>'爱丁堡','SN'=>'达喀尔','ZA'=>'比勒陀利亚／布隆方丹／开普敦','KR'=>'首尔','ES'=>'马德里','SE'=>'斯德哥尔摩','CH'=>'伯尔尼','TN'=>'突尼斯市','TR'=>'安卡拉','US'=>'华盛顿','UY'=>'蒙得维的亚','UZ'=>'塔什干'];
  return $labels[strtoupper($country['country_code'])]??($country['capital']?:'—');
 }
 public static function terms(?string $value): string
 {
  static $labels=null;$labels??=json_decode(file_get_contents(ROOT_PATH.'/data/country-labels.zh.json'),true);
  return implode('、',array_map(static fn($part)=>$labels[trim($part)]??trim($part),explode('、',$value?:'—')));
 }
 public static function flag(array $country): string
 {
  $team=$country['linked_teams'][0]??null;
  return $team?TeamUi::flag(['code'=>$team['code'],'name'=>$team['name_cn']?:$team['name_original']]):'';
 }
}
