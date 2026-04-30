const fs   = require('fs');
const path = require('path');
const crypto = require('crypto');

const TTL = 8 * 60 * 60 * 1000;

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

function loadDir(dir, type, pattern) {
  if (!fs.existsSync(dir)) return [];
  try {
    return fs.readdirSync(dir)
      .filter(f => f.endsWith('.json') && (!pattern || f.startsWith(pattern)))
      .map(f => {
        try {
          const d = JSON.parse(fs.readFileSync(path.join(dir, f), 'utf8'));
          d._type = type;
          d._file = f;
          return d;
        } catch { return null; }
      })
      .filter(Boolean)
      .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
  } catch { return []; }
}

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  const password = process.env.ADMIN_PASSWORD;
  if (!password) return res.status(500).json({ error: 'Admin not configured' });

  const cookie = req.headers.cookie || '';
  const match  = cookie.match(/admin_token=([^;]+)/);
  const token  = match ? match[1] : '';
  if (!verifyToken(token, password)) {
    return res.status(401).json({ error: 'Not authenticated' });
  }

  const root = path.join(__dirname, '..');
  const consultations = loadDir(path.join(root, 'free-consultations'), 'free',    'consult-');
  const paid          = loadDir(path.join(root, 'bookings'),           'paid',    'booking-');
  const pending       = loadDir(path.join(root, 'pending-bookings'),   'pending', 'booking-');

  const totalRevenue = paid.reduce((s, b) => s + parseFloat(b.price_chf || 0), 0);

  return res.status(200).json({
    stats: {
      consultations: consultations.length,
      paid:          paid.length,
      pending:       pending.length,
      revenue:       totalRevenue.toFixed(2),
    },
    entries: [...consultations, ...paid, ...pending],
  });
};
