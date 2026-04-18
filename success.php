<?php
require_once __DIR__ . '/security.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.use_strict_mode', '1');

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$sessionIdFromSession = $_SESSION['last_checkout_session_id'] ?? null;
$sessionIdFromUrl = $_GET['session_id'] ?? null;
$sessionId = $sessionIdFromUrl ?? $sessionIdFromSession;

// Validate session ID format — Stripe IDs start with 'cs_'
if (!$sessionId || !preg_match('/^cs_[a-zA-Z0-9_]+$/', $sessionId)) {
    header('Location: booking.php?error=invalid_session');
    exit;
}

try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    // Only show the confirmation page for completed, paid sessions
    if ($session->payment_status !== 'paid') {
        header('Location: booking.php?error=payment_processing');
        exit;
    }

    $internalBookingId = $session->metadata->internal_booking_id ?? '';
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $internalBookingId);

    $pendingFile = __DIR__ . '/pending-bookings/booking-' . $safeId . '.json';
    $archiveFile = __DIR__ . '/bookings/booking-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $session->id) . '.json';

    // Load full booking data (pending file has name/email/phone etc.)
    $pendingBooking = [];
    if ($safeId !== '' && file_exists($pendingFile)) {
        $decoded = json_decode(file_get_contents($pendingFile), true);
        if (is_array($decoded)) {
            $pendingBooking = $decoded;
        }
    }

    // Build canonical booking record
    if (file_exists($archiveFile)) {
        $bookingData = json_decode(file_get_contents($archiveFile), true) ?: [];
    } else {
        $bookingData = [
            'internal_booking_id' => $internalBookingId,
            'stripe_session_id'   => $session->id,
            'payment_status'      => $session->payment_status,
            'package'             => $pendingBooking['package']      ?? ($session->metadata->package ?? ''),
            'package_name'        => $pendingBooking['package_name'] ?? ($session->metadata->package_name ?? ''),
            'price_chf'           => $pendingBooking['price_chf']    ?? ($session->metadata->price_chf ?? ''),
            'name'                => $pendingBooking['name']     ?? '',
            'email'               => $pendingBooking['email']    ?? '',
            'phone'               => $pendingBooking['phone']    ?? '',
            'location'            => $pendingBooking['location'] ?? '',
            'preferred'           => $pendingBooking['preferred'] ?? '',
            'message'             => $pendingBooking['message']  ?? '',
            'created_at'          => $pendingBooking['created_at'] ?? date('c'),
            'paid_at'             => date('c'),
            'admin_email_sent'    => false,
            'client_email_sent'   => false,
        ];

        // Save archive
        if (!is_dir(__DIR__ . '/bookings')) {
            mkdir(__DIR__ . '/bookings', 0750, true);
        }
        file_put_contents($archiveFile, json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    // Send emails if not already sent by webhook
    if (!empty($bookingData['email']) && (($bookingData['admin_email_sent'] ?? false) !== true || ($bookingData['client_email_sent'] ?? false) !== true)) {
        function successMailer(): \PHPMailer\PHPMailer\PHPMailer {
            $m = new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host       = $_ENV['SMTP_HOST'];
            $m->SMTPAuth   = true;
            $m->Username   = $_ENV['SMTP_USER'];
            $m->Password   = $_ENV['SMTP_PASS'];
            $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $m->Port       = (int) $_ENV['SMTP_PORT'];
            $m->CharSet    = 'UTF-8';
            $m->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
            return $m;
        }

        $safeName = str_replace(["\r", "\n"], ' ', $bookingData['name'] ?: 'Client');

        // Admin email
        if (($bookingData['admin_email_sent'] ?? false) !== true) {
            try {
                $m = successMailer();
                $m->addAddress($_ENV['ADMIN_EMAIL']);
                $m->addReplyTo($bookingData['email'], $safeName);
                $m->Subject = 'New Paid Booking: ' . $bookingData['package_name'];
                $m->Body =
                    "New paid booking received.\n\n" .
                    "Package: {$bookingData['package_name']}\n" .
                    "Price: CHF {$bookingData['price_chf']}\n" .
                    "Name: {$bookingData['name']}\n" .
                    "Email: {$bookingData['email']}\n" .
                    "Phone: {$bookingData['phone']}\n" .
                    "Location: {$bookingData['location']}\n" .
                    "Format: {$bookingData['preferred']}\n\n" .
                    "Message:\n{$bookingData['message']}\n\n" .
                    "Stripe session: {$bookingData['stripe_session_id']}\n" .
                    "Paid at: {$bookingData['paid_at']}\n";
                $m->send();
                $bookingData['admin_email_sent'] = true;
            } catch (Exception $e) {
                error_log('success.php admin mail error: ' . $e->getMessage());
            }
        }

        // Client email
        if (($bookingData['client_email_sent'] ?? false) !== true) {
            try {
                $m = successMailer();
                $m->addAddress($bookingData['email'], $safeName);
                $m->Subject = 'Your booking is confirmed — Easy Help Switzerland';
                $m->Body =
                    "Hello {$safeName},\n\n" .
                    "Your paid booking has been received successfully.\n\n" .
                    "Package: {$bookingData['package_name']}\n" .
                    "Price: CHF {$bookingData['price_chf']}\n" .
                    "Format: {$bookingData['preferred']}\n" .
                    "Paid at: {$bookingData['paid_at']}\n\n" .
                    "We will contact you shortly with next steps.\n\n" .
                    "Best regards,\nPolina Kravtsova\nEasy Help Switzerland";
                $m->send();
                $bookingData['client_email_sent'] = true;
            } catch (Exception $e) {
                error_log('success.php client mail error: ' . $e->getMessage());
            }
        }

        // Update archive with email sent flags
        file_put_contents($archiveFile, json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        // Remove pending file
        if (file_exists($pendingFile)) {
            unlink($pendingFile);
        }
    }

    unset($_SESSION['last_checkout_session_id']);
    unset($_SESSION['booking']);

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
        url('/img/lugano.jpg') center/cover no-repeat;
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
    .nav-lang-mobile{display:none}
    .lang-switch{display:flex;gap:10px;align-items:center}
    .lang-switch button{background:transparent;border:none;font-size:14px;font-weight:500;color:rgba(255,255,255,.65);cursor:pointer;padding:0;transition:.2s ease}
    .lang-switch button:hover{color:#fff}
    .lang-switch button.active{color:var(--blue)}
    .back-link{color:rgba(255,255,255,.9);text-decoration:none}

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
      .hero-center{position:relative;inset:auto;transform:none;display:block;text-align:left;padding-top:140px}
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
    .whatsapp-float{position:fixed;left:18px;bottom:18px;z-index:999;display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:999px;background:#25D366;color:#fff;text-decoration:none;box-shadow:0 14px 32px rgba(0,0,0,.18);transition:transform .2s,box-shadow .2s}
    .whatsapp-float:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(37,211,102,.4)}
    .wa-icon-wrap{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .burger{display:none;flex-direction:column;justify-content:center;gap:5px;padding:8px;background:transparent;border:0;cursor:pointer;flex-shrink:0;z-index:10}
    .burger span{display:block;width:22px;height:2px;background:rgba(255,255,255,.85);border-radius:2px;transition:transform .25s,opacity .25s}
    .burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
    .burger.open span:nth-child(2){opacity:0;transform:scaleX(0)}
    .burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
    @media(max-width:768px){
      .burger{display:flex}
      .hero-top{display:flex !important;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0;position:relative}
      .nav{display:none;flex-basis:100%;flex-direction:column;gap:0;background:none;border:none;border-radius:0;padding:4px 0 8px;margin-top:6px;box-shadow:none}
      .nav.open{display:flex}
      .nav a{padding:13px 4px;font-size:17px;font-weight:500;color:#fff;border-bottom:1px solid rgba(255,255,255,.15);letter-spacing:.01em;text-shadow:0 1px 10px rgba(0,0,0,.55);transition:color .2s,letter-spacing .2s}
      .nav a:hover{color:rgba(255,255,255,.75);letter-spacing:.03em}
      .nav a::after{display:none}
      .nav a:last-child{border-bottom:1px solid rgba(255,255,255,.15)}
      .nav-lang-mobile{display:flex;gap:8px;padding:14px 4px 2px;align-items:center}
      .nav-lang-mobile button{background:transparent;border:1px solid rgba(255,255,255,.30);border-radius:8px;color:rgba(255,255,255,.65);font-size:12px;font-weight:600;letter-spacing:.06em;padding:5px 12px;cursor:pointer;transition:all .2s;font-family:inherit;text-shadow:0 1px 6px rgba(0,0,0,.4)}
      .nav-lang-mobile button:hover{border-color:rgba(255,255,255,.7);color:#fff}
      .nav-lang-mobile button.active{background:var(--blue);border-color:var(--blue);color:#fff;text-shadow:none}
      .hero-right{display:none}
      .hero-center{padding-top:60px}
      .hero-title{font-size:clamp(44px,11vw,76px) !important}
      .hero-title span{font-size:clamp(30px,8vw,52px) !important}
      .hero-sub{font-size:15px;max-width:100%}
      .hero-years{display:none}
      .hero-bottom{margin-top:28px}
    }
    @media(max-width:480px){
      :root{--container:min(100% - 16px,1700px)}
      .hero-title{font-size:clamp(36px,10vw,56px) !important}
      .hero-title span{font-size:clamp(24px,7vw,40px) !important}
      .whatsapp-float span:last-child{display:none}
      .whatsapp-float{padding:14px;border-radius:50%;gap:0;width:52px;height:52px;justify-content:center}
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

        <button class="burger" id="burgerBtn" type="button" aria-label="Open menu"><span></span><span></span><span></span></button>
        <nav class="nav" id="mainNav">
          <a href="index.html" data-i18n="nav_home">Home</a>
          <a href="booking.php" data-i18n="success_nav_booking">Booking</a>
          <a href="blog.html" data-i18n="success_nav_guides">Guides</a>
          <a href="free-consultation.php" data-i18n="success_nav_free_consultation">Free consultation</a>
          <a href="index.html#contact" data-i18n="success_nav_contacts">Contacts</a>
          
        </nav>

        <div class="hero-right">
          
          <div class="lang-switch">
            <button type="button" data-lang="en" class="active">EN</button>
            <button type="button" data-lang="es">ES</button>
            <button type="button" data-lang="de">DE</button>
            <button type="button" data-lang="uk">UA</button>
          </div>
          <a class="back-link" href="index.html" data-i18n="back">← Back</a>
        </div>
      </div>

      <div class="hero-center">
        <div>
          <div class="micro" data-i18n="success_micro">Booking confirmed</div>
          <h1 class="hero-title" data-i18n="success_hero_title" data-i18n-html>Payment <span>successful</span></h1>
          <div class="hero-sub" data-i18n="success_hero_sub">Your consultation request has been received. Thank you for your booking!</div>
        </div>
      </div>

      <div class="hero-bottom">
        <div class="hero-features">
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="success_feature_1">Payment received</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="success_feature_2">Booking saved</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="success_feature_3">Our team notified</div></div>
        </div>
        <div class="hero-years">
          <strong>24</strong>
          <span data-i18n="success_hero_hours">hours target for follow-up contact</span>
        </div>
      </div>
    </section>

    <section class="success-zone">
      <div class="success-layout">
        <main class="main-card glass-card">
          <p class="section-label" data-i18n="success_section_label">Confirmation</p>
          <h1 class="title" data-i18n="success_title">Thank you for your booking</h1>
          <p class="subtitle" data-i18n="success_subtitle">Your payment has been confirmed. Check the details of your application. By any issues write us in the contact form. Save the booking data.</p>

          <div class="status-box">
            <div class="status-row">
              <span class="status-chip"><i></i><?= htmlspecialchars($bookingData['payment_status'] ?: 'unknown') ?></span>
              <strong class="price"><?= !empty($bookingData['price_chf']) ? 'CHF ' . htmlspecialchars($bookingData['price_chf']) : '—' ?></strong>
            </div>
            <div style="font-size:28px;line-height:1.02;letter-spacing:-.05em;font-weight:500;"><?= htmlspecialchars($bookingData['package_name'] ?: 'Consultation booking') ?></div>
          </div>
      

          <div class="notice" data-i18n="success_notice">
            Your consultation request has been received successfully. You should now be contacted to confirm the format and next practical steps.
          </div>

          <div class="btn-row">
            <a class="btn-blue" href="index.html" data-i18n="success_btn_home">Back to homepage</a>
            <a class="btn-outline" href="blog.html" data-i18n="success_btn_guides">Read relocation guides</a>
          </div>
        </main>

        <aside class="sidebar">
          <section class="side-card glass-card">
            <p class="section-label" data-i18n="success_side_1_label">Payment status</p>
            <h3 data-i18n="success_side_1_title">Confirmation received</h3>
            <div class="step-list">
              <div class="step"><span>01</span><strong data-i18n="success_step_1">The payment is now being processed by bank.</strong></div>
              <div class="step"><span>02</span><strong data-i18n="success_step_2">We get the payment and prepare to the first call.</strong></div>
              <div class="step"><span>03</span><strong data-i18n="success_step_3">You are getting a call from us.</strong></div>
            </div>
          </section>

          <section class="side-card glass-card">
            <p class="section-label" data-i18n="success_side_2_label">Information check</p>
            <h3 data-i18n="success_side_2_title">What you should remember</h3>
            <p data-i18n="success_side_2_text">All the booking and cancellation rules were introduced to you on the previous steps. The wrong bookings due to negligence will be cancelled with the fee. Be sure you get your confirmation per e-mail.</p>
          </section>
        </aside>
      </div>

      <section class="cta-band glass-card">
        <div>
          <p class="section-label" data-i18n="success_cta_label">After booking</p>
          <h2 data-i18n="success_cta_title">Everything is now being processed</h2>
          <p data-i18n="success_cta_text">If you got an e-mail confirmation your booking was successful and we got the information. In case of any questions or mistakes contact us or we will contact you. Await our call within next 24 hours.</p>
        </div>
        <div class="cta-actions">
          <a href="index.html" class="btn-blue" data-i18n="success_cta_btn_home">Return home</a>
          <a href="booking.php" class="btn-outline" data-i18n="success_cta_btn_book">Book another consultation</a>
        </div>
      </section>
    </section>

    <footer class="contact-footer">
      <div class="footer-brand">
        <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
        <span>Easy Help Switzerland</span>
      </div>
      <div class="footer-center" data-i18n="booking_footer_copy">© 2026 Easy Help Switzerland - all rights reserved.</div>
      <div class="footer-right" data-i18n="success_footer_page">Confirmation page</div>
    </footer>
  </div>
  <a class="whatsapp-float" href="https://wa.me/41764497581" target="_blank" rel="noopener" aria-label="WhatsApp">
    <span class="wa-icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>
    <span><strong style="display:block;font-size:14px">WhatsApp</strong><small style="opacity:.75;font-size:12px">Write to us</small></span>
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
