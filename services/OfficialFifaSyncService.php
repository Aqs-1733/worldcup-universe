<?php
/** FIFA-only ingestion: validate raw evidence, preserve public IDs, quarantine legacy rows. */
declare(strict_types=1);
namespace app\services;
use Yii;
use yii\db\Query;
use yii\helpers\Inflector;
final class OfficialFifaSyncService
{
    private const API='https://api.fifa.com/api/v3';
    private const PDF='https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf';
    private function text(?array $v): string { foreach($v??[] as $s) { if(str_starts_with($s['Locale']??'','en')) { return $s['Description']; } }return ''; }
    private function row(string $table,array $condition): ?array { return (new Query())->from($table)->where($condition)->one()?:null; }
    private function put(string $table,array $condition,array $data): int
    {
        $row=$this->row($table,$condition);
        if($row) { Yii::$app->db->createCommand()->update($table,$data,['id'=>$row['id']])->execute();return (int)$row['id']; }
        Yii::$app->db->createCommand()->insert($table,$data)->execute();return (int)Yii::$app->db->lastInsertID;
    }
    public function import(string $folder): array
    {
        $dir=realpath($folder);$allowed=false;
        foreach(['storage/source_snapshots/official','data/official'] as $base) { $root=realpath(Yii::getAlias('@app').'/'.$base);if($root&&$dir&&str_starts_with(str_replace('\\','/',$dir).'/',str_replace('\\','/',$root).'/')) {$allowed=true;} }
        if(!$allowed) { throw new \InvalidArgumentException('Use an official snapshot directory under storage/source_snapshots/official or data/official.'); }
        $manifest=file_get_contents($dir.'/manifest.json');$m=json_decode($manifest,true,512,JSON_THROW_ON_ERROR);
        if(($m['complete']??false)!==true||[$m['teams'],$m['players'],$m['matches'],$m['match_details']]!==[48,1248,104,104]) { throw new \RuntimeException('Incomplete collection'); }
        if(!hash_equals($m['normalized_sha256'],hash_file('sha256',$dir.'/normalized.json'))) { throw new \RuntimeException('Normalized hash mismatch'); }
        foreach($m['sources'] as $s) {
            $u=parse_url($s['url']);$path=realpath($dir.'/'.$s['file']);
            if(($u['scheme']??'')!=='https'||!in_array($u['host']??'',['api.fifa.com','fdp.fifa.org'],true)||$s['http_status']!==200||!$path||dirname($path)!==$dir||!hash_equals($s['sha256'],hash_file('sha256',$path))) { throw new \RuntimeException('Source evidence validation failed'); }
        }
        $d=json_decode(file_get_contents($dir.'/normalized.json'),true,512,JSON_THROW_ON_ERROR);
        if(count($d['squads'])!==48||count($d['teams'])!==48||count($d['matches'])!==104||count($d['live'])!==104||array_sum(array_map(fn($t)=>count($t['players']),$d['squads']))!==1248) { throw new \RuntimeException('Entity completeness failed'); }
        $hash=hash('sha256',$manifest);$existing=$this->row('official_imports',['manifest_sha256'=>$hash]);
        if($existing && is_array(json_decode($existing['report_json'],true)) && (json_decode($existing['report_json'],true)['parser_version']??0)===2) { return json_decode($existing['report_json'],true)+['reused'=>true]; }
        $tx=Yii::$app->db->beginTransaction();
        try {
            foreach(['teams','players','matches','standings','match_lineups','match_stats','match_events','player_statistics'] as $table) { Yii::$app->db->createCommand()->update($table,['official_import_id'=>null])->execute(); }
            $import=$this->put('official_imports',['manifest_sha256'=>$hash],['provider'=>'FIFA','manifest_sha256'=>$hash,'snapshot_path'=>str_replace('\\','/',substr($dir,strlen(Yii::getAlias('@app'))+1)),'manifest_json'=>new \yii\db\JsonExpression($m)]);
            $now=gmdate('Y-m-d H:i:s');$stamp=['source_synced_at'=>$now,'official_import_id'=>$import];$teamMap=[];$playerMap=[];$matchMap=[];$groupMap=[];$coachMap=[];$pictures=[];$lineCount=0;
            foreach($d['matches'] as $game) { if($game['IdStage']==='289273') { foreach(['Home','Away'] as $side) { $groupMap[$game[$side]['IdTeam']]=str_replace('Group ','',$this->text($game['GroupName'])); } } }
            // Use the latest observed roster for names, IDs and official portrait references.
            usort($d['live'],fn($a,$b)=>strcmp($a['Date'],$b['Date']));
            foreach($d['live'] as $live) { foreach(['HomeTeam','AwayTeam'] as $side) { $team=$live[$side];foreach($team['Players']??[] as $p) { $pictures[$team['IdCountry']][(int)$p['ShirtNumber']]=$p; }foreach($team['Coaches']??[] as $c) { if($c['Role']===0) {$coachMap[$team['IdTeam']]=$this->text($c['Alias']?:$c['Name']);} } } }
            $squads=array_column($d['squads'],null,'code');
            foreach($d['teams'] as $fifa=>$t) {
                $old=$this->row('teams',['code'=>$t['Abbreviation']]);$name=$this->text($t['Name']);
                $data=$stamp+['fifa_id'=>(string)$fifa,'code'=>$t['Abbreviation'],'slug'=>$old['slug']??Inflector::slug($name),'name_original'=>$name,'name_cn'=>$old['name_cn']??null,'confederation'=>$t['IdConfederation'],'coach_name'=>$coachMap[$fifa]??null,'group_name'=>$groupMap[$fifa]??null,'world_ranking'=>null,'profile'=>null,'flag_url'=>str_replace(['{format}','{size}'],['sq','4'],$t['PictureUrl']),'source_url'=>self::API.'/teams/'.$fifa.'?language=en'];
                $id=$this->put('teams',$old?['id'=>$old['id']]:['fifa_id'=>(string)$fifa],$data);$teamMap[$fifa]=$id;
                foreach($squads[$t['IdCountry']]['players'] as $p) {
                    $ap=$pictures[$t['IdCountry']][$p['shirt_number']]??null;
                    if(!$ap) { throw new \RuntimeException('Official roster missing player '.$t['IdCountry'].' #'.$p['shirt_number']); }
                    $oldPlayer=$ap?$this->row('players',['fifa_id'=>'fifa-'.$ap['IdPlayer']]):null;
                    if(!$oldPlayer&&$ap) {$oldPlayer=$this->row('players',['fifa_id'=>(string)$ap['IdPlayer']]);}
                    // Shirt number is only used inside the same official squad, not across teams.
                    if(!$oldPlayer) {$oldPlayer=$this->row('players',['team_id'=>$id,'shirt_number'=>$p['shirt_number']]);}
                    $fifaPlayer=$ap?(string)$ap['IdPlayer']:'pdf-'.$t['IdCountry'].'-'.$p['shirt_number'];
                    $playerName=$ap?$this->text($ap['PlayerName']):trim($p['first_names'].' '.$p['last_names']);
                    $pic=$ap['PlayerPicture']['PictureUrl']??null;
                    if($pic&&!preg_match('~^https://(?:digitalhub|api)\.fifa\.com/~',$pic)) {$pic=null;}
                    $pd=$stamp+['team_id'=>$id,'fifa_id'=>$fifaPlayer,'slug'=>$oldPlayer['slug']??Inflector::slug($playerName).'-'.$fifaPlayer,'name_original'=>$playerName,'name_cn'=>$oldPlayer['name_cn']??null,'position'=>$p['position'],'shirt_number'=>$p['shirt_number'],'birth_date'=>$p['birth_date'],'age'=>null,'club'=>$p['club'],'height_cm'=>$p['height_cm'],'caps'=>$p['caps'],'goals'=>$p['goals'],'photo_url'=>$pic,'popularity_score'=>0,'source_url'=>self::PDF.'#page='.$p['pdf_page']];
                    $pid=$this->put('players',$oldPlayer?['id'=>$oldPlayer['id']]:['fifa_id'=>$fifaPlayer],$pd);$playerMap[$fifaPlayer]=$pid;
                }
            }
            foreach($d['matches'] as $game) {
                $stadium=$game['Stadium'];$name=$this->text($stadium['Name']);
                $url=self::API.'/calendar/matches?idCompetition=17&idSeason=285023&language=en&count=500';
                $venue=$this->row('venues',['fifa_id'=>$stadium['IdStadium']])??$this->row('venues',['name_original'=>$name]);
                $vid=$this->put('venues',$venue?['id'=>$venue['id']]:['fifa_id'=>$stadium['IdStadium']],['fifa_id'=>$stadium['IdStadium'],'name_original'=>$name,'name_cn'=>$venue['name_cn']??null,'city_original'=>$this->text($stadium['CityName']),'city_cn'=>null,'capacity'=>$stadium['Capacity'],'source_url'=>$url]);
                $old=$this->row('matches',['external_id'=>'worldcup-match-'.str_pad((string)$game['MatchNumber'],3,'0',STR_PAD_LEFT)])??$this->row('matches',['external_id'=>$game['IdMatch']]);
                $stage=['First Stage'=>'小组赛','Round of 32'=>'32强赛','Round of 16'=>'16强赛','Quarter-final'=>'四分之一决赛','Semi-final'=>'半决赛','Play-off for third place'=>'季军赛','Bronze final'=>'季军赛','Final'=>'决赛'][$this->text($game['StageName'])]??$this->text($game['StageName']);
                $status=$game['MatchStatus']===0&&$game['HomeTeamScore']!==null&&$game['AwayTeamScore']!==null?'finished':(strtotime($game['Date'])>time()?'scheduled':'unknown');
                $mid=$this->put('matches',$old?['id'=>$old['id']]:['external_id'=>$game['IdMatch']],$stamp+['external_id'=>$game['IdMatch'],'match_number'=>$game['MatchNumber'],'stage'=>$stage,'group_name'=>$game['GroupName']?str_replace('Group ','',$this->text($game['GroupName'])):null,'home_team_id'=>$teamMap[$game['Home']['IdTeam']]??null,'away_team_id'=>$teamMap[$game['Away']['IdTeam']]??null,'home_team_name'=>$this->text($game['Home']['TeamName']??[]),'away_team_name'=>$this->text($game['Away']['TeamName']??[]),'home_score'=>$game['HomeTeamScore'],'away_score'=>$game['AwayTeamScore'],'home_penalty_score'=>$game['HomeTeamPenaltyScore'],'away_penalty_score'=>$game['AwayTeamPenaltyScore'],'status'=>$status,'starts_at'=>gmdate('Y-m-d H:i:s',strtotime($game['Date'])),'venue_id'=>$vid,'winner_fifa_id'=>$game['Winner'],'placeholder_home'=>$game['PlaceHolderA'],'placeholder_away'=>$game['PlaceHolderB'],'source_url'=>$url]);
                $matchMap[(int)$game['MatchNumber']]=$mid;
            }
            Yii::$app->db->createCommand()->delete('match_progressions')->execute();$edges=0;
            foreach($d['matches'] as $g) { foreach(['PlaceHolderA'=>'home','PlaceHolderB'=>'away'] as $key=>$slot) { if((int)$g['MatchNumber']>=89&&preg_match('/^(W|RU)(\d+)$/',$g[$key]??'',$v)&&isset($matchMap[(int)$v[2]])) { Yii::$app->db->createCommand()->insert('match_progressions',['from_match_id'=>$matchMap[(int)$v[2]],'to_match_id'=>$matchMap[(int)$g['MatchNumber']],'outcome'=>$v[1]==='W'?'winner':'loser','slot'=>$slot,'source_url'=>self::API.'/calendar/matches?idCompetition=17&idSeason=285023&language=en&count=500','is_verified'=>1])->execute();$edges++; } } }
            foreach($d['live'] as $live) { $mid=$matchMap[(int)$live['MatchNumber']];$url=self::API.'/live/football/17/285023/'.$live['IdStage'].'/'.$live['IdMatch'].'?language=en';foreach(['HomeTeam','AwayTeam'] as $side) { $t=$live[$side];foreach($t['Players']??[] as $p) { $external='fifa-'.$live['IdMatch'].'-'.$p['IdPlayer'];$this->put('match_lineups',['external_lineup_id'=>$external],$stamp+['external_lineup_id'=>$external,'match_id'=>$mid,'team_id'=>$teamMap[$t['IdTeam']],'player_id'=>$playerMap[$p['IdPlayer']]??null,'is_starting_xi'=>$p['Status']===1?1:0,'tactical_position'=>[0=>'GK',1=>'DF',2=>'MF',3=>'FW'][$p['Position']]??null,'minutes_played'=>null,'source_url'=>$url,'raw_payload_json'=>new \yii\db\JsonExpression($p)]);$lineCount++;} } }
            // Standings below are reproducibly computed from official group-stage scores.
            $groups=[];foreach($teamMap as $fifa=>$id) {$groups[$id]=['team_id'=>$id,'group_name'=>$groupMap[$fifa],'played'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'goals_for'=>0,'goals_against'=>0,'goal_difference'=>0,'points'=>0];}
            foreach($d['matches'] as $g) { if($g['IdStage']!=='289273'||$g['MatchStatus']!==0||$g['HomeTeamScore']===null||$g['AwayTeamScore']===null) {continue;}foreach([['Home','Away'],['Away','Home']] as [$s,$o]) {$id=$teamMap[$g[$s]['IdTeam']];$gf=$g[$s.'TeamScore'];$ga=$g[$o.'TeamScore'];$groups[$id]['played']++;$groups[$id]['goals_for']+=$gf;$groups[$id]['goals_against']+=$ga;$result=$gf>$ga?'won':($gf===$ga?'drawn':'lost');$groups[$id][$result]++;$groups[$id]['points']+=$gf>$ga?3:($gf===$ga?1:0);$groups[$id]['goal_difference']=$groups[$id]['goals_for']-$groups[$id]['goals_against'];}}
            foreach($groups as $id=>$row) {$this->put('standings',['team_id'=>$id,'group_name'=>$row['group_name']],$stamp+$row+['source_url'=>self::API.'/calendar/matches?idCompetition=17&idSeason=285023&language=en&count=500']);}
            $report=['parser_version'=>2,'teams'=>48,'players'=>1248,'matches'=>104,'lineups'=>$lineCount,'verified_edges'=>$edges,'standings'=>48,'portrait_urls'=>count(array_filter(array_merge(...array_values(array_map(fn($p)=>array_values($p),$pictures))),fn($p)=>!empty($p['PlayerPicture']['PictureUrl']))),'import_id'=>$import,'manifest_sha256'=>$hash];
            Yii::$app->db->createCommand()->update('official_imports',['report_json'=>new \yii\db\JsonExpression($report)],['id'=>$import])->execute();
            $tx->commit();Yii::$app->cache->flush();return $report;
        } catch(\Throwable $e) {$tx->rollBack();throw $e;}
    }
}
