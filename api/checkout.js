const Stripe = require('stripe');
const crypto = require('crypto');
const { saveEntry } = require('../lib/github-storage');

function getRawBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', c => chunks.push(typeof c === 'string' ? Buffer.from(c) : c));
    req.on('end',  () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

const PACKAGES = {
  initial: { name: 'Quick Consultation',    amount: 7900  },
  review:  { name: 'Relocation Help',       amount: 18900 },
  support: { name: 'Relocation Support',    amount: 34900 },
};

module.exports = async (req, res) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  let body = {};
  try {
    const raw = await getRawBody(req);
    if (raw.length) body = JSON.parse(raw.toString('utf8'));
  } catch {}

  const { package: pkg, name, email, phone, location, preferred, message } = body || {};

  const selectedPackage = PACKAGES[pkg];
  if (!selectedPackage) return res.status(400).json({ error: 'Invalid package' });
  if (!name || name.length < 2 || name.length > 100) return res.status(400).json({ error: 'Invalid name' });
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return res.status(400).json({ error: 'Invalid email' });
  if (phone && phone.length > 50)    return res.status(400).json({ error: 'Phone too long' });
  if (location && location.length > 100) return res.status(400).json({ error: 'Location too long' });
  if (message && message.length > 2000)  return res.status(400).json({ error: 'Message too long' });

  const baseUrl = (process.env.APP_URL || 'https://easyhelpswitzerland.ch').replace(/\/$/, '');

  // Generate a booking ID now so webhook can reference it
  const pendingId = crypto.randomBytes(16).toString('hex');
  const now       = new Date().toISOString();

  // Save pending booking to GitHub
  try {
    const record = {
      internal_booking_id: pendingId,
      type:         'pending',
      package:      pkg,
      package_name: selectedPackage.name,
      name:         String(name).slice(0, 100),
      email:        String(email).slice(0, 200),
      phone:        String(phone     || '').slice(0, 50),
      location:     String(location  || '').slice(0, 100),
      preferred:    String(preferred || '').slice(0, 30),
      message:      String(message   || '').slice(0, 500),
      price_chf:    (selectedPackage.amount / 100).toFixed(2),
      created_at:   now,
    };
    await saveEntry('pending-bookings', `booking-${pendingId}.json`, record);
  } catch (err) {
    console.error('GitHub save pending booking error:', err.message);
    // Non-fatal — Stripe session still works without it
  }

  try {
    const stripe = new Stripe(process.env.STRIPE_SECRET_KEY, { apiVersion: '2024-06-20' });

    const session = await stripe.checkout.sessions.create({
      payment_method_types: ['card'],
      mode: 'payment',
      customer_email: email || undefined,
      line_items: [{
        price_data: {
          currency: 'chf',
          product_data: { name: selectedPackage.name },
          unit_amount: selectedPackage.amount,
        },
        quantity: 1,
      }],
      metadata: {
        pending_booking_id: pendingId,
        package:      pkg,
        package_name: selectedPackage.name,
        name:         String(name).slice(0, 100),
        email:        String(email).slice(0, 200),
        phone:        String(phone     || '').slice(0, 50),
        location:     String(location  || '').slice(0, 100),
        preferred:    String(preferred || '').slice(0, 30),
        message:      String(message   || '').slice(0, 500),
      },
      success_url: `${baseUrl}/consultation-success.html?session_id={CHECKOUT_SESSION_ID}`,
      cancel_url:  `${baseUrl}/booking.html`,
    });

    return res.status(200).json({ url: session.url });

  } catch (err) {
    console.error('Stripe error:', err.message);
    return res.status(500).json({ error: 'Payment service unavailable. Please try again.' });
  }
};

module.exports.config = { api: { bodyParser: false } };
