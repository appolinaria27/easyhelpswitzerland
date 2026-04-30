let cache = { data: null, at: 0 };
const TTL = 6 * 60 * 60 * 1000; // 6 hours

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  // No CDN caching — previous s-maxage locked in an empty response for 6h.
  // In-process cache (TTL variable) handles deduplication within a warm instance.
  res.setHeader('Cache-Control', 'no-store');

  if (cache.data && Date.now() - cache.at < TTL) {
    return res.status(200).json(cache.data);
  }

  const apiKey = process.env.GNEWS_API_KEY;
  if (!apiKey) return res.status(200).json({ articles: [] });

  // Fetch with two queries in parallel so we always get enough results
  const queries = ['Switzerland expat permit relocation', 'Switzerland living working'];
  try {
    const results = await Promise.all(queries.map(q => {
      const url = `https://gnews.io/api/v4/search?q=${encodeURIComponent(q)}&lang=en&max=3&sortby=publishedAt&apikey=${apiKey}`;
      return fetch(url).then(r => r.ok ? r.json() : { articles: [] }).catch(() => ({ articles: [] }));
    }));

    // Merge, deduplicate by URL, take first 3
    const seen = new Set();
    const articles = results
      .flatMap(j => j.articles || [])
      .filter(a => {
        if (!a.url || seen.has(a.url)) return false;
        seen.add(a.url);
        return true;
      })
      .slice(0, 3)
      .map(a => ({
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
