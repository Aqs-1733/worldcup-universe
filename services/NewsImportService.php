<?php
/** Live publisher RSS/Atom ingestion, safe metadata, source snapshots and bounded excerpts. */
declare(strict_types=1);
namespace app\services;
use app\models\{NewsArticle,NewsSource,Tag};
use Yii;
final class NewsImportService
{
    public function sync(int $perSource=24): array
    {
        if(!Yii::$app->cache->add('news-import-running',1,150)) {return ['saved'=>0,'updated'=>0,'busy'=>true,'sources'=>[]];}
        $result=['saved'=>0,'updated'=>0,'sources'=>[]];
        try {
            foreach(NewsSource::find()->where(['is_enabled'=>1])->all() as $source) {
                try {
                    $xml=$this->fetch((string)$source->rss_url);$items=$this->parse($xml);$saved=0;$updated=0;
                    $folder=ROOT_PATH.'/storage/source_snapshots/news';if(!is_dir($folder))mkdir($folder,0775,true);
                    file_put_contents($folder.'/source-'.$source->id.'.xml',$xml,LOCK_EX);
                    foreach(array_slice($items,0,max(1,min(60,$perSource))) as $item) {
                        if(parse_url($item['url'],PHP_URL_HOST)==='www.skysports.com'&&!str_starts_with((string)parse_url($item['url'],PHP_URL_PATH),'/football/')) {continue;}
                        if($source->language_code==='zh-CN'&&!preg_match('/足球|世界杯|国足|男足|女足|欧冠|欧联|英超|西甲|意甲|德甲|中超|梅西|C罗|巴萨|皇马|曼联|切尔西|阿森纳|利物浦/u',$item['title'].' '.$item['summary'])) {continue;}
                        $article=NewsArticle::findOne(['source_url'=>$item['url']]);$new=!$article;$article??=new NewsArticle();
                        if($new) {$article->source_id=$source->id;$article->source_name=$source->name;$article->status='published';$article->language_code=$source->language_code;$article->credibility_score=$source->trust_level;}
                        $article->title_original=mb_substr($item['title'],0,280);$article->summary_original=mb_substr($item['summary'],0,240);$article->published_at=$item['published_at'];$article->source_url=$item['url'];
                        $article->raw_payload_json=new \yii\db\JsonExpression(['kind'=>'publisher_rss','image'=>$item['image'],'categories'=>$item['categories'],'retrieved_at'=>gmdate(DATE_ATOM),'feed_url'=>$source->rss_url,'feed_sha256'=>hash('sha256',$xml)]);
                        if($source->language_code==='zh-CN') {$article->title_cn=$article->title_original;$article->summary_cn=$article->summary_original;}
                        if($article->save()) {$this->assignTags($article);$new?$saved++:$updated++;}
                    }
                    $source->last_synced_at=date('Y-m-d H:i:s');$source->last_error=null;$source->save(false);
                    $result['saved']+=$saved;$result['updated']+=$updated;$result['sources'][]=['name'=>$source->name,'saved'=>$saved,'updated'=>$updated,'status'=>'ok'];
                } catch(\Throwable $e) {
                    $source->last_error=mb_substr($e->getMessage(),0,1000);$source->save(false);$result['sources'][]=['name'=>$source->name,'status'=>'failed'];Yii::warning($e->getMessage(),'rss');
                }
            }
            return $result;
        } finally {Yii::$app->cache->delete('news-import-running');}
    }
    public function parse(string $xml): array
    {
        if(strlen($xml)>2097152)throw new \RuntimeException('RSS document too large');
        $old=libxml_use_internal_errors(true);$root=simplexml_load_string($xml,\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);libxml_clear_errors();libxml_use_internal_errors($old);
        if(!$root)throw new \RuntimeException('Invalid RSS/Atom document');
        $nodes=$root->channel->item??$root->entry??[];$items=[];
        foreach($nodes as $node) {
            $url=trim((string)($node->link['href']??$node->link));$title=trim(strip_tags((string)$node->title));
            if(!preg_match('~^https?://~i',$url)||strlen($url)>900||$title==='')continue;
            $time=strtotime((string)($node->pubDate??$node->published??$node->updated));$image='';$media=$node->children('http://search.yahoo.com/mrss/');
            // Feeds often list 140px, 460px and 700px variants in that order.
            // Choose the largest publisher-supplied URL, never invent an unsigned derivative.
            $bestWidth=-1;
            foreach([$media->content,$media->thumbnail,$media->group->content,$media->group->thumbnail,$node->enclosure] as $images) {if(!$images)continue;foreach($images as $im) {$attrs=$im->attributes();if(($candidate=$this->imageUrl((string)$attrs['url']))&&($width=(int)($attrs['width']??0))>$bestWidth) {$image=$candidate;$bestWidth=$width;}}}
            $description=(string)($node->description??$node->summary??$node->content);
            if(!$image&&preg_match('~<img[^>]+src=["\x27]([^"\x27]+)~i',$description,$match)) {$image=$this->imageUrl(html_entity_decode($match[1],ENT_QUOTES|ENT_HTML5,'UTF-8'));}
            $items[]=['url'=>$url,'title'=>$title,'summary'=>trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($description),ENT_QUOTES|ENT_HTML5,'UTF-8'))),'published_at'=>$time?date('Y-m-d H:i:s',$time):null,'image'=>$image,'categories'=>array_map('strval',iterator_to_array($node->category,false))];
        }
        return $items;
    }
    public function imageUrl(string $url): string
    {
        $host=strtolower((string)parse_url($url,PHP_URL_HOST));$allowed=['ichef.bbci.co.uk','a.espncdn.com','e0.365dm.com','i.guim.co.uk','media.guim.co.uk','static01.nyt.com','sportshub.cbsistatic.com','www.chinanews.com.cn','image.cns.com.cn','i2.chinanews.com.cn'];
        return strlen($url)<=1200&&parse_url($url,PHP_URL_SCHEME)==='https'&&in_array($host,$allowed,true)?$url:'';
    }
    public function backfillTags(): int
    {
        $count=0;foreach(NewsArticle::find()->where(['not',['source_id'=>null]])->each(100) as $article){$this->assignTags($article);$count++;}return $count;
    }
    private function fetch(string $url): string
    {
        $allowed=['feeds.bbci.co.uk','www.espn.com','www.theguardian.com','www.cbssports.com','rss.nytimes.com','www.skysports.com','www.chinanews.com.cn'];
        if(parse_url($url,PHP_URL_SCHEME)!=='https'||!in_array(strtolower((string)parse_url($url,PHP_URL_HOST)),$allowed,true))throw new \RuntimeException('RSS host is not in the configured source allowlist');
        $curl=curl_init($url);$body='';curl_setopt_array($curl,[CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_OPTIONS=>CURLSSLOPT_NATIVE_CA,CURLOPT_USERAGENT=>'WorldCupUniverse-CourseProject/2.0',CURLOPT_WRITEFUNCTION=>function($c,$data)use(&$body){if(strlen($body)+strlen($data)>2097152)return 0;$body.=$data;return strlen($data);}]);
        $ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if(!$ok||$status!==200)throw new \RuntimeException('RSS request failed: HTTP '.$status.' '.$error);return $body;
    }
    private function assignTags(NewsArticle $article): void
    {
        $text=mb_strtolower($article->title_original.' '.$article->summary_original);$groups=['tactics'=>['tactic','formation','pressing','战术','阵型','复盘'],'team'=>['squad','transfer','coach','injury','阵容','转会','教练','伤病'],'fan'=>['fans','supporters','球迷','看台'],'entertainment'=>['celebration','ceremony','庆祝','开幕','花絮']];
        $slugs=['football'];foreach($groups as $slug=>$words){foreach($words as $word){if(str_contains($text,$word)){$slugs[]=$slug;break;}}}if(count($slugs)===1)$slugs[]='match';if(preg_match('/world\s*cup|世界杯/iu',$text))$slugs[]='worldcup';
        Yii::$app->db->createCommand()->delete('news_article_tags',['article_id'=>$article->id])->execute();foreach(Tag::find()->where(['slug'=>$slugs])->all() as $tag){Yii::$app->db->createCommand()->insert('news_article_tags',['article_id'=>$article->id,'tag_id'=>$tag->id])->execute();}
    }
}
