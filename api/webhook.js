const Stripe     = require('stripe');
const nodemailer = require('nodemailer');
const crypto     = require('crypto');
const { saveEntry } = require('../lib/github-storage');

function getRawBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
    req.on('end',   () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

function makeTransporter() {
  return nodemailer.createTransport({
    host:   process.env.SMTP_HOST,
    port:   parseInt(process.env.SMTP_PORT || '587'),
    secure: false,
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS,
    },
  });
}

async function sendAdminEmail(meta, amountChf) {
  const t = makeTransporter();
  await t.sendMail({
    from:    `"Easy Help Switzerland" <${process.env.MAIL_FROM || process.env.SMTP_USER}>`,
    to:      process.env.ADMIN_EMAIL,
    replyTo: meta.email,
    subject: `✅ New booking: ${meta.name} — ${meta.package} (CHF ${amountChf})`,
    html: `
      <div style="font-family:sans-serif;max-width:560px;margin:0 auto">
        <div style="background:#0a0e14;padding:24px 32px;border-radius:12px 12px 0 0">
          <p style="margin:0;color:#fff;font-size:20px">Easy Help Switzerland</p>
          <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">New Paid Booking</p>
        </div>
        <div style="background:#f0f5ff;padding:24px 32px;border-radius:0 0 12px 12px">
          <table style="width:100%;border-collapse:collapse">
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Package</td>
                <td style="padding:8px 0;font-weight:600">${meta.package_name || meta.package} — CHF ${amountChf}</td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Name</td>
                <td style="padding:8px 0;font-weight:600">${meta.name}</td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Email</td>
                <td style="padding:8px 0"><a href="mailto:${meta.email}">${meta.email}</a></td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Phone</td>
                <td style="padding:8px 0">${meta.phone || '—'}</td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Location</td>
                <td style="padding:8px 0">${meta.location || '—'}</td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Preferred</td>
                <td style="padding:8px 0">${meta.preferred || '—'}</td></tr>
            <tr><td style="padding:8px 0;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em;vertical-align:top">Message</td>
                <td style="padding:8px 0">${meta.message || '—'}</td></tr>
          </table>
        </div>
      </div>`,
    text: `New booking\nPackage: ${meta.package_name || meta.package} CHF ${amountChf}\nName: ${meta.name}\nEmail: ${meta.email}\nPhone: ${meta.phone}\nLocation: ${meta.location}\nMessage: ${meta.message}`,
  });
}

async function sendClientEmail(meta) {
  if (!meta.email) return;
  const t = makeTransporter();
  await t.sendMail({
    from:    `"Easy Help Switzerland" <${process.env.MAIL_FROM || process.env.SMTP_USER}>`,
    to:      meta.email,
    subject: 'Your booking is confirmed — Easy Help Switzerland',
    html: `
      <div style="font-family:sans-serif;max-width:560px;margin:0 auto">
        <div style="background:#0a0e14;padding:24px 32px;border-radius:12px 12px 0 0">
          <p style="margin:0;color:#fff;font-size:20px">Easy Help Switzerland</p>
          <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase">Booking Confirmed</p>
        </div>
        <div style="padding:28px 32px">
          <p style="margin:0 0 16px">Dear <strong>${meta.name}</strong>,</p>
          <p style="margin:0 0 16px;color:#444;line-height:1.6">
            Your booking is confirmed. We will contact you within 24 hours to arrange the details of your consultation.
          </p>
          <p style="margin:0 0 8px;color:#888;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Package</p>
          <p style="margin:0 0 20px;font-weight:600">${meta.package_name || meta.package}</p>
          <p style="margin:0;color:#555;line-height:1.6">Looking forward to speaking with you.<br><br>Polina Kravtsova<br>Easy Help Switzerland</p>
        </div>
      </div>`,
    text: `Dear ${meta.name},\n\nYour booking is confirmed. We will contact you within 24 hours.\n\nPackage: ${meta.package_name || meta.package}\n\nBest regards,\nPolina Kravtsova\nEasy Help Switzerland`,
  });
}

module.exports = async (req, res) => {
  if (req.method !== 'POST') return res.status(405).end('Method Not Allowed');

  const rawBody = await getRawBody(req);
  const sig     = req.headers['stripe-signature'];

  let event;
  try {
    const stripe = new Stripe(process.env.STRIPE_SECRET_KEY, { apiVersion: '2024-06-20' });
    event = stripe.webhooks.constructEvent(rawBody, sig, process.env.STRIPE_WEBHOOK_SECRET);
  } catch (err) {
    console.error('Webhook signature error:', err.message);
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  if (event.type === 'checkout.session.completed') {
    const session    = event.data.object;
    const meta       = session.metadata || {};
    const amountChf  = ((session.amount_total || 0) / 100).toFixed(2);
    const stripeId   = session.id;

    // ── Find and promote pending booking, or create new paid record ──────────
    const id  = meta.pending_booking_id || crypto.randomBytes(16).toString('hex');
    const now = new Date().toISOString();

    try {
      // Delete pending booking if it exists (we promoted it to paid)
      if (meta.pending_booking_id) {
        const { ghRead, ghDelete } = require('../lib/github-storage');
        const pendingPath = `pending-bookings/booking-${meta.pending_booking_id}.json`;
        const { sha: pendingSha } = await ghRead(pendingPath);
        if (pendingSha) await ghDelete(pendingPath, pendingSha);
      }

      // Save as paid booking
      const record = {
        internal_booking_id: id,
        type:         'paid',
        stripe_session_id: stripeId,
        package:      meta.package      || '',
        package_name: meta.package_name || meta.package || '',
        name:         meta.name         || '',
        email:        meta.email        || '',
        phone:        meta.phone        || '',
        location:     meta.location     || '',
        preferred:    meta.preferred    || '',
        message:      meta.message      || '',
        price_chf:    amountChf,
        created_at:   now,
      };
      await saveEntry('bookings', `booking-${id}.json`, record);
    } catch (err) {
      console.error('GitHub save paid booking error:', err.message);
    }

    try { await sendAdminEmail(meta, amountChf); } catch (e) { console.error('Admin email error:', e.message); }
    try { await sendClientEmail(meta); }          catch (e) { console.error('Client email error:', e.message); }
  }

  return res.status(200).json({ received: true });
};

module.exports.config = { api: { bodyParser: false } };
