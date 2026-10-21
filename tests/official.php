<?php
/** Compare every published sports row with the bundled raw official responses. */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/app/bootstrap.php';
$app=new yii\console\Application(require ROOT_PATH.'/config/console.php');
$folder=ROOT_PATH.'/data/official/20261021';$d=json_decode(file_get_contents($folder.'/normalized.json'),true,512,JSON_THROW_ON_ERROR);
$m=json_decode(file_get_contents($folder.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);$checks=0;
function officialCheck(bool $condition,string $label): void { global $checks;if(!$condition) { throw new RuntimeException($label); }$checks++; }
foreach($m['sources'] as $source) {officialCheck(hash_file('sha256',$folder.'/'.$source['file'])===$source['sha256'],'raw source hash '.$source['file']);officialCheck(in_array(parse_url($source['url'],PHP_URL_HOST),['api.fifa.com','fdp.fifa.org'],true),'official source host');}
officialCheck(hash_file('sha256',$folder.'/normalized.json')===$m['normalized_sha256'],'normalized hash');
foreach(['teams'=>48,'players'=>1248,'matches'=>104,'match_lineups'=>5323,'standings'=>48] as $table=>$count) {officialCheck((int)(new yii\db\Query())->from($table)->where(['not',['official_import_id'=>null]])->count()===$count,$table.' official count');}
foreach(['match_stats','match_events','player_statistics'] as $table) {officialCheck(!(new yii\db\Query())->from($table)->where(['not',['official_import_id'=>null]])->exists(),$table.' legacy values quarantined');}
$teams=app\models\Team::find()->where(['not',['official_import_id'=>null]])->indexBy('fifa_id')->all();
foreach($d['teams'] as $id=>$raw) {officialCheck(isset($teams[$id])&&$teams[$id]->code===$raw['Abbreviation']&&$teams[$id]->confederation===$raw['IdConfederation'],'team fields '.$id);}
$games=app\models\MatchGame::find()->where(['not',['official_import_id'=>null]])->indexBy('external_id')->all();
foreach($d['matches'] as $raw) {$game=$games[$raw['IdMatch']]??null;officialCheck($game&&(int)$game->match_number===(int)$raw['MatchNumber']&&(int)$game->home_score===$raw['HomeTeamScore']&&(int)$game->away_score===$raw['AwayTeamScore']&&(int)$game->home_team_id===(int)$teams[$raw['Home']['IdTeam']]->id&&(int)$game->away_team_id===(int)$teams[$raw['Away']['IdTeam']]->id&&$game->starts_at===gmdate('Y-m-d H:i:s',strtotime($raw['Date'])),'match fields '.$raw['IdMatch']);}
$byCode=array_column($d['squads'],null,'code');
foreach($teams as $team) { $players=app\models\Player::find()->where(['team_id'=>$team->id])->andWhere(['not',['official_import_id'=>null]])->indexBy('shirt_number')->all();officialCheck(count($players)===26,'squad size '.$team->code);foreach($byCode[$team->code]['players'] as $raw) { $p=$players[$raw['shirt_number']]??null;officialCheck($p&&$p->birth_date===$raw['birth_date']&&$p->position===$raw['position']&&(int)$p->height_cm===$raw['height_cm']&&(int)$p->caps===$raw['caps']&&(int)$p->goals===$raw['goals']&&$p->club===$raw['club'],'squad fields '.$team->code.' #'.$raw['shirt_number']);officialCheck((bool)preg_match('~^https://(?:digitalhub|api)\.fifa\.com/~',$p->photo_url),'official portrait host');}}
$edges=app\models\MatchProgression::find()->where(['is_verified'=>1])->all();officialCheck(count($edges)===32,'32 official bracket links');
$numbers=[];foreach($games as $g) {$numbers[(int)$g->match_number]=$g;}
foreach($d['matches'] as $g) {if($g['MatchNumber']<89) {continue;}foreach(['PlaceHolderA'=>'home','PlaceHolderB'=>'away'] as $key=>$slot) {preg_match('/^(W|RU)(\d+)$/',$g[$key],$v);officialCheck(isset($v[2])&&app\models\MatchProgression::find()->where(['from_match_id'=>$numbers[(int)$v[2]]->id,'to_match_id'=>$numbers[(int)$g['MatchNumber']]->id,'slot'=>$slot,'outcome'=>$v[1]==='W'?'winner':'loser','is_verified'=>1])->exists(),'official placeholder '.$g['MatchNumber'].' '.$slot);}}
$report=(new app\services\OfficialFifaSyncService())->import($folder);officialCheck($report['reused']===true,'identical official import is idempotent');
$tx=$app->db->beginTransaction();try { $team=reset($teams);$team->coach_name='手工测试修改';$team->save(false);officialCheck($team->official_import_id===null,'manual factual edit clears official verification'); }finally{$tx->rollBack();}
echo 'PASS '.$checks." official-source checks; 48 teams / 1248 players / 104 matches / 5323 lineups / 32 edges.\n";
