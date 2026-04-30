const crypto = require('crypto');

// Simple token: HMAC of timestamp, signed with ADMIN_PASSWORD
// Token is valid for 8 hours
const TTL = 8 * 60 * 60 * 1000;

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
  return crypto.timingSafeEqual(Buffer.from(sig, 'hex'), Buffer.from(expected, 'hex'));
}

module.exports = async (req, res) => {
  const password = process.env.ADMIN_PASSWORD;
  if (!password) return res.status(500).json({ error: 'Admin not configured' });

  // POST /api/admin-auth — login
  if (req.method === 'POST') {
    const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
    if (body.password !== password) {
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
