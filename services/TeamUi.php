<?php
/** Shared, escaped national-team presentation; every named team carries its flag. */
declare(strict_types=1);
namespace app\services;
use yii\helpers\Html;
final class TeamUi
{
    public static function url($team): string
    {
        if (!$team) { return ''; }
        $code=is_array($team)?($team['code']??''):($team->code??'');
        if (preg_match('/^[A-Z]{3}$/',(string)$code) && is_file(ROOT_PATH.'/public/assets/media/flags/'.$code.'.png')) { return '/assets/media/flags/'.$code.'.png'; }
        $url=is_array($team)?($team['flag_url']??''):($team->flag_url??'');
        return preg_match('~^https://(?:api\.fifa\.com|digitalhub\.fifa\.com)/~',(string)$url)?$url:'';
    }
    public static function flag($team,string $class=''): string
    {
        $src=self::url($team);
        if (!$src) { return '<span class="flag-placeholder" aria-label="球队待定">⚑</span>'; }
        $name=is_array($team)?($team['name']??'球队'):($team->name??'球队');
        return Html::img($src,['class'=>'team-flag '.$class,'alt'=>$name.'国旗','width'=>30,'height'=>20,'loading'=>'lazy','decoding'=>'async']);
    }
    public static function label($team,string $class='',string $fallback='待定'): string
    {
        $name=is_array($team)?($team['name']??$fallback):($team?->name??$fallback);
        return Html::tag('span',self::flag($team).Html::tag('span',Html::encode($name)),['class'=>'team-label '.$class]);
    }
}
