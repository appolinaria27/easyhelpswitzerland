<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

$payload = @file_get_contents('php://input');
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
        $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        $filename = __DIR__ . '/bookings/booking-' . $safeSessionId . '.json';

        if (!file_exists($filename)) {
            $bookingData = [
                'stripe_session_id' => $sessionId,
                'payment_status' => $session->payment_status,
                'amount_total' => $session->amount_total,
                'currency' => $session->currency,

                'package' => $metadata->package ?? '',
                'package_name' => $metadata->package_name ?? '',
                'price_chf' => $metadata->price_chf ?? '',
                'name' => $metadata->name ?? '',
                'email' => $metadata->email ?? '',
                'phone' => $metadata->phone ?? '',
                'location' => $metadata->location ?? '',
                'preferred' => $metadata->preferred ?? '',
                'message' => $metadata->message ?? '',

                'created_at' => date('c'),
                'admin_email_sent' => false
            ];

            if (!is_dir(__DIR__ . '/bookings')) {
                mkdir(__DIR__ . '/bookings', 0750, true);
            }

            file_put_contents(
                $filename,
                json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        } else {
            $existingJson = file_get_contents($filename);
            $bookingData = json_decode($existingJson, true);

            if (!is_array($bookingData)) {
                error_log('Webhook booking JSON decode error: ' . $filename);
                http_response_code(500);
                exit();
            }
        }

        if (($bookingData['admin_email_sent'] ?? false) !== true) {
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

                $bookingData['admin_email_sent'] = true;

                file_put_contents(
                    $filename,
                    json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
            } catch (Exception $e) {
                error_log('Webhook mail error: ' . $mail->ErrorInfo);
            }
        }
    }
}

http_response_code(200);