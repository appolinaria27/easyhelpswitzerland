const crypto     = require('crypto');
const nodemailer = require('nodemailer');
const Stripe     = require('stripe');
const { loadBills, loadBill, saveBill, loadPositions } = require('../lib/github-storage');

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

function calcTotal(positions) {
  return +positions.reduce((s, p) => s + p.total_chf, 0).toFixed(2);
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

function invoiceHtml(bill, paymentUrl) {
  const billDate = new Date(bill.closed_at).toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' });
  const billNum  = `INV-${bill.id}`;

  const rows = bill.positions.map(p => `
    <tr>
      <td style="padding:10px 14px;border-bottom:1px solid #f0f0f0;color:#555">${p.position_number}</td>
      <td style="padding:10px 14px;border-bottom:1px solid #f0f0f0">${p.name}</td>
      <td style="padding:10px 14px;border-bottom:1px solid #f0f0f0;text-align:center">${p.quantity}</td>
      <td style="padding:10px 14px;border-bottom:1px solid #f0f0f0;text-align:right">CHF ${parseFloat(p.unit_price_chf).toFixed(2)}</td>
      <td style="padding:10px 14px;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:600">CHF ${parseFloat(p.total_chf).toFixed(2)}</td>
    </tr>`).join('');

  // QR code via free public API — encodes the Stripe payment link
  const qrBlock = paymentUrl ? `
    <div style="padding:28px 40px;border-top:1px solid #eee;text-align:center">
      <p style="margin:0 0 6px;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.05em">Pay online</p>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=6&data=${encodeURIComponent(paymentUrl)}"
           alt="Payment QR code" width="160" height="160"
           style="display:block;margin:0 auto 12px;border:1px solid #eee;border-radius:8px">
      <p style="margin:0;font-size:13px;color:#444">Scan to pay — or click the button below</p>
      <a href="${paymentUrl}"
         style="display:inline-block;margin-top:14px;padding:12px 28px;background:#4693e8;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600">
        Pay CHF ${parseFloat(bill.total_chf).toFixed(2)} now
      </a>
    </div>` : '';

  return `<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:'Helvetica Neue',Arial,sans-serif">
  <div style="max-width:620px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">
    <div style="background:#4693e8;padding:32px 40px">
      <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700">Easy Help Switzerland</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:14px">Your Invoice</p>
    </div>
    <div style="padding:28px 40px 0">
      <div style="display:flex;justify-content:space-between">
        <div>
          <p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.05em">Invoice number</p>
          <p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#111">${billNum}</p>
        </div>
        <div style="text-align:right">
          <p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.05em">Date</p>
          <p style="margin:4px 0 0;font-size:15px;color:#333">${billDate}</p>
        </div>
      </div>
    </div>
    <div style="padding:20px 40px;border-bottom:1px solid #eee">
      <p style="margin:0;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.05em">Billed to</p>
      <p style="margin:6px 0 0;font-size:16px;font-weight:600;color:#111">${bill.client_name}</p>
      <p style="margin:2px 0 0;font-size:14px;color:#555">${bill.client_email}</p>
    </div>
    <div style="padding:24px 40px">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#f8f9fb">
            <th style="padding:10px 14px;text-align:left;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.05em">#</th>
            <th style="padding:10px 14px;text-align:left;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.05em">Description</th>
            <th style="padding:10px 14px;text-align:center;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.05em">Qty</th>
            <th style="padding:10px 14px;text-align:right;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.05em">Unit price</th>
            <th style="padding:10px 14px;text-align:right;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.05em">Total</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
      <div style="display:flex;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:2px solid #111">
        <div style="text-align:right">
          <span style="font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.05em;margin-right:24px">Total due</span>
          <span style="font-size:26px;font-weight:700;color:#4693e8">CHF ${parseFloat(bill.total_chf).toFixed(2)}</span>
        </div>
      </div>
    </div>
    ${qrBlock}
    <div style="padding:24px 40px;background:#f8f9fb;border-top:1px solid #eee">
      <p style="margin:0;font-size:13px;color:#888;text-align:center">
        Questions? Contact us at <a href="mailto:info@easyhelpswitzerland.ch" style="color:#4693e8">info@easyhelpswitzerland.ch</a>
      </p>
      <p style="margin:8px 0 0;font-size:12px;color:#bbb;text-align:center">Easy Help Switzerland · easyhelpswitzerland.ch</p>
    </div>
  </div>
</body>
</html>`;
}

// ── Handler ───────────────────────────────────────────────────────────────────
module.exports = async (req, res) => {
  const password = (process.env.ADMIN_PASSWORD || '').trim();
  if (!password)              return res.status(500).json({ error: 'Admin not configured' });
  if (!isAuth(req, password)) return res.status(401).json({ error: 'Not authenticated' });

  let body = {};
  if (req.method !== 'GET') {
    try {
      const raw = await getRawBody(req);
      if (raw.length) body = JSON.parse(raw.toString('utf8'));
    } catch {}
  }

  // GET — list all or fetch single
  if (req.method === 'GET') {
    const qs = req.url.includes('?') ? req.url.split('?')[1] : '';
    const id = new URLSearchParams(qs).get('id');
    if (id) {
      const bill = await loadBill(id);
      if (!bill) return res.status(404).json({ error: 'Not found' });
      return res.status(200).json(bill);
    }
    return res.status(200).json(await loadBills());
  }

  // POST — actions
  if (req.method === 'POST') {
    const { action } = body;

    // ── create ──────────────────────────────────────────────────────────────
    if (action === 'create') {
      const { client_name, client_email } = body;
      if (!client_name || !client_email)
        return res.status(400).json({ error: 'Missing client name or email' });
      const bill = {
        id:           Date.now().toString(),
        client_name:  client_name.trim(),
        client_email: client_email.trim(),
        status:       'open',
        created_at:   new Date().toISOString(),
        closed_at:    null,
        positions:    [],
        total_chf:    0,
      };
      await saveBill(bill);
      return res.status(200).json(bill);
    }

    // ── add_position ─────────────────────────────────────────────────────────
    if (action === 'add_position') {
      const { bill_id, position_number, quantity } = body;
      const bill = await loadBill(bill_id);
      if (!bill)               return res.status(404).json({ error: 'Bill not found' });
      if (bill.status !== 'open') return res.status(400).json({ error: 'Bill is closed' });

      const positions = await loadPositions();
      const pos = positions.find(p => p.number === parseInt(position_number));
      if (!pos) return res.status(404).json({ error: `Position #${position_number} not found in catalog` });

      const qty = parseFloat(quantity) || 1;
      bill.positions.push({
        position_number: pos.number,
        name:            pos.name,
        quantity:        qty,
        unit_price_chf:  pos.price_chf,
        total_chf:       +(pos.price_chf * qty).toFixed(2),
      });
      bill.total_chf = calcTotal(bill.positions);
      await saveBill(bill);
      return res.status(200).json(bill);
    }

    // ── remove_position ──────────────────────────────────────────────────────
    if (action === 'remove_position') {
      const { bill_id, position_index } = body;
      const bill = await loadBill(bill_id);
      if (!bill)               return res.status(404).json({ error: 'Bill not found' });
      if (bill.status !== 'open') return res.status(400).json({ error: 'Bill is closed' });

      bill.positions.splice(parseInt(position_index), 1);
      bill.total_chf = calcTotal(bill.positions);
      await saveBill(bill);
      return res.status(200).json(bill);
    }

    // ── update_position ──────────────────────────────────────────────────────
    if (action === 'update_position') {
      const { bill_id, position_index, quantity } = body;
      const bill = await loadBill(bill_id);
      if (!bill)               return res.status(404).json({ error: 'Bill not found' });
      if (bill.status !== 'open') return res.status(400).json({ error: 'Bill is closed' });

      const idx = parseInt(position_index);
      const p   = bill.positions[idx];
      if (!p) return res.status(404).json({ error: 'Position not found' });

      const qty = parseFloat(quantity) || 1;
      p.quantity  = qty;
      p.total_chf = +(p.unit_price_chf * qty).toFixed(2);
      bill.total_chf = calcTotal(bill.positions);
      await saveBill(bill);
      return res.status(200).json(bill);
    }

    // ── close ────────────────────────────────────────────────────────────────
    if (action === 'close') {
      const { bill_id } = body;
      const bill = await loadBill(bill_id);
      if (!bill)                  return res.status(404).json({ error: 'Bill not found' });
      if (bill.status !== 'open') return res.status(400).json({ error: 'Bill is already closed' });
      if (!bill.positions.length) return res.status(400).json({ error: 'Add at least one position before closing' });

      bill.status    = 'closed';
      bill.closed_at = new Date().toISOString();

      // ── Create Stripe payment link for this bill ──────────────────────────
      let paymentUrl = null;
      try {
        const stripe = new Stripe(process.env.STRIPE_SECRET_KEY, { apiVersion: '2024-06-20' });
        // Create a one-time price attached to a product
        const product = await stripe.products.create({
          name: `Invoice INV-${bill.id} — ${bill.client_name}`,
        });
        const price = await stripe.prices.create({
          product:     product.id,
          unit_amount: Math.round(bill.total_chf * 100),
          currency:    'chf',
        });
        const link = await stripe.paymentLinks.create({
          line_items: [{ price: price.id, quantity: 1 }],
        });
        paymentUrl         = link.url;
        bill.payment_link  = paymentUrl;
        bill.stripe_price  = price.id;
        bill.stripe_product = product.id;
      } catch (e) {
        console.error('Stripe payment link error:', e.message);
        // Non-fatal — invoice is still sent, just without the QR / pay button
      }

      await saveBill(bill);

      try {
        const transporter = makeTransporter();
        await transporter.sendMail({
          from:    process.env.SMTP_FROM || process.env.SMTP_USER,
          to:      bill.client_email,
          subject: `Your Invoice from Easy Help Switzerland — CHF ${parseFloat(bill.total_chf).toFixed(2)}`,
          html:    invoiceHtml(bill, paymentUrl),
        });
        return res.status(200).json({ bill, email_sent: true, payment_link: paymentUrl });
      } catch (e) {
        return res.status(200).json({ bill, email_sent: false, email_error: e.message, payment_link: paymentUrl });
      }
    }

    return res.status(400).json({ error: 'Unknown action' });
  }

  return res.status(405).end();
};

module.exports.config = { api: { bodyParser: false } };
