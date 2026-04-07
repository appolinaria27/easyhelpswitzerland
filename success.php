<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$sessionId = $_GET['session_id'] ?? '';

if (!$sessionId) {
  header('Location: booking.php?error=invalid_session');
  exit;
}

try {
  $session = \Stripe\Checkout\Session::retrieve($sessionId);

  $bookingData = [
    'stripe_session_id' => $session->id,
    'payment_status' => $session->payment_status,
    'package' => $session->metadata->package ?? '',
    'package_name' => $session->metadata->package_name ?? '',
    'price_chf' => $session->metadata->price_chf ?? '',
    'name' => $session->metadata->name ?? '',
    'email' => $session->metadata->email ?? '',
    'phone' => $session->metadata->phone ?? '',
    'location' => $session->metadata->location ?? '',
    'preferred' => $session->metadata->preferred ?? '',
    'message' => $session->metadata->message ?? '',
    'created_at' => date('Y-m-d H:i:s'),
  ];

  $emailSent = false;
  $emailError = '';

  if ($session->payment_status === 'paid') {
  if (!is_dir('bookings')) {
    mkdir('bookings', 0777, true);
  }

  $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $session->id);
  $filename = 'bookings/booking-' . $safeSessionId . '.json';
  $alreadyProcessed = file_exists($filename);

  if (!$alreadyProcessed) {
    file_put_contents(
      $filename,
      json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->Host = $_ENV['SMTP_HOST'];
      $mail->SMTPAuth = true;
      $mail->Username = $_ENV['SMTP_USER'];
      $mail->Password = $_ENV['SMTP_PASS'];
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = (int) $_ENV['SMTP_PORT'];
      $mail->CharSet = 'UTF-8';

      $mail->setFrom($_ENV['MAIL_FROM'], 'Polina Kravtsova Legal Advisory');
      $mail->addAddress($_ENV['ADMIN_EMAIL']);

      if (!empty($bookingData['email'])) {
        $mail->addReplyTo($bookingData['email'], $bookingData['name'] ?: 'Client');
      }

      $mail->Subject = 'New Paid Booking Received';
      $mail->Body =
        "A new paid consultation booking has been received.\n\n" .
        "Package: {$bookingData['package_name']}\n" .
        "Price: CHF {$bookingData['price_chf']}\n" .
        "Name: {$bookingData['name']}\n" .
        "Email: {$bookingData['email']}\n" .
        "Phone / WhatsApp: {$bookingData['phone']}\n" .
        "Current location: {$bookingData['location']}\n" .
        "Preferred consultation format: {$bookingData['preferred']}\n\n" .
        "Message:\n{$bookingData['message']}\n\n" .
        "Stripe session ID: {$bookingData['stripe_session_id']}\n" .
        "Payment status: {$bookingData['payment_status']}\n" .
        "Submitted at: {$bookingData['created_at']}\n";

      $mail->send();
      $emailSent = true;
    } catch (Exception $e) {
      $emailError = $mail->ErrorInfo;
    }
  }
}

} catch (Exception $e) {
  error_log('Stripe session error: ' . $e->getMessage());
  header('Location: booking.php?error=payment_processing');
  exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment Successful | Easy Help Switzerland</title>
  <meta name="description" content="Your consultation booking has been received successfully." />
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

    .shell{width:var(--container);margin:20px auto 48px}

    .hero-wrap{
      position:relative;
      min-height:660px;
      border-radius:44px;
      overflow:hidden;
      color:#fff;
      padding:26px 36px 34px;
      background:
        linear-gradient(180deg, rgba(9,12,15,.48), rgba(9,12,15,.66)),
        radial-gradient(circle at 50% 40%, rgba(255,255,255,.03), transparent 35%),
        url('https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
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
    .back-pill{
      border:1px solid rgba(255,255,255,.18);
      background:rgba(255,255,255,.08);
      color:#fff;
      padding:10px 14px;
      border-radius:999px;
    }

    .hero-center{
      position:absolute;
      inset:150px 90px 165px;
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
      font-size:clamp(82px,10.5vw,190px);
      line-height:.82;
      letter-spacing:-.06em;
      margin:0;
    }
    .hero-title span{display:block;font-size:clamp(48px,4.8vw,96px);margin-top:10px}
    .hero-sub{margin-top:20px;font-size:18px;color:rgba(255,255,255,.86);max-width:64ch}

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
    .hero-years span{max-width:160px;font-size:18px;line-height:1.05;color:rgba(255,255,255,.9);padding-top:8px}

    .success-zone{padding:68px 0 0;background:#fff}
    .success-layout{
      width:var(--content);
      margin:0 auto;
      display:grid;
      grid-template-columns:minmax(0,1.06fr) 390px;
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
    .main-card,.side-card,.cta-band{
      background:
        radial-gradient(circle at 20% 20%, rgba(193,232,241,.55), transparent 35%),
        radial-gradient(circle at 80% 70%, rgba(70,147,232,.18), transparent 30%),
        linear-gradient(180deg, #f7fafb 0%, #eef5f7 100%);
    }

    .main-card{padding:38px}
    .section-label{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin:0 0 12px}
    .title{margin:0 0 14px;font-weight:300;font-size:clamp(42px,4.5vw,72px);line-height:.95;letter-spacing:-.06em}
    .subtitle{margin:0;color:var(--muted);line-height:1.55;max-width:60ch}

    .status-box{
      margin-top:28px;
      padding:26px 28px;
      border-radius:28px;
      background:rgba(255,255,255,.72);
      border:1px solid rgba(255,255,255,.34);
      box-shadow:inset 0 1px 0 rgba(255,255,255,.35);
      display:grid;
      gap:12px;
    }
    .status-row{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .status-chip{
      display:inline-flex;
      align-items:center;
      gap:10px;
      padding:10px 14px;
      border-radius:999px;
      background:linear-gradient(135deg, rgba(70,147,232,.14), rgba(193,232,241,.34));
      color:#16324f;
      font-size:14px;
      font-weight:600;
    }
    .status-chip i{width:10px;height:10px;border-radius:50%;background:#4693e8;display:block}
    .price{font-size:32px;line-height:.95;letter-spacing:-.06em;font-weight:400}

    .details{display:grid;gap:16px;margin-top:24px}
    .detail{
      display:grid;
      grid-template-columns:210px 1fr;
      gap:18px;
      align-items:start;
      padding:16px 0;
      border-bottom:1px solid rgba(17,17,17,.08);
    }
    .detail:last-child{border-bottom:none}
    .label{display:block;font-size:13px;text-transform:uppercase;letter-spacing:.12em;color:#7a8a99}
    .value{font-size:17px;color:#111;line-height:1.55;word-break:break-word}

    .notice{
      margin-top:24px;
      padding:20px 22px;
      border-radius:22px;
      background:#fff;
      border:1px solid rgba(17,17,17,.06);
      box-shadow:0 12px 24px rgba(0,0,0,.04);
      color:#303030;
      line-height:1.6;
    }
    .notice.error{border-color:rgba(160,0,0,.15);color:#7b1f1f;background:#fff8f8}

    .btn-row{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:28px}
    .btn-blue,.btn-outline{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      padding:18px 30px;
      font-weight:600;
      cursor:pointer;
      transition:.25s ease;
      text-decoration:none;
    }
    .btn-blue{
      border:0;
      background:linear-gradient(135deg,#4693e8 0%,#6fb3f2 100%);
      color:#fff;
      box-shadow:0 8px 20px rgba(70,147,232,.22), inset 0 1px 0 rgba(255,255,255,.22);
    }
    .btn-blue:hover{background:linear-gradient(135deg,#317bcd 0%,#5aa6ec 100%);transform:translateY(-1px)}
    .btn-outline{border:1px solid var(--line);background:#fff;color:#111}

    .sidebar{position:sticky;top:24px;display:grid;gap:22px}
    .side-card{padding:28px}
    .side-card h3{margin:0 0 12px;font-size:30px;line-height:1.02;letter-spacing:-.05em;font-weight:500}
    .side-card p{margin:0;color:#303030;line-height:1.55}
    .step-list{display:grid;gap:14px;margin-top:14px}
    .step{padding:14px 0;border-bottom:1px solid rgba(17,17,17,.08)}
    .step:last-child{border-bottom:none;padding-bottom:0}
    .step span{display:block;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin-bottom:6px}
    .step strong{font-size:17px;line-height:1.35}

    .cta-band{
      width:var(--content);
      margin:28px auto 0;
      padding:46px;
      border-radius:38px;
      display:grid;
      grid-template-columns:1.15fr .85fr;
      gap:30px;
      align-items:center;
    }
    .cta-band h2{margin:0 0 12px;font-size:clamp(42px,4vw,72px);line-height:.95;font-weight:300;letter-spacing:-.06em}
    .cta-band p{margin:0;color:#303030;line-height:1.55;max-width:58ch}
    .cta-actions{display:flex;justify-content:flex-end;gap:14px;flex-wrap:wrap}

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

    @media (max-width:1180px){
      .hero-top{grid-template-columns:1fr;justify-items:start}
      .nav{justify-content:flex-start;gap:18px}
      .hero-center{position:relative;inset:auto;display:block;text-align:left;padding-top:140px}
      .hero-bottom{position:relative;left:auto;right:auto;bottom:auto;margin-top:90px;flex-direction:column;align-items:flex-start}
      .hero-wrap{min-height:auto;padding-bottom:34px}
      .success-layout,.cta-band{grid-template-columns:1fr}
      .sidebar{position:relative;top:0}
      .cta-actions{justify-content:flex-start}
    }

    @media (max-width:780px){
      :root{--container:min(100% - 18px, 1700px);--content:min(100% - 20px, 1480px)}
      .hero-wrap{padding:18px 18px 22px;border-radius:28px}
      .main-card,.side-card{padding:24px 20px}
      .detail,.contact-footer{grid-template-columns:1fr}
      .btn-row{flex-direction:column;align-items:stretch}
      .btn-row .btn-blue,.btn-row .btn-outline{width:100%}
      .cta-band{padding:26px 22px;border-radius:28px}
      .contact-footer{text-align:left;border-radius:0 0 28px 28px}
      .contact-footer .footer-center,.contact-footer .footer-right{text-align:left;justify-self:start}
    }
  </style>
</head>
<body>
  <div class="shell">
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
          <a href="index.html">Home</a>
          <a href="booking.php">Booking</a>
          <a href="blog.html">Guides</a>
          <a href="free-consultation.php">Free consultation</a>
          <a href="index.html#contact">Contacts</a>
        </nav>

        <div class="hero-right">
          <a href="tel:+41764497581">+41 76 449 75 81</a>
          <a class="back-pill" href="index.html">Back to homepage</a>
        </div>
      </div>

      <div class="hero-center">
        <div>
          <div class="micro">Booking confirmed</div>
          <h1 class="hero-title">Payment <span>successful</span></h1>
          <div class="hero-sub">Your consultation request has been received. Your booking data has been processed from Stripe metadata, stored on the server, and the admin notification email has been attempted automatically.</div>
        </div>
      </div>

      <div class="hero-bottom">
        <div class="hero-features">
          <div class="hero-feature"><div class="dot">✓</div><div>Payment received</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div>Booking saved</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div>Admin notified</div></div>
        </div>
        <div class="hero-years">
          <strong>24</strong>
          <span>hours target for follow-up contact</span>
        </div>
      </div>
    </section>

    <section class="success-zone">
      <div class="success-layout">
        <main class="main-card glass-card">
          <p class="section-label">Confirmation</p>
          <h1 class="title">Thank you for your booking</h1>
          <p class="subtitle">This page keeps the original backend behavior: once Stripe marks the session as paid, the booking is saved to a JSON file in the <code>bookings</code> folder and an email notification is sent through PHPMailer to the configured admin address.</p>

          <div class="status-box">
            <div class="status-row">
              <span class="status-chip"><i></i>Payment status: <?= htmlspecialchars($bookingData['payment_status'] ?: 'unknown') ?></span>
              <strong class="price"><?= !empty($bookingData['price_chf']) ? 'CHF ' . htmlspecialchars($bookingData['price_chf']) : '—' ?></strong>
            </div>
            <div style="font-size:28px;line-height:1.02;letter-spacing:-.05em;font-weight:500;"><?= htmlspecialchars($bookingData['package_name'] ?: 'Consultation booking') ?></div>
          </div>

          <div class="details">
            <div class="detail">
              <span class="label">Full name</span>
              <div class="value"><?= htmlspecialchars($bookingData['name'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Email</span>
              <div class="value"><?= htmlspecialchars($bookingData['email'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Phone / WhatsApp</span>
              <div class="value"><?= htmlspecialchars($bookingData['phone'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Current location</span>
              <div class="value"><?= htmlspecialchars($bookingData['location'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Preferred format</span>
              <div class="value"><?= htmlspecialchars($bookingData['preferred'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Short description</span>
              <div class="value"><?= nl2br(htmlspecialchars($bookingData['message'] ?: '—')) ?></div>
            </div>
            <div class="detail">
              <span class="label">Stripe session ID</span>
              <div class="value"><?= htmlspecialchars($bookingData['stripe_session_id'] ?: '—') ?></div>
            </div>
            <div class="detail">
              <span class="label">Submitted at</span>
              <div class="value"><?= htmlspecialchars($bookingData['created_at'] ?: '—') ?></div>
            </div>
          </div>

          <div class="notice">
            Your consultation request has been received successfully. You should now be contacted to confirm the format and next practical steps.
          </div>

          <?php if (!$emailSent && !empty($emailError)): ?>
            <div class="notice error">
              Booking saved, but the notification email failed: <?= htmlspecialchars($emailError) ?>
            </div>
          <?php elseif ($emailSent): ?>
            <div class="notice">
              The admin notification email was sent successfully.
            </div>
          <?php endif; ?>

          <div class="btn-row">
            <a class="btn-blue" href="index.html">Back to homepage</a>
            <a class="btn-outline" href="blog.html">Read relocation guides</a>
          </div>
        </main>

        <aside class="sidebar">
          <section class="side-card glass-card">
            <p class="section-label">What happened behind the scenes</p>
            <h3>Server-side actions completed</h3>
            <div class="step-list">
              <div class="step"><span>01</span><strong>Stripe session retrieved using <code>session_id</code>.</strong></div>
              <div class="step"><span>02</span><strong>Booking metadata collected and stored as JSON when payment is marked as paid.</strong></div>
              <div class="step"><span>03</span><strong>Email notification attempted via PHPMailer and SMTP credentials from environment variables.</strong></div>
            </div>
          </section>

          <section class="side-card glass-card">
            <p class="section-label">Next for the client</p>
            <h3>What to expect now</h3>
            <p>You can use this page to reassure users that the booking is recorded, the payment has gone through, and they should expect a follow-up message shortly. That closes the booking flow cleanly after <code>payment.html</code> and Stripe checkout.</p>
          </section>
        </aside>
      </div>

      <section class="cta-band glass-card">
        <div>
          <p class="section-label">After booking</p>
          <h2>Everything is now in the system</h2>
          <p>Your request has been submitted with your selected package, contact details, and message. The next step is human follow-up and case review.</p>
        </div>
        <div class="cta-actions">
          <a href="index.html" class="btn-blue">Return home</a>
          <a href="booking.php" class="btn-outline">Book another consultation</a>
        </div>
      </section>
    </section>

    <footer class="contact-footer">
      <div class="footer-brand">
        <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
        <span>Easy Help Switzerland</span>
      </div>
      <div class="footer-center">© 2026 Zurich Relocation</div>
      <div class="footer-right">Confirmation page aligned to the main website</div>
    </footer>
  </div>
</body>
</html>
