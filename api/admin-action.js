const fs         = require('fs');
const path       = require('path');
const crypto     = require('crypto');
const nodemailer = require('nodemailer');

const TTL  = 8 * 60 * 60 * 1000;
const ROOT = path.join(__dirname, '..');

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

// ── Admin-data helpers ────────────────────────────────────────────────────────
function noteFile(id) {
  const clean = (id || '').replace(/[^a-f0-9]/g, '');
  return path.join(ROOT, 'admin-data', clean + '.json');
}
function loadNote(id) {
  const fp = noteFile(id);
  if (!fs.existsSync(fp)) return {};
  try { return JSON.parse(fs.readFileSync(fp, 'utf8')); }
  catch { return {}; }
}
function saveNote(id, note) {
  const dir = path.join(ROOT, 'admin-data');
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(noteFile(id), JSON.stringify({ ...note, updated_at: new Date().toISOString() }, null, 2));
}

// ── Find booking across all dirs ──────────────────────────────────────────────
function findBooking(id) {
  const clean = (id || '').replace(/[^a-f0-9]/g, '');
  const dirs = [
    { dir: path.join(ROOT, 'bookings'),           prefix: 'booking-', type: 'paid'    },
    { dir: path.join(ROOT, 'pending-bookings'),   prefix: 'booking-', type: 'pending' },
    { dir: path.join(ROOT, 'free-consultations'), prefix: 'consult-', type: 'free'    },
  ];
  for (const { dir, type } of dirs) {
    if (!fs.existsSync(dir)) continue;
    for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.json'))) {
      try {
        const data = JSON.parse(fs.readFileSync(path.join(dir, f), 'utf8'));
        if (data.internal_booking_id === clean) return { data, file: path.join(dir, f), type };
      } catch {}
    }
  }
  return null;
}

// ── Email ─────────────────────────────────────────────────────────────────────
function makeTransporter() {
  return nodemailer.createTransport({
    host:   process.env.SMTP_HOST,
    port:   parseInt(process.env.SMTP_PORT || '587'),
    secure: false,
    auth:   { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS },
  });
}

