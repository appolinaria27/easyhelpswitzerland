/**
 * api/news.js
 * Fetches Switzerland-related news from Google News RSS.
 * Images: fetched from each article's og:image meta tag.
 * Translations: via MyMemory free API (EN → DE/ES/UK).
 * Cache stored in GitHub so it survives Vercel cold starts.
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache.json';
const TTL_MS     = 6 * 60 * 60 * 1000; // 6 hours

let memCache = { data: null, at: 0 };

const RSS_URL = 'https://news.google.com/rss/search?q=Switzerland+immigration+expat+permit+relocation&hl=en-US&gl=US&ceid=US:en';

const TARGET_LANGS = ['de', 'es', 'uk'];

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

    // Description: entity-encoded HTML — strip tags to get plain text
    let description = '';
    const rawDescM = chunk.match(/<description>([\s\S]*?)<\/description>/);
    if (rawDescM) {
      description = decodeEntities(rawDescM[1])
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 200);
    }

    articles.push({ title, url, source, publishedAt, image: null, description });
  }

  return articles;
}

/**
 * Fetch the first 20 KB of an article page and extract its og:image.
 * Uses Twitterbot UA so sites render social meta tags properly.
 */
async function fetchOgImage(url) {
  try {
    const res = await fetch(url, {
      headers: { 'User-Agent': 'Twitterbot/1.0' },
      signal: AbortSignal.timeout(8000),
      redirect: 'follow',
    });
    if (!res.ok) return null;

    // Stream only the first 20 KB — og:image is always in <head>
    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let html = '';
    while (html.length < 20000) {
      const { value, done } = await reader.read();
      if (done) break;
      html += decoder.decode(value, { stream: true });
    }
    reader.cancel().catch(() => {});

    // Match both attribute orderings
    const m = html.match(/<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)["']/i)
           || html.match(/<meta[^>]+content=["']([^"']+)["'][^>]+property=["']og:image["']/i);
    return m ? m[1] : null;
  } catch {
    return null;
  }
}

/** Attach og:image to every article in parallel. */
async function attachImages(articles) {
  const images = await Promise.all(articles.map(a => fetchOgImage(a.url)));
  return articles.map((a, i) => ({ ...a, image: images[i] || null }));
}

async function translateText(text, targetLang) {
  if (!text) return '';
  try {
    const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=en|${targetLang}`;
    const r = await fetch(url, { signal: AbortSignal.timeout(5000) });
    if (!r.ok) return text;
    const j = await r.json();
    const translated = j?.responseData?.translatedText;
    if (!translated || translated.toLowerCase().startsWith('mymemory')) return text;
    return translated;
  } catch {
    return text;
  }
}

async function addTranslations(articles) {
  return Promise.all(articles.map(async (a) => {
    const translations = { en: { title: a.title, description: a.description } };
    await Promise.all(TARGET_LANGS.map(async (lang) => {
      const [title, description] = await Promise.all([
        translateText(a.title, lang),
        translateText(a.description.slice(0, 150), lang),
      ]);
      translations[lang] = { title, description };
    }));
    return { ...a, translations };
  }));
}

async function fetchArticles() {
  const res = await fetch(RSS_URL, {
    headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
  });
  if (!res.ok) throw new Error(`RSS fetch failed: ${res.status}`);
  const xml = await res.text();
  const parsed = parseRSS(xml);
  // Fetch og:image for each article in parallel (8 s timeout per article)
  return attachImages(parsed);
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

  // 3. Fetch fresh + images + translations
  try {
    const withImages = await fetchArticles();

    if (withImages.length > 0) {
      const articles = await addTranslations(withImages);
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
    console.error('Fetch error:', e.message);
  }

  // 4. Return stale cache rather than nothing
  if (memCache.data) return res.status(200).json(memCache.data);
  return res.status(200).json({ articles: [] });
};
