<?php http_response_code(200); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Privacy Policy — Easy Help Switzerland</title>
  <meta name="description" content="Privacy Policy for Easy Help Switzerland — how we collect, use, and protect your personal data." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    :root{--blue:#4693e8;--container:min(100% - 48px,760px)}
    body{font-family:"Manrope",system-ui,sans-serif;background:#f7fafb;color:#1a1a1a;line-height:1.7}
    header{background:#0a0e14;color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    header a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;transition:.2s}
    header a:hover{color:#fff}
    .brand{font-family:"Cormorant Garamond",serif;font-size:20px;font-weight:500;color:#fff;text-decoration:none}
    .header-right{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
    .lang-switch{display:flex;gap:8px;align-items:center}
    .lang-switch button{background:transparent;border:1px solid rgba(255,255,255,.25);border-radius:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,.6);cursor:pointer;padding:4px 10px;transition:.2s;font-family:inherit;letter-spacing:.06em}
    .lang-switch button:hover{color:#fff;border-color:rgba(255,255,255,.6)}
    .lang-switch button.active{background:var(--blue);border-color:var(--blue);color:#fff}
    main{width:var(--container);margin:0 auto;padding:60px 0 80px}
    h1{font-family:"Cormorant Garamond",serif;font-size:clamp(32px,6vw,52px);font-weight:500;margin-bottom:8px}
    .updated{color:#888;font-size:14px;margin-bottom:48px}
    h2{font-family:"Cormorant Garamond",serif;font-size:24px;font-weight:500;margin:40px 0 12px;color:#111}
    p,li{font-size:15px;color:#444;margin-bottom:10px}
    ul{padding-left:22px;margin-bottom:10px}
    a{color:var(--blue);text-decoration:none}
    a:hover{text-decoration:underline}
    footer{background:#0a0e14;color:rgba(255,255,255,.5);text-align:center;padding:24px;font-size:13px}
    @media(max-width:600px){main{padding:40px 0 60px}}
  </style>
</head>
<body>

<header>
  <a href="/" class="brand">Easy Help Switzerland</a>
  <div class="header-right">
    <div class="lang-switch">
      <button type="button" data-lang="en" class="active">EN</button>
      <button type="button" data-lang="es">ES</button>
      <button type="button" data-lang="de">DE</button>
      <button type="button" data-lang="uk">UA</button>
    </div>
    <a href="/" data-i18n="privacy_back">← Back to home</a>
  </div>
</header>

<main>
  <h1 data-i18n="privacy_h1">Privacy Policy</h1>
  <p class="updated" data-i18n="privacy_updated">Last updated: April 2026</p>

  <h2 data-i18n="privacy_s1_title">1. Who we are</h2>
  <p data-i18n="privacy_s1_text">Easy Help Switzerland is a personal relocation consulting service based in Zürich, Switzerland. We help individuals and families relocate to Switzerland and navigate Swiss administrative procedures.</p>

  <h2 data-i18n="privacy_s2_title">2. What data we collect</h2>
  <p data-i18n="privacy_s2_intro">We collect personal data only when you actively provide it through our forms:</p>
  <ul>
    <li data-i18n="privacy_s2_li1" data-i18n-html><strong>Free consultation form:</strong> name, email address, phone number, current location, and message content.</li>
    <li data-i18n="privacy_s2_li2" data-i18n-html><strong>Paid booking form:</strong> name, email address, phone number, current location, preferred consultation format, and message content.</li>
    <li data-i18n="privacy_s2_li3" data-i18n-html><strong>Payment processing:</strong> payment is handled entirely by Stripe. We do not collect or store your card details. We receive confirmation of the payment amount, currency, and status from Stripe.</li>
  </ul>
  <p data-i18n="privacy_s2_note">We do not use cookies for tracking or advertising. We do not use Google Analytics or any third-party tracking tools.</p>

  <h2 data-i18n="privacy_s3_title">3. How we use your data</h2>
  <p data-i18n="privacy_s3_text">All personal information you provide is used exclusively to respond to your request and deliver the service you asked for. We handle your data in accordance with the Swiss Datenschutzgesetz (DSG).</p>
  <ul>
    <li data-i18n="privacy_s3_li1">To respond to your consultation or booking request by email or phone.</li>
    <li data-i18n="privacy_s3_li2">To send you a confirmation email after your request or payment.</li>
    <li data-i18n="privacy_s3_li3">To record your booking for our internal records.</li>
  </ul>
  <p data-i18n="privacy_s3_note">We do not sell, rent, or share your personal data with third parties for marketing purposes.</p>

  <h2 data-i18n="privacy_s4_title">4. Third-party processors</h2>
  <ul>
    <li data-i18n="privacy_s4_li1" data-i18n-html><strong>Stripe</strong> — payment processing. Your card data is processed by Stripe, Inc. and never stored on our servers. Stripe's privacy policy: <a href="https://stripe.com/privacy" target="_blank" rel="noopener">stripe.com/privacy</a></li>
    <li data-i18n="privacy_s4_li2" data-i18n-html><strong>Google Fonts</strong> — fonts are loaded from Google's servers. Google may collect IP addresses in this process.</li>
  </ul>

  <h2 data-i18n="privacy_s5_title">5. How long we keep your data</h2>
  <ul>
    <li data-i18n="privacy_s5_li1">Consultation form submissions are retained for up to 12 months.</li>
    <li data-i18n="privacy_s5_li2">Booking records are retained for up to 7 years.</li>
    <li data-i18n="privacy_s5_li3">Rate-limiting records (hashed IP addresses) are automatically purged after 15 minutes.</li>
  </ul>

  <h2 data-i18n="privacy_s6_title">6. Your rights</h2>
  <p data-i18n="privacy_s6_text1">You have the right to access, correct, or delete the personal data we hold about you. To make a request, please reach out to us via the contact form on our website. We will respond within 30 days.</p>
  <p data-i18n="privacy_s6_text2" data-i18n-html>You also have the right to contact the Swiss Federal Data Protection and Information Commissioner (FDPIC) at <a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">edoeb.admin.ch</a>.</p>

  <h2 data-i18n="privacy_s7_title">7. Data security</h2>
  <p data-i18n="privacy_s7_text">We implement appropriate technical measures to protect your data, including HTTPS encryption, server-side access controls, and CSRF protection on all forms. Booking files are stored in directories not accessible via the web.</p>

  <h2 data-i18n="privacy_s8_title">8. Changes to this policy</h2>
  <p data-i18n="privacy_s8_text">We may update this Privacy Policy from time to time. The date at the top of this page shows when it was last revised.</p>
</main>

<footer>
  <span data-i18n="privacy_footer">© 2026 Easy Help Switzerland — all rights reserved.</span>
</footer>

<script src="site.js"></script>
</body>
</html>
