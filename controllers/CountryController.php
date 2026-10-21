<?php
/** Globe and accessible country-list read models use Yii2 AR. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{CountryProfile,Venue,Team};
final class CountryController extends BaseController
{
    public function actionIndex()
    {
        $teams=Team::find()->andWhere(['not',['official_import_id'=>null]])->asArray()->all(); $map=[]; foreach($teams as $team) { $code=strtolower((string)$team['country_code']); $map[$code][]=$team; }
        $countries=CountryProfile::find()->orderBy(['country_name_original'=>SORT_ASC])->asArray()->all();
        foreach ($countries as &$country) { $linked=$map[strtolower($country['country_code'])]??[]; $country['linked_teams']=$linked; $country['team_names']=implode('、',array_column($linked,'name_cn')); $country['team_flag_url']=$linked[0]['flag_url']??''; $country['team_flag_emoji']=$linked[0]['flag_emoji']??''; }
        unset($country);
        $venues=Venue::find()->where(['id'=>\app\models\MatchGame::find()->select('venue_id')->where(['not',['official_import_id'=>null]])])->asArray()->all(); foreach($venues as &$venue) { $venue['country']=$venue['country_code']; $venue['city']=$venue['city_cn']?:$venue['city_original']; $venue['name']=$venue['name_cn']?:$venue['name_original']; } unset($venue);
        return $this->render('/countries',['countries'=>$countries,'venues'=>$venues]);
    }
}
