<?php
/** Public read queries and paginated view models, separate from presentation. */
declare(strict_types=1);
namespace app\services;
use app\models\{Team,Player,MatchGame,Standing,PlayerStatistic,MatchProgression,NewsArticle,CountryProfile,TeamMember,AdminPost};
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
final class CatalogService
{
    public function featuredPlayers(): array
    {
        $config=require ROOT_PATH.'/config/presentation.php';
        $slugs=$config['featuredPlayerSlugs'];
        $players=Player::find()->where(['slug'=>$slugs])->andWhere(['not',['official_import_id'=>null]])->with('team')->indexBy('slug')->all();
        $featured=array_values(array_filter(array_map(static fn($slug)=>$players[$slug]??null,$slugs)));
        return $featured?:Player::find()->where(['not',['official_import_id'=>null]])->with('team')->orderBy(['caps'=>SORT_DESC])->limit(3)->all();
    }
    public function teams(array $filters=[]): ActiveDataProvider
    {
        $q=Team::find()->where(['not',['official_import_id'=>null]])->with('featurePlayer')->orderBy(TeamPhoto::order());
        if (!empty($filters['q'])) { $q->andWhere(['or',['like','name_cn',mb_substr((string)$filters['q'],0,120)],['like','name_original',mb_substr((string)$filters['q'],0,120)]]); }
        $q->andFilterWhere(['confederation'=>$filters['confederation']??null]);
        return $this->provider($q,24);
    }
    public function players(array $filters=[]): ActiveDataProvider
    {
        $q=Player::find()->where(['not',['official_import_id'=>null]])->with('team')->orderBy(['caps'=>SORT_DESC,'id'=>SORT_ASC]);
        if (!empty($filters['q'])) { $search=mb_substr((string)$filters['q'],0,120); $q->andWhere(['or',['like','name_cn',$search],['like','name_original',$search],['like','club',$search]]); }
        $q->andFilterWhere(['team_id'=>$filters['team_id']??null,'position'=>$filters['position']??null]); return $this->provider($q,24);
    }
    public function matches(array $filters=[]): ActiveDataProvider
    {
        $q=MatchGame::find()->where(['not',['official_import_id'=>null]])->with(['homeTeam','awayTeam','venue'])->orderBy(['starts_at'=>SORT_ASC,'id'=>SORT_ASC]);
        $q->andFilterWhere(['stage'=>$filters['stage']??null,'status'=>$filters['status']??null]);
        if (!empty($filters['team_id'])) { $q->andWhere(['or',['home_team_id'=>$filters['team_id']],['away_team_id'=>$filters['team_id']]]); }
        return $this->provider($q,32);
    }
    public function news(array $filters=[]): ActiveDataProvider
    {
        $q=NewsArticle::find()->with('tags')->where(['news_articles.status'=>'published'])->andWhere(['<>','news_articles.source_name','赛事简报'])->orderBy(['news_articles.published_at'=>SORT_DESC,'news_articles.id'=>SORT_DESC]);
        $now=date('Y-m-d H:i:s');
        $q->andWhere(['or',['news_articles.published_at'=>null],['<=','news_articles.published_at',$now]]);
        if(empty($filters['q'])&&empty($filters['source'])) {
            // Interleave publishers by their own recency, instead of letting one feed fill every card.
            $rank=(new \yii\db\Query())->select(['id','source_rank'=>new Expression('ROW_NUMBER() OVER (PARTITION BY source_id ORDER BY published_at DESC,id DESC)')])->from('news_articles')->where(['status'=>'published'])->andWhere(['<>','source_name','赛事简报'])->andWhere(['or',['published_at'=>null],['<=','published_at',$now]]);
            $q->leftJoin(['feed_rank'=>$rank],'feed_rank.id=news_articles.id')->andWhere(['or',['news_articles.published_at'=>null],['>=','news_articles.published_at',date('Y-m-d H:i:s',time()-30*86400)]])->orderBy(['feed_rank.source_rank'=>SORT_ASC,'news_articles.published_at'=>SORT_DESC,'news_articles.id'=>SORT_DESC]);
        }
        if (!empty($filters['q'])) { $q->andWhere(['or',['like','title_cn',mb_substr((string)$filters['q'],0,120)],['like','title_original',mb_substr((string)$filters['q'],0,120)]]); }
        if (!empty($filters['tag'])) { $q->joinWith('tags')->andWhere(['tags.slug'=>$filters['tag']])->distinct(); }
        $q->andFilterWhere(['source_id'=>$filters['source']??null]);
        return $this->provider($q,12);
    }
    public function find(string $class,string|int $id)
    {
        $model=$class::findOne(ctype_digit((string)$id)?['id'=>(int)$id]:['slug'=>$id]);
        if ($model && in_array($class,[Team::class,Player::class,MatchGame::class],true) && !$model->official_import_id) { $model=null; }
        if (!$model) { throw new NotFoundHttpException('内容不存在。'); } return $model;
    }
    public function stats(): array
    {
        return ['standings'=>Standing::find()->where(['not',['official_import_id'=>null]])->with('team')->orderBy(['group_name'=>SORT_ASC,'points'=>SORT_DESC,'goal_difference'=>SORT_DESC,'goals_for'=>SORT_DESC])->all(),
            'scorers'=>PlayerStatistic::find()->joinWith('player')->where(['not',['player_statistics.official_import_id'=>null]])->andWhere(['not',['player_id'=>null]])->andWhere(['not',['player_statistics.goals'=>null]])->orderBy(['player_statistics.goals'=>SORT_DESC,'player_statistics.assists'=>SORT_DESC])->limit(20)->all()];
    }
    public function bracket(): array
    {
        return MatchProgression::find()->where(['is_verified'=>1])->asArray()->all();
    }
    public function home(): array
    {
        return ['hero'=>\app\models\MediaAsset::findOne(['asset_key'=>'home-hero','status'=>'approved']),'teams'=>Team::find()->where(['not',['official_import_id'=>null]])->orderBy(TeamPhoto::order())->limit(6)->all(),
            'players'=>$this->featuredPlayers(),
            'matches'=>MatchGame::find()->where(['not',['official_import_id'=>null]])->with(['homeTeam','awayTeam'])->orderBy(['starts_at'=>SORT_DESC])->limit(4)->all(),
            'news'=>NewsArticle::find()->with('tags')->where(['status'=>'published'])->andWhere(['<>','source_name','赛事简报'])->orderBy(['published_at'=>SORT_DESC])->limit(3)->all(),
            'members'=>TeamMember::find()->where(['is_visible'=>1])->orderBy(['sort_order'=>SORT_ASC])->all(),
            'announcements'=>AdminPost::find()->where(['status'=>'published'])->orderBy(['published_at'=>SORT_DESC])->limit(3)->all()];
    }
    private function provider($q,int $size): ActiveDataProvider { return new ActiveDataProvider(['query'=>$q,'pagination'=>['defaultPageSize'=>$size,'pageSizeLimit'=>[1,50]],'sort'=>false]); }
}
