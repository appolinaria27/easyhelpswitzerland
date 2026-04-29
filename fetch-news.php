<?php
/**
 * fetch-news.php
 * Fetches Switzerland-related articles from GNews API and saves to news-cache.json.
 * Called automatically by blog.php when cache is >24h old.
 * Can also be triggered manually: php fetch-news.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

define('CACHE_FILE', __DIR__ . '/news-cache.json');
define('GNEWS_MAX_PER_TOPIC', 3);

$topics = [
    [
        'q'        => 'Switzerland immigration',
        'label'    => 'Permits & Immigration',
        'fallback' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'q'        => 'Switzerland living',
        'label'    => 'Economy & Living',
        'fallback' => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'q'        => 'Switzerland Zurich',
        'label'    => 'Zürich & Life',
        'fallback' => 'https://images.unsplash.com/photo-1536105338741-2956a08f81e4?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'q'        => 'Switzerland housing rent',
        'label'    => 'Housing & Costs',
        'fallback' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=900&q=80',
    ],
];

function fetchTopic(string $q, string $label, string $fallback, string $apiKey): array
{
    $url = 'https://gnews.io/api/v4/search?' . http_build_query([
        'q'      => $q,
        'lang'   => 'en',
        'max'    => GNEWS_MAX_PER_TOPIC,
        'sortby' => 'publishedAt',
        'token'  => $apiKey,
    ]);

    $ctx = stream_context_create(['http' => [
        'timeout'       => 12,
        'ignore_errors' => true,
        'user_agent'    => 'EasyHelpSwitzerland/1.0',
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        error_log("fetch-news: HTTP request failed for topic: $label");
        return [];
    }

    $data = json_decode($raw, true);
    if (!isset($data['articles']) || !is_array($data['articles'])) {
        error_log("fetch-news: unexpected response for topic $label: " . substr($raw, 0, 200));
        return [];
    }

    $results = [];
    foreach ($data['articles'] as $a) {
        $title = trim($a['title'] ?? '');
        $desc  = trim($a['description'] ?? '');
        $url   = trim($a['url'] ?? '');
        if (!$title || !$url) continue;

        // Remove " - Source Name" appended by GNews to titles
        $title = preg_replace('/\s+[-–]\s+[^-–]{3,50}$/', '', $title);

        $results[] = [
            'id'          => md5($url),
            'title'       => $title,
            'description' => $desc ?: $title,
            'url'         => $url,
            'image'       => (!empty($a['image']) ? $a['image'] : $fallback),
            'publishedAt' => $a['publishedAt'] ?? date('c'),
            'source'      => $a['source']['name'] ?? 'Source',
            'category'    => $label,
        ];
    }
    return $results;
}

function fetchAllNews(string $apiKey, array $topics): array
{
    $all  = [];
    $seen = [];

    foreach ($topics as $topic) {
        $articles = fetchTopic($topic['q'], $topic['label'], $topic['fallback'], $apiKey);
        foreach ($articles as $article) {
            if (!isset($seen[$article['id']])) {
                $seen[$article['id']] = true;
                $all[] = $article;
            }
        }
        // Small delay to be polite to the API
        usleep(1200000); // 1.2s — stay within GNews rate limit
    }

    // Sort newest first
    usort($all, fn($a, $b) => strcmp($b['publishedAt'], $a['publishedAt']));

    return $all;
}

function saveCache(array $articles): void
{
    $cache = [
        'updated_at' => date('c'),
        'count'      => count($articles),
        'articles'   => $articles,
    ];
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// ── Main ──────────────────────────────────────────────────────────────────────

// Prevent concurrent runs (e.g. CLI + web request at the same time)
define('LOCK_FILE', __DIR__ . '/news-fetch.lock');
$lockFp = @fopen(LOCK_FILE, 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    error_log('fetch-news: another instance is already running, skipping.');
    exit(0);
}

$apiKey = $_ENV['GNEWS_API_KEY'] ?? '';

if (!$apiKey) {
    http_response_code(500);
    $msg = 'fetch-news: GNEWS_API_KEY not set in .env';
    error_log($msg);
    echo $msg . PHP_EOL;
    exit(1);
}

$articles = fetchAllNews($apiKey, $topics);
saveCache($articles);

flock($lockFp, LOCK_UN);
fclose($lockFp);
@unlink(LOCK_FILE);

$count = count($articles);
$msg   = "fetch-news: saved $count articles to cache at " . date('Y-m-d H:i:s');
error_log($msg);
echo $msg . PHP_EOL;
