<?php
/**
 * 文件作用：球队控制器：球队列表（搜索、按足联筛选）和球队详情。
 * 作者：崔迪_2412439
 */
declare(strict_types=1);
namespace app\controllers;
use app\models\{Team,Player,MatchGame,Comment};
use app\services\CatalogService;
use Yii;
final class TeamController extends BaseController
{
    // 球队列表：把 GET 里的搜索、筛选条件交给服务层分页查询
    public function actionIndex() { return $this->render('/catalog/teams',['provider'=>(new CatalogService())->teams(Yii::$app->request->get())]); }
    public function actionView(string $id)
    {
        $team=(new CatalogService())->find(Team::class,$id); // 找不到会返回 404
        // 再查出本队球员（按号码）、比赛记录和最近 30 条留言
        return $this->render('/catalog/team',['model'=>$team,'players'=>Player::find()->where(['team_id'=>$team->id])->andWhere(['not',['official_import_id'=>null]])->orderBy(['shirt_number'=>SORT_ASC])->all(),
            'matches'=>MatchGame::find()->with(['homeTeam','awayTeam'])->where(['or',['home_team_id'=>$team->id],['away_team_id'=>$team->id]])->andWhere(['not',['official_import_id'=>null]])->orderBy(['starts_at'=>SORT_ASC])->all(),
            'comments'=>Comment::find()->with('user')->where(['entity_type'=>'team','entity_id'=>$team->id,'status'=>'visible'])->orderBy(['id'=>SORT_DESC])->limit(30)->all()]);
    }
}
