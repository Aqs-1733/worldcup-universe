<?php
/** Publisher-feed list, status and user-triggered refresh. */
declare(strict_types=1);
namespace app\controllers;

use app\models\{Comment, NewsArticle, NewsSource, Tag};
use app\services\CatalogService;
use Yii;
use yii\web\NotFoundHttpException;

final class NewsController extends BaseController
{
    public function actionIndex()
    {
        return $this->render('/catalog/news', [
            'provider' => (new CatalogService())->news(Yii::$app->request->get()),
            'tags' => Tag::find()->where(['slug' => ['match', 'tactics', 'team', 'fan', 'entertainment', 'worldcup']])->orderBy('id')->all(),
            'sources' => NewsSource::find()->where(['is_enabled' => 1])->all(),
        ]);
    }

    public function actionStatus()
    {
        $published = NewsArticle::find()->where(['status' => 'published'])->andWhere(['<>', 'source_name', '赛事简报']);
        $updated = NewsSource::find()->where(['is_enabled' => 1])->max('last_synced_at');
        $articleUpdated = (clone $published)->max('updated_at');
        return $this->asJson([
            'count' => (int) (clone $published)->count(),
            'latest' => (int) (clone $published)->max('id'),
            'updated' => $updated,
            'revision' => (string) $updated . ':' . (string) $articleUpdated,
        ]);
    }

    public function actionView(int $id)
    {
        $model = NewsArticle::findOne(['id' => $id, 'status' => 'published']);
        if (!$model) {
            throw new NotFoundHttpException();
        }
        return $this->render('/catalog/article', [
            'model' => $model,
            'comments' => Comment::find()->with('user')->where(['entity_type' => 'news', 'entity_id' => $id, 'status' => 'visible'])->orderBy(['id' => SORT_DESC])->limit(50)->all(),
        ]);
    }

    public function actionRefresh()
    {
        $this->posted();
        if (!Yii::$app->cache->add('news-sync-lock', 1, 60)) {
            Yii::$app->session->setFlash('info', '新闻源正在更新，请稍后刷新页面。');
            return $this->redirect('/news');
        }

        $result = (new \app\services\NewsImportService())->sync();
        $failed = count(array_filter($result['sources'], static fn(array $source): bool => $source['status'] === 'failed'));
        $changed = (int) $result['saved'] + (int) $result['updated'];
        if ($failed) {
            Yii::$app->session->setFlash('warning', '新闻源已检查，部分来源暂时不可用。');
        } elseif ($changed) {
            Yii::$app->session->setFlash('success', '新闻已更新。');
        } else {
            Yii::$app->session->setFlash('info', '已检查新闻源，暂时没有新的报道。');
        }
        return $this->redirect('/news');
    }
}
