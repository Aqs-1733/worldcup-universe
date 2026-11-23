<?php
/**
 * 文件作用：球员控制器：球员搜索、筛选和球员详情。
 * 作者：崔迪_2412439
 */
declare(strict_types=1);
namespace app\controllers;
use app\models\{Player,Team};
use app\services\CatalogService;
use Yii;
final class PlayerController extends BaseController
{
    public function actionIndex() { return $this->render('/catalog/players',['provider'=>(new CatalogService())->players(Yii::$app->request->get()),'featured'=>(new CatalogService())->featuredPlayers(),'positions'=>Player::find()->andWhere(['not',['official_import_id'=>null]])->select('position')->distinct()->column(),'teams'=>Team::find()->andWhere(['not',['official_import_id'=>null]])->orderBy(['name_original'=>SORT_ASC])->all()]); }
    public function actionView(string $id) { return $this->render('/catalog/player',['model'=>(new CatalogService())->find(Player::class,$id)]); }
}
