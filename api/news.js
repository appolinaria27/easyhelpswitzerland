/**
 * api/news.js
 * Fetches Switzerland-related news from Google News RSS.
 * No API key, no rate limits, always has relevant results.
 * Cache stored in GitHub so it survives Vercel cold starts.
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache.json';
const TTL_MS     = 6 * 60 * 60 * 1000; // 6 hours

// In-process fallback for repeated calls on the same warm instance
let memCache = { data: null, at: 0 };

// Google News RSS — no auth required, excellent coverage
// German-language query so articles arrive in German natively (no translation API needed)
const RSS_URL = 'https://news.google.com/rss/search?q=Schweiz+Einwanderung+Aufenthaltsbewilligung+Expat+Ausländer&hl=de-CH&gl=CH&ceid=CH:de';

function decodeEntities(str) {
  return str
    .replace(/&amp;/g,  '&')
    .replace(/&lt;/g,   '<')
    .replace(/&gt;/g,   '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g,  "'")
    .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(parseInt(n)));
}

function parseRSS(xml) {
  const articles = [];
  const itemRe = /<item>([\s\S]*?)<\/item>/g;
  let m;

  while ((m = itemRe.exec(xml)) !== null && articles.length < 3) {
    const chunk = m[1];

    // Title
    const titleM = chunk.match(/<title>([\s\S]*?)<\/title>/);
    if (!titleM) continue;
    let title = decodeEntities(titleM[1].trim());

    // Source name
    const srcM = chunk.match(/<source[^>]*>([\s\S]*?)<\/source>/);
    const source = srcM ? decodeEntities(srcM[1].trim()) : '';

    // Strip "- Source" suffix Google appends to titles
    if (source && title.endsWith(' - ' + source)) {
      title = title.slice(0, -(3 + source.length));
    }

    // URL — Google redirect link, fully functional
    const linkM = chunk.match(/<link>([\s\S]*?)<\/link>/);
    const url = linkM ? linkM[1].trim() : '';
    if (!url) continue;

    // Pub date
    const pubM = chunk.match(/<pubDate>([\s\S]*?)<\/pubDate>/);
    let publishedAt = null;
    try { if (pubM) publishedAt = new Date(pubM[1].trim()).toISOString(); } catch {}

    // Image — Google News puts a thumbnail inside the <description> CDATA as an <img> tag
    let image = null;
    let description = '';
    const descM = chunk.match(/<description><!\[CDATA\[([\s\S]*?)\]\]><\/description>/);
    if (descM) {
      const html = descM[1];
      // Extract thumbnail URL
      const imgM = html.match(/<img[^>]+src="([^"]+)"/);
      if (imgM) image = imgM[1];
      // Extract plain-text snippet (strip all tags)
      description = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
    }
    // Fallback: <media:content url="...">
    if (!image) {
      const mediaM = chunk.match(/<media:content[^>]+url="([^"]+)"/);
      if (mediaM) image = mediaM[1];
    }

    articles.push({ title, url, source, publishedAt, image, description });
  }

  return articles;
}

async function fetchArticles() {
  const res = await fetch(RSS_URL, {
    headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
  });
  if (!res.ok) throw new Error(`RSS fetch failed: ${res.status}`);
  const xml = await res.text();
  return parseRSS(xml);
}

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 'public, s-maxage=21600, stale-while-revalidate=3600');

  const now = Date.now();

  // 1. In-process memory cache
  if (memCache.data && now - memCache.at < TTL_MS) {
    return res.status(200).json(memCache.data);
  }

  // 2. GitHub persistent cache
  try {
    const { data } = await ghRead(CACHE_PATH);
    if (data && data.fetched_at && (now - data.fetched_at) < TTL_MS && data.articles?.length) {
      memCache = { data: { articles: data.articles }, at: data.fetched_at };
      return res.status(200).json({ articles: data.articles });
    }
  } catch (e) {
    console.error('GitHub cache read:', e.message);
  }

  // 3. Fetch fresh from Google News RSS
  try {
    const articles = await fetchArticles();

    if (articles.length > 0) {
      const payload = { articles, fetched_at: now };

      // Save to GitHub cache (non-blocking)
      (async () => {
        try {
          const { sha } = await ghRead(CACHE_PATH);
          await ghWrite(CACHE_PATH, payload, sha);
        } catch (e) { console.error('GitHub cache write:', e.message); }
      })();

      memCache = { data: { articles }, at: now };
      return res.status(200).json({ articles });
    }
  } catch (e) {
    console.error('RSS fetch error:', e.message);
  }

  // 4. Return stale cache rather than nothing
  if (memCache.data) return res.status(200).json(memCache.data);
  return res.status(200).json({ articles: [] });
};
