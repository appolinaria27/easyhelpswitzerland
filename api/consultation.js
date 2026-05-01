const nodemailer = require('nodemailer');
const crypto     = require('crypto');
const { saveEntry } = require('../lib/github-storage');

function getRawBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
    req.on('end',  () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

function makeTransporter() {
  return nodemailer.createTransport({
    host:   process.env.SMTP_HOST,
    port:   parseInt(process.env.SMTP_PORT || '587'),
    secure: false,
    auth: { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS },
  });
}

module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  // Parse body manually (bodyParser: false)
  let body = {};
  try {
    const raw = await getRawBody(req);
    if (raw.length) body = JSON.parse(raw.toString('utf8'));
  } catch {}

  const { name, email, phone, location, topic, message } = body || {};

  if (!name || name.length < 2 || name.length > 100) return res.status(400).json({ error: 'Invalid name' });
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return res.status(400).json({ error: 'Invalid email' });
  if (phone   && phone.length   > 50)   return res.status(400).json({ error: 'Phone too long' });
  if (location && location.length > 100) return res.status(400).json({ error: 'Location too long' });
  if (topic   && topic.length   > 200)  return res.status(400).json({ error: 'Topic too long' });
  if (message && message.length > 2000) return res.status(400).json({ error: 'Message too long' });

  const safeName = String(name).replace(/[\r\n]/g, ' ');
  const id       = crypto.randomBytes(16).toString('hex');
  const now      = new Date().toISOString();

  // ── Persist to GitHub ────────────────────────────────────────────────────────
  try {
    const record = {
      internal_booking_id: id,
      type:       'free',
      name:       safeName,
      email:      String(email).slice(0, 200),
      phone:      String(phone    || '').slice(0, 50),
      location:   String(location || '').slice(0, 100),
      topic:      String(topic    || '').slice(0, 200),
      message:    String(message  || '').slice(0, 2000),
      created_at: now,
    };
    await saveEntry('free-consultations', `consult-${id}.json`, record);
  } catch (err) {
    console.error('GitHub save error:', err.message);
    // Non-fatal — still send emails so client is not left without response
  }

  // ── Emails ───────────────────────────────────────────────────────────────────
  const t = makeTransporter();

  try {
    await t.sendMail({
      from:    `"Easy Help Switzerland" <${process.env.SMTP_FROM || process.env.SMTP_USER}>`,
      to:      process.env.ADMIN_EMAIL,
      replyTo: email,
      subject: `New free consultation request — ${safeName}`,
      html: `
        <div style="font-family:sans-serif;max-width:560px;margin:0 auto">
          <div style="background:#0a0e14;padding:24px 32px;border-radius:12px 12px 0 0">
            <p style="margin:0;color:#fff;font-size:20px">Easy Help Switzerland</p>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">Free Consultation Request</p>
          </div>
          <div style="background:#f0f5ff;padding:24px 32px;border-radius:0 0 12px 12px">
            <table style="width:100%;border-collapse:collapse">
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Name</td>
                  <td style="padding:8px 0;font-weight:600">${safeName}</td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Email</td>
                  <td style="padding:8px 0"><a href="mailto:${email}">${email}</a></td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Phone</td>
                  <td style="padding:8px 0">${phone || '—'}</td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Location</td>
                  <td style="padding:8px 0">${location || '—'}</td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Topic</td>
                  <td style="padding:8px 0">${topic || '—'}</td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;vertical-align:top">Message</td>
                  <td style="padding:8px 0">${message || '—'}</td></tr>
            </table>
          </div>
        </div>`,
      text: `New free consultation request\nName: ${safeName}\nEmail: ${email}\nPhone: ${phone}\nLocation: ${location}\nTopic: ${topic}\nMessage: ${message}`,
    });
  } catch (err) {
    console.error('Admin email error:', err.message);
    return res.status(500).json({ error: 'Could not send your request. Please try again.' });
  }

  try {
    await t.sendMail({
      from:    `"Easy Help Switzerland" <${process.env.SMTP_FROM || process.env.SMTP_USER}>`,
      to:      email,
      subject: 'We received your consultation request — Easy Help Switzerland',
      html: `
        <div style="font-family:sans-serif;max-width:560px;margin:0 auto">
          <div style="background:#0a0e14;padding:24px 32px;border-radius:12px 12px 0 0">
            <p style="margin:0;color:#fff;font-size:20px">Easy Help Switzerland</p>
          </div>
          <div style="padding:28px 32px">
            <p style="margin:0 0 16px">Dear <strong>${safeName}</strong>,</p>
            <p style="margin:0 0 16px;color:#444;line-height:1.6">
              Thank you for reaching out. We have received your free consultation request and will get back to you within 24 hours.
            </p>
            <p style="margin:0;color:#555">Best regards,<br>Polina Kravtsova<br>Easy Help Switzerland</p>
          </div>
        </div>`,
      text: `Dear ${safeName},\n\nThank you for reaching out. We will contact you within 24 hours.\n\nBest regards,\nPolina Kravtsova\nEasy Help Switzerland`,
    });
  } catch (err) {
    console.error('Client confirmation email error:', err.message);
  }

  return res.status(200).json({ ok: true });
};

module.exports.config = { api: { bodyParser: false } };
