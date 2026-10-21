<?php
/** Native team list/detail vertical slice (AR, Controller, View). */
declare(strict_types=1);
namespace app\controllers;
use app\models\{Team,Player,MatchGame,Comment};
use app\services\CatalogService;
use Yii;
final class TeamController extends BaseController
{
    public function actionIndex() { return $this->render('/catalog/teams',['provider'=>(new CatalogService())->teams(Yii::$app->request->get())]); }
    public function actionView(string $id)
    {
        $team=(new CatalogService())->find(Team::class,$id);
        return $this->render('/catalog/team',['model'=>$team,'players'=>Player::find()->where(['team_id'=>$team->id])->andWhere(['not',['official_import_id'=>null]])->orderBy(['shirt_number'=>SORT_ASC])->all(),
            'matches'=>MatchGame::find()->with(['homeTeam','awayTeam'])->where(['or',['home_team_id'=>$team->id],['away_team_id'=>$team->id]])->andWhere(['not',['official_import_id'=>null]])->orderBy(['starts_at'=>SORT_ASC])->all(),
            'comments'=>Comment::find()->with('user')->where(['entity_type'=>'team','entity_id'=>$team->id,'status'=>'visible'])->orderBy(['id'=>SORT_DESC])->limit(30)->all()]);
    }
}
