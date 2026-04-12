<?php

require 'vendor/autoload.php';
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$baseUrl = rtrim($_ENV['APP_URL'], '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        header('Location: ' . $baseUrl . '/payment.php?error=invalid_request');
exit;
    }
}

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$booking = $_SESSION['booking'] ?? [];

if (empty($booking)) {
    header('Location: ' . $baseUrl . '/booking.php');
    exit;
}

$package = $booking['package'] ?? 'initial';
$name = $booking['name'] ?? '';
$email = $booking['email'] ?? '';
$phone = $booking['phone'] ?? '';
$location = $booking['location'] ?? '';
$preferred = $booking['preferred'] ?? '';
$message = $booking['message'] ?? '';

$allowedPackages = ['initial', 'review', 'support'];
$allowedPreferred = ['', 'online', 'zurich', 'phone'];

if (!in_array($package, $allowedPackages, true)) {
    header('Location: ' . $baseUrl . '/booking.php');
    exit;
}

if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
}

if (mb_strlen($phone) > 50) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
}

if (mb_strlen($location) > 100) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
}

if (!in_array($preferred, $allowedPreferred, true)) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
}

if (mb_strlen($message) > 2000) {
    header('Location: ' . $baseUrl . '/payment.php');
    exit;
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

$checkout_session = \Stripe\Checkout\Session::create([
  'payment_method_types' => ['card'],
  'mode' => 'payment',

  'customer_email' => $email ?: null,

  'line_items' => [[
    'price_data' => [
      'currency' => 'chf',
      'product_data' => [
        'name' => $selectedPackage['name'],
      ],
      'unit_amount' => $selectedPackage['amount'],
    ],
    'quantity' => 1,
  ]],

  'metadata' => [
    'package' => $package,
    'package_name' => $selectedPackage['name'],
    'price_chf' => number_format($selectedPackage['amount'] / 100, 2, '.', ''),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'location' => $location,
    'preferred' => $preferred,
    'message' => $message,
  ],

  'success_url' => $baseUrl . '/success.php?session_id={CHECKOUT_SESSION_ID}',
  'cancel_url' => $baseUrl . '/payment.php',
]);

header('Location: ' . $checkout_session->url);
exit;