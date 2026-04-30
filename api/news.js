// Simple in-process cache to avoid hitting GNews rate limits
let cache = { data: null, at: 0 };
const TTL = 6 * 60 * 60 * 1000; // 6 hours

const QUERIES = [
  'Switzerland permit residence',
  'Switzerland expat living',
  'Switzerland immigration',
  'Zürich relocation',
];

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 's-maxage=21600, stale-while-revalidate');

  if (cache.data && Date.now() - cache.at < TTL) {
    return res.status(200).json(cache.data);
  }

  const apiKey = process.env.GNEWS_API_KEY;
  if (!apiKey) return res.status(200).json({ articles: [] });

  // Pick a random query each call so we get variety over time
  const q = QUERIES[Math.floor(Math.random() * QUERIES.length)];
  const url = `https://gnews.io/api/v4/search?q=${encodeURIComponent(q)}&lang=en&max=6&sortby=publishedAt&apikey=${apiKey}`;

  try {
    const r = await fetch(url);
    if (!r.ok) return res.status(200).json({ articles: [] });
    const json = await r.json();
    const articles = (json.articles || []).map(a => ({
      title:       a.title,
      description: a.description,
      url:         a.url,
      image:       a.image,
      source:      a.source && a.source.name,
      publishedAt: a.publishedAt,
    }));
    cache = { data: { articles }, at: Date.now() };
    return res.status(200).json({ articles });
  } catch (err) {
    console.error('GNews error:', err.message);
    return res.status(200).json({ articles: [] });
  }
};
