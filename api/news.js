/**
 * api/news.js
 * Fetches Switzerland news from Bing News RSS.
 * - Real article URLs decoded from Bing redirect links
 * - Thumbnail images from <News:Image> tags (Bing CDN, works as <img src>)
 * - Translations via MyMemory (EN→DE/ES/UK, parallel)
 * - Cache stored in GitHub (6 h TTL), survives Vercel cold starts
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache-v4.json';
const TTL_MS     = 6 * 60 * 60 * 1000; // 6 hours

let memCache = { data: null, at: 0 };

// Multiple queries — run all, merge, sort by date, keep 3 freshest
const RSS_QUERIES = [
  'Switzerland+immigration+permit',
  'Switzerland+expat+work+visa',
  'Switzerland+living+residence',
];

const TARGET_LANGS = ['de', 'es', 'uk'];

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

  while ((m = itemRe.exec(xml)) !== null) {
    const chunk = m[1];

    // Title
    const titleM = chunk.match(/<title>([\s\S]*?)<\/title>/);
    if (!titleM) continue;
    const title = decodeEntities(titleM[1].trim());

    // Source
    const srcM = chunk.match(/<News:Source>([\s\S]*?)<\/News:Source>/);
    const source = srcM ? decodeEntities(srcM[1].trim()) : '';

    // Real URL — encoded in the Bing redirect link's ?url= param
    const linkM = chunk.match(/<link>([\s\S]*?)<\/link>/);
    let url = '';
    if (linkM) {
      const raw = decodeEntities(linkM[1].trim());
      const urlParam = raw.match(/[?&]url=([^&]+)/);
      url = urlParam ? decodeURIComponent(urlParam[1]) : raw;
    }
    if (!url) continue;

    // Pub date
    const pubM = chunk.match(/<pubDate>([\s\S]*?)<\/pubDate>/);
    let publishedAt = null;
    try { if (pubM) publishedAt = new Date(pubM[1].trim()).toISOString(); } catch {}

    // Image — Bing provides this directly in <News:Image>
    const imgM = chunk.match(/<News:Image>([\s\S]*?)<\/News:Image>/);
    const image = imgM ? decodeEntities(imgM[1].trim()) + '&w=600&h=338&c=14' : null;

    // Description — plain text
    const descM = chunk.match(/<description>([\s\S]*?)<\/description>/);
    const description = descM
      ? decodeEntities(descM[1]).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200)
      : '';

    articles.push({ title, url, source, publishedAt, image, description });
  }

  return articles;
}

// ── Translations ──────────────────────────────────────────────────────────────

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

async function addTranslations(articles) {
  return Promise.all(articles.map(async (a) => {
    const langPairs = await Promise.all(
      TARGET_LANGS.map(lang =>
        Promise.all([
          translateText(a.title, lang),
          translateText(a.description.slice(0, 150), lang),
        ])
      )
    );
    const translations = { en: { title: a.title, description: a.description } };
    TARGET_LANGS.forEach((lang, i) => {
      translations[lang] = { title: langPairs[i][0], description: langPairs[i][1] };
    });
    return { ...a, translations };
  }));
}

// ── Fetch from all queries, dedupe, sort newest first, keep 3 ─────────────────

async function fetchArticles() {
  const results = await Promise.all(
    RSS_QUERIES.map(async (q) => {
      try {
        const res = await fetch(
          `https://www.bing.com/news/search?q=${q}&format=rss&mkt=en-US`,
          {
            headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
            signal: AbortSignal.timeout(8000),
          }
        );
        if (!res.ok) return [];
        return parseRSS(await res.text());
      } catch {
        return [];
      }
    })
  );

  // Flatten, dedupe by URL, sort newest first, return top 3
  const seen = new Set();
  const all = results.flat().filter(a => {
    if (seen.has(a.url)) return false;
    seen.add(a.url);
    return true;
  });

  all.sort((a, b) => {
    const ta = a.publishedAt ? new Date(a.publishedAt).getTime() : 0;
    const tb = b.publishedAt ? new Date(b.publishedAt).getTime() : 0;
    return tb - ta;
  });

  return all.slice(0, 3);
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

  // 3. Fetch + translate
  try {
    const raw = await fetchArticles();
    if (raw.length > 0) {
      const articles = await addTranslations(raw);
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
