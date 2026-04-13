<?php
require_once __DIR__ . '/security.php';
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$_SESSION['consultation_form_started_at'] = time();

$errorMessages = [
    'invalid_request' => 'Request could not be validated. Please try again.',
    'too_fast' => 'The form was submitted too quickly. Please wait a moment and try again.',
    'invalid_name' => 'Please enter a valid full name.',
    'invalid_email' => 'Please enter a valid email address.',
    'invalid_phone' => 'Please enter a valid phone number.',
    'invalid_location' => 'Please enter a shorter location.',
    'invalid_topic' => 'Please choose a valid topic.',
    'invalid_message' => 'Please enter a shorter message.',
    'rate_limited' => 'Too many attempts. Please wait a few minutes and try again.',
];

$errorCode = $_GET['error'] ?? '';
$errorMessage = $errorMessages[$errorCode] ?? '';

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Free Consultation | Easy Help Switzerland</title>
  <meta name="description" content="Request a free initial consultation for permits, relocation, documents, and practical support in Switzerland." />
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
    button,input,select,textarea{font:inherit}

    .shell{width:var(--container);margin:20px auto 48px}

    .hero-wrap{
      position:relative;
      min-height:700px;
      border-radius:44px;
      overflow:hidden;
      color:#fff;
      padding:26px 36px 34px;
      background:
        linear-gradient(180deg, rgba(9,12,15,.48), rgba(9,12,15,.66)),
        radial-gradient(circle at 50% 40%, rgba(255,255,255,.03), transparent 35%),
        url('https://images.goway.com/production/hero/iStock-1815540289.jpg?VersionId=v3RZM1U2qDrqLjxmBwOzenktyl.2R9Rx') center/cover no-repeat;
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

    /* LANGUAGE SWITCH — same as index */

.lang-switch{
  display:flex;
  gap:10px;
  align-items:center;
}

.lang-switch button{
  background:transparent;
  border:none;
  font-size:14px;
  font-weight:500;
  color:rgba(255,255,255,.65);
  cursor:pointer;
  padding:0;
  transition:.2s ease;
}

.lang-switch button:hover{
  color:#fff;
}

.lang-switch button.active{
  color:var(--blue); /* same blue as your site */
}

    .hero-center{
      position:absolute;
      inset:155px 90px 170px;
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
      font-size:clamp(82px,10.5vw,200px);
      line-height:.82;
      letter-spacing:-.06em;
      margin:0;
    }
    .hero-title span{display:block;font-size:clamp(48px,4.8vw,98px);margin-top:10px}
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
    .hero-years span{max-width:165px;font-size:18px;line-height:1.05;color:rgba(255,255,255,.9);padding-top:8px}

    .section-heading-bracket{
      display:grid;
      grid-template-columns:46px auto 46px;
      align-items:start;
      column-gap:18px;
      width:fit-content;
    }
    .section-bracket{width:14px;height:80px;margin-top:2px;position:relative}
    .section-bracket::before,.section-bracket::after{content:"";position:absolute;width:12px;height:1.5px;background:rgba(17,17,17,.50)}
    .section-bracket::before{top:0}
    .section-bracket::after{bottom:0}
    .section-bracket-left{border-left:1.5px solid rgba(17,17,17,.50)}
    .section-bracket-right{border-right:1.5px solid rgba(17,17,17,.50)}
    .section-title{margin:0;font-weight:300;font-size:clamp(54px,6vw,92px);line-height:.95;letter-spacing:-.06em}

    .consult-zone{padding:68px 0 0;background:#fff}
    .consult-layout{
      width:var(--content);
      margin:0 auto;
      display:grid;
      grid-template-columns:minmax(0,1.04fr) 390px;
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
    .intro-card,.form-card,.side-card,.why-band{
      background:
        radial-gradient(circle at 20% 20%, rgba(193,232,241,.55), transparent 35%),
        radial-gradient(circle at 80% 70%, rgba(70,147,232,.18), transparent 30%),
        linear-gradient(180deg, #f7fafb 0%, #eef5f7 100%);
    }

    .intro-card{padding:36px 38px;margin-bottom:24px}
    .intro-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:28px;align-items:start}
    .intro-copy,.intro-card p,.side-card p{margin:0;color:#303030;line-height:1.55}
    .bullet-list{display:grid;gap:14px;margin-top:18px}
    .bullet{display:flex;gap:12px;align-items:flex-start;font-size:17px;line-height:1.35;color:#222}
    .bullet i{width:12px;height:12px;border-radius:50%;background:#111;display:block;flex:0 0 auto;margin-top:6px}

    .form-card{padding:38px}
    .section-label{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin:0 0 12px}
    .form-head{display:grid;grid-template-columns:1fr .72fr;gap:24px;align-items:end;margin-bottom:28px}
    .form-head p:last-child{margin:0;color:var(--muted);line-height:1.55}

    .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px 20px}
    .field{display:grid;gap:8px}
    .field.full{grid-column:1 / -1}
    .field label{font-size:14px;color:#333}
    .field input,.field select,.field textarea{
      width:100%;
      border:0;
      border-radius:18px;
      padding:16px 18px;
      background:rgba(255,255,255,.72);
      border:1px solid rgba(255,255,255,.34);
      box-shadow:inset 0 1px 0 rgba(255,255,255,.35);
      color:#111;
      outline:none;
    }
    .field textarea{min-height:150px;resize:vertical}

    .btn-row{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:26px}
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

    .small-note{margin-top:18px;font-size:13px;color:#5a5a5a;line-height:1.7}

    .sidebar{position:sticky;top:24px;display:grid;gap:22px}
    .side-card{padding:28px}
    .side-card h3{margin:0 0 12px;font-size:30px;line-height:1.02;letter-spacing:-.05em;font-weight:500}
    .step-list{display:grid;gap:14px;margin-top:14px}
    .step{padding:14px 0;border-bottom:1px solid rgba(17,17,17,.08)}
    .step:last-child{border-bottom:none;padding-bottom:0}
    .step span{display:block;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#7a8a99;margin-bottom:6px}
    .step strong{font-size:17px;line-height:1.35}

    .why-band{
      width:var(--content);
      margin:28px auto 0;
      padding:46px;
      border-radius:38px;
      display:grid;
      grid-template-columns:1.1fr .9fr;
      gap:30px;
      align-items:center;
    }
    .why-band h2{margin:0 0 12px;font-size:clamp(42px,4vw,72px);line-height:.95;font-weight:300;letter-spacing:-.06em}
    .why-band p{margin:0;color:#303030;line-height:1.55;max-width:58ch}
    .why-points{display:grid;gap:16px}
    .why-point{padding:18px 20px;border-radius:22px;background:#fff;border:1px solid rgba(17,17,17,.06);box-shadow:0 12px 24px rgba(0,0,0,.04)}
    .why-point strong{display:block;margin-bottom:6px;font-size:18px;letter-spacing:-.03em}
    .why-point span{display:block;color:#4d4d4d;line-height:1.45}

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
      .consult-layout,.intro-grid,.form-head,.why-band{grid-template-columns:1fr}
      .sidebar{position:relative;top:0}
    }

    @media (max-width:780px){
      :root{--container:min(100% - 18px, 1700px);--content:min(100% - 20px, 1480px)}
      .hero-wrap{padding:18px 18px 22px;border-radius:28px}
      .section-title{font-size:52px}
      .intro-card,.form-card,.side-card{padding:24px 20px}
      .form-grid,.contact-footer{grid-template-columns:1fr}
      .btn-row{flex-direction:column;align-items:stretch}
      .btn-row .btn-blue,.btn-row .btn-outline{width:100%}
      .why-band{padding:26px 22px;border-radius:28px}
      .contact-footer{text-align:left;border-radius:0 0 28px 28px}
      .contact-footer .footer-center,.contact-footer .footer-right{text-align:left;justify-self:start}
    }
  </style>
</head>
<body>

<?php if ($errorMessage !== ''): ?>
  <div style="margin:20px;padding:15px;border:1px solid #e0b4b4;background:#fff6f6;border-radius:10px;color:#9f3a38;">
    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

  <div class="shell">
    <section class="hero-wrap" id="top">
      <div class="hero-top">
        <a class="brand" href="index.html" aria-label="Easy Help Switzerland home">
          <svg viewBox="0 0 32 48" aria-hidden="true">
            <path d="M4 44V10l10-8 10 8v34"></path>
            <path d="M14 44V22l10-8v30"></path>
          </svg>
          <div>
            <div style="font-size:18px" data-i18n="consult_brand_main">Easy Help</div>
            <div style="font-size:12px;color:rgba(255,255,255,.65);letter-spacing:.12em;text-transform:uppercase" data-i18n="consult_brand_sub">Switzerland</div>
          </div>
        </a>

        <nav class="nav">
          <a href="index.html" data-i18n="nav_home">Home</a>
          <a href="index.html#services" data-i18n="nav_objects">Services</a>
          <a href="blog.html" data-i18n="consult_nav_guides">Guides</a>
          <a href="booking.php" data-i18n="consult_nav_booking">Booking</a>
          <a href="index.html#contact" data-i18n="nav_contact">Contacts</a>
        </nav>

        <div class="hero-right">
          <a href="tel:+41764497581">+41 76 449 75 81</a>
          <div class="lang-switch">
  <button type="button" data-lang="en" class="active">EN</button>
  <button type="button" data-lang="es">ES</button>
  <button type="button" data-lang="de">DE</button>
  <button type="button" data-lang="uk">UA</button>
</div>
          <a class="back-pill" href="index.html" data-i18n="back">← Back</a>
        </div>
      </div>

      <div class="hero-center">
        <div>
          <div class="micro" data-i18n="consult_micro">Free first step</div>
          <h1 class="hero-title" data-i18n="consult_title" data-i18n-html>Request a free <span>consultation</span></h1>
          <div class="hero-sub" data-i18n="consult_subtitle">Tell us briefly about your situation and we will contact you to arrange a free initial consultation.</div>
        </div>
      </div>

      <div class="hero-bottom">
        <div class="hero-features">
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="consult_feature_1">Free initial request</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="consult_feature_2">Clear next-step guidance</div></div>
          <div class="hero-feature"><div class="dot">✓</div><div data-i18n="consult_feature_3">Good place to start</div></div>
        </div>
        <div class="hero-years">
          <strong>30</strong>
          <span data-i18n="consult_hero_minutes">minutes is often enough to define the next route</span>
        </div>
      </div>
    </section>

    <section class="consult-zone">
      <div class="consult-layout">
        <main>
          <section class="intro-card glass-card">
            <div class="intro-grid">
              <div>
                <div class="section-heading-bracket">
                  <span class="section-bracket section-bracket-left"></span>
                  <h2 class="section-title" data-i18n="consult_start_here">Start here</h2>
                  <span class="section-bracket section-bracket-right"></span>
                </div>
              </div>
              <div>
                <p class="intro-copy" data-i18n="consult_intro_copy" data-i18n-html>The original free consultation page uses a simple multilingual form that posts directly to <code>submit-consultation.php</code>. It includes full name, email, phone, current location, help topic, and a short description. This redesign keeps that exact purpose and structure while bringing it into the same premium system as the homepage. </p>
                <div class="bullet-list">
                  <div class="bullet"><i></i><span data-i18n="consult_bullet_1">Fast first contact without payment</span></div>
                  <div class="bullet"><i></i><span data-i18n="consult_bullet_2">Good for simple orientation and case clarity</span></div>
                  <div class="bullet"><i></i><span data-i18n="consult_bullet_3">Same language dropdown behavior preserved</span></div>
                </div>
              </div>
            </div>
          </section>

          <section class="form-card glass-card">
            <div class="form-head">
              <div>
                <p class="section-label" data-i18n="consult_form_label">Free consultation request</p>
                <h2 class="section-title" style="font-size:clamp(42px,4.4vw,72px)" data-i18n="consult_form_title">Tell us briefly about your situation</h2>
              </div>
              <p data-i18n="consult_form_text" data-i18n-html>This form still posts to <code>submit-consultation.php</code> and keeps the same topic options: residence permit, work permit, relocation to Zürich, legal consultation, and other. </p>
            </div>

            <form action="submit-consultation.php" method="POST">
              <input type="text" name="website" style="display:none">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="form_started_at" value="<?= htmlspecialchars((string)($_SESSION['consultation_form_started_at'] ?? time()), ENT_QUOTES, 'UTF-8') ?>">

              <div class="form-grid">
                <div class="field">
                  <label data-i18n="name">Full name</label>
                  <input type="text" name="name" required>
                </div>

                <div class="field">
                  <label data-i18n="email">Email</label>
                  <input type="email" name="email" required>
                </div>

                <div class="field">
                  <label data-i18n="phone">Phone / WhatsApp</label>
                  <input type="text" name="phone">
                </div>

                <div class="field">
                  <label data-i18n="location">Current location</label>
                  <input type="text" name="location">
                </div>

                <div class="field full">
                  <label data-i18n="topic">What do you need help with?</label>
                  <select name="topic">
                    <option data-i18n="opt_residence">Residence permit</option>
                    <option data-i18n="opt_work">Work permit</option>
                    <option data-i18n="opt_relocation">Relocation to Zürich</option>
                    <option data-i18n="opt_legal">Consultation</option>
                    <option data-i18n="opt_other">Other</option>
                  </select>
                </div>

                <div class="field full">
                  <label data-i18n="message">Short description</label>
                  <textarea name="message"></textarea>
                </div>
              </div>

              <div class="btn-row">
                <button class="btn-blue" type="submit" data-i18n="submit">Request consultation</button>
                <a class="btn-outline" href="booking.php" data-i18n="consult_paid_booking">Go to paid booking</a>
              </div>

              <p class="small-note" data-i18n="note">This request does not create a lawyer-client relationship. If formal legal representation is required, you may be referred to a licensed Swiss attorney.</p>
            </form>
          </section>
        </main>

        <aside class="sidebar">
          <div class="side-card glass-card">
            <p class="section-label" data-i18n="consult_how_label">How it works</p>
            <h3 data-i18n="consult_how_title">Simple first contact</h3>
            <div class="step-list">
              <div class="step"><span>01</span><strong data-i18n="consult_step_1">Send your request and describe the matter briefly.</strong></div>
              <div class="step"><span>02</span><strong data-i18n="consult_step_2">Your request is reviewed and routed to the right next step.</strong></div>
              <div class="step"><span>03</span><strong data-i18n="consult_step_3">You are contacted to arrange the free initial consultation.</strong></div>
            </div>
          </div>

          <div class="side-card glass-card">
            <p class="section-label" data-i18n="consult_email_label">Sent by email</p>
            <h3 data-i18n="consult_email_title">Backend flow kept</h3>
            <p data-i18n="consult_email_text" data-i18n-html>The existing PHP handler reads <code>name</code>, <code>email</code>, <code>phone</code>, <code>location</code>, <code>topic</code>, and <code>message</code>, requires the name and email fields, and sends the consultation request by email via PHPMailer. </p>
          </div>

          <div class="side-card glass-card">
            <p class="section-label" data-i18n="consult_paid_label">Need more than a first chat?</p>
            <h3 data-i18n="consult_paid_title">Move to paid support</h3>
            <p data-i18n="consult_paid_text">Once the case is clearer, users can continue into the paid booking flow for structured consultation, review, or relocation support.</p>
            <div style="margin-top:18px"><a href="booking.php" class="btn-blue" data-i18n="consult_open_booking">Open booking page</a></div>
          </div>
        </aside>
      </div>

      <section class="why-band glass-card">
        <div>
          <p class="section-label" data-i18n="consult_why_label">Why clients start here</p>
          <h2 data-i18n="consult_why_title">Practical support before unnecessary costs</h2>
          <p data-i18n="consult_why_text">Many people first need clarity, structure, and help understanding their documents or next administrative step — not a full legal mandate immediately. A short consultation can often save time and reduce stress early.</p>
        </div>
        <div class="why-points">
          <div class="why-point"><strong data-i18n="consult_why_point_1_title">Good for orientation</strong><span data-i18n="consult_why_point_1_text">Useful when the first problem is uncertainty, not yet execution.</span></div>
          <div class="why-point"><strong data-i18n="consult_why_point_2_title">Lower friction</strong><span data-i18n="consult_why_point_2_text">A simpler entry point for visitors who are not ready to pay yet.</span></div>
          <div class="why-point"><strong data-i18n="consult_why_point_3_title">Natural path onward</strong><span data-i18n="consult_why_point_3_text">From this page, users can be directed into paid support when needed.</span></div>
        </div>
      </section>
    </section>

    <footer class="contact-footer">
      <div class="footer-brand">
        <svg viewBox="0 0 32 48" aria-hidden="true"><path d="M4 44V10l10-8 10 8v34"></path><path d="M14 44V22l10-8v30"></path></svg>
        <span data-i18n="footer_brand">Easy Help Switzerland</span>
      </div>
      <div class="footer-center" data-i18n="consult_footer_copy">© 2026 Easy Help Switzerland - all rights preserved.</div>
      <div class="footer-right" data-i18n="consult_footer_right">Free consultation page aligned to the main website</div>
    </footer>
  </div>

  <a class="whatsapp-float" href="https://wa.me/41764497581" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff"><path d="M20 12a8 8 0 1 0-14.5 4.7L4 21l4.5-1.4A8 8 0 1 0 20 12z"/><path d="M9.5 9.5c.3-.6.6-.6.9-.6h.7c.2 0 .4 0 .6.5.2.5.7 1.7.8 1.8.1.1.1.3 0 .5s-.2.3-.3.4c-.1.1-.3.3-.4.4-.1.1-.2.3 0 .6.2.3.8 1.3 1.8 2 .3.3.6.4.8.2.2-.2.4-.5.5-.7.1-.2.3-.2.5-.1.2.1 1.4.7 1.6.8.2.1.3.2.3.3 0 .1 0 .7-.4 1.3-.4.6-1.1 1.2-1.6 1.3-.4.1-1 .2-2.6-.5-2-.9-3.4-3.2-3.5-3.3-.1-.1-.8-1.1-.8-2.1 0-1 .5-1.5.7-1.7z" fill="#fff"/></svg>
  </a>

  <script src="site.js"></script>

</body>
</html>