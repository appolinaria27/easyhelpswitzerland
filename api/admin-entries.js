const crypto = require('crypto');
const { loadDir, loadNote } = require('../lib/github-storage');

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

module.exports = async (req, res) => {
  if (req.method !== 'GET') return res.status(405).end();

  const password = (process.env.ADMIN_PASSWORD || '').trim();
  if (!password) return res.status(500).json({ error: 'Admin not configured' });

  const cookie = req.headers.cookie || '';
  const match  = cookie.match(/admin_token=([^;]+)/);
  const token  = match ? match[1] : '';
  if (!verifyToken(token, password)) return res.status(401).json({ error: 'Not authenticated' });

  // Load all three directories from GitHub in parallel
  const [consultations, paid, pending] = await Promise.all([
    loadDir('free-consultations', 'free'),
    loadDir('bookings',           'paid'),
    loadDir('pending-bookings',   'pending'),
  ]);

  // Merge admin-data notes into each entry (in parallel)
  const mergeNote = async entry => {
    try {
      const note = await loadNote(entry.internal_booking_id);
      entry._termin     = note.termin     || null;
      entry._status     = note.status     || (entry._type === 'pending' ? 'pending' : 'confirmed');
      entry._admin_note = note.admin_note || '';
    } catch {
      entry._termin     = null;
      entry._status     = entry._type === 'pending' ? 'pending' : 'confirmed';
      entry._admin_note = '';
    }
    return entry;
  };

  const allEntries = await Promise.all(
    [...consultations, ...paid, ...pending].map(mergeNote)
  );

  const totalRevenue = paid.reduce((s, b) => s + parseFloat(b.price_chf || 0), 0);

  return res.status(200).json({
    stats: {
      consultations: consultations.length,
      paid:          paid.length,
      pending:       pending.length,
      revenue:       totalRevenue.toFixed(2),
    },
    entries: allEntries,
  });
};
