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

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

// CSRF
$csrf = $body['csrf'] ?? '';
if (empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'error' => 'CSRF mismatch']);
    exit;
}

$id        = preg_replace('/[^a-f0-9]/', '', $body['id'] ?? '');
$datetime  = trim($body['datetime'] ?? '');
$sendMail  = !empty($body['send_mail']);
$emailOnly = !empty($body['email_only']); // send email using already-saved termin, no datetime required

if (!$id || (!$datetime && !$emailOnly)) {
    echo json_encode(['ok' => false, 'error' => 'Missing data']);
    exit;
}

// Load .env
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

// Find the booking
$booking = null;
$searchDirs = [
    __DIR__ . '/bookings'          => 'booking-*.json',
    __DIR__ . '/pending-bookings'  => 'booking-*.json',
    __DIR__ . '/free-consultations'=> 'consult-*.json',
];
foreach ($searchDirs as $dir => $pattern) {
    foreach (glob($dir . '/' . $pattern) as $f) {
        $data = json_decode(file_get_contents($f), true);
        if (($data['internal_booking_id'] ?? '') === $id) {
            $booking = $data;
            break 2;
        }
    }
}

if (!$booking) {
    echo json_encode(['ok' => false, 'error' => 'Booking not found']);
    exit;
}

// Load/save admin-data note
$dataDir = __DIR__ . '/admin-data';
if (!is_dir($dataDir)) mkdir($dataDir, 0700, true);
$noteFile = $dataDir . '/' . $id . '.json';
$note = file_exists($noteFile) ? (json_decode(file_get_contents($noteFile), true) ?? []) : [];

if (!$emailOnly) {
    // Parse and save the datetime
    try {
        $dt = new DateTime($datetime);
        $terminISO  = $dt->format('c');
        $terminDay  = $dt->format('l, d.m.Y');
        $terminTime = $dt->format('H:i');
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Invalid datetime']);
        exit;
    }
    $note['termin']     = $terminISO;
    $note['status']     = $note['status'] ?? 'confirmed';
    $note['updated_at'] = date('c');
    file_put_contents($noteFile, json_encode($note, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
} else {
    // Email-only: read saved termin
    $savedTermin = $note['termin'] ?? '';
    if (!$savedTermin) {
        echo json_encode(['ok' => false, 'error' => 'No saved termin found']);
        exit;
    }
    try {
        $dt = new DateTime($savedTermin);
        $terminDay  = $dt->format('l, d.m.Y');
        $terminTime = $dt->format('H:i');
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Invalid saved termin']);
        exit;
    }
}

// Send confirmation email
if ($sendMail && !empty($booking['email'])) {

    $clientName  = $booking['name'] ?? 'Client';
    $clientEmail = $booking['email'];
    $package     = $booking['package_name'] ?? $booking['package'] ?? '';
    $format      = $booking['preferred'] ?? '';

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
        $mail->addAddress($clientEmail, $clientName);

        $mail->Subject = "Your consultation is confirmed — {$terminDay} at {$terminTime}";

        $formatLine = $format ? "<p><strong>Format:</strong> " . htmlspecialchars($format) . "</p>" : '';
        $packageLine = $package ? "<p><strong>Service:</strong> " . htmlspecialchars($package) . "</p>" : '';

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
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">Consultation Confirmed</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <p style="margin:0 0 20px;font-size:16px;color:#1a1a2e;">Dear <strong>{$clientName}</strong>,</p>
            <p style="margin:0 0 28px;font-size:15px;color:#444;line-height:1.6;">
              Your consultation with Easy Help Switzerland has been scheduled. Please see the details below:
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:24px;margin-bottom:28px;">
              <tr>
                <td style="padding:8px 0;">
                  <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Date</p>
                  <p style="margin:4px 0 0;font-size:17px;font-weight:600;color:#1a1a2e;">{$terminDay}</p>
                </td>
              </tr>
              <tr>
                <td style="padding:8px 0;">
                  <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Time</p>
                  <p style="margin:4px 0 0;font-size:17px;font-weight:600;color:#1a1a2e;">{$terminTime}</p>
                </td>
              </tr>
              {$formatLine}
              {$packageLine}
            </table>
            <p style="margin:0 0 12px;font-size:14px;color:#555;line-height:1.6;">
              If you need to reschedule or have any questions, simply reply to this email.
            </p>
            <p style="margin:0;font-size:14px;color:#555;line-height:1.6;">
              We look forward to speaking with you.
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

        $mail->AltBody = "Dear {$clientName},\n\nYour consultation is confirmed.\n\nDate: {$terminDay}\nTime: {$terminTime}\n\nIf you need to reschedule, please reply to this email.\n\nBest regards,\nEasy Help Switzerland";

        $mail->send();
        echo json_encode(['ok' => true, 'email_sent' => true]);
    } catch (Exception $e) {
        // Saved but email failed — still OK, report error
        echo json_encode(['ok' => true, 'email_sent' => false, 'email_error' => $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['ok' => true, 'email_sent' => false]);
}
