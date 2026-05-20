/**
 * api/news-telegram.js
 * Fetches Switzerland migration & expat news from Bing RSS,
 * posts one fresh article per day to @easyhelpswitzerland Telegram channel.
 *
 * Uses GitHub storage to track posted URLs — survives Vercel cold starts.
 * Triggered by cron: daily at 17:00 UTC (19:00 Zürich time).
 */

const { ghRead, ghWrite } = require('../lib/github-storage');

const POSTED_PATH = 'data/tg-posted-news.json';
const MAX_STORED  = 120; // keep only last 120 URLs to avoid bloat

// Direct RSS feeds — reliable, no bot-blocking
const DIRECT_FEEDS = [
  { url: 'https://www.swissinfo.ch/eng/rss/top_news',  source: 'SWI swissinfo.ch' },
  { url: 'https://www.thelocal.ch/feeds/rss.php',      source: 'The Local Switzerland' },
];

// Bing as secondary supplement
const BING_QUERIES = [
  'Switzerland+immigration+permit+expat',
  'Switzerland+relocation+residence+foreigners',
];

const KEYWORDS = [
  'switzerland', 'swiss', 'zürich', 'zurich', 'bern', 'geneva', 'basel',
  'migration', 'permit', 'residence', 'relocation', 'expat', 'immigrant',
  'visa', 'citizenship', 'naturaliz', 'foreigner', 'work permit', 'asylum',
  'integration', 'housing', 'health insurance', 'aufenthalt', 'anmeldung',
];

// ── RSS helpers ───────────────────────────────────────────────────────────────

function decodeEntities(str = '') {
  return str
    .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"').replace(/&#39;/g, "'")
    .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(parseInt(n)));
}

function parseRSS(xml) {
  const items = [];
  const re = /<item>([\s\S]*?)<\/item>/g;
  let m;
  while ((m = re.exec(xml)) !== null) {
    const chunk = m[1];
    const titleM = chunk.match(/<title>([\s\S]*?)<\/title>/);
    if (!titleM) continue;
    const title = decodeEntities(titleM[1].replace(/<!\[CDATA\[|\]\]>/g, '').trim());

    // Bing encodes the real URL in the redirect ?url= param
    const linkM = chunk.match(/<link>([\s\S]*?)<\/link>/);
    let url = '';
    if (linkM) {
      const raw = decodeEntities(linkM[1].trim());
      const urlParam = raw.match(/[?&]url=([^&]+)/);
      url = urlParam ? decodeURIComponent(urlParam[1]) : raw;
    }
    if (!url || url.includes('bing.com')) continue;

    const srcM  = chunk.match(/<News:Source>([\s\S]*?)<\/News:Source>/);
    const source = srcM ? decodeEntities(srcM[1].trim()) : '';

    const descM = chunk.match(/<description>([\s\S]*?)<\/description>/);
    const description = descM
      ? decodeEntities(descM[1].replace(/<!\[CDATA\[|\]\]>/g, '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220)
      : '';

    const pubM = chunk.match(/<pubDate>([^<]+)<\/pubDate>/);
    let publishedAt = null;
    try { if (pubM) publishedAt = new Date(pubM[1].trim()); } catch {}

    items.push({ title, url, source, description, publishedAt });
  }
  return items;
}

function isRelevant({ title, description }) {
  const text = `${title} ${description}`.toLowerCase();
  return KEYWORDS.some(kw => text.includes(kw.toLowerCase()));
}

// ── Fetch news ────────────────────────────────────────────────────────────────

async function fetchFeed(url, defaultSource) {
  try {
    const res = await fetch(url, {
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' },
      signal: AbortSignal.timeout(9000),
    });
    if (!res.ok) return [];
    return parseRSS(await res.text()).map(a => ({ ...a, source: a.source || defaultSource }));
  } catch (e) {
    console.warn('Feed failed:', url, e.message);
    return [];
  }
}

async function fetchBing(query) {
  try {
    const res = await fetch(
      `https://www.bing.com/news/search?q=${query}&format=rss&mkt=en-US&freshness=Day`,
      { headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EasyHelpBot/1.0)' }, signal: AbortSignal.timeout(8000) }
    );
    if (!res.ok) return [];
    return parseRSS(await res.text());
  } catch { return []; }
}

async function fetchNews() {
  const cutoff = Date.now() - 72 * 60 * 60 * 1000; // last 72 hours

  // 1. Direct feeds first
  const directResults = await Promise.all(DIRECT_FEEDS.map(f => fetchFeed(f.url, f.source)));
  let all = directResults.flat();

  // 2. Add Bing if we need more
  if (all.filter(isRelevant).length < 3) {
    const bingResults = await Promise.all(BING_QUERIES.map(fetchBing));
    all = [...all, ...bingResults.flat()];
  }

  const seen = new Set();
  return all
    .filter(a => {
      if (!a.url || seen.has(a.url)) return false;
      seen.add(a.url);
      if (a.publishedAt && a.publishedAt.getTime() < cutoff) return false;
      return isRelevant(a);
    })
    .sort((a, b) => {
      const ta = a.publishedAt ? a.publishedAt.getTime() : 0;
      const tb = b.publishedAt ? b.publishedAt.getTime() : 0;
      return tb - ta;
    });
}

// ── Telegram ──────────────────────────────────────────────────────────────────

async function postToTelegram(article) {
  const dateStr = article.publishedAt
    ? article.publishedAt.toLocaleDateString('en-GB', { day: 'numeric', month: 'long' })
    : '';

  const text = [
    `📰 *News for Swiss expats*${dateStr ? ` · ${dateStr}` : ''}`,
    '',
    `*${article.title}*`,
    '',
    article.description ? `${article.description}…` : '',
    '',
    article.source ? `🔗 _${article.source}_ — [Read full article](${article.url})` : `🔗 [Read full article](${article.url})`,
    '',
    `💬 Questions? Write us on WhatsApp or book a free call at easyhelpswitzerland.ch`,
  ].filter(l => l !== undefined).join('\n').replace(/\n{3,}/g, '\n\n');

  const res = await fetch(
    `https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendMessage`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        chat_id: '@easyhelpswitzerland',
        text,
        parse_mode: 'Markdown',
        disable_web_page_preview: false,
      }),
    }
  );
  const data = await res.json();
  if (!data.ok) throw new Error(`Telegram error: ${JSON.stringify(data)}`);
  return data.result;
}

