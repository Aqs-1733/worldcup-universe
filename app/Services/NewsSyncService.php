<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AppRepository;
use PDO;

final class NewsSyncService
{
    public function __construct(
        private readonly AppRepository $repo = new AppRepository(),
        private readonly HttpClient $http = new HttpClient(),
        private readonly ArkClient $ark = new ArkClient(),
    ) {
    }

    /** @return array{fetched:int,inserted:int,translated:int,failed:int,errors:array<int,string>} */
    public function sync(int $maxPerSource = 0): array
    {
        $maxPerSource = $maxPerSource > 0 ? $maxPerSource : max(1, (int) env('NEWS_MAX_PER_SOURCE', 5));
        $pdo = Database::pdo();
        $sources = $pdo->query('SELECT * FROM news_sources WHERE is_enabled = 1 AND rss_url IS NOT NULL ORDER BY trust_level DESC')->fetchAll();
        $result = ['fetched' => 0, 'inserted' => 0, 'translated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($sources as $source) {
            try {
                $rssTimeout = max(2, (int) env('NEWS_RSS_TIMEOUT', 5));
                $xmlBody = $this->http->get((string) $source['rss_url'], ['Accept' => 'application/rss+xml, application/xml, text/xml'], $rssTimeout);
                $xml = @simplexml_load_string($xmlBody, 'SimpleXMLElement', LIBXML_NOCDATA);
                if (!$xml) {
                    throw new \RuntimeException('RSS parse failed.');
                }

                $items = $xml->channel->item ?? $xml->entry ?? [];
                $articles = [];
                $count = 0;
                foreach ($items as $item) {
                    if ($count >= $maxPerSource) {
                        break;
                    }
                    $article = $this->articleFromItem((array) $source, $item);
                    if (!$article) {
                        continue;
                    }
                    $articles[] = $article;
                    $count++;
                }

                $needsTranslation = array_filter(
                    $articles,
                    static fn (array $article): bool => ($article['translation_status'] ?? 'pending') !== 'translated'
                        || empty($article['title_cn'])
                );
                $translations = $this->translateArticles($needsTranslation);
                foreach ($articles as $index => $article) {
                    if (isset($translations[$index])) {
                        $article = array_merge($article, $translations[$index]);
                        $article['translation_status'] = 'translated';
                        $result['translated']++;
                    }
                    $tags = $this->classify(
                        $article['title_original'] . ' ' .
                        ($article['summary_original'] ?? '') . ' ' .
                        ($article['title_cn'] ?? '') . ' ' .
                        ($article['summary_cn'] ?? '')
                    );
                    $articleId = $this->repo->upsertNews($article, $tags);
                    $result['fetched']++;
                    if ($articleId > 0) {
                        $result['inserted']++;
                    }
                }

                $stmt = $pdo->prepare('UPDATE news_sources SET last_synced_at = NOW(), last_error = NULL WHERE id = ?');
                $stmt->execute([$source['id']]);
            } catch (\Throwable $error) {
                $result['failed']++;
                $result['errors'][] = $source['name'] . ': ' . $error->getMessage();
                $stmt = $pdo->prepare('UPDATE news_sources SET last_error = ? WHERE id = ?');
                $stmt->execute([$error->getMessage(), $source['id']]);
            }
        }

        return $result;
    }

    /** @return array{checked:int,translated:int,failed:int} */
    public function translatePending(int $limit = 40): array
    {
        $limit = max(1, $limit);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT *
             FROM news_articles
             WHERE translation_status <> "translated"
                OR translation_status = ""
                OR title_cn IS NULL
                OR title_cn = ""
             ORDER BY COALESCE(published_at, fetched_at) DESC, id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $result = ['checked' => count($rows), 'translated' => 0, 'failed' => 0];
        foreach (array_chunk($rows, 5) as $chunk) {
            $translations = $this->translateArticles($chunk);
            foreach ($chunk as $index => $article) {
                $fields = $translations[$index] ?? [];
                if (!$fields) {
                    $result['failed']++;
                    $pdo->prepare('UPDATE news_articles SET translation_status = "failed" WHERE id = ?')->execute([$article['id']]);
                    continue;
                }

                $update = $pdo->prepare(
                    'UPDATE news_articles
                     SET title_cn = COALESCE(NULLIF(?, ""), title_cn),
                         summary_cn = COALESCE(NULLIF(?, ""), summary_cn),
                         content_cn = COALESCE(NULLIF(?, ""), content_cn),
                         translation_status = "translated"
                     WHERE id = ?'
                );
                $update->execute([
                    $fields['title_cn'] ?? null,
                    $fields['summary_cn'] ?? null,
                    $fields['content_cn'] ?? null,
                    $article['id'],
                ]);
                $result['translated']++;
            }
        }

        return $result;
    }

