<?php
/**
 * 文件作用：赛事控制器：赛程与晋级、比赛详情、小组积分和数据统计页面。
 * 作者：梁靖泽_2412852
 */
declare(strict_types=1);
namespace app\controllers;
use app\models\MatchGame;
use app\services\CatalogService;
use Yii;
final class MatchController extends BaseController
{
    // 赛程与晋级页
    public function actionIndex()
    {
        // 按阶段、状态、球队筛选比赛
        $service=new CatalogService();$query=MatchGame::find()->with(['homeTeam','awayTeam'])->where(['not',['official_import_id'=>null]])->orderBy(['starts_at'=>SORT_ASC,'match_number'=>SORT_ASC]);
        $query->andFilterWhere(['stage'=>Yii::$app->request->get('stage'),'status'=>Yii::$app->request->get('status')]);
        if($team=Yii::$app->request->get('team_id')){$query->andWhere(['or',['home_team_id'=>$team],['away_team_id'=>$team]]);}
        // 小组赛按组分开，淘汰赛按轮次分开
        $groupFixtures=[];$roundFixtures=[];foreach($query->all() as $game){if($game->stage==='小组赛'){$groupFixtures[$game->group_name][]=$game;}else{$roundFixtures[$game->stage][]=$game;}}ksort($groupFixtures);
        return $this->render('/catalog/matches',['groupFixtures'=>$groupFixtures,'roundFixtures'=>$roundFixtures,'provider'=>$service->matches(Yii::$app->request->get()),'stages'=>MatchGame::find()->where(['not',['official_import_id'=>null]])->select('stage')->distinct()->column(),'links'=>$service->bracket(),'bracketMatches'=>MatchGame::find()->with(['homeTeam','awayTeam'])->where(['not',['official_import_id'=>null]])->andWhere(['<>','stage','小组赛'])->orderBy('match_number')->all(),'teams'=>\app\models\Team::find()->where(['not',['official_import_id'=>null]])->orderBy('name_original')->all()]);
    }
    // 比赛详情：同时加载双方球队、场馆、统计、事件和阵容
    public function actionView(int $id) { $model=MatchGame::find()->with(['homeTeam','awayTeam','venue','stats.team','events.player','lineups.player'])->where(['id'=>$id])->andWhere(['not',['official_import_id'=>null]])->one(); if(!$model) { throw new \yii\web\NotFoundHttpException(); } return $this->render('/catalog/match',['model'=>$model]); }
    // 旧地址 /stats 永久跳转到积分页
    public function actionStats() { return $this->redirect('/worldcup/standings',301); }
    // 小组积分与数据统计
    public function actionStandings() { return $this->render('/catalog/stats',(new CatalogService())->stats()); }
}
