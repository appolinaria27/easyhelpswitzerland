/**
 * api/news.js
 * Fetches Switzerland news from Bing News RSS.
 * - Real article URLs decoded from Bing redirect links
 * - Thumbnail images from <News:Image> tags (Bing CDN, works as <img src>)
 * - Translations via MyMemory (EN→DE/ES/UK, parallel)
 * - Cache stored in GitHub (6 h TTL), survives Vercel cold starts
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const CACHE_PATH = 'data/news-cache-v6.json'; // bumped → forces fresh fetch with image fix
const TTL_MS     = 6 * 60 * 60 * 1000; // 6 hours

let memCache = { data: null, at: 0 };

// Direct RSS feeds — reliable, no bot-blocking issues
const DIRECT_FEEDS = [
  { url: 'https://www.swissinfo.ch/eng/rss/top_news',          source: 'SWI swissinfo.ch' },
  { url: 'https://www.thelocal.ch/feeds/rss.php',              source: 'The Local Switzerland' },
  { url: 'https://feeds.feedburner.com/Swissinfo-En',          source: 'SWI swissinfo.ch' },
];

// Bing queries as secondary source if direct feeds return too few results
const BING_QUERIES = [
  'Switzerland+immigration+permit+expat',
  'Switzerland+relocation+residence+foreigners',
];

// Keywords to filter for relevance
const KEYWORDS = [
  'switzerland', 'swiss', 'zürich', 'zurich', 'bern', 'geneva', 'basel',
  'migration', 'permit', 'residence', 'relocation', 'expat', 'immigrant',
  'visa', 'citizenship', 'naturali', 'foreigner', 'work permit', 'asylum',
  'integration', 'housing', 'health insurance', 'ahv', 'pension',
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

    // Image — try all common RSS image formats in priority order
    let image = null;

    // 1. Bing <News:Image>
    const bingImg = chunk.match(/<News:Image>([\s\S]*?)<\/News:Image>/);
    if (bingImg) image = decodeEntities(bingImg[1].trim()) + '&w=600&h=338&c=14';

    // 2. <media:content url="..."> (swissinfo, thelocal, most modern feeds)
    if (!image) {
      const mediaContent = chunk.match(/<media:content[^>]+url=["']([^"']+)["']/i);
      if (mediaContent) image = decodeEntities(mediaContent[1]);
    }

    // 3. <media:thumbnail url="...">
    if (!image) {
      const mediaThumbnail = chunk.match(/<media:thumbnail[^>]+url=["']([^"']+)["']/i);
      if (mediaThumbnail) image = decodeEntities(mediaThumbnail[1]);
    }

    // 4. <enclosure url="..." type="image/...">
    if (!image) {
      const enclosure = chunk.match(/<enclosure[^>]+type=["']image\/[^"']*["'][^>]+url=["']([^"']+)["']/i)
                     || chunk.match(/<enclosure[^>]+url=["']([^"']+)["'][^>]+type=["']image\/[^"']*["']/i);
      if (enclosure) image = decodeEntities(enclosure[1]);
    }

    // 5. First <img src="..."> inside description/content
    if (!image) {
      const descImgM = chunk.match(/<img[^>]+src=["']([^"']+)["']/i);
      if (descImgM) image = decodeEntities(descImgM[1]);
    }

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

// ── Fetch helpers ─────────────────────────────────────────────────────────────

function isRelevant({ title = '', description = '' }) {
  const text = `${title} ${description}`.toLowerCase();
  return KEYWORDS.some(kw => text.includes(kw));
}

async function fetchFeed(url, defaultSource) {
  try {
    const res = await fetch(url, {
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
      signal: AbortSignal.timeout(9000),
    });
    if (!res.ok) return [];
    const items = parseRSS(await res.text());
    return items.map(a => ({ ...a, source: a.source || defaultSource }));
  } catch (e) {
    console.warn('Feed failed:', url, e.message);
    return [];
  }
}

async function fetchBing(query) {
  try {
    const res = await fetch(
      `https://www.bing.com/news/search?q=${query}&format=rss&mkt=en-US&freshness=Week`,
      {
        headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
        signal: AbortSignal.timeout(9000),
      }
    );
    if (!res.ok) return [];
    return parseRSS(await res.text());
  } catch {
    return [];
  }
}

// ── Main fetch — direct feeds first, Bing as fallback ────────────────────────

async function fetchArticles() {
  // 1. Try direct RSS feeds — most reliable
  const directResults = await Promise.all(
    DIRECT_FEEDS.map(f => fetchFeed(f.url, f.source))
  );
  let all = directResults.flat().filter(isRelevant);

  // 2. If fewer than 3 results, supplement with Bing
  if (all.length < 3) {
    const bingResults = await Promise.all(BING_QUERIES.map(fetchBing));
    all = [...all, ...bingResults.flat().filter(isRelevant)];
  }

  // Dedupe by URL, sort newest first, return top 3
  const seen = new Set();
  const deduped = all.filter(a => {
    if (!a.url || seen.has(a.url)) return false;
    seen.add(a.url);
    return true;
  });

  deduped.sort((a, b) => {
    const ta = a.publishedAt ? new Date(a.publishedAt).getTime() : 0;
    const tb = b.publishedAt ? new Date(b.publishedAt).getTime() : 0;
    return tb - ta;
  });

  return deduped.slice(0, 3);
}

// ── Handler ───────────────────────────────────────────────────────────────────

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 'public, s-maxage=3600, stale-while-revalidate=600');

  const now = Date.now();

  // 1. In-process memory cache (warm instance)
  if (memCache.data && now - memCache.at < TTL_MS) {
    return res.status(200).json(memCache.data);
  }

  // 2. GitHub persistent cache — always load into memCache as fallback,
  //    return immediately only if still fresh
  let ghStale = null;
  try {
    const { data } = await ghRead(CACHE_PATH);
    if (data?.articles?.length) {
      // always store as fallback regardless of age
      ghStale = { articles: data.articles };
      if (data.fetched_at && (now - data.fetched_at) < TTL_MS) {
        memCache = { data: ghStale, at: data.fetched_at };
        return res.status(200).json(ghStale);
      }
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

  // 4. Stale GitHub cache beats empty — always better than nothing
  if (ghStale) { memCache = { data: ghStale, at: now - TTL_MS + 60000 }; return res.status(200).json(ghStale); }
  if (memCache.data) return res.status(200).json(memCache.data);
  return res.status(200).json({ articles: [] });
};
