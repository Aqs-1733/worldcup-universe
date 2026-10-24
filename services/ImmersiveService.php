<?php
/** Read-only data for the motion-led home page; every fact still comes from verified records. */
declare(strict_types=1);
namespace app\services;
use app\models\{MatchGame,Player};
final class ImmersiveService
{
    /** Players whose official portrait is cached locally, so the rail never shows broken images. */
    public function showcasePlayers(int $limit=12): array
    {
        $file=ROOT_PATH.'/public/assets/media/portraits/manifest.json';
        $manifest=is_file($file)?(json_decode((string)file_get_contents($file),true)?:[]):[];
        $sources=array_values(array_filter(array_column($manifest,'source_url')));
        if(!$sources)return [];
        $config=require ROOT_PATH.'/config/presentation.php';
        $pinned=$config['featuredPlayerSlugs']??[];
        $players=Player::find()->where(['photo_url'=>$sources])->andWhere(['not',['official_import_id'=>null]])->with('team')->orderBy(['caps'=>SORT_DESC,'id'=>SORT_ASC])->limit(80)->all();
        usort($players,static fn($a,$b)=>(int)!in_array($a->slug,$pinned,true)<=>(int)!in_array($b->slug,$pinned,true));
        $seen=[];$picked=[];
        // One star per nation first, so the rail reads as a tour of the tournament.
        foreach($players as $p){$code=$p->team?->code;if(isset($seen[$code]))continue;$seen[$code]=true;$picked[]=$p;if(count($picked)>=$limit)break;}
        foreach($players as $p){if(count($picked)>=$limit)break;if(!in_array($p,$picked,true))$picked[]=$p;}
        return $picked;
    }
    public function finalMatch(): ?MatchGame
    {
        return MatchGame::find()->where(['stage'=>'决赛'])->andWhere(['not',['official_import_id'=>null]])->with(['homeTeam','awayTeam','venue'])->one();
    }
    /** Knockout results first, newest first, for the scrolling ticker. */
    public function tickerMatches(int $limit=24): array
    {
        return MatchGame::find()->where(['status'=>'finished'])->andWhere(['not',['official_import_id'=>null]])->andWhere(['not',['home_team_id'=>null]])->with(['homeTeam','awayTeam'])->orderBy(['starts_at'=>SORT_DESC])->limit($limit)->all();
    }
    public static function winnerSide(MatchGame $m): ?string
    {
        if($m->home_score===null||$m->away_score===null)return null;
        if($m->home_score!==$m->away_score)return $m->home_score>$m->away_score?'home':'away';
        if($m->home_penalty_score!==null)return $m->home_penalty_score>$m->away_penalty_score?'home':'away';
        if($m->winner_fifa_id){return $m->homeTeam?->fifa_id===$m->winner_fifa_id?'home':($m->awayTeam?->fifa_id===$m->winner_fifa_id?'away':null);}
        return null;
    }
}
