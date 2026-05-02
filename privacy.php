<?php
require_once __DIR__ . '/security.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <title>Privacy Policy — Easy Help Switzerland</title>
  <meta name="description" content="Privacy Policy for Easy Help Switzerland — how we collect, use, and protect your personal data." />
  <link rel="canonical" href="https://easyhelpswitzerland.ch/privacy.php" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#ffffff;
      --text:#111111;
      --muted:#4f4f4f;
      --line:rgba(17,17,17,.08);
      --blue:#4693e8;
      --container:min(100% - 38px,1700px);
      --content:min(100% - 90px,960px);
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{margin:0;background:var(--bg);color:var(--text);font-family:"Manrope",system-ui,sans-serif;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
    a{text-decoration:none;color:inherit}
    button,input{font:inherit}

    .shell{width:var(--container);margin:20px auto 48px}

    .hero-wrap{
      position:relative;
      min-height:560px;
      border-radius:44px;
      overflow:hidden;
      color:#fff;
      padding:26px 36px 34px;
      background:
        linear-gradient(180deg,rgba(9,12,15,.52),rgba(9,12,15,.72)),
        url('https://images.unsplash.com/photo-1586769852836-bc069f19e1b6?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
    }
    .hero-top{
      display:grid;
      grid-template-columns:220px 1fr auto;
      align-items:center;
      gap:24px;
      font-size:15px;
      position:relative;
      z-index:2;
    }
    .brand{display:flex;align-items:center;gap:14px;font-weight:500}
    .brand svg{width:36px;height:52px;stroke:#fff;fill:none;stroke-width:1.5;opacity:.92}
    .nav{display:flex;justify-content:center;gap:40px;color:rgba(255,255,255,.9);flex-wrap:wrap}
    .nav a{font-weight:400;position:relative}
    .nav a::after{content:"";position:absolute;left:0;right:0;bottom:-6px;height:1px;background:rgba(255,255,255,.8);transform:scaleX(0);transition:.25s ease}
    .nav a:hover::after{transform:scaleX(1)}
    .hero-right{display:flex;align-items:center;gap:18px;white-space:nowrap;font-size:16px;flex-wrap:wrap}
    .lang-switch{display:flex;gap:10px;align-items:center}
    .lang-switch button{background:transparent;border:none;font-size:14px;font-weight:500;color:rgba(255,255,255,.65);cursor:pointer;padding:0;transition:.2s ease}
    .lang-switch button:hover{color:#fff}
    .lang-switch button.active{color:var(--blue)}
    .nav-lang-mobile{display:none}

    .hero-center{
      position:absolute;
      inset:140px 90px 120px;
      display:grid;
      place-items:center;
      text-align:center;
      z-index:1;
    }
    .micro{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.72);display:inline-flex;align-items:center;gap:8px;margin-bottom:20px}
    .hero-title{font-family:"Cormorant Garamond",serif;font-weight:500;font-size:clamp(64px,8vw,130px);line-height:.85;letter-spacing:-.06em;margin:0}
    .hero-title span{display:block;font-size:clamp(40px,4.5vw,82px);margin-top:8px}
    .hero-sub{margin-top:22px;font-size:17px;color:rgba(255,255,255,.84);max-width:56ch}

    .hero-bottom{
      position:absolute;
      left:36px;right:36px;bottom:28px;
      display:flex;justify-content:space-between;align-items:flex-end;gap:20px;z-index:2;
    }
    .hero-features{display:flex;gap:38px;flex-wrap:wrap;color:rgba(255,255,255,.95)}
    .hero-feature{display:flex;gap:12px;font-size:17px;line-height:1.1;max-width:240px}
    .hero-feature .dot{width:15px;height:15px;border-radius:50%;background:#fff;color:#111;display:grid;place-items:center;font-size:10px;flex:0 0 auto;margin-top:2px}

    .content-zone{padding:56px 0 0;background:#fff}
    .content-wrap{width:var(--content);margin:0 auto;display:grid;gap:24px}

    .glass-card{
      background:radial-gradient(circle at 20% 20%,rgba(193,232,241,.55),transparent 35%),
        radial-gradient(circle at 80% 70%,rgba(70,147,232,.18),transparent 30%),
        linear-gradient(180deg,#f7fafb 0%,#eef5f7 100%);
      border:1px solid rgba(255,255,255,.22);
      border-radius:28px;
      box-shadow:0 10px 30px rgba(0,0,0,.08),inset 0 1px 0 rgba(255,255,255,.22);
      backdrop-filter:blur(18px);
      -webkit-backdrop-filter:blur(18px);
    }
    .section-label{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin:0 0 10px}
    .card-inner{padding:38px 44px}
    .updated-note{font-size:13px;color:#999;margin-bottom:24px}

    .sections-grid{display:grid;gap:0}
    .policy-section{padding:28px 0;border-bottom:1px solid rgba(17,17,17,.07)}
    .policy-section:first-child{padding-top:0}
    .policy-section:last-child{border-bottom:none;padding-bottom:0}
    .policy-section h2{font-family:"Cormorant Garamond",serif;font-size:22px;font-weight:500;margin:0 0 10px;color:#111}
    .policy-section p,.policy-section li{font-size:15px;color:#444;line-height:1.65;margin:0 0 8px}
    .policy-section p:last-child{margin-bottom:0}
    .policy-section ul{padding-left:20px;margin:10px 0 0;display:grid;gap:8px}
    .policy-section a{color:var(--blue)}
    .policy-section a:hover{text-decoration:underline}

    .contact-footer{
      width:100%;margin-top:28px;padding:28px 32px;
      background:#02070d;color:#fff;
      border-top:1px solid rgba(255,255,255,.08);
      display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;
      align-items:center;font-size:14px;
      border-radius:0 0 38px 38px;
    }
    .contact-footer .footer-brand{display:flex;align-items:center;gap:12px;font-size:18px;color:#fff;justify-self:start}
    .contact-footer .footer-brand svg{width:26px;height:40px;stroke:#fff;fill:none;stroke-width:1.3}
    .contact-footer .footer-center{text-align:center;color:rgba(255,255,255,.72);justify-self:center}
    .contact-footer .footer-right{text-align:right;color:rgba(255,255,255,.72);justify-self:end}

    .whatsapp-float{position:fixed;left:18px;bottom:18px;z-index:999;display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:999px;background:#25D366;color:#fff;text-decoration:none;box-shadow:0 14px 32px rgba(0,0,0,.18);transition:transform .2s,box-shadow .2s}
    .whatsapp-float:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(37,211,102,.4)}
    .wa-icon-wrap{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0}

    .burger{display:none;flex-direction:column;justify-content:center;gap:5px;padding:8px;background:transparent;border:0;cursor:pointer;flex-shrink:0;z-index:10}
    .burger span{display:block;width:22px;height:2px;background:rgba(255,255,255,.85);border-radius:2px;transition:transform .25s,opacity .25s}
    .burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
    .burger.open span:nth-child(2){opacity:0;transform:scaleX(0)}
    .burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

    @media(max-width:1180px){
      .hero-top{grid-template-columns:1fr;justify-items:start}
      .hero-center{position:relative;inset:auto;display:block;text-align:left;padding-top:120px}
      .hero-bottom{position:relative;left:auto;right:auto;bottom:auto;margin-top:60px;flex-direction:column;align-items:flex-start}
      .hero-wrap{min-height:auto;padding-bottom:34px}
    }
    @media(max-width:780px){
      :root{--container:min(100% - 18px,1700px);--content:min(100% - 20px,960px)}
      .hero-wrap{padding:18px 18px 22px;border-radius:28px}
      .card-inner{padding:24px 22px}
      .contact-footer{grid-template-columns:1fr;border-radius:0 0 28px 28px}
      .contact-footer .footer-center,.contact-footer .footer-right{text-align:left;justify-self:start}
    }
    @media(max-width:768px){
      .burger{display:flex}
      .hero-top{display:flex !important;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0;position:relative}
      .nav{display:none;flex-basis:100%;flex-direction:column;gap:0;padding:4px 0 8px;margin-top:6px}
      .nav.open{display:flex}
      .nav a{padding:13px 4px;font-size:17px;font-weight:500;border-bottom:1px solid rgba(255,255,255,.15)}
      .nav-lang-mobile{display:flex;gap:8px;padding:14px 4px 2px;align-items:center}
      .nav-lang-mobile button{background:transparent;border:1px solid rgba(255,255,255,.30);border-radius:8px;color:rgba(255,255,255,.65);font-size:12px;font-weight:600;letter-spacing:.06em;padding:5px 12px;cursor:pointer;transition:all .2s;font-family:inherit}
      .nav-lang-mobile button.active{background:var(--blue);border-color:var(--blue);color:#fff}
      .hero-right{display:none}
      .hero-center{padding-top:60px}
      .hero-title{font-size:clamp(44px,11vw,76px) !important}
      .hero-title span{font-size:clamp(28px,7vw,48px) !important}
    }
    @media(max-width:480px){
      .whatsapp-float span:last-child{display:none}
      .whatsapp-float{padding:14px;border-radius:50%;gap:0;width:52px;height:52px;justify-content:center}
    }
  </style>
</head>
<body>

<div class="shell">
  <section class="hero-wrap">
    <div class="hero-top">
      <a class="brand" href="index.html" aria-label="Easy Help Switzerland home">
        <svg viewBox="0 0 32 48" aria-hidden="true">
          <path d="M4 44V10l10-8 10 8v34"></path>
          <path d="M14 44V22l10-8v30"></path>
        </svg>
        <div>
          <div style="font-size:18px" data-i18n="brand_main">Easy Help</div>
          <div style="font-size:12px;color:rgba(255,255,255,.65);letter-spacing:.12em;text-transform:uppercase" data-i18n="brand_sub">Switzerland</div>
        </div>
      </a>
      <button class="burger" id="burgerBtn" type="button" aria-label="Open menu"><span></span><span></span><span></span></button>
      <nav class="nav" id="mainNav">
        <a href="index.html" data-i18n="nav_home">Home</a>
        <a href="index.html#services" data-i18n="nav_objects">Services</a>
        <a href="blog.html" data-i18n="blog_nav_guides">Guides</a>
        <a href="free-consultation.php" data-i18n="booking_nav_free_consultation">Free consultation</a>
        <a href="index.html#contact" data-i18n="nav_contact">Contacts</a>
        <div class="nav-lang-mobile">
          <button type="button" data-lang="en" class="active">EN</button>
          <button type="button" data-lang="es">ES</button>
          <button type="button" data-lang="de">DE</button>
          <button type="button" data-lang="uk">UA</button>
        </div>
      </nav>
      <div class="hero-right">
        <div class="lang-switch">
          <button type="button" data-lang="en" class="active">EN</button>
          <button type="button" data-lang="es">ES</button>
          <button type="button" data-lang="de">DE</button>
          <button type="button" data-lang="uk">UA</button>
        </div>
      </div>
    </div>

    <div class="hero-center">
      <div>
        <div class="micro" data-i18n="privacy_micro">Privacy</div>
        <h1 class="hero-title" data-i18n-html data-i18n="privacy_hero_title">Privacy <span>Policy</span></h1>
        <div class="hero-sub" data-i18n="privacy_hero_sub">How we collect, use, and protect your personal data.</div>
      </div>
    </div>

    <div class="hero-bottom">
      <div class="hero-features">
        <div class="hero-feature"><div class="dot">✓</div><div data-i18n="privacy_feature_1">No tracking or ad cookies</div></div>
        <div class="hero-feature"><div class="dot">✓</div><div data-i18n="privacy_feature_2">Stripe handles all payments</div></div>
        <div class="hero-feature"><div class="dot">✓</div><div data-i18n="privacy_feature_3">Swiss DSG compliant</div></div>
      </div>
    </div>
  </section>

  <section class="content-zone">
    <div class="content-wrap">
      <div class="glass-card card-inner">
        <p class="section-label" data-i18n="privacy_label">Privacy Policy</p>
        <p class="updated-note" data-i18n="privacy_updated">Last updated: April 2026</p>

        <div class="sections-grid">
          <div class="policy-section">
            <h2 data-i18n="privacy_s1_title">1. Who we are</h2>
            <p data-i18n="privacy_s1_text">Easy Help Switzerland is a personal relocation consulting service based in Zürich, Switzerland. We help individuals and families relocate to Switzerland and navigate Swiss administrative procedures.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s2_title">2. What data we collect</h2>
            <p data-i18n="privacy_s2_intro">We collect personal data only when you actively provide it through our forms:</p>
            <ul>
              <li data-i18n="privacy_s2_li1" data-i18n-html><strong>Free consultation form:</strong> name, email address, phone number, current location, and message content.</li>
              <li data-i18n="privacy_s2_li2" data-i18n-html><strong>Paid booking form:</strong> name, email address, phone number, current location, preferred consultation format, and message content.</li>
              <li data-i18n="privacy_s2_li3" data-i18n-html><strong>Payment processing:</strong> payment is handled entirely by Stripe. We do not collect or store your card details. We receive confirmation of the payment amount, currency, and status from Stripe.</li>
            </ul>
            <p data-i18n="privacy_s2_note" style="margin-top:10px">We do not use cookies for tracking or advertising. We do not use Google Analytics or any third-party tracking tools.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s3_title">3. How we use your data</h2>
            <p data-i18n="privacy_s3_text">All personal information you provide is used exclusively to respond to your request and deliver the service you asked for. We handle your data in accordance with the Swiss Datenschutzgesetz (DSG).</p>
            <ul>
              <li data-i18n="privacy_s3_li1">To respond to your consultation or booking request by email or phone.</li>
              <li data-i18n="privacy_s3_li2">To send you a confirmation email after your request or payment.</li>
              <li data-i18n="privacy_s3_li3">To record your booking for our internal records.</li>
            </ul>
            <p data-i18n="privacy_s3_note" style="margin-top:10px">We do not sell, rent, or share your personal data with third parties for marketing purposes.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s4_title">4. Third-party processors</h2>
            <ul>
              <li data-i18n="privacy_s4_li1" data-i18n-html><strong>Stripe</strong> — payment processing. Your card data is processed by Stripe, Inc. and never stored on our servers. Stripe's privacy policy: <a href="https://stripe.com/privacy" target="_blank" rel="noopener">stripe.com/privacy</a></li>
              <li data-i18n="privacy_s4_li2" data-i18n-html><strong>Google Fonts</strong> — fonts are loaded from Google's servers. Google may collect IP addresses in this process.</li>
            </ul>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s5_title">5. How long we keep your data</h2>
            <ul>
              <li data-i18n="privacy_s5_li1">Consultation form submissions are retained for up to 12 months.</li>
              <li data-i18n="privacy_s5_li2">Booking records are retained for up to 7 years.</li>
              <li data-i18n="privacy_s5_li3">Rate-limiting records (hashed IP addresses) are automatically purged after 15 minutes.</li>
            </ul>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s6_title">6. Your rights</h2>
            <p data-i18n="privacy_s6_text1">You have the right to access, correct, or delete the personal data we hold about you. To make a request, please reach out to us via the contact form on our website. We will respond within 30 days.</p>
            <p data-i18n="privacy_s6_text2" data-i18n-html>You also have the right to contact the Swiss Federal Data Protection and Information Commissioner (FDPIC) at <a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">edoeb.admin.ch</a>.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s7_title">7. Data security</h2>
            <p data-i18n="privacy_s7_text">We implement appropriate technical measures to protect your data, including HTTPS encryption, server-side access controls, and CSRF protection on all forms. Booking files are stored in directories not accessible via the web.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s8_title">8. Changes to this policy</h2>
            <p data-i18n="privacy_s8_text">We may update this Privacy Policy from time to time. The date at the top of this page shows when it was last revised.</p>
          </div>

          <div class="policy-section">
            <h2 data-i18n="privacy_s9_title">9. Cookies and local storage</h2>
            <p data-i18n="privacy_s9_text">We do not use tracking or advertising cookies. Our website uses browser local storage for two purposes only: to remember your language preference (so the site loads in the correct language on your next visit), and to store your cookie consent choice. No data stored locally is ever sent to third parties. You can clear this data at any time by clearing your browser's site data for this domain.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="contact-footer">
    <div class="footer-brand">
      <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
      <span data-i18n="footer_brand">Easy Help Switzerland</span>
    </div>
    <div class="footer-center">
      <span data-i18n="footer_copy">© 2026 Easy Help Switzerland — all rights reserved.</span><br>
      <a href="terms.php" style="color:rgba(255,255,255,.45);font-size:12px;text-decoration:none" data-i18n="footer_terms">Terms of Service</a>
      &nbsp;·&nbsp;
      <a href="refund-policy.php" style="color:rgba(255,255,255,.45);font-size:12px;text-decoration:none" data-i18n="footer_refund">Refund Policy</a>
    </div>
    <div class="footer-right" style="color:rgba(255,255,255,.35);font-size:12px">
      <a href="admin.php" style="color:rgba(255,255,255,.2);text-decoration:none;font-size:11px">Admin</a>
    </div>
  </footer>
</div>

<a class="whatsapp-float" href="https://wa.me/41764497581" target="_blank" rel="noopener" aria-label="WhatsApp">
  <span class="wa-icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>
  <span><strong style="display:block;font-size:14px" data-i18n="wa_label">WhatsApp</strong><small style="opacity:.75;font-size:12px" data-i18n="wa_write">Write to us</small></span>
</a>

<script src="site.js"></script>
<script>
  (() => {
    const burger = document.getElementById('burgerBtn');
    const nav = document.getElementById('mainNav');
    if (!burger || !nav) return;
    burger.addEventListener('click', () => { burger.classList.toggle('open'); nav.classList.toggle('open'); });
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { burger.classList.remove('open'); nav.classList.remove('open'); }));
  })();
</script>
</body>
</html>