function confirmationEmail(booking, terminISO) {
  const dt   = new Date(terminISO);
  const day  = dt.toLocaleDateString('en-GB', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
  const time = dt.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
  const name = booking.name || 'Client';
  const pkg  = booking.package_name || booking.package || booking.topic || '';
  const fmt  = booking.preferred || '';

  return {
    subject: `Your consultation is confirmed — ${day} at ${time}`,
    html: `<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08)">
<tr><td style="background:#0a0e14;padding:28px 36px">
  <p style="margin:0;color:#fff;font-size:22px">Easy Help Switzerland</p>
  <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">Consultation Confirmed</p>
</td></tr>
<tr><td style="padding:36px">
  <p style="font-size:16px;color:#111">Dear <strong>${name}</strong>,</p>
  <p style="font-size:15px;color:#444;line-height:1.6">Your consultation has been confirmed. Here are the details:</p>
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:24px;margin:20px 0">
    <tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Date</p><p style="margin:4px 0 0;font-size:18px;font-weight:600;color:#111">${day}</p></td></tr>
    <tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Time</p><p style="margin:4px 0 0;font-size:18px;font-weight:600;color:#111">${time}</p></td></tr>
    ${pkg ? `<tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Service</p><p style="margin:4px 0 0;font-size:15px;color:#111">${pkg}</p></td></tr>` : ''}
    ${fmt ? `<tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Format</p><p style="margin:4px 0 0;font-size:15px;color:#111">${fmt}</p></td></tr>` : ''}
  </table>
  <p style="font-size:14px;color:#555;line-height:1.6">If you need to reschedule, simply reply to this email.</p>
</td></tr>
<tr><td style="background:#f8f9fb;padding:20px 36px;border-top:1px solid #eee">
  <p style="margin:0;font-size:12px;color:#aaa;text-align:center">Easy Help Switzerland · easyhelpswitzerland.ch</p>
</td></tr>
</table></td></tr></table></body></html>`,
  };
}

function cancellationEmail(booking) {
  const name = booking.name || 'Client';
  const pkg  = booking.package_name || booking.package || booking.topic || '';
  return {
    subject: 'Your consultation has been cancelled',
    html: `<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08)">
<tr><td style="background:#0a0e14;padding:28px 36px">
  <p style="margin:0;color:#fff;font-size:22px">Easy Help Switzerland</p>
  <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">Consultation Cancelled</p>
</td></tr>
<tr><td style="padding:36px">
  <p style="font-size:16px;color:#111">Dear <strong>${name}</strong>,</p>
  <p style="font-size:15px;color:#444;line-height:1.6">We are writing to inform you that your consultation has been cancelled.</p>
  ${pkg ? `<p style="font-size:14px;color:#555"><strong>Service:</strong> ${pkg}</p>` : ''}
  <p style="font-size:14px;color:#555;line-height:1.6">If you have questions or would like to reschedule, please reply to this email. We apologise for any inconvenience.</p>
</td></tr>
<tr><td style="background:#f8f9fb;padding:20px 36px;border-top:1px solid #eee">
  <p style="margin:0;font-size:12px;color:#aaa;text-align:center">Easy Help Switzerland · easyhelpswitzerland.ch</p>
</td></tr>
</table></td></tr></table></body></html>`,
  };
}

// ── Handler ───────────────────────────────────────────────────────────────────
module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).end();

  const password = (process.env.ADMIN_PASSWORD || '').trim();
  if (!password)              return res.status(500).json({ error: 'Admin not configured' });
  if (!isAuth(req, password)) return res.status(401).json({ error: 'Not authenticated' });

  let body = {};
  try {
    const raw = await getRawBody(req);
    if (raw.length) body = JSON.parse(raw.toString('utf8'));
  } catch {}

  const { action, booking_id } = body;
  const id = (booking_id || '').replace(/[^a-f0-9]/g, '');

  // ── save_note ─────────────────────────────────────────────────────────────
  if (action === 'save_note') {
    const note = loadNote(id);
    if (body.termin !== undefined) note.termin     = body.termin || null;
    if (body.status !== undefined) note.status     = body.status;
    if (body.admin_note !== undefined) note.admin_note = body.admin_note;
    saveNote(id, note);
    return res.status(200).json({ ok: true, note });
  }

  // ── email_only — send confirmation for already-saved termin ───────────────
  if (action === 'email_only') {
    const found = findBooking(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });
    const note = loadNote(id);
    if (!note.termin) return res.status(400).json({ error: 'No termin saved yet' });

    try {
      const { subject, html } = confirmationEmail(found.data, note.termin);
      const t = makeTransporter();
      await t.sendMail({ from: process.env.SMTP_FROM || process.env.SMTP_USER, to: found.data.email, subject, html });
      return res.status(200).json({ ok: true, email_sent: true });
    } catch (e) {
      return res.status(200).json({ ok: true, email_sent: false, email_error: e.message });
    }
  }

  // ── schedule — save termin + optionally email ─────────────────────────────
  if (action === 'schedule') {
    const { datetime, send_mail } = body;
    if (!datetime) return res.status(400).json({ error: 'Missing datetime' });

    const note = loadNote(id);
    note.termin = datetime;
    note.status = note.status || 'confirmed';
    saveNote(id, note);

    if (send_mail) {
      const found = findBooking(id);
      if (found?.data?.email) {
        try {
          const { subject, html } = confirmationEmail(found.data, datetime);
          const t = makeTransporter();
          await t.sendMail({ from: process.env.SMTP_FROM || process.env.SMTP_USER, to: found.data.email, subject, html });
          return res.status(200).json({ ok: true, email_sent: true });
        } catch (e) {
          return res.status(200).json({ ok: true, email_sent: false, email_error: e.message });
        }
      }
    }
    return res.status(200).json({ ok: true, email_sent: false });
  }

  // ── cancel ────────────────────────────────────────────────────────────────
  if (action === 'cancel') {
    const found = findBooking(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });

    const note = loadNote(id);
    note.status = 'cancelled';
    saveNote(id, note);

    if (found.data.email) {
      try {
        const { subject, html } = cancellationEmail(found.data);
        const t = makeTransporter();
        await t.sendMail({ from: process.env.SMTP_FROM || process.env.SMTP_USER, to: found.data.email, subject, html });
        return res.status(200).json({ ok: true, email_sent: true });
      } catch (e) {
        return res.status(200).json({ ok: true, email_sent: false, email_error: e.message });
      }
    }
    return res.status(200).json({ ok: true, email_sent: false });
  }

  // ── delete ────────────────────────────────────────────────────────────────
  if (action === 'delete') {
    const found = findBooking(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });
    try {
      fs.unlinkSync(found.file);
      // Also remove admin-data note
      const nf = noteFile(id);
      if (fs.existsSync(nf)) fs.unlinkSync(nf);
    } catch {}
    return res.status(200).json({ ok: true });
  }

  return res.status(400).json({ error: 'Unknown action' });
};

module.exports.config = { api: { bodyParser: false } };
