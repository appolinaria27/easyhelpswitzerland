<?php
require_once __DIR__ . '/security.php';
require 'vendor/autoload.php';

$logDir = __DIR__ . '/logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}

file_put_contents(
    $logDir . '/webhook-log.txt',
    date('c') . " webhook called\n",
    FILE_APPEND | LOCK_EX
);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

function createWebhookMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
    return $mail;
}

/**
 * Send a record to Airtable. Silently logs on failure — never blocks the booking.
 */
function airtableInsert(string $tableEnvKey, array $fields): void
{
    $apiKey  = $_ENV['AIRTABLE_API_KEY']  ?? '';
    $baseId  = $_ENV['AIRTABLE_BASE_ID']  ?? '';
    $table   = $_ENV[$tableEnvKey]        ?? '';
    if (!$apiKey || !$baseId || !$table) return;

    $url  = "https://api.airtable.com/v0/{$baseId}/" . rawurlencode($table);
    $body = json_encode(['fields' => $fields]);
    $ctx  = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        error_log('Airtable insert failed for table env key: ' . $tableEnvKey);
    }
}

$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
if (!$endpoint_secret) {
    error_log('Stripe webhook: STRIPE_WEBHOOK_SECRET not configured');
    http_response_code(500);
    exit;
}

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

if ($event->type === 'checkout.session.completed') {
    // Idempotency: use flock to prevent duplicate processing if Stripe retries
    $eventsDir = __DIR__ . '/stripe-events';
    if (!is_dir($eventsDir)) mkdir($eventsDir, 0700, true);
    $eventFlagFile = $eventsDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $event->id) . '.lock';
    $lockFp = fopen($eventFlagFile, 'c');
    if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
        // Another process is handling this event right now
        if ($lockFp) fclose($lockFp);
        http_response_code(200);
        exit();
    }
    // Check if it was already fully processed (file has content)
    fseek($lockFp, 0);
    if (fread($lockFp, 4) !== '') {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
        http_response_code(200);
        exit();
    }
    chmod($eventFlagFile, 0600);

    $session = $event->data->object;
    $metadata = $session->metadata;

    if ($session->payment_status === 'paid') {
        $sessionId = $session->id;
        $internalBookingId = $metadata->internal_booking_id ?? '';

        if ($internalBookingId === '') {
            error_log('Webhook error: missing internal_booking_id');
            http_response_code(400);
            exit();
        }

        $safeInternalBookingId = preg_replace('/[^a-zA-Z0-9_-]/', '', $internalBookingId);
        $pendingFile = __DIR__ . '/pending-bookings/booking-' . $safeInternalBookingId . '.json';

        if (!file_exists($pendingFile)) {
            error_log('Webhook error: pending booking file not found: ' . $pendingFile);
            http_response_code(404);
            exit();
        }

        $pendingJson = file_get_contents($pendingFile);
        $pendingBooking = json_decode($pendingJson, true);

        if (!is_array($pendingBooking)) {
            error_log('Webhook error: invalid pending booking JSON: ' . $pendingFile);
            http_response_code(500);
            exit();
        }

        $bookingsDir = __DIR__ . '/bookings';
        if (!is_dir($bookingsDir)) {
            mkdir($bookingsDir, 0750, true);
        }

        $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        $archiveFile = $bookingsDir . '/booking-' . $safeSessionId . '.json';

        if (file_exists($archiveFile)) {
            $existingJson = file_get_contents($archiveFile);
            $bookingData = json_decode($existingJson, true);

            if (!is_array($bookingData)) {
                error_log('Webhook booking JSON decode error: ' . $archiveFile);
                http_response_code(500);
                exit();
            }
        } else {
            $bookingData = [
                'internal_booking_id' => $internalBookingId,
                'stripe_session_id' => $sessionId,
                'payment_status' => $session->payment_status,
                'amount_total' => $session->amount_total,
                'currency' => $session->currency,

                'package' => $pendingBooking['package'] ?? ($metadata->package ?? ''),
                'package_name' => $pendingBooking['package_name'] ?? ($metadata->package_name ?? ''),
                'price_chf' => $pendingBooking['price_chf'] ?? ($metadata->price_chf ?? ''),

                'name' => $pendingBooking['name'] ?? '',
                'email' => $pendingBooking['email'] ?? '',
                'phone' => $pendingBooking['phone'] ?? '',
                'location' => $pendingBooking['location'] ?? '',
                'preferred' => $pendingBooking['preferred'] ?? '',
                'message' => $pendingBooking['message'] ?? '',

                'created_at' => $pendingBooking['created_at'] ?? date('c'),
                'paid_at' => date('c'),
                'admin_email_sent' => false,
                'client_email_sent' => false
            ];

            if (!is_dir(__DIR__ . '/bookings')) {
                mkdir(__DIR__ . '/bookings', 0700, true);
            }

            $saved = file_put_contents(
                $archiveFile,
                json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );

            if ($saved === false) {
                error_log('Webhook archive write error: ' . $archiveFile);
                http_response_code(500);
                exit();
            }
            chmod($archiveFile, 0600);
        }

        // Helper: persist booking state immediately after each change (idempotency)
        $saveBooking = function () use (&$bookingData, $archiveFile) {
            file_put_contents(
                $archiveFile,
                json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        };

        // Admin email
        if (($bookingData['admin_email_sent'] ?? false) !== true) {
            try {
                $mail = createWebhookMailer();
                $mail->addAddress($_ENV['ADMIN_EMAIL']);
                if (!empty($bookingData['email'])) {
                    $safeName = str_replace(["\r", "\n"], ' ', $bookingData['name'] ?: 'Client');
                    $mail->addReplyTo($bookingData['email'], $safeName);
                }
                $mail->Subject = 'New Paid Booking Received';
                $mail->isHTML(true);
                $adminPackage  = htmlspecialchars($bookingData['package_name'] ?? '');
                $adminPrice    = htmlspecialchars($bookingData['price_chf'] ?? '');
                $adminName     = htmlspecialchars($bookingData['name'] ?? '');
                $adminEmail    = htmlspecialchars($bookingData['email'] ?? '');
                $adminPhone    = htmlspecialchars($bookingData['phone'] ?? '');
                $adminLocation = htmlspecialchars($bookingData['location'] ?? '');
                $adminFormat   = htmlspecialchars($bookingData['preferred'] ?? '');
                $adminMessage  = htmlspecialchars($bookingData['message'] ?? '');
                $adminSession  = htmlspecialchars($bookingData['stripe_session_id'] ?? '');
                $adminPaidAt   = htmlspecialchars($bookingData['paid_at'] ?? '');
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
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">New Paid Booking</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:20px 24px;">
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Package</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminPackage}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Price CHF</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminPrice}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Name</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminName}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Email</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminEmail}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Phone / WhatsApp</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminPhone}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Location</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminLocation}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Format</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminFormat}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Message</p>
                <p style="margin:4px 0 0;font-size:15px;color:#1a1a2e;line-height:1.6;">{$adminMessage}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Stripe Session ID</p>
                <p style="margin:4px 0 0;font-size:13px;font-weight:600;color:#1a1a2e;word-break:break-all;">{$adminSession}</p>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <p style="margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em;">Paid At</p>
                <p style="margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e;">{$adminPaidAt}</p>
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
                    "Submitted at: {$bookingData['created_at']}\n" .
                    "Paid at: {$bookingData['paid_at']}\n";
                $mail->send();
                $bookingData['admin_email_sent'] = true;
                $saveBooking(); // persist flag immediately so retries don't resend
            } catch (Exception $e) {
                error_log('Webhook admin mail error: ' . $e->getMessage());
            }
        }

        // Client confirmation email
        if (!empty($bookingData['email']) && ($bookingData['client_email_sent'] ?? false) !== true) {
            try {
                $clientName    = $bookingData['name'] ?: 'Client';
                $clientPackage = $bookingData['package_name'] ?? '';
                $clientPrice   = $bookingData['price_chf'] ?? '';
                $clientFormat  = $bookingData['preferred'] ?? '';

                $packageLine = $clientPackage ? "<tr><td style='padding:8px 0'><p style='margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em'>Service</p><p style='margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e'>" . htmlspecialchars($clientPackage) . "</p></td></tr>" : '';
                $priceLineTxt = $clientPrice ? "<tr><td style='padding:8px 0'><p style='margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em'>Amount paid</p><p style='margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e'>CHF " . htmlspecialchars($clientPrice) . "</p></td></tr>" : '';
                $formatLine   = $clientFormat ? "<tr><td style='padding:8px 0'><p style='margin:0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.08em'>Format</p><p style='margin:4px 0 0;font-size:15px;font-weight:600;color:#1a1a2e'>" . htmlspecialchars($clientFormat) . "</p></td></tr>" : '';

                $clientMail = createWebhookMailer();
                $clientMail->addAddress($bookingData['email'], $clientName);
                $clientMail->Subject = 'Booking confirmed — Easy Help Switzerland';
                $clientMail->isHTML(true);
                $clientMail->CharSet = 'UTF-8';
                $clientMail->Body = <<<HTML
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
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px;letter-spacing:.1em;text-transform:uppercase;">Booking Confirmed</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 28px;">
            <p style="margin:0 0 20px;font-size:16px;color:#1a1a2e;">Dear <strong>{$clientName}</strong>,</p>
            <p style="margin:0 0 28px;font-size:15px;color:#444;line-height:1.6;">
              Thank you — your payment was received and your consultation request is confirmed. I will be in touch shortly to schedule a time that works for you.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff;border-radius:12px;padding:20px 24px;margin-bottom:28px;">
              {$packageLine}
              {$priceLineTxt}
              {$formatLine}
            </table>
            <p style="margin:0 0 12px;font-size:14px;color:#555;line-height:1.6;">
              If you have any questions in the meantime, simply reply to this email.
            </p>
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
                $clientMail->AltBody =
                    "Dear {$clientName},\n\n" .
                    "Your payment was received and your consultation is confirmed.\n\n" .
                    ($clientPackage ? "Service: {$clientPackage}\n" : '') .
                    ($clientPrice   ? "Amount paid: CHF {$clientPrice}\n" : '') .
                    ($clientFormat  ? "Format: {$clientFormat}\n" : '') .
                    "\nI will be in touch shortly to arrange a time.\n\n" .
                    "Best regards,\nPolina Kravtsova\nEasy Help Switzerland";
                $clientMail->send();
                $bookingData['client_email_sent'] = true;
                $saveBooking();
            } catch (Exception $e) {
                error_log('Webhook client mail error: ' . $e->getMessage());
            }
        }

        // Sync to Airtable
        airtableInsert('AIRTABLE_TABLE_BOOKINGS', [
            'Name'       => $bookingData['name']         ?? '',
            'Email'      => $bookingData['email']        ?? '',
            'Phone'      => $bookingData['phone']        ?? '',
            'Package'    => $bookingData['package_name'] ?? ($bookingData['package'] ?? ''),
            'Format'     => $bookingData['preferred']    ?? '',
            'Location'   => $bookingData['location']     ?? '',
            'Message'    => $bookingData['message']      ?? '',
            'Amount CHF' => isset($bookingData['amount_chf']) ? (float)$bookingData['amount_chf'] : null,
            'Booking ID' => $bookingData['internal_booking_id'] ?? '',
            'Created At' => $bookingData['created_at']   ?? date('c'),
            'Status'     => 'paid',
        ]);

        // Clean up pending file
        if (file_exists($pendingFile)) {
            unlink($pendingFile);
        }

        // Mark idempotency lock as fully processed
        fwrite($lockFp, date('c'));
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

http_response_code(200);