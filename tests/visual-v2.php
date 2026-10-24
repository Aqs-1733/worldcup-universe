<?php
/** Read-only regression for the new geographic joins and editorial data selection. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');$checks=0;
function visualCheck(bool $ok,string $message): void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
$service=new app\services\GlobeCountryService();$map=require ROOT_PATH.'/config/globe-country-map.php';
foreach($map as $key=>$code){
 $data=$service->detail($key);visualCheck($data['country_code']===$code,'explicit country mapping '.$key);
 $ids=[];foreach($data['teams'] as $team){$record=app\models\Team::findOne($team['id']);visualCheck($record&&$record->official_import_id&&$record->country_code===$code,'verified linked team '.$code);$ids[]=$team['id'];}
 foreach($data['players'] as $player){$record=app\models\Player::findOne($player['id']);visualCheck($record&&$record->official_import_id&&in_array((int)$record->team_id,$ids,true),'player belongs to mapped team');}
 foreach($data['matches'] as $game){$record=app\models\MatchGame::findOne($game['id']);visualCheck($record&&$record->official_import_id&&(in_array((int)$record->home_team_id,$ids,true)||in_array((int)$record->away_team_id,$ids,true)),'match belongs to mapped team');}
}
foreach(['CV','CW'] as $code){$data=$service->detail('country-'.$code);visualCheck($data['country_code']===$code&&count($data['teams'])>0,'small-island database fallback');}
$china=$service->detail('ne-1159320471');visualCheck($china['status']==='no_linked_data'&&!$china['players']&&!$china['matches'],'unlinked country does not fabricate sports records');
try{$service->detail('ne-0');throw new RuntimeException('invalid key was accepted');}catch(yii\web\NotFoundHttpException){$checks++;}
$players=(new app\services\CatalogService())->featuredPlayers();visualCheck(count($players)===3,'configured editorial selection');foreach($players as $player)visualCheck((bool)$player->official_import_id,'featured player is verified');
$video=new app\models\MatchVideo(['video_type'=>'highlights']);visualCheck($video->validate(['video_type']),'highlight catalogue type supported');$video->video_type='arbitrary';visualCheck(!$video->validate(['video_type']),'unknown catalogue type rejected');
echo 'PASS '.$checks." visual-v2 read-only data checks.\n";
