/**
 * api/news.js
 * Fetches Switzerland-related news from Google News RSS.
 * - RSS query uses a rolling 6-month `after:` filter for freshness
 * - Parses up to 10 items, sorts by date, keeps 3 newest
 * - Images via Microlink (free meta-scraper API, no key needed, 50 req/day)
 * - Translations via MyMemory (EN→DE/ES/UK, parallel)
 * - Cache stored in GitHub (6 h TTL), survives Vercel cold starts
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache-v3.json';
const TTL_MS     = 6 * 60 * 60 * 1000; // 6 hours

let memCache = { data: null, at: 0 };

const TARGET_LANGS = ['de', 'es', 'uk'];

// Rolling "last 6 months" date for freshness
function rssUrl() {
  const d = new Date(Date.now() - 180 * 24 * 60 * 60 * 1000);
  const after = d.toISOString().slice(0, 10); // YYYY-MM-DD
  return `https://news.google.com/rss/search?q=Switzerland+immigration+work+permit+expat+residence+after:${after}&hl=en-US&gl=US&ceid=US:en`;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

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

  // Parse up to 10 items, sort by date below, keep 3 freshest
  while ((m = itemRe.exec(xml)) !== null && articles.length < 10) {
    const chunk = m[1];

    const titleM = chunk.match(/<title>([\s\S]*?)<\/title>/);
    if (!titleM) continue;
    let title = decodeEntities(titleM[1].trim());

    const srcM = chunk.match(/<source[^>]*>([\s\S]*?)<\/source>/);
    const source = srcM ? decodeEntities(srcM[1].trim()) : '';
    if (source && title.endsWith(' - ' + source)) {
      title = title.slice(0, -(3 + source.length));
    }

    const linkM = chunk.match(/<link>([\s\S]*?)<\/link>/);
    const url = linkM ? linkM[1].trim() : '';
    if (!url) continue;

    const pubM = chunk.match(/<pubDate>([\s\S]*?)<\/pubDate>/);
    let publishedAt = null;
    try { if (pubM) publishedAt = new Date(pubM[1].trim()).toISOString(); } catch {}

    let description = '';
    const rawDescM = chunk.match(/<description>([\s\S]*?)<\/description>/);
    if (rawDescM) {
      description = decodeEntities(rawDescM[1])
        .replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
    }

    articles.push({ title, url, source, publishedAt, image: null, description });
  }

  // Sort newest first, keep top 3
  articles.sort((a, b) => {
    const ta = a.publishedAt ? new Date(a.publishedAt).getTime() : 0;
    const tb = b.publishedAt ? new Date(b.publishedAt).getTime() : 0;
    return tb - ta;
  });

  return articles.slice(0, 3);
}

// ── Image via Microlink (free, no key, handles JS/paywalls/redirects) ─────────

async function fetchImage(url) {
  try {
    const api = `https://api.microlink.io/?url=${encodeURIComponent(url)}&meta=false`;
    const r = await fetch(api, {
      headers: { 'User-Agent': 'Mozilla/5.0' },
      signal: AbortSignal.timeout(6000),
    });
    if (!r.ok) return null;
    const j = await r.json();
    return j?.data?.image?.url || null;
  } catch {
    return null;
  }
}

// ── Translation via MyMemory (free, no key) ───────────────────────────────────

async function translateText(text, lang) {
  if (!text) return '';
  try {
    const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=en|${lang}`;
    const r = await fetch(url, { signal: AbortSignal.timeout(4000) });
    if (!r.ok) return text;
    const j = await r.json();
    const t = j?.responseData?.translatedText;
    if (!t || t.toLowerCase().startsWith('mymemory')) return text;
    return t;
  } catch {
    return text;
  }
}

// ── Enrich: images + translations fire simultaneously per article ──────────────

async function enrichArticles(articles) {
  return Promise.all(articles.map(async (a) => {
    const [image, ...langPairs] = await Promise.all([
      fetchImage(a.url),
      ...TARGET_LANGS.map(lang =>
        Promise.all([
          translateText(a.title, lang),
          translateText(a.description.slice(0, 150), lang),
        ])
      ),
    ]);

    const translations = { en: { title: a.title, description: a.description } };
    TARGET_LANGS.forEach((lang, i) => {
      translations[lang] = { title: langPairs[i][0], description: langPairs[i][1] };
    });

    return { ...a, image: image || null, translations };
  }));
}

// ── RSS fetch ─────────────────────────────────────────────────────────────────

async function fetchArticles() {
  const res = await fetch(rssUrl(), {
    headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
    signal: AbortSignal.timeout(8000),
  });
  if (!res.ok) throw new Error(`RSS fetch failed: ${res.status}`);
  return parseRSS(await res.text());
}

// ── Handler ───────────────────────────────────────────────────────────────────

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
    if (data?.fetched_at && (now - data.fetched_at) < TTL_MS && data.articles?.length) {
      memCache = { data: { articles: data.articles }, at: data.fetched_at };
      return res.status(200).json({ articles: data.articles });
    }
  } catch (e) { console.error('GitHub cache read:', e.message); }

  // 3. Fetch RSS → enrich (images + translations in parallel)
  try {
    const raw = await fetchArticles();
    if (raw.length > 0) {
      const articles = await enrichArticles(raw);
      const payload  = { articles, fetched_at: now };

      // Write GitHub cache (non-blocking)
      (async () => {
        try {
          const { sha } = await ghRead(CACHE_PATH);
          await ghWrite(CACHE_PATH, payload, sha);
        } catch (e) { console.error('GitHub cache write:', e.message); }
      })();

      memCache = { data: { articles }, at: now };
      return res.status(200).json({ articles });
    }
  } catch (e) { console.error('Fetch error:', e.message); }

  // 4. Stale beats empty
  if (memCache.data) return res.status(200).json(memCache.data);
  return res.status(200).json({ articles: [] });
};
