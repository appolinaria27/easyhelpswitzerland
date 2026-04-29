<?php
/**
 * news-api.php
 * Returns up to 3 latest articles from news-cache.json as JSON.
 * Used by the homepage blog section.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

define('CACHE_FILE', __DIR__ . '/news-cache.json');

if (!file_exists(CACHE_FILE)) {
    echo json_encode(['articles' => []]);
    exit;
}

$cache = json_decode(file_get_contents(CACHE_FILE), true);

if (!isset($cache['articles']) || !is_array($cache['articles'])) {
    echo json_encode(['articles' => []]);
    exit;
}

$articles = array_slice($cache['articles'], 0, 3);

echo json_encode(['articles' => $articles], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
