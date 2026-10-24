<?php
/** Versioned JSON read/write facade over the same native Yii2 services. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{Team,Player,MatchGame,CountryProfile,ForumThread,ForumReply,ForumCategory,Notification};
use app\services\{CatalogService,ForumService,PreferenceService};
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\filters\VerbFilter;
final class ApiController extends BaseController
{
    public function behaviors(): array { return ['verbs'=>['class'=>VerbFilter::class,'actions'=>[
        'session'=>['GET'],'teams'=>['GET'],'team'=>['GET'],'players'=>['GET'],'player'=>['GET'],
        'matches'=>['GET'],'match'=>['GET'],'standings'=>['GET'],'stats'=>['GET'],'news'=>['GET'],
        'countries'=>['GET'],'globe'=>['GET'],'home'=>['GET'],'bracket'=>['GET'],'forum'=>['GET','POST'],
        'thread'=>['GET','PATCH','DELETE'],'profile'=>['GET','POST'],
    ]]]; }
    public function beforeAction($action): bool { Yii::$app->response->format=Response::FORMAT_JSON; return parent::beforeAction($action); }
    public function actionSession(): array { return ['data'=>['user'=>Yii::$app->user->identity?->toArray(),'csrfToken'=>Yii::$app->request->csrfToken,'csrfParam'=>Yii::$app->request->csrfParam,'isAdmin'=>Yii::$app->user->identity?->isAdmin()??false]]; }
    public function actionTeams(): array { return $this->page((new CatalogService())->teams(Yii::$app->request->get())); }
    public function actionPlayers(): array { return $this->page((new CatalogService())->players(Yii::$app->request->get())); }
    public function actionMatches(): array { return $this->page((new CatalogService())->matches(Yii::$app->request->get())); }
    public function actionTeam(string $id): array { $m=(new CatalogService())->find(Team::class,$id); return ['data'=>array_merge($m->toArray(),['players'=>$this->arrays($m->players)])]; }
    public function actionPlayer(string $id): array { $m=(new CatalogService())->find(Player::class,$id); return ['data'=>array_merge($m->toArray(),['team'=>$m->team?->toArray(),'statistics'=>$this->arrays($m->statistics)])]; }
    public function actionMatch(int $id): array { $m=(new CatalogService())->find(MatchGame::class,$id); return ['data'=>array_merge($m->toArray(),['homeTeam'=>$m->homeTeam?->toArray(),'awayTeam'=>$m->awayTeam?->toArray(),'events'=>$this->arrays($m->events),'stats'=>$this->arrays($m->stats),'lineups'=>$this->arrays($m->lineups)])]; }
    public function actionNews(): array { return $this->page((new CatalogService())->news(Yii::$app->request->get())); }
    public function actionCountries(): array { return $this->page(new ActiveDataProvider(['query'=>CountryProfile::find()->orderBy('country_name_original'),'pagination'=>['pageSize'=>50],'sort'=>false])); }
    public function actionGlobe(string $key): array { return ['data'=>(new \app\services\GlobeCountryService())->detail($key)]; }
    public function actionStandings(): array { return ['data'=>$this->arrays((new CatalogService())->stats()['standings'])]; }
    public function actionStats(): array { $d=(new CatalogService())->stats(); return ['data'=>['standings'=>$this->arrays($d['standings']),'scorers'=>$this->arrays($d['scorers'])],'meta'=>['emptyMeaning'=>'未采集到统计时返回空数组，不生成估计值']]; }
    public function actionBracket(): array { return ['data'=>(new CatalogService())->bracket(),'meta'=>['verifiedOnly'=>true]]; }
    public function actionHome(): array { $d=(new CatalogService())->home(); $hero=$d['hero']; unset($d['hero']); foreach($d as &$items) { $items=$this->arrays($items); } unset($items); $d['hero']=$hero?->toArray(); return ['data'=>$d]; }
    public function actionForum(): array
    {
        if (Yii::$app->request->isPost) { $model=(new ForumService())->saveThread($this->actor(),Yii::$app->request->bodyParams); Yii::$app->response->statusCode=$model->hasErrors()?422:201; return $model->hasErrors()?['errors'=>$model->errors]:['data'=>$model->toArray()]; }
        $q=ForumThread::find()->where(['status'=>'published'])->andFilterWhere(['team_id'=>Yii::$app->request->get('team_id'),'category_id'=>Yii::$app->request->get('category_id')])->orderBy(['is_pinned'=>SORT_DESC,'last_activity_at'=>SORT_DESC]); return $this->page(new ActiveDataProvider(['query'=>$q,'pagination'=>['pageSize'=>20],'sort'=>false]));
    }
    public function actionThread(int $id): array
    {
        if (Yii::$app->request->isDelete) { (new ForumService())->remove($this->actor(),'thread',$id); return ['data'=>['deleted'=>true]]; }
        if (Yii::$app->request->isPatch) { $m=(new ForumService())->saveThread($this->actor(),Yii::$app->request->bodyParams,$id); Yii::$app->response->statusCode=$m->hasErrors()?422:200; return $m->hasErrors()?['errors'=>$m->errors]:['data'=>$m->toArray()]; }
        $m=(new ForumService())->readable($id,Yii::$app->user->identity); return ['data'=>array_merge($m->toArray(),['author'=>$m->author?->toArray(),'category'=>$m->category?->toArray(),'replies'=>$this->arrays(ForumReply::find()->with('user')->where(['thread_id'=>$id,'status'=>'visible'])->limit(30)->all())])];
    }
    public function actionProfile(): array
    {
        $actor=$this->actor();
        if (Yii::$app->request->isPost) { $m=(new PreferenceService())->save($actor,Yii::$app->request->bodyParams); Yii::$app->response->statusCode=$m->hasErrors()?422:200; return $m->hasErrors()?['errors'=>$m->errors]:['data'=>$m->toArray()]; }
        return ['data'=>['user'=>$actor->toArray(),'profile'=>$actor->profile?->toArray(),'unread'=>(int)Notification::find()->where(['user_id'=>$actor->id,'read_at'=>null])->count()]];
    }
    private function arrays(array $models): array { return array_map(fn($m)=>$m->toArray(),$models); }
    private function page(ActiveDataProvider $p): array { return ['data'=>$this->arrays($p->getModels()),'pagination'=>['total'=>$p->getTotalCount(),'page'=>$p->pagination->page+1,'pageSize'=>$p->pagination->pageSize]]; }
}
