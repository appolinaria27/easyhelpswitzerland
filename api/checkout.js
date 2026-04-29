const Stripe = require('stripe');

const PACKAGES = {
  initial: { name: 'Quick Consultation',    amount: 7900  },
  review:  { name: 'Relocation Help',       amount: 18900 },
  support: { name: 'Relocation Support',    amount: 34900 },
};

module.exports = async (req, res) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  // Parse body (Vercel passes parsed body for JSON, raw string otherwise)
  let body = req.body;
  if (typeof body === 'string') {
    try { body = JSON.parse(body); } catch { body = {}; }
  }

  const { package: pkg, name, email, phone, location, preferred, message } = body || {};

  const selectedPackage = PACKAGES[pkg];
  if (!selectedPackage) return res.status(400).json({ error: 'Invalid package' });
  if (!name || name.length < 2 || name.length > 100) return res.status(400).json({ error: 'Invalid name' });
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return res.status(400).json({ error: 'Invalid email' });
  if (phone && phone.length > 50)    return res.status(400).json({ error: 'Phone too long' });
  if (location && location.length > 100) return res.status(400).json({ error: 'Location too long' });
  if (message && message.length > 2000)  return res.status(400).json({ error: 'Message too long' });

  const baseUrl = (process.env.APP_URL || 'https://easyhelpswitzerland.ch').replace(/\/$/, '');

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
        package:   pkg,
        name:      String(name).slice(0, 100),
        email:     String(email).slice(0, 200),
        phone:     String(phone || '').slice(0, 50),
        location:  String(location || '').slice(0, 100),
        preferred: String(preferred || '').slice(0, 30),
        message:   String(message || '').slice(0, 500),
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
