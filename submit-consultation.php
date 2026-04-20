<?php
require_once __DIR__ . '/security.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

function consultationFail(string $reason, string $errorCode): void
{
    error_log('Consultation form rejected: ' . $reason);
    header('Location: free-consultation.php?error=' . urlencode($errorCode));
    exit;
}

// honeypot (боты заполняют это поле)
if (!empty($_POST['website'])) {
    exit; // тихо игнорируем
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    consultationFail('csrf validation failed', 'invalid_request');
}

$formStartedAt = (int)($_POST['form_started_at'] ?? 0);
$sessionStartedAt = (int)($_SESSION['consultation_form_started_at'] ?? 0);

if ($formStartedAt <= 0 || $sessionStartedAt <= 0 || abs($formStartedAt - $sessionStartedAt) > 10) {
    consultationFail('form timestamp mismatch or missing', 'invalid_request');
}

if ((time() - $sessionStartedAt) < 3) {
    consultationFail('form submitted too quickly', 'too_fast');
}

unset($_SESSION['consultation_form_started_at']);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitDir = __DIR__ . '/rate-limit';

if (!is_dir($rateLimitDir)) {
    mkdir($rateLimitDir, 0750, true);
}

$rateLimitKey = hash('sha256', 'consultation:' . $ip);
$rateLimitFile = $rateLimitDir . '/' . $rateLimitKey . '.json';

$now = time();
$windowSeconds = 600; // 10 минут
$maxRequests = 3;     // максимум 3 отправки за 10 минут

$requests = [];

if (file_exists($rateLimitFile)) {
    $existing = file_get_contents($rateLimitFile);
    $decoded = json_decode($existing, true);

    if (is_array($decoded)) {
        $requests = $decoded;
    }
}

$requests = array_filter($requests, function ($timestamp) use ($now, $windowSeconds) {
    return is_int($timestamp) && ($now - $timestamp) < $windowSeconds;
});

if (count($requests) >= $maxRequests) {
    consultationFail('rate limit exceeded', 'rate_limited');
}

$requests[] = $now;

$saved = file_put_contents(
    $rateLimitFile,
    json_encode(array_values($requests), JSON_PRETTY_PRINT),
    LOCK_EX
);

if ($saved === false) {
    consultationFail('rate limit write failed', 'invalid_request');
}

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$location    = trim($_POST['location'] ?? '');
$topic       = trim($_POST['topic'] ?? '');
$messageText = trim($_POST['message'] ?? '');

// Validate required fields and email format
if ($name === '' || $email === '') {
    header('Location: free-consultation.php?error=required_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: free-consultation.php?error=invalid_email');
    exit;
}

// Enforce length limits
if (mb_strlen($name) > 100 || mb_strlen($phone) > 50 || mb_strlen($location) > 100
    || mb_strlen($topic) > 200 || mb_strlen($messageText) > 2000) {
    header('Location: free-consultation.php?error=invalid_request');
    exit;
}

// Strip newlines from name to prevent email header injection
$safeName = str_replace(["\r", "\n"], ' ', $name);

$mail = new PHPMailer(true);

if ($phone !== '' && mb_strlen($phone) > 50) {
    consultationFail('invalid phone', 'invalid_phone');
}

if ($location !== '' && mb_strlen($location) > 100) {
    consultationFail('invalid location', 'invalid_location');
}

if ($topic !== '' && mb_strlen($topic) > 150) {
    consultationFail('invalid topic', 'invalid_topic');
}

if ($messageText !== '' && mb_strlen($messageText) > 2000) {
    consultationFail('invalid message', 'invalid_message');
}

$name = str_replace(["\r", "\n"], ' ', $name);
$email = str_replace(["\r", "\n"], '', $email);

function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet    = 'UTF-8';
    return $mail;
}

