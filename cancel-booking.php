<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

session_start();

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Detect session hijacking
if (empty($_SESSION['admin_ip']) || $_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_destroy();
    echo json_encode(['ok' => false, 'error' => 'Session invalid']);
    exit;
}

// Require AJAX header — blocks cross-site form submissions
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad request']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['ok' => false, 'error' => 'Invalid request']); exit; }

$csrf = $body['csrf'] ?? '';
if (empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'error' => 'CSRF mismatch']);
    exit;
}

$id = preg_replace('/[^a-f0-9]/', '', $body['id'] ?? '');
if (!$id) { echo json_encode(['ok' => false, 'error' => 'Missing ID']); exit; }

// Load .env
foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v);
}

// Find booking
$booking = null;
foreach (glob(__DIR__ . '/bookings/booking-*.json') as $f) {
    $data = json_decode(file_get_contents($f), true);
    if (($data['internal_booking_id'] ?? '') === $id) { $booking = $data; break; }
}
if (!$booking) { echo json_encode(['ok' => false, 'error' => 'Booking not found']); exit; }

// Update status in admin-data
$dataDir  = __DIR__ . '/admin-data';
if (!is_dir($dataDir)) mkdir($dataDir, 0700, true);
$noteFile = $dataDir . '/' . $id . '.json';
$note     = file_exists($noteFile) ? (json_decode(file_get_contents($noteFile), true) ?? []) : [];
$note['status']     = 'cancelled';
$note['updated_at'] = date('c');
file_put_contents($noteFile, json_encode($note, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
chmod($noteFile, 0600);

// Send cancellation email
$emailSent = false;
if (!empty($booking['email'])) {
    $name    = $booking['name'] ?? 'Client';
    $email   = $booking['email'];
    $package = $booking['package_name'] ?? $booking['package'] ?? '';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($_ENV['MAIL_FROM'] ?? $_ENV['SMTP_USER'], 'Easy Help Switzerland');
        $mail->addAddress($email, $name);
        $mail->Subject = 'Your consultation has been cancelled';
        $mail->isHTML(true);
        $packageLine = $package ? "<p><strong>Service:</strong> " . htmlspecialchars($package) . "</p>" : '';
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
            <p style="margin:0;color:#ffffff;font-family:'Georgia',serif;font-size:22px;font-weight:400;">Easy Help Switzerland</p>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">Consultation Cancelled</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <p style="margin:0 0 20px;font-size:16px;color:#1a1a2e;">Dear <strong>{$name}</strong>,</p>
            <p style="margin:0 0 24px;font-size:15px;color:#444;line-height:1.6;">
              We are writing to inform you that your consultation has been cancelled.
            </p>
            {$packageLine}
            <p style="margin:20px 0 12px;font-size:14px;color:#555;line-height:1.6;">
              If you have any questions or would like to reschedule, please reply to this email or contact us directly. We apologise for any inconvenience.
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
        $mail->AltBody = "Dear {$name},\n\nYour consultation has been cancelled.\n\nIf you have questions or would like to reschedule, please reply to this email.\n\nBest regards,\nEasy Help Switzerland";
        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
        echo json_encode(['ok' => true, 'email_sent' => false, 'email_error' => $mail->ErrorInfo]);
        exit;
    }
}

echo json_encode(['ok' => true, 'email_sent' => $emailSent]);
