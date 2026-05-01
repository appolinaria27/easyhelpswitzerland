/**
 * api/news.js
 * Fetches Swiss news from GNews and caches the result in GitHub so the
 * cache survives Vercel cold starts and is shared across all instances.
 * GNews free plan = 100 req/day, so we only refresh once every 12 hours.
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache.json';
const TTL_MS     = 12 * 60 * 60 * 1000; // 12 hours

// Tiny in-process fallback so repeated calls within the same warm instance
// don't hit GitHub either
let memCache = { data: null, at: 0 };

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 'public, s-maxage=43200, stale-while-revalidate=3600');

  const now = Date.now();

  // 1. Serve from in-process memory if warm
  if (memCache.data && now - memCache.at < TTL_MS) {
    return res.status(200).json(memCache.data);
  }

  // 2. Try GitHub cache
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

  const q   = 'Switzerland expat permit relocation living';
  const url = `https://gnews.io/api/v4/search?q=${encodeURIComponent(q)}&lang=en&max=3&sortby=publishedAt&apikey=${apiKey}`;

  try {
    const r    = await fetch(url);
    const text = await r.text();

    if (!r.ok) {
      console.error('GNews HTTP error:', r.status, text.slice(0, 200));
      // Return whatever we have cached even if stale, rather than nothing
      if (memCache.data) return res.status(200).json(memCache.data);
      return res.status(200).json({ articles: [] });
    }

    const json = JSON.parse(text);

    if (!json.articles?.length) {
      console.error('GNews empty/unexpected response:', text.slice(0, 200));
      if (memCache.data) return res.status(200).json(memCache.data);
      return res.status(200).json({ articles: [] });
    }

    const articles = json.articles.map(a => ({
      title:       a.title,
      description: a.description,
      url:         a.url,
      image:       a.image,
      source:      a.source?.name || '',
      publishedAt: a.publishedAt,
    }));

    const payload = { articles, fetched_at: now };

    // 4. Save to GitHub cache (non-blocking — don't await so response is fast)
    (async () => {
      try {
        const { sha } = await ghRead(CACHE_PATH);
        await ghWrite(CACHE_PATH, payload, sha);
      } catch (e) {
        console.error('GitHub cache write error:', e.message);
      }
    })();

    memCache = { data: { articles }, at: now };
    return res.status(200).json({ articles });

  } catch (err) {
    console.error('GNews fetch error:', err.message);
    if (memCache.data) return res.status(200).json(memCache.data);
    return res.status(200).json({ articles: [] });
  }
};
