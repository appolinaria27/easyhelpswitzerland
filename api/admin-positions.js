const crypto = require('crypto');
const { loadPositions, savePositions } = require('../lib/github-storage');

const TTL = 8 * 60 * 60 * 1000;

function getRawBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
    req.on('end',  () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

function verifyToken(token, password) {
  if (!token) return false;
  const parts = token.split('.');
  if (parts.length !== 2) return false;
  const [ts, sig] = parts;
  if (Date.now() - parseInt(ts) > TTL) return false;
  try {
    const expected = crypto.createHmac('sha256', password).update(ts).digest('hex');
    return crypto.timingSafeEqual(Buffer.from(sig, 'hex'), Buffer.from(expected, 'hex'));
  } catch { return false; }
}

function isAuth(req, password) {
  const cookie = req.headers.cookie || '';
  const match  = cookie.match(/admin_token=([^;]+)/);
  return verifyToken(match ? match[1] : '', password);
}

module.exports = async (req, res) => {
  const password = (process.env.ADMIN_PASSWORD || '').trim();
  if (!password)              return res.status(500).json({ error: 'Admin not configured' });
  if (!isAuth(req, password)) return res.status(401).json({ error: 'Not authenticated' });

  // GET — list all positions
  if (req.method === 'GET') {
    return res.status(200).json(await loadPositions());
  }

  // POST — create / update / delete
  if (req.method === 'POST') {
    let body = {};
    try {
      const raw = await getRawBody(req);
      if (raw.length) body = JSON.parse(raw.toString('utf8'));
    } catch {}

    const { action, number, name, price_chf } = body;
    const num = parseInt(number);

    if (action === 'create') {
      const positions = await loadPositions();
      if (positions.find(p => p.number === num))
        return res.status(400).json({ error: 'Position number already exists' });
      positions.push({ number: num, name: String(name).trim(), price_chf: parseFloat(price_chf) });
      positions.sort((a, b) => a.number - b.number);
      await savePositions(positions);
      return res.status(200).json(positions);
    }

    if (action === 'update') {
      const positions = await loadPositions();
      const idx = positions.findIndex(p => p.number === num);
      if (idx === -1) return res.status(404).json({ error: 'Not found' });
      positions[idx] = { number: num, name: String(name).trim(), price_chf: parseFloat(price_chf) };
      await savePositions(positions);
      return res.status(200).json(positions);
    }

    if (action === 'delete') {
      const positions = (await loadPositions()).filter(p => p.number !== num);
      await savePositions(positions);
      return res.status(200).json(positions);
    }

    return res.status(400).json({ error: 'Unknown action' });
  }

  return res.status(405).end();
};

module.exports.config = { api: { bodyParser: false } };