try {
    // Admin notification
    $mail = createMailer();
    $mail->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
    $mail->addAddress($_ENV['ADMIN_EMAIL']);
    $mail->addReplyTo($email, $safeName);
    $mail->Subject = 'New Free Consultation Request';
    $mail->isHTML(true);
    $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#0a0e14;padding:28px 36px;">
            <p style="margin:0;color:#ffffff;font-family:'Georgia',serif;font-size:22px;font-weight:400;letter-spacing:.02em;">Easy Help Switzerland</p>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">New Free Consultation Request</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:20px 24px;">
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Name</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$safeName}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Email</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$email}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Phone / WhatsApp</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$phone}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Current Location</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$location}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Topic</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$topic}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Message</p>
                <p style="margin:4px 0 0;font-size:15px;color:#1a1a2e;line-height:1.6;">{$messageText}</p>
              </td></tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f9fb;padding:20px 36px;border-top:1px solid #eee;">
            <p style="margin:0;font-size:12px;color:#aaa;text-align:center;">
              Easy Help Switzerland · Zürich · <a href="https://easyhelp.ch" style="color:#4693e8;text-decoration:none;">easyhelp.ch</a>
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    $mail->AltBody =
        "New free consultation request received\n\n" .
        "Name: {$safeName}\n" .
        "Email: {$email}\n" .
        "Phone / WhatsApp: {$phone}\n" .
        "Current location: {$location}\n" .
        "Topic: {$topic}\n" .
        "Message:\n{$messageText}\n";
    $mail->send();
} catch (Exception $e) {
    error_log('Consultation admin mail error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Request is not sent, please try again later.';
    exit;
}

// Save consultation to file for admin panel
$consultDir = __DIR__ . '/free-consultations';
if (!is_dir($consultDir)) mkdir($consultDir, 0750, true);
$consultId = bin2hex(random_bytes(16));
$consultData = [
    'internal_booking_id' => $consultId,
    'type'       => 'free',
    'name'       => $safeName,
    'email'      => $email,
    'phone'      => $phone,
    'location'   => $location,
    'topic'      => $topic,
    'message'    => $messageText,
    'created_at' => date('c'),
];
file_put_contents(
    $consultDir . '/consult-' . $consultId . '.json',
    json_encode($consultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

// Customer confirmation (non-critical — log failure but still confirm to user)
try {
    $confirmation = createMailer();
    $confirmation->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
    $confirmation->addAddress($email, $safeName);
    $confirmation->Subject = 'We received your consultation request';
    $confirmation->isHTML(true);
    $confirmation->Body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#0a0e14;padding:28px 36px;">
            <p style="margin:0;color:#ffffff;font-family:'Georgia',serif;font-size:22px;font-weight:400;letter-spacing:.02em;">Easy Help Switzerland</p>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">Request Received</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <p style="margin:0 0 20px;font-size:16px;color:#1a1a2e;">Dear <strong>{$safeName}</strong>,</p>
            <p style="margin:0 0 28px;font-size:15px;color:#444;line-height:1.6;">
              Thank you for reaching out! We have received your free consultation request and will get back to you within 24 hours.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:20px 24px;margin-bottom:28px;">
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Topic</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$topic}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Message</p>
                <p style="margin:4px 0 0;font-size:15px;color:#1a1a2e;line-height:1.6;">{$messageText}</p>
              </td></tr>
            </table>
            <p style="margin:0;font-size:14px;color:#555;line-height:1.6;">
              Looking forward to speaking with you.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f9fb;padding:20px 36px;border-top:1px solid #eee;">
            <p style="margin:0;font-size:12px;color:#aaa;text-align:center;">
              Easy Help Switzerland · Zürich · <a href="https://easyhelp.ch" style="color:#4693e8;text-decoration:none;">easyhelp.ch</a>
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    $confirmation->AltBody =
        "Dear {$safeName},\n\n" .
        "Thank you for reaching out! We have received your free consultation request and will get back to you within 24 hours.\n\n" .
        "Topic: {$topic}\n" .
        "Message:\n{$messageText}\n\n" .
        "Looking forward to speaking with you.\n\nBest regards,\nPolina Kravtsova\nEasy Help Switzerland";
    $confirmation->send();
} catch (Exception $e) {
    error_log('Consultation confirmation mail error: ' . $e->getMessage());
}

header('Location: consultation-success.php');
exit;