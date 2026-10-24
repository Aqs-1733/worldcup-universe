<?php
/** Compact data for the story modes of the 3D globe: who qualified, how far they went, and where they played. */
declare(strict_types=1);
namespace app\services;
use app\models\{CountryProfile,MatchGame,Team};
final class GlobeStoryService
{
    private const ORDER=['小组赛'=>1,'32强赛'=>2,'16强赛'=>3,'四分之一决赛'=>4,'半决赛'=>5,'决赛'=>6];
    public function payload(): array
    {
        $geo=array_flip(require ROOT_PATH.'/config/globe-country-map.php');
        $venueGeo=require ROOT_PATH.'/config/venue-geo.php';
        $countries=[];foreach(CountryProfile::find()->asArray()->all() as $c)$countries[strtoupper($c['country_code'])]=$c;
        $teams=Team::find()->where(['not',['official_import_id'=>null]])->all();
        $matches=MatchGame::find()->where(['not',['official_import_id'=>null]])->with(['venue','homeTeam','awayTeam'])->orderBy(['starts_at'=>SORT_ASC,'id'=>SORT_ASC])->all();
        $reach=[];$champion=null;$venues=[];$games=[];
        foreach($matches as $m){
            $level=self::ORDER[$m->stage]??0;
            foreach([(int)$m->home_team_id,(int)$m->away_team_id] as $id){if($id)$reach[$id]=max($reach[$id]??0,$level);}
            if($m->stage==='决赛'){$w=ImmersiveService::winnerSide($m);if($w)$champion=(int)($w==='home'?$m->home_team_id:$m->away_team_id);}
            $v=$m->venue;$point=$v?($venueGeo[$v->name_original]??null):null;
            if(!$point||!$m->home_team_id||!$m->away_team_id)continue;
            $key=$v->name_original;
            $venues[$key]??=['name'=>$v->name_cn?:$v->name_original,'city'=>$point['city'],'lat'=>$point['lat'],'lng'=>$point['lng'],'matches'=>0,'final'=>false];
            $venues[$key]['matches']++;if($m->stage==='决赛')$venues[$key]['final']=true;
            $games[]=['h'=>(int)$m->home_team_id,'a'=>(int)$m->away_team_id,'v'=>array_search($key,array_keys($venues),true),'s'=>$m->stage,'d'=>substr((string)$m->starts_at,5,5),'hs'=>$m->home_score,'as'=>$m->away_score,'id'=>(int)$m->id];
        }
        $out=[];
        foreach($teams as $t){
            $c=$countries[strtoupper((string)$t->country_code)]??null;if(!$c)continue;
            $id=(int)$t->id;$level=$id===$champion?7:($reach[$id]??1);
            $out[]=['id'=>$id,'code'=>$t->code,'name'=>$t->name,'geo'=>$geo[strtoupper((string)$t->country_code)]??null,'country'=>strtoupper((string)$t->country_code),'lat'=>(float)$c['latitude'],'lng'=>(float)$c['longitude'],'flag'=>TeamUi::url($t),'conf'=>$t->confederation,'group'=>$t->group_name,'reach'=>$level,'url'=>'/teams/'.$t->slug];
        }
        return ['teams'=>$out,'venues'=>array_values($venues),'games'=>$games,'champion'=>$champion];
    }
}
