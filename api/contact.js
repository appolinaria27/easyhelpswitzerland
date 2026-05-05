/**
 * api/contact.js
 * Simple contact form — sends an email notification to admin.
 * No booking created, no data stored.
 */

const nodemailer = require('nodemailer');

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

function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  let body = {};
  try {
    const raw = await getRawBody(req);
    if (raw.length) body = JSON.parse(raw.toString('utf8'));
  } catch {
    return res.status(400).json({ error: 'Invalid JSON' });
  }

  const name    = String(body.name    || '').trim().slice(0, 100);
  const email   = String(body.email   || '').trim().slice(0, 200);
  const phone   = String(body.phone   || '').trim().slice(0, 50);
  const message = String(body.message || '').trim().slice(0, 2000);

  if (!name || !email) {
    return res.status(400).json({ error: 'Name and email are required' });
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return res.status(400).json({ error: 'Invalid email' });
  }

  const adminEmail = process.env.ADMIN_EMAIL || process.env.SMTP_USER;
  const t = makeTransporter();

  try {
    // Email to admin
    await t.sendMail({
      from:    `"Easy Help Switzerland" <${process.env.MAIL_FROM || process.env.SMTP_USER}>`,
      to:      adminEmail,
      subject: `New inquiry — ${name}`,
      html: `
        <div style="font-family:Arial,sans-serif;max-width:540px;margin:0 auto">
          <div style="background:#111;padding:24px 28px;border-radius:12px 12px 0 0">
            <p style="margin:0;color:#fff;font-size:18px;font-weight:600">Easy Help Switzerland</p>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">New Contact Inquiry</p>
          </div>
          <div style="background:#f9f9f9;padding:28px;border-radius:0 0 12px 12px">
            <table style="width:100%;border-collapse:collapse">
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;width:100px">Name</td><td style="padding:8px 0;font-size:15px;color:#111">${esc(name)}</td></tr>
              <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Email</td><td style="padding:8px 0;font-size:15px"><a href="mailto:${esc(email)}" style="color:#4693e8">${esc(email)}</a></td></tr>
              ${phone ? `<tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase">Phone</td><td style="padding:8px 0;font-size:15px;color:#111">${esc(phone)}</td></tr>` : ''}
              ${message ? `<tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;vertical-align:top">Message</td><td style="padding:8px 0;font-size:15px;color:#111;white-space:pre-wrap">${esc(message)}</td></tr>` : ''}
            </table>
            <p style="margin:24px 0 0;font-size:13px;color:#aaa">Reply directly to this email to respond to ${esc(name)}.</p>
          </div>
        </div>`,
      replyTo: email,
      text: `New inquiry\nName: ${name}\nEmail: ${email}\nPhone: ${phone}\nMessage: ${message}`,
    });

    // Confirmation to the person who wrote
    await t.sendMail({
      from:    `"Easy Help Switzerland" <${process.env.MAIL_FROM || process.env.SMTP_USER}>`,
      to:      email,
      subject: 'We received your message — Easy Help Switzerland',
      html: `
        <div style="font-family:Arial,sans-serif;max-width:540px;margin:0 auto">
          <div style="background:#111;padding:24px 28px;border-radius:12px 12px 0 0">
            <p style="margin:0;color:#fff;font-size:18px;font-weight:600">Easy Help Switzerland</p>
          </div>
          <div style="background:#f9f9f9;padding:28px;border-radius:0 0 12px 12px">
            <p style="font-size:16px;color:#111">Dear ${esc(name)},</p>
            <p style="font-size:15px;color:#333;line-height:1.6">
              Thank you for reaching out. We have received your message and will get back to you within 24 hours.
            </p>
            <p style="font-size:15px;color:#333;line-height:1.6">
              Best regards,<br>
              <strong>Polina Kravtsova</strong><br>
              Easy Help Switzerland
            </p>
          </div>
        </div>`,
      text: `Dear ${name},\n\nThank you for reaching out. We will get back to you within 24 hours.\n\nBest regards,\nPolina Kravtsova\nEasy Help Switzerland`,
    });

    return res.status(200).json({ ok: true });
  } catch (err) {
    console.error('Contact mail error:', err.message);
    return res.status(500).json({ error: 'Failed to send email' });
  }
};