    private function articleFromItem(array $source, mixed $item): ?array
    {
        $title = trim((string) ($item->title ?? ''));
        $link = trim((string) ($item->link['href'] ?? $item->link ?? ''));
        if ($title === '' || $link === '') {
            return null;
        }

        $summary = $this->cleanText((string) ($item->description ?? $item->summary ?? ''));
        if (!$this->isFootballArticle($title, $summary, $link)) {
            return null;
        }

        $existing = $this->existingTranslatedArticle($link);
        if ($existing) {
            return [
                'source_id' => $source['id'],
                'source_name' => $source['name'],
                'source_url' => $link,
                'title_cn' => $existing['title_cn'],
                'title_original' => $title,
                'summary_cn' => $existing['summary_cn'],
                'summary_original' => $summary ?: ($existing['summary_original'] ?? null),
                'content_cn' => $existing['content_cn'],
                'content_original' => $existing['content_original'],
                'language_code' => (string) ($source['language_code'] ?? 'en'),
                'published_at' => $existing['published_at'],
                'credibility_score' => (int) $source['trust_level'],
                'translation_status' => 'translated',
                'raw_payload' => [
                    'rss_title' => $title,
                    'rss_link' => $link,
                    'content_source' => 'existing_translated',
                ],
            ];
        }

        $rssContent = $this->cleanText((string) ($item->children('content', true)->encoded ?? ''));
        $pageContent = $this->fetchArticleText($link);
        $content = $pageContent ?: ($rssContent ?: $summary);
        $publishedRaw = trim((string) ($item->pubDate ?? $item->published ?? $item->updated ?? ''));
        $publishedAt = $publishedRaw ? date('Y-m-d H:i:s', strtotime($publishedRaw) ?: time()) : null;
        $language = (string) ($source['language_code'] ?? 'en');
        $titleCn = is_chinese_text($title) ? $title : null;
        $summaryCn = $summary && is_chinese_text($summary) ? $summary : null;
        $contentCn = $content && is_chinese_text($content) ? $content : null;

        return [
            'source_id' => $source['id'],
            'source_name' => $source['name'],
            'source_url' => $link,
            'title_cn' => $titleCn,
            'title_original' => $title,
            'summary_cn' => $summaryCn,
            'summary_original' => $summary ?: null,
            'content_cn' => $contentCn,
            'content_original' => $content ?: null,
            'language_code' => $language,
            'published_at' => $publishedAt,
            'credibility_score' => (int) $source['trust_level'],
            'translation_status' => ($titleCn || $summaryCn || $contentCn) ? 'translated' : 'pending',
            'raw_payload' => [
                'rss_title' => $title,
                'rss_link' => $link,
                'content_source' => $pageContent ? 'article_page' : ($rssContent ? 'rss_content' : 'rss_summary'),
            ],
        ];
    }

