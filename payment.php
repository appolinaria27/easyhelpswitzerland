<?php
require_once __DIR__ . '/security.php';
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$bookingData = $_SESSION['booking'] ?? [
    'package' => 'initial',
    'name' => '',
    'email' => '',
    'phone' => '',
    'location' => '',
    'preferred' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header('Location: booking.php?error=invalid_request');
exit;
}

    $package = trim($_POST['package'] ?? 'initial');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $preferred = trim($_POST['preferred'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $allowedPackages = ['initial', 'review', 'support'];
    $allowedPreferred = ['', 'online', 'zurich', 'phone'];

    if (!in_array($package, $allowedPackages, true)) {
        $errors[] = 'Invalid package selected.';
    }

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Please enter a valid name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (mb_strlen($phone) > 50) {
        $errors[] = 'Phone number is too long.';
    }

    if (mb_strlen($location) > 100) {
        $errors[] = 'Location is too long.';
    }

    if (!in_array($preferred, $allowedPreferred, true)) {
        $errors[] = 'Invalid consultation format selected.';
    }

    if (mb_strlen($message) > 2000) {
        $errors[] = 'Message is too long.';
    }

    // Store raw values in session - apply htmlspecialchars only when outputting to HTML
    $bookingData = [
        'package' => $package,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'location' => $location,
        'preferred' => $preferred,
        'message' => $message,
    ];

    if (empty($errors)) {
        $_SESSION['booking'] = $bookingData;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Review & Payment | Easy Help Switzerland</title>
  <meta name="description" content="Review your booking details and continue to secure payment in a premium, consistent checkout step." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#ffffff;
      --panel:#e8e8e8;
      --panel-soft:#f4f4f2;
      --text:#111111;
      --muted:#4f4f4f;
      --line:rgba(17,17,17,.08);
      --white:#ffffff;
      --blue:#4693e8;
      --blue-dark:#317bcd;
      --shadow:0 10px 28px rgba(0,0,0,.04);
      --container:min(100% - 38px, 1700px);
      --content:min(100% - 90px, 1480px);
    }

    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family:"Manrope",system-ui,sans-serif;
      -webkit-font-smoothing:antialiased;
      text-rendering:optimizeLegibility;
    }
    a{text-decoration:none;color:inherit}
    button,input,select,textarea{font:inherit}

    .shell{width:var(--container);margin:20px auto 48px}

    .hero-wrap{
      position:relative;
      min-height:620px;
      border-radius:44px;
      overflow:hidden;
      color:#fff;
      padding:26px 36px 34px;
      background:
        linear-gradient(180deg, rgba(9,12,15,.48), rgba(9,12,15,.66)),
        radial-gradient(circle at 50% 40%, rgba(255,255,255,.03), transparent 35%),
        url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
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
    
    .hero-right{
  display:flex;
  align-items:center;
  gap:26px;
  white-space:nowrap;
  font-size:18px;
  flex-wrap:wrap;
}

.lang-switch{
  display:flex;
  gap:8px;
  font-size:16px;
}

.lang-switch button{
  border:0;
  background:transparent;
  color:rgba(255,255,255,.7);
  cursor:pointer;
  padding:0;
}

.lang-switch button.active{
  color:var(--blue);
}

.back-link{
  color:rgba(255,255,255,.9);
  text-decoration:none;
}

    .hero-center{
      position:absolute;
      inset:150px 90px 150px;
      display:grid;
      place-items:center;
      text-align:center;
      z-index:1;
    }
    .micro{
      font-size:12px;
      letter-spacing:.16em;
      text-transform:uppercase;
      color:rgba(255,255,255,.72);
      display:inline-flex;
      align-items:center;
      gap:8px;
      margin-bottom:20px;
    }
    .hero-title{
      font-family:"Cormorant Garamond",serif;
      font-weight:500;
      font-size:clamp(76px,10vw,180px);
      line-height:.82;
      letter-spacing:-.06em;
      margin:0;
    }
    .hero-title span{display:block;font-size:clamp(46px,4.5vw,96px);margin-top:10px}
    .hero-sub{margin-top:20px;font-size:18px;color:rgba(255,255,255,.86);max-width:62ch}

    .hero-bottom{
      position:absolute;
      left:36px;right:36px;bottom:28px;
      display:flex;justify-content:space-between;align-items:flex-end;gap:20px;z-index:2;
    }
    .hero-features{display:flex;gap:38px;flex-wrap:wrap;color:rgba(255,255,255,.95)}
    .hero-feature{display:flex;gap:12px;font-size:18px;line-height:1.1;max-width:220px}
    .hero-feature .dot{width:15px;height:15px;border-radius:50%;background:#fff;color:#111;display:grid;place-items:center;font-size:10px;flex:0 0 auto;margin-top:2px}
    .hero-years{display:flex;align-items:flex-start;gap:12px;color:#fff;text-align:left}
    .hero-years strong{font-family:"Cormorant Garamond",serif;font-size:86px;line-height:.8;font-weight:500}
    .hero-years span{max-width:145px;font-size:18px;line-height:1.05;color:rgba(255,255,255,.9);padding-top:8px}

    .payment-zone{padding:68px 0 0;background:#fff}
    .payment-layout{
      width:var(--content);
      margin:0 auto;
      display:grid;
      grid-template-columns:minmax(0, 1.05fr) 400px;
      gap:28px;
      align-items:start;
    }

    .glass-card{
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.22);
      border-radius:28px;
      box-shadow:0 10px 30px rgba(0,0,0,.08), inset 0 1px 0 rgba(255,255,255,.22);
      backdrop-filter:blur(18px);
      -webkit-backdrop-filter:blur(18px);
    }

    .summary-card,
    .payment-card,
    .info-card{
      background:
        radial-gradient(circle at 20% 20%, rgba(193,232,241,.55), transparent 35%),
        radial-gradient(circle at 80% 70%, rgba(70,147,232,.18), transparent 30%),
        linear-gradient(180deg, #f7fafb 0%, #eef5f7 100%);
    }

    .summary-card{padding:38px}
    .section-label{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin:0 0 12px}
    .title{margin:0 0 14px;font-weight:300;font-size:clamp(42px,4.5vw,72px);line-height:.95;letter-spacing:-.06em}
    .subtitle{margin:0;color:var(--muted);line-height:1.55;max-width:60ch}

    .summary-box{
      margin-top:28px;
      display:grid;
      grid-template-columns:1fr auto;
      gap:18px;
      align-items:end;
      padding:26px 28px;
      border-radius:28px;
      background:rgba(255,255,255,.72);
      border:1px solid rgba(255,255,255,.34);
      box-shadow:inset 0 1px 0 rgba(255,255,255,.35);
    }
    .package-name{font-size:32px;line-height:1.02;letter-spacing:-.05em;font-weight:500}
    .price{font-size:42px;line-height:.95;letter-spacing:-.06em;font-weight:300;white-space:nowrap}

    .details{display:grid;gap:16px;margin-top:24px}
    .detail{
      display:grid;
      grid-template-columns:190px 1fr;
      gap:18px;
      align-items:start;
      padding:16px 0;
      border-bottom:1px solid rgba(17,17,17,.08);
    }
    .detail:last-child{border-bottom:none}
    .label{display:block;font-size:13px;text-transform:uppercase;letter-spacing:.12em;color:#7a8a99}
    .value{font-size:17px;color:#111;line-height:1.55;word-break:break-word}

    .total{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:20px 0;
      border-top:1px solid rgba(17,17,17,.08);
      border-bottom:1px solid rgba(17,17,17,.08);
      margin:24px 0 0;
      gap:20px;
    }
    .total span:first-child{font-size:15px;color:var(--muted)}
    .total strong{font-size:32px;line-height:.95;letter-spacing:-.06em;font-weight:400}

    .sidebar{position:sticky;top:24px;display:grid;gap:22px}
    .payment-card,.info-card{padding:28px}
    .payment-card h2,.info-card h3{margin:0 0 12px;font-size:30px;line-height:1.02;letter-spacing:-.05em;font-weight:500}
    .payment-card p,.info-card p{margin:0;color:#303030;line-height:1.5}

    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:100%;
      min-height:58px;
      padding:16px 24px;
      border:none;
      border-radius:999px;
      cursor:pointer;
      font-size:16px;
      line-height:1;
      transition:transform .2s ease, box-shadow .2s ease, background .25s ease;
      margin-top:16px;
    }
    .btn:hover{transform:translateY(-1px)}
    .btn.primary{
      background:linear-gradient(135deg,#4693e8 0%,#6fb3f2 100%);
      color:#fff;
      box-shadow:0 8px 20px rgba(70,147,232,.22), inset 0 1px 0 rgba(255,255,255,.22);
    }
    .btn.secondary{background:#fff;color:#111;border:1px solid rgba(17,17,17,.08)}

    .small{margin-top:16px;font-size:13px;color:#5a5a5a;line-height:1.7}
    .step-list{display:grid;gap:14px;margin-top:18px}
    .step{padding:14px 0;border-bottom:1px solid rgba(17,17,17,.08)}
    .step:last-child{border-bottom:none;padding-bottom:0}
    .step span{display:block;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin-bottom:6px}
    .step strong{font-size:17px;line-height:1.35}

    .contact-footer{
      width:100%;
      margin-top:28px;
      padding:28px 32px;
      background:#02070d;
      color:#ffffff;
      border-top:1px solid rgba(255,255,255,.08);
      display:grid;
      grid-template-columns:1fr 1fr 1fr;
      gap:20px;
      align-items:center;
      font-size:14px;
      border-radius:0 0 38px 38px;
    }
    .contact-footer .footer-brand{display:flex;align-items:center;gap:12px;font-size:18px;color:#ffffff;justify-self:start}
    .contact-footer .footer-brand svg{width:26px;height:40px;stroke:#ffffff;fill:none;stroke-width:1.3}
    .contact-footer .footer-center{text-align:center;color:rgba(255,255,255,.72);justify-self:center}
    .contact-footer .footer-right{text-align:right;color:rgba(255,255,255,.72);justify-self:end}

    .whatsapp-float{
      position:fixed;
      left:24px;
      bottom:24px;
      width:58px;
      height:58px;
      border-radius:50%;
      background:#25D366;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      text-decoration:none;
      box-shadow:0 10px 24px rgba(0,0,0,.22);
      z-index:999;
    }

    @media (max-width:1180px){
      .hero-top{grid-template-columns:1fr;justify-items:start}
      .nav{justify-content:flex-start;gap:18px}
      .hero-center{position:relative;inset:auto;display:block;text-align:left;padding-top:140px}
      .hero-bottom{position:relative;left:auto;right:auto;bottom:auto;margin-top:90px;flex-direction:column;align-items:flex-start}
      .hero-wrap{min-height:auto;padding-bottom:34px}
      .payment-layout{grid-template-columns:1fr}
      .sidebar{position:relative;top:0}
    }

    @media (max-width:780px){
      :root{--container:min(100% - 18px, 1700px);--content:min(100% - 20px, 1480px)}
      .hero-wrap{padding:18px 18px 22px;border-radius:28px}
      .summary-card,.payment-card,.info-card{padding:24px 20px}
      .summary-box,.detail,.contact-footer{grid-template-columns:1fr}
      .price{white-space:normal}
      .contact-footer{text-align:left;border-radius:0 0 28px 28px}
      .contact-footer .footer-center,.contact-footer .footer-right{text-align:left;justify-self:start}
    }
  </style>
</head>
<body>

<?php if (!empty($_GET['error'])): ?>
  <div style="margin:20px;padding:15px;border:1px solid #e0b4b4;background:#fff6f6;border-radius:10px;color:#9f3a38;">
    Something went wrong. Please try again.
  </div>
<?php endif; ?>

  <div class="shell">

  <?php if (!empty($errors)): ?>
  <div style="width:min(100% - 38px, 1700px);margin:20px auto 0;padding:16px 20px;border-radius:18px;background:#fff4f4;border:1px solid #f1c0c0;color:#8a1f1f;">
    <strong>Please fix the following:</strong>
    <ul style="margin:10px 0 0 18px;padding:0;">
      <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

    <section class="hero-wrap" id="top">
      <div class="hero-top">
        <a class="brand" href="index.html" aria-label="Easy Help Switzerland home">
          <svg viewBox="0 0 32 48" aria-hidden="true">
            <path d="M4 44V10l10-8 10 8v34"></path>
            <path d="M14 44V22l10-8v30"></path>
          </svg>
          <div>
            <div style="font-size:18px">Easy Help</div>
            <div style="font-size:12px;color:rgba(255,255,255,.65);letter-spacing:.12em;text-transform:uppercase">Switzerland</div>
          </div>
        </a>

        <nav class="nav">
          <a href="index.html" data-i18n="payment_home">Home</a>
          <a href="booking.php" data-i18n="payment_booking_nav">Booking</a>
          <a href="blog.html" data-i18n="payment_guides">Guides</a>
          <a href="free-consultation.php" data-i18n="payment_free_consultation">Free consultation</a>
          <a href="index.html#contact" data-i18n="payment_contacts">Contacts</a>
        </nav>

        <div class="hero-right">
  <a href="tel:+41764497581">+41 76 449 75 81</a>

  <div class="lang-switch">
    <button type="button" data-lang="en" class="active">EN</button>
    <button type="button" data-lang="es">ES</button>
    <button type="button" data-lang="de">DE</button>
    <button type="button" data-lang="uk">UA</button>
  </div>

  <a class="back-link" href="booking.php" data-i18n="payment_back">← Back</a>
</div>
      </div>

      <div class="hero-center">
        <div>
          <div class="micro" data-i18n="payment_micro">Payment review</div>
          <h1 class="hero-title" data-i18n="payment_hero_title">Review your <span data-i18n="payment_hero_title_span">consultation</span></h1>
          <div class="hero-sub" data-i18n="payment_hero_sub">Confirm your selected package and your details before continuing to secure payment.</div>
        </div>
      </div>

      <div class="hero-bottom">
        <div class="hero-features">
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="payment_feature_summary">Booking summary</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="payment_feature_stripe">Secure Stripe handoff</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="payment_feature_edit">Edit before payment</div></div>
        </div>

        <div class="hero-years">
          <strong>1</strong>
          <span data-i18n="payment_final_step">final step before checkout</span>
        </div>
      </div>
    </section>

    <section class="payment-zone">
      <div class="payment-layout">
        <section class="summary-card glass-card">
          <p class="section-label" data-i18n="payment_summary_kicker">Booking summary</p>
          <h1 class="title" data-i18n="payment_title">Review your consultation before payment</h1>
          <p class="subtitle" data-i18n="payment_subtitle">Please confirm your selected package and your details below.</p>

          <div class="summary-box">
            <div id="packageName" class="package-name">—</div>
            <div id="packagePrice" class="price">—</div>
          </div>

          <div class="details">
            <div class="detail">
              <span class="label" data-i18n="payment_name">Full name</span>
              <div id="customerName" class="value">—</div>
            </div>
            <div class="detail">
              <span class="label" data-i18n="payment_email">Email</span>
              <div id="customerEmail" class="value">—</div>
            </div>
            <div class="detail">
              <span class="label" data-i18n="payment_phone">Phone / WhatsApp</span>
              <div id="customerPhone" class="value">—</div>
            </div>
            <div class="detail">
              <span class="label" data-i18n="payment_location">Current location</span>
              <div id="customerLocation" class="value">—</div>
            </div>
            <div class="detail">
              <span class="label" data-i18n="payment_format">Preferred format</span>
              <div id="customerPreferred" class="value">—</div>
            </div>
            <div class="detail">
              <span class="label" data-i18n="payment_message">Short description</span>
              <div id="customerMessage" class="value">—</div>
            </div>
          </div>

          <div class="total">
            <span data-i18n="payment_total">Total due</span>
            <strong id="summaryTotalPrice">—</strong>
          </div>
        </section>

        <aside class="sidebar">
          <section class="payment-card glass-card">
            <p class="section-label" data-i18n="payment_kicker">Payment</p>
            <h2 data-i18n="payment_right_title">Continue to secure payment</h2>
            <p data-i18n="payment_right_text">When you click the button below, you can continue with your payment setup.</p>

            <div class="total">
              <span data-i18n="payment_total">Total due</span>
              <strong id="paymentTotalPrice">—</strong>
            </div>

            <form id="checkoutForm" action="create-checkout-session.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="package" id="checkoutPackage">
  <input type="hidden" name="name" id="checkoutName">
  <input type="hidden" name="email" id="checkoutEmail">
  <input type="hidden" name="phone" id="checkoutPhone">
  <input type="hidden" name="location" id="checkoutLocation">
  <input type="hidden" name="preferred" id="checkoutPreferred">
  <input type="hidden" name="message" id="checkoutMessage">

  <button type="submit" class="btn primary" data-i18n="payment_pay_now">
    Pay now
  </button>
</form>
            <a class="btn secondary" href="booking.php" data-i18n="payment_edit">Edit booking</a>

            <p class="small" data-i18n="payment_note">Payment confirms your consultation request. If your matter requires formal legal representation, you may be referred to a licensed lawyer where appropriate.</p>
          </section>

          <section class="info-card glass-card">
            <p class="section-label" data-i18n="payment_attention">Pay attention</p>
            <h3 data-i18n="payment_no_free_consultation">Booked without free consultation</h3>
            <p data-i18n="payment_attention_text">We recommend to book a free consultation. If you book a package and turns out, that your case is hopeless, we will charge 10% from the package price and give you back the rest.</p>
            <div class="step-list">
              <div class="step"><span data-i18n="payment_package_pricing">Package pricing</span><strong data-i18n="package_initial">Quick consultation — CHF 79</strong></div>
              <div class="step"><span data-i18n="payment_package_pricing">Package pricing</span><strong data-i18n="package_review">Relocation support — CHF 189</strong></div>
              <div class="step"><span data-i18n="payment_package_pricing">Package pricing</span><strong data-i18n="package_relocation">Settlement strategy — CHF 349</strong></div>
            </div>
          </section>
        </aside>
      </div>
    </section>

    <footer class="contact-footer">
      <div class="footer-brand">
        <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
        <span>Easy Help Switzerland</span>
      </div>
      <div class="footer-center" data-i18n="payment_footer_rights">© 2026 Easy Help Switzerland - all rights reserved.</div>
      <div class="footer-right" data-i18n="payment_footer_page">Payment page</div>
    </footer>
  </div>

  <a class="whatsapp-float" href="https://wa.me/41764497581" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff"><path d="M20 12a8 8 0 1 0-14.5 4.7L4 21l4.5-1.4A8 8 0 1 0 20 12z"/><path d="M9.5 9.5c.3-.6.6-.6.9-.6h.7c.2 0 .4 0 .6.5.2.5.7 1.7.8 1.8.1.1.1.3 0 .5s-.2.3-.3.4c-.1.1-.3.3-.4.4-.1.1-.2.3 0 .6.2.3.8 1.3 1.8 2 .3.3.6.4.8.2.2-.2.4-.5.5-.7.1-.2.3-.2.5-.1.2.1 1.4.7 1.6.8.2.1.3.2.3.3 0 .1 0 .7-.4 1.3-.4.6-1.1 1.2-1.6 1.3-.4.1-1 .2-2.6-.5-2-.9-3.4-3.2-3.5-3.3-.1-.1-.8-1.1-.8-2.1 0-1 .5-1.5.7-1.7z" fill="#fff"/></svg>
  </a>

  <script>
document.addEventListener("DOMContentLoaded", () => {

  const bookingData = <?php echo json_encode($bookingData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  const packages = {
    initial: { name: "Quick consultation", price: "CHF 79" },
    review: { name: "Relocation support", price: "CHF 189" },
    support: { name: "Settlement strategy", price: "CHF 349" }
  };

  const selectedPackage = packages[bookingData.package] || packages.initial;

  const setText = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value && String(value).trim() ? value : "—";
  };

  const setHiddenValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.value = value || "";
  };

  setText("packageName", selectedPackage.name);
  setText("packagePrice", selectedPackage.price);
  setText("summaryTotalPrice", selectedPackage.price);
  setText("paymentTotalPrice", selectedPackage.price);

  setText("customerName", bookingData.name);
  setText("customerEmail", bookingData.email);
  setText("customerPhone", bookingData.phone);
  setText("customerLocation", bookingData.location);
  setText("customerPreferred", bookingData.preferred);
  setText("customerMessage", bookingData.message);

  setHiddenValue("checkoutPackage", bookingData.package);
  setHiddenValue("checkoutName", bookingData.name);
  setHiddenValue("checkoutEmail", bookingData.email);
  setHiddenValue("checkoutPhone", bookingData.phone);
  setHiddenValue("checkoutLocation", bookingData.location);
  setHiddenValue("checkoutPreferred", bookingData.preferred);
  setHiddenValue("checkoutMessage", bookingData.message);

});
</script>

  <script src="site.js"></script>
</body>
</html>