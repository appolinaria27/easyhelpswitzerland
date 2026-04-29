<?php
require_once __DIR__ . '/security.php';
require 'vendor/autoload.php';


session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$baseUrl = rtrim($_ENV['APP_URL'], '/');

function checkoutFail(string $reason, string $redirectUrl): void
{
    $logMsg = '[' . date('c') . '] Checkout rejected: ' . $reason . PHP_EOL;
    file_put_contents(__DIR__ . '/logs/checkout-errors.log', $logMsg, FILE_APPEND | LOCK_EX);
    error_log('Checkout rejected: ' . $reason);
    header('Location: ' . $redirectUrl);
    exit;
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    checkoutFail('csrf validation failed', $baseUrl . '/payment.php?error=invalid_request');
}


\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$booking = $_SESSION['booking'] ?? [];

if (empty($booking)) {
    checkoutFail('session booking empty', $baseUrl . '/payment.php?error=invalid_request');
}

$package  = $booking['package']   ?? 'initial';
$name     = $booking['name']      ?? '';
$email    = $booking['email']     ?? '';
$phone    = $booking['phone']     ?? '';
$location = $booking['location']  ?? '';
$preferred= $booking['preferred'] ?? '';
$message  = $booking['message']   ?? '';

$allowedPackages  = ['initial', 'review', 'support'];
$allowedPreferred = ['', 'online', 'zurich', 'phone'];

if (!in_array($package, $allowedPackages, true)) {
    checkoutFail('invalid package: ' . $package, $baseUrl . '/booking.php');
}

if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    checkoutFail('invalid name: ' . $name, $baseUrl . '/payment.php?error=invalid_request');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    checkoutFail('invalid email: ' . $email, $baseUrl . '/payment.php?error=invalid_request');
}

if (mb_strlen($phone) > 50) {
    checkoutFail('phone too long', $baseUrl . '/payment.php?error=invalid_request');
}

if (mb_strlen($location) > 100) {
    checkoutFail('location too long', $baseUrl . '/payment.php?error=invalid_request');
}

if (!in_array($preferred, $allowedPreferred, true)) {
    checkoutFail('invalid preferred: [' . $preferred . ']', $baseUrl . '/payment.php?error=invalid_request');
}

if (mb_strlen($message) > 2000) {
    checkoutFail('message too long', $baseUrl . '/payment.php?error=invalid_request');
}

$packages = [
  'initial' => [
    'name' => 'Quick consultation',
    'amount' => 7900,
  ],
  'review' => [
    'name' => 'Relocation help',
    'amount' => 18900,
  ],
  'support' => [
    'name' => 'Relocation support',
    'amount' => 34900,
  ],
];

$selectedPackage = $packages[$package] ?? $packages['initial'];

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitDir = __DIR__ . '/rate-limit';

if (!is_dir($rateLimitDir)) {
    mkdir($rateLimitDir, 0750, true);
}

$rateLimitKey = hash('sha256', 'checkout:' . $ip);
$rateLimitFile = $rateLimitDir . '/' . $rateLimitKey . '.json';

$now = time();
$windowSeconds = 3600; // 1 hour
$maxRequests = 10;     // max 10 checkout attempts per hour

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
    checkoutFail('checkout rate limit exceeded', $baseUrl . '/payment.php?error=rate_limited');
}

$requests[] = $now;

$saved = file_put_contents(
    $rateLimitFile,
    json_encode(array_values($requests), JSON_PRETTY_PRINT),
    LOCK_EX
);

if ($saved === false) {
    checkoutFail('rate limit write failed', $baseUrl . '/payment.php?error=system_error');
}

$internalBookingId = bin2hex(random_bytes(16));

$pendingBooking = [
    'internal_booking_id' => $internalBookingId,
    'package' => $package,
    'package_name' => $selectedPackage['name'],
    'price_chf' => number_format($selectedPackage['amount'] / 100, 2, '.', ''),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'location' => $location,
    'preferred' => $preferred,
    'message' => $message,
    'created_at' => date('c'),
    'status' => 'pending_payment',
];

$pendingDir = __DIR__ . '/pending-bookings';

if (!is_dir($pendingDir)) {
    mkdir($pendingDir, 0750, true);
}

$pendingFile = $pendingDir . '/booking-' . $internalBookingId . '.json';

if (file_exists($pendingFile)) {
    checkoutFail('pending booking file already exists', $baseUrl . '/payment.php?error=system_error');
}

$result = file_put_contents(
    $pendingFile,
    json_encode($pendingBooking, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

if ($result === false) {
    checkoutFail('failed to write pending booking file', $baseUrl . '/payment.php?error=system_error');
}

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'customer_email' => $email ?: null,
        'line_items' => [[
            'price_data' => [
                'currency' => 'chf',
                'product_data' => ['name' => $selectedPackage['name']],
                'unit_amount' => $selectedPackage['amount'],
            ],
            'quantity' => 1,
        ]],
        'metadata' => [
            'internal_booking_id' => $internalBookingId,
            'package' => $package,
            'package_name' => $selectedPackage['name'],
            'price_chf' => number_format($selectedPackage['amount'] / 100, 2, '.', ''),
        ],
        'success_url' => $baseUrl . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $baseUrl . '/payment.php',
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe API error: ' . $e->getMessage() . ' | Code: ' . $e->getStripeCode() . ' | HTTP: ' . $e->getHttpStatus());
    // Clean up pending booking file since Stripe session was not created
    if (file_exists($pendingFile)) {
        unlink($pendingFile);
    }
    checkoutFail('stripe api error', $baseUrl . '/payment.php?error=payment_unavailable');
} catch (\Exception $e) {
    error_log('Checkout unexpected error [' . get_class($e) . ']: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    checkoutFail('unexpected checkout error', $baseUrl . '/payment.php?error=system_error');
}

$_SESSION['last_checkout_session_id'] = $checkout_session->id;

header('Location: ' . $checkout_session->url);
exit;