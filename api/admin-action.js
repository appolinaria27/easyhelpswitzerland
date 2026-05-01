const crypto     = require('crypto');
const nodemailer = require('nodemailer');
const { loadNote, saveNote, findEntry, deleteEntry } = require('../lib/github-storage');

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
  <p style="font-size:14px;color:#555;line-height:1.6">If you would like to postpone your appointment, simply reply to this email and let us know your preferred date and time — we will do our best to accommodate you.</p>
</td></tr>
<tr><td style="background:#f8f9fb;padding:20px 36px;border-top:1px solid #eee">
  <p style="margin:0;font-size:12px;color:#aaa;text-align:center">Easy Help Switzerland · easyhelpswitzerland.ch</p>
</td></tr>
</table></td></tr></table></body></html>`,
  };
}

function rescheduleEmail(booking, terminISO) {
  const dt   = new Date(terminISO);
  const day  = dt.toLocaleDateString('en-GB', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
  const time = dt.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
  const name = booking.name || 'Client';
  const pkg  = booking.package_name || booking.package || booking.topic || '';
  const fmt  = booking.preferred || '';

  return {
    subject: `Your consultation has been rescheduled — ${day} at ${time}`,
    html: `<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08)">
<tr><td style="background:#0a0e14;padding:28px 36px">
  <p style="margin:0;color:#fff;font-size:22px">Easy Help Switzerland</p>
  <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">Appointment Rescheduled</p>
</td></tr>
<tr><td style="padding:36px">
  <p style="font-size:16px;color:#111">Dear <strong>${name}</strong>,</p>
  <p style="font-size:15px;color:#444;line-height:1.6">Your consultation has been rescheduled. Here are your new appointment details:</p>
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:24px;margin:20px 0">
    <tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">New date</p><p style="margin:4px 0 0;font-size:18px;font-weight:600;color:#111">${day}</p></td></tr>
    <tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">New time</p><p style="margin:4px 0 0;font-size:18px;font-weight:600;color:#111">${time}</p></td></tr>
    ${pkg ? `<tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Service</p><p style="margin:4px 0 0;font-size:15px;color:#111">${pkg}</p></td></tr>` : ''}
    ${fmt ? `<tr><td style="padding:8px 0"><p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.08em">Format</p><p style="margin:4px 0 0;font-size:15px;color:#111">${fmt}</p></td></tr>` : ''}
  </table>
  <p style="font-size:14px;color:#555;line-height:1.6">If you would like to postpone your appointment again, simply reply to this email and let us know your preferred date and time — we will do our best to accommodate you.</p>
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
    const note = await loadNote(id);
    if (body.termin      !== undefined) note.termin     = body.termin || null;
    if (body.status      !== undefined) note.status     = body.status;
    if (body.admin_note  !== undefined) note.admin_note = body.admin_note;
    await saveNote(id, note);
    return res.status(200).json({ ok: true, note });
  }

  // ── email_only — send confirmation for already-saved termin ───────────────
  if (action === 'email_only') {
    const found = await findEntry(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });
    const note = await loadNote(id);
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

  // ── resched_email — send rescheduling notification for already-saved termin ─
  if (action === 'resched_email') {
    const found = await findEntry(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });
    const note = await loadNote(id);
    if (!note.termin) return res.status(400).json({ error: 'No termin saved yet' });

    try {
      const { subject, html } = rescheduleEmail(found.data, note.termin);
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

    const note = await loadNote(id);
    note.termin = datetime;
    note.status = note.status || 'confirmed';
    await saveNote(id, note);

    if (send_mail) {
      const found = await findEntry(id);
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
    const found = await findEntry(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });

    const note = await loadNote(id);
    note.status = 'cancelled';
    await saveNote(id, note);

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
    const found = await findEntry(id);
    if (!found) return res.status(404).json({ error: 'Booking not found' });
    try {
      await deleteEntry(found.path, found.sha);
      // Also remove admin-data note
      try {
        const { ghRead, ghDelete } = require('../lib/github-storage');
        const notePath = `admin-data/${id}.json`;
        const { sha: noteSha } = await ghRead(notePath);
        if (noteSha) await ghDelete(notePath, noteSha);
      } catch {}
    } catch (e) {
      return res.status(500).json({ error: e.message });
    }
    return res.status(200).json({ ok: true });
  }

  return res.status(400).json({ error: 'Unknown action' });
};

module.exports.config = { api: { bodyParser: false } };
