<?php
/** Native player search/filter/detail controller. */
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
