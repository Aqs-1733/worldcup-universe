<?php
/** Native fixtures, verified progression data and real-statistics controller. */
declare(strict_types=1);
namespace app\controllers;
use app\models\MatchGame;
use app\services\CatalogService;
use Yii;
final class MatchController extends BaseController
{
    public function actionIndex()
    {
        $service=new CatalogService();$query=MatchGame::find()->with(['homeTeam','awayTeam'])->where(['not',['official_import_id'=>null]])->orderBy(['starts_at'=>SORT_ASC,'match_number'=>SORT_ASC]);
        $query->andFilterWhere(['stage'=>Yii::$app->request->get('stage'),'status'=>Yii::$app->request->get('status')]);
        if($team=Yii::$app->request->get('team_id')){$query->andWhere(['or',['home_team_id'=>$team],['away_team_id'=>$team]]);}
        $groupFixtures=[];$roundFixtures=[];foreach($query->all() as $game){if($game->stage==='小组赛'){$groupFixtures[$game->group_name][]=$game;}else{$roundFixtures[$game->stage][]=$game;}}ksort($groupFixtures);
        return $this->render('/catalog/matches',['groupFixtures'=>$groupFixtures,'roundFixtures'=>$roundFixtures,'provider'=>$service->matches(Yii::$app->request->get()),'stages'=>MatchGame::find()->where(['not',['official_import_id'=>null]])->select('stage')->distinct()->column(),'links'=>$service->bracket(),'bracketMatches'=>MatchGame::find()->with(['homeTeam','awayTeam'])->where(['not',['official_import_id'=>null]])->andWhere(['<>','stage','小组赛'])->orderBy('match_number')->all(),'teams'=>\app\models\Team::find()->where(['not',['official_import_id'=>null]])->orderBy('name_original')->all()]);
    }
    public function actionView(int $id) { $model=MatchGame::find()->with(['homeTeam','awayTeam','venue','stats.team','events.player','lineups.player'])->where(['id'=>$id])->andWhere(['not',['official_import_id'=>null]])->one(); if(!$model) { throw new \yii\web\NotFoundHttpException(); } return $this->render('/catalog/match',['model'=>$model]); }
    public function actionStats() { return $this->redirect('/worldcup/standings',301); }
    public function actionStandings() { return $this->render('/catalog/stats',(new CatalogService())->stats()); }
}
