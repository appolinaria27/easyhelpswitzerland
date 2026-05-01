/**
 * api/news.js
 * Fetches Swiss-relevant news from GNews.
 * Cache is stored in GitHub so it survives Vercel cold starts and is
 * shared across all instances — GNews free plan = 100 req/day.
 *
 * Query strategy: try specific queries first, fall back to broader ones
 * so we always return at least 3 articles.
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache.json';
const TTL_MS     = 12 * 60 * 60 * 1000; // 12 hours

// In-process memory cache — shared within one warm Vercel instance
let memCache = { data: null, at: 0 };

// Queries tried in order until we get ≥ 3 articles
const QUERIES = [
  'Switzerland foreigners housing permit',
  'Switzerland migration work',
  'Switzerland news',
];

async function fetchFromGNews(apiKey) {
  for (const q of QUERIES) {
    const url = `https://gnews.io/api/v4/search?q=${encodeURIComponent(q)}&lang=en&max=3&sortby=publishedAt&apikey=${apiKey}`;
    try {
      const r    = await fetch(url);
      const text = await r.text();
      if (!r.ok) { console.error('GNews error:', r.status, text.slice(0, 100)); continue; }
      const json = JSON.parse(text);
      const arts = json.articles || [];
      if (arts.length > 0) {
        return arts.map(a => ({
          title:       a.title,
          description: a.description,
          url:         a.url,
          image:       a.image,
          source:      a.source?.name || '',
          publishedAt: a.publishedAt,
        }));
      }
    } catch (e) { console.error('GNews fetch error:', e.message); }
  }
  return [];
}

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 'public, s-maxage=43200, stale-while-revalidate=3600');

  const now = Date.now();

  // 1. Serve from in-process memory if warm and fresh
  if (memCache.data && now - memCache.at < TTL_MS) {
    return res.status(200).json(memCache.data);
  }

  // 2. Try GitHub persistent cache
  try {
    const { data } = await ghRead(CACHE_PATH);
    if (data && data.fetched_at && now - data.fetched_at < TTL_MS && data.articles?.length) {
      memCache = { data: { articles: data.articles }, at: data.fetched_at };
      return res.status(200).json({ articles: data.articles });
    }
  } catch (e) {
    console.error('GitHub cache read error:', e.message);
  }

  // 3. Fetch fresh from GNews
  const apiKey = process.env.GNEWS_API_KEY;
  if (!apiKey) {
    console.error('GNEWS_API_KEY not set');
    return res.status(200).json({ articles: [] });
  }

  const articles = await fetchFromGNews(apiKey);

  if (articles.length > 0) {
    const payload = { articles, fetched_at: now };

    // Save to GitHub cache (non-blocking)
    (async () => {
      try {
        const { sha } = await ghRead(CACHE_PATH);
        await ghWrite(CACHE_PATH, payload, sha);
      } catch (e) { console.error('GitHub cache write error:', e.message); }
    })();

    memCache = { data: { articles }, at: now };
    return res.status(200).json({ articles });
  }

  // 4. Return stale cache rather than empty if GNews failed completely
  if (memCache.data) return res.status(200).json(memCache.data);
  return res.status(200).json({ articles: [] });
};
