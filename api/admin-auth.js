const crypto = require('crypto');

// Simple token: HMAC of timestamp, signed with ADMIN_PASSWORD
// Token is valid for 8 hours
const TTL = 8 * 60 * 60 * 1000;

function getRawBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
    req.on('end',  () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

function makeToken(password) {
  const ts = Date.now();
  const sig = crypto.createHmac('sha256', password).update(String(ts)).digest('hex');
  return `${ts}.${sig}`;
}

function verifyToken(token, password) {
  if (!token) return false;
  const parts = token.split('.');
  if (parts.length !== 2) return false;
  const [ts, sig] = parts;
  if (Date.now() - parseInt(ts) > TTL) return false;
  const expected = crypto.createHmac('sha256', password).update(ts).digest('hex');
  try {
    return crypto.timingSafeEqual(Buffer.from(sig, 'hex'), Buffer.from(expected, 'hex'));
  } catch { return false; }
}

module.exports = async (req, res) => {
  const password = (process.env.ADMIN_PASSWORD || '').trim();
  if (!password) return res.status(500).json({ error: 'Admin not configured' });

  // POST /api/admin-auth — login
  if (req.method === 'POST') {
    let body = {};
    try {
      const raw = await getRawBody(req);
      if (raw.length > 0) body = JSON.parse(raw.toString('utf8'));
    } catch { /* malformed body — body stays {} */ }

    if (!body.password || body.password.trim() !== password) {
      return res.status(401).json({ error: 'Wrong password' });
    }
    const token = makeToken(password);
    res.setHeader('Set-Cookie', `admin_token=${token}; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=28800`);
    return res.status(200).json({ ok: true });
  }

  // GET /api/admin-auth — validate existing cookie
  if (req.method === 'GET') {
    const cookie = req.headers.cookie || '';
    const match = cookie.match(/admin_token=([^;]+)/);
    const token = match ? match[1] : '';
    if (!verifyToken(token, password)) {
      return res.status(401).json({ error: 'Not authenticated' });
    }
    return res.status(200).json({ ok: true });
  }

  // DELETE /api/admin-auth — logout
  if (req.method === 'DELETE') {
    res.setHeader('Set-Cookie', 'admin_token=; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=0');
    return res.status(200).json({ ok: true });
  }

  return res.status(405).end();
};

// Disable Vercel's automatic body parser so we can read the raw stream
module.exports.config = { api: { bodyParser: false } };