    private function existingTranslatedArticle(string $sourceUrl): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT title_cn, summary_cn, summary_original, content_cn, content_original, published_at
             FROM news_articles
             WHERE source_url = ?
               AND translation_status = "translated"
               AND title_cn IS NOT NULL
               AND title_cn <> ""
             LIMIT 1'
        );
        $stmt->execute([$sourceUrl]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function fetchArticleText(string $url): ?string
    {
        try {
            $timeout = max(1, (int) env('NEWS_ARTICLE_TIMEOUT', 2));
            $html = $this->http->get($url, ['Accept' => 'text/html,application/xhtml+xml'], $timeout);
        } catch (\Throwable) {
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            libxml_clear_errors();
            return null;
        }
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script|//style|//noscript|//svg|//nav|//footer|//header|//form') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        $paragraphs = [];
        foreach ($xpath->query('//article//p|//*[@data-component="text-block"]//p|//main//p') ?: [] as $node) {
            $text = $this->cleanText((string) $node->textContent);
            if (mb_strlen($text) >= 40) {
                $paragraphs[] = $text;
            }
            if (count($paragraphs) >= 8) {
                break;
            }
        }

        $content = trim(implode("\n\n", array_unique($paragraphs)));
        return mb_strlen($content) >= 80 ? mb_substr($content, 0, 2600) : null;
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array<int, array{title_cn?:string,summary_cn?:string,content_cn?:string}>
     */
    private function translateArticles(array $articles): array
    {
        if (!$articles) {
            return [];
        }

        $provider = strtolower((string) env('NEWS_TRANSLATION_PROVIDER', 'auto'));
        if ($provider === 'mymemory') {
            return $this->translateArticlesWithMyMemory($articles);
        }
        if ($provider === 'ark') {
            return $this->translateArticlesWithArk($articles);
        }

        $arkTranslations = $this->translateArticlesWithArk($articles);
        $missingArticles = array_filter(
            $articles,
            static fn (array $article, int $index): bool => !isset($arkTranslations[$index]),
            ARRAY_FILTER_USE_BOTH
        );
        if (!$missingArticles) {
            return $arkTranslations;
        }

        return $arkTranslations + $this->translateArticlesWithMyMemory($missingArticles);
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array<int, array{title_cn?:string,summary_cn?:string,content_cn?:string}>
     */
    private function translateArticlesWithArk(array $articles): array
    {
        $result = [];
        foreach ($articles as $index => $article) {
            if (
                is_chinese_text($article['title_original'] ?? null) &&
                (empty($article['summary_original']) || is_chinese_text($article['summary_original'])) &&
                (empty($article['content_original']) || is_chinese_text($article['content_original']))
            ) {
                $result[$index] = [
                    'title_cn' => (string) $article['title_original'],
                    'summary_cn' => (string) ($article['summary_original'] ?? ''),
                    'content_cn' => (string) ($article['content_original'] ?? $article['summary_original'] ?? ''),
                ];
                continue;
            }

            $translated = $this->translateOneArticleWithArk($article);
            if ($translated) {
                $result[$index] = $translated;
            }
        }

        return $result;
    }

    /** @return array{title_cn?:string,summary_cn?:string,content_cn?:string} */
    private function translateOneArticleWithArk(array $article): array
    {
        if (!$this->ark->textReady()) {
            return [];
        }

        $title = trim((string) ($article['title_original'] ?? ''));
        if ($title === '') {
            return [];
        }

        try {
            $timeout = max(8, (int) env('NEWS_ARK_TRANSLATE_TIMEOUT', 18));
            $text = trim($this->ark->chat('Translate this football headline to Simplified Chinese only: ' . $title, 'concise', null, $timeout, 90, 0.1));
        } catch (\Throwable) {
            return [];
        }

        $titleCn = trim(preg_replace('/^#+\s*/u', '', $text) ?? $text);
        return $titleCn !== '' ? [
            'title_cn' => $titleCn,
            'summary_cn' => $titleCn,
            'content_cn' => $titleCn,
        ] : [];
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array<int, array{title_cn?:string,summary_cn?:string,content_cn?:string}>
     */
    private function translateArticlesWithMyMemory(array $articles): array
    {
        $result = [];
        foreach ($articles as $index => $article) {
            $title = (string) ($article['title_original'] ?? '');
            $summary = mb_substr((string) ($article['summary_original'] ?? $article['content_original'] ?? ''), 0, 420);

            if (is_chinese_text($title) && ($summary === '' || is_chinese_text($summary))) {
                $titleCn = $title;
                $summaryCn = $summary !== '' ? $summary : null;
            } else {
                $combined = $summary !== '' ? $title . "\n@@@\n" . $summary : $title;
                $translated = $this->translateTextWithMyMemory($combined, 760);
                $parts = $translated ? preg_split('/\s*@@@\s*/u', $translated, 2) : [];
                $titleCn = trim((string) ($parts[0] ?? '')) ?: null;
                $summaryCn = trim((string) ($parts[1] ?? '')) ?: null;
                if (!$summaryCn && $summary !== '' && $titleCn && mb_strlen($titleCn) > 80) {
                    $sentences = preg_split('/(?<=[。！？!?])\s*/u', $titleCn, 2);
                    $titleCn = trim((string) ($sentences[0] ?? $titleCn));
                    $summaryCn = trim((string) ($sentences[1] ?? '')) ?: null;
                }
            }

            $fields = array_filter([
                'title_cn' => $titleCn,
                'summary_cn' => $summaryCn,
                'content_cn' => $summaryCn,
            ], static fn (?string $value): bool => $value !== null && $value !== '');
            if ($fields) {
                $result[$index] = $fields;
            }
        }

        return $result;
    }

    private function translateTextWithMyMemory(string $text, int $limit): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (is_chinese_text($text)) {
            return $text;
        }

        $query = http_build_query([
            'q' => mb_substr($text, 0, $limit),
            'langpair' => 'en|zh-CN',
        ]);

        try {
            $body = $this->http->get('https://api.mymemory.translated.net/get?' . $query, [], max(4, (int) env('NEWS_TRANSLATE_TIMEOUT', 6)));
        } catch (\Throwable) {
            return null;
        }

        $json = json_decode($body, true);
        if ((int) ($json['responseStatus'] ?? 0) !== 200) {
            return null;
        }
        $translated = trim((string) ($json['responseData']['translatedText'] ?? ''));
        if ($translated === '' || $translated === $text || str_contains($translated, 'MYMEMORY WARNING')) {
            return null;
        }

        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function cleanText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s*\n\s*/u', "\n", $text) ?? $text;
        return trim($text);
    }

    private function isFootballArticle(string $title, string $summary, string $url): bool
    {
        $text = mb_strtolower($title . ' ' . $summary . ' ' . $url);
        $footballSignals = [
            'football', 'soccer', 'fifa', 'world cup', 'premier league', 'champions league',
            'europa league', 'la liga', 'serie a', 'bundesliga', 'ligue 1', 'mls', 'carabao cup',
            'facup', 'fa cup', 'arsenal', 'chelsea', 'liverpool', 'man utd', 'manchester united',
            'man city', 'manchester city', 'tottenham', 'barcelona', 'real madrid', 'bayern',
            'psg', 'inter milan', 'juventus', 'ajax', 'leeds', 'messi', 'ronaldo', 'mbappe',
            'haaland', 'odegaard', 'arteta', 'goalkeeper', 'striker', 'midfielder',
            '世界杯', '足球', '欧冠', '英超', '西甲', '意甲', '德甲', '法甲', '梅西', 'C罗',
        ];
        foreach ($footballSignals as $signal) {
            if ($this->keywordMatches($text, $signal)) {
                return true;
            }
        }

        $nonFootballSignals = [
            'tennis', 'us open', 'wimbledon', 'atp', 'wta', 'zverev', 'sabalenka', 'pegula',
            'rybakina', 'gauff', 'golf', 'cricket', 'rugby', 'horse racing', 'doncaster',
            '赛马', '网球', '高尔夫', '板球',
        ];
        foreach ($nonFootballSignals as $signal) {
            if ($this->keywordMatches($text, $signal)) {
                return false;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function classify(string $text): array
    {
        $text = mb_strtolower($text);
        $rules = [
            'match' => ['match', 'fixture', 'score', 'goal', '赛程', '比分', '进球', '淘汰赛', '小组赛'],
            'tactics' => ['tactic', 'formation', 'lineup', 'pressing', '战术', '阵型', '首发'],
            'team' => ['squad', 'team', 'coach', 'training', '球队', '名单', '教练', '训练'],
            'fan' => ['fans', 'supporter', 'stadium', '球迷', '看台', '助威'],
            'entertainment' => ['music', 'celebrity', 'culture', 'entertainment', '娱乐', '音乐', '活动'],
            'fanmade' => ['poster', 'meme', 'video', 'creative', '二创', '海报', '短视频'],
            'worldcup' => ['world cup', 'fifa', '世界杯'],
            'football' => ['football', 'soccer', '足球'],
            'technology' => ['ai', 'data', 'var', 'technology', '技术', '人工智能', '数据'],
        ];

        $tags = [];
        foreach ($rules as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->keywordMatches($text, mb_strtolower($keyword))) {
                    $tags[] = $slug;
                    break;
                }
            }
        }
        return $tags ?: ['worldcup'];
    }

    private function keywordMatches(string $text, string $keyword): bool
    {
        $keyword = mb_strtolower($keyword);
        if ($keyword === '') {
            return false;
        }
        if (preg_match('/^[a-z0-9 .-]+$/i', $keyword) === 1) {
            return preg_match('/(?<![a-z0-9])' . preg_quote($keyword, '/') . '(?![a-z0-9])/i', $text) === 1;
        }
        return str_contains($text, $keyword);
    }
}