// ── Handler ───────────────────────────────────────────────────────────────────

module.exports = async (req, res) => {
  if (req.method !== 'GET' && req.method !== 'POST') return res.status(405).end();

  const cronSecret = process.env.CRON_SECRET;
  if (cronSecret) {
    const ok =
      req.headers.authorization === `Bearer ${cronSecret}` ||
      req.query.secret === cronSecret;
    if (!ok) return res.status(401).json({ error: 'Unauthorized' });
  }

  try {
    // Load list of already-posted URLs from GitHub
    const { data: stored, sha } = await ghRead(POSTED_PATH);
    const postedUrls = new Set((stored?.urls || []));

    // Fetch fresh relevant articles
    const articles = await fetchNews();
    if (articles.length === 0) {
      return res.status(200).json({ success: true, skipped: true, reason: 'no_relevant_news_in_48h' });
    }

    // Pick the first article not yet posted
    const fresh = articles.find(a => !postedUrls.has(a.url));
    if (!fresh) {
      return res.status(200).json({ success: true, skipped: true, reason: 'all_articles_already_posted' });
    }

    // Post to Telegram
    const result = await postToTelegram(fresh);

    // Save updated posted list (keep last MAX_STORED)
    const updatedUrls = [...postedUrls, fresh.url].slice(-MAX_STORED);
    await ghWrite(
      POSTED_PATH,
      { urls: updatedUrls, last_post: fresh.url, updated_at: new Date().toISOString() },
      sha
    );

    return res.status(200).json({
      success: true,
      title: fresh.title,
      source: fresh.source,
      url: fresh.url,
      message_id: result.message_id,
    });
  } catch (err) {
    console.error('[news-telegram]', err);
    return res.status(500).json({ error: err.message });
  }
};
