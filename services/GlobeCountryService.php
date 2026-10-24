<?php
declare(strict_types=1);
namespace app\services;
use app\models\{CountryProfile,Team,Player,MatchGame};
use yii\web\NotFoundHttpException;
final class GlobeCountryService
{
 public function detail(string $key): array
 {
  $mapping=require ROOT_PATH.'/config/globe-country-map.php';
  $features=json_decode(file_get_contents(ROOT_PATH.'/public/assets/data/globe/natural-earth-admin0-map-units-110m.web.geojson'),true)['features'];
  $feature=null;foreach($features as $item){if('ne-'.$item['properties']['NE_ID']===$key){$feature=$item;break;}}
  $code=$mapping[$key]??null;
  if(str_starts_with($key,'country-'))$code=substr($key,8);
  if(!$feature&&!$code)throw new NotFoundHttpException('地图单元不存在。');
  $country=$code?CountryProfile::find()->where(['country_code'=>$code])->asArray()->one():null;
  if(!$feature&&!$country)throw new NotFoundHttpException('国家资料不存在。');
  $teams=$country?Team::find()->where(['country_code'=>$country['country_code']])->andWhere(['not',['official_import_id'=>null]])->orderBy('id')->all():[];
  $ids=array_map(static fn($team)=>(int)$team->id,$teams);
  $players=$ids?Player::find()->where(['team_id'=>$ids])->andWhere(['not',['official_import_id'=>null]])->orderBy(['caps'=>SORT_DESC,'id'=>SORT_ASC])->limit(4)->all():[];
  $matches=$ids?MatchGame::find()->where(['or',['home_team_id'=>$ids],['away_team_id'=>$ids]])->andWhere(['not',['official_import_id'=>null]])->with(['homeTeam','awayTeam'])->orderBy(['starts_at'=>SORT_DESC])->limit(3)->all():[];
  $countryForUi=$country;if($countryForUi)$countryForUi['linked_teams']=array_map(static fn($team)=>$team->toArray(),$teams);
  return [
   'geo_key'=>$key,'name'=>$countryForUi?CountryUi::name($countryForUi):($feature['properties']['NAME_ZH']?:$feature['properties']['NAME']),
   'country_code'=>$country['country_code']??null,'capital'=>$country?CountryUi::capital($country):null,
   'region'=>$country?CountryUi::region($country):($feature['properties']['CONTINENT']??null),
   'url'=>$country?'/countries/'.strtolower($country['country_code']):null,
   'status'=>$teams?'linked':'no_linked_data',
   'message'=>$teams?'已关联本届世界杯资料':'当前数据库没有关联的本届世界杯球队资料，不能据此判断资格赛结果。',
   'teams'=>array_map(static fn($team)=>['id'=>(int)$team->id,'name'=>$team->name,'code'=>$team->code,'group'=>$team->group_name,'url'=>'/teams/'.$team->slug,'flag'=>TeamUi::url($team)],$teams),
   'players'=>array_map(static fn($player)=>['id'=>(int)$player->id,'name'=>$player->name,'position'=>$player->position,'photo'=>$player->displayPhotoUrl,'url'=>'/players/'.$player->slug],$players),
   'matches'=>array_map(static fn($game)=>['id'=>(int)$game->id,'stage'=>$game->stage,'home'=>$game->homeTeam?->name,'away'=>$game->awayTeam?->name,'home_score'=>$game->home_score,'away_score'=>$game->away_score,'date'=>$game->starts_at,'url'=>'/matches/'.$game->id],$matches),
  ];
 }
}
