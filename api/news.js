let cache = { data: null, at: 0 };
const TTL = 12 * 60 * 60 * 1000; // 12 hours — conserve free-plan quota

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Cache-Control', 'public, s-maxage=43200, stale-while-revalidate=3600');

  // Serve from in-process cache if still warm
  if (cache.data && Date.now() - cache.at < TTL) {
    return res.status(200).json(cache.data);
  }

  const apiKey = process.env.GNEWS_API_KEY;
  if (!apiKey) {
    console.error('GNEWS_API_KEY not set');
    return res.status(200).json({ articles: [] });
  }

  const q = 'Switzerland expat permit relocation living';
  const url = `https://gnews.io/api/v4/search?q=${encodeURIComponent(q)}&lang=en&max=3&sortby=publishedAt&apikey=${apiKey}`;

  try {
    const r = await fetch(url);
    const text = await r.text();

    if (!r.ok) {
      console.error('GNews HTTP error:', r.status, text.slice(0, 200));
      return res.status(200).json({ articles: [] });
    }

    const json = JSON.parse(text);

    if (!json.articles) {
      console.error('GNews unexpected response:', text.slice(0, 200));
      return res.status(200).json({ articles: [] });
    }

    const articles = json.articles.map(a => ({
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
    console.error('GNews fetch error:', err.message);
    return res.status(200).json({ articles: [] });
  }
};
