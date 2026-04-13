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

$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

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

            file_put_contents(
                $filename,
                json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            if ($saved === false) {
                error_log('Webhook archive write error: ' . $archiveFile);
                http_response_code(500);
                exit();
            }
        }

        // Admin email - separate try/catch so a failure does not block the client email
        if (($bookingData['admin_email_sent'] ?? false) !== true) {
            try {
                $mail = createWebhookMailer();
                $mail->addAddress($_ENV['ADMIN_EMAIL']);

                if (!empty($bookingData['email'])) {
                    // Strip newlines from name to prevent email header injection
                    $safeName = str_replace(["\r", "\n"], ' ', $bookingData['name'] ?: 'Client');
                    $mail->addReplyTo($bookingData['email'], $safeName);
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
                    "Submitted at: {$bookingData['created_at']}\n" .
                    "Paid at: {$bookingData['paid_at']}\n";

                $mail->send();
                $bookingData['admin_email_sent'] = true;
            } catch (Exception $e) {
                error_log('Webhook admin mail error: ' . $e->getMessage());
            }
        }

        // Client confirmation email - separate try/catch so it always runs
        if (
            !empty($bookingData['email']) &&
            ($bookingData['client_email_sent'] ?? false) !== true
        ) {
            try {
                $clientMail = createWebhookMailer();
                $clientMail->addAddress($bookingData['email'], $bookingData['name'] ?: 'Client');
                $clientMail->Subject = 'Your booking is confirmed';
                $clientMail->Body =
                    "Hello " . ($bookingData['name'] ?: 'Client') . ",\n\n" .
                    "Thank you. Your paid booking has been received successfully.\n\n" .
                    "Booking details:\n" .
                    "Package: {$bookingData['package_name']}\n" .
                    "Price: CHF {$bookingData['price_chf']}\n" .
                    "Preferred consultation format: {$bookingData['preferred']}\n" .
                    "Submitted at: {$bookingData['created_at']}\n" .
                    "Paid at: {$bookingData['paid_at']}\n\n" .
                    "We will contact you shortly regarding the next steps.\n\n" .
                    "Best regards,\n" .
                    "Polina Kravtsova";

                $clientMail->send();
                $bookingData['client_email_sent'] = true;
            } catch (Exception $e) {
                error_log('Webhook client mail error: ' . $e->getMessage());
            }
        }

        // Save final booking state and clean up pending file
        $saved = file_put_contents(
            $archiveFile,
            json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        if ($saved === false) {
            error_log('Webhook archive update error: ' . $archiveFile);
            http_response_code(500);
            exit();
        }

        if (file_exists($pendingFile)) {
            unlink($pendingFile);
        }
    }
}

http_response_code(200);