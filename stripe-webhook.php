<?php
require 'vendor/autoload.php';

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

        $filename = 'bookings/booking-' . $sessionId . '.json';

        if (!file_exists($filename)) {

            $bookingData = [
    'stripe_session_id' => $sessionId,
    'payment_status' => $session->payment_status,
    'amount_total' => $session->amount_total,
    'currency' => $session->currency,

    'package' => $metadata->package ?? '',
    'name' => $metadata->name ?? '',
    'email' => $metadata->email ?? '',
    'phone' => $metadata->phone ?? '',
    'location' => $metadata->location ?? '',
    'preferred' => $metadata->preferred ?? '',
    'message' => $metadata->message ?? '',

    'created_at' => date('c')
];

            if (!is_dir('bookings')) {
                mkdir('bookings', 0755, true);
            }

            file_put_contents(
                $filename,
                json_encode($bookingData, JSON_PRETTY_PRINT)
            );
        }
    }
}

http_response_code(200);