<?php

require 'vendor/autoload.php';
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$booking = $_SESSION['booking'] ?? [];

if (empty($booking)) {
    header('Location: http://localhost:8000/booking.php');
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
    header('Location: http://localhost:8000/booking.php');
    exit;
}

if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

if (mb_strlen($phone) > 50) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

if (mb_strlen($location) > 100) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

if (!in_array($preferred, $allowedPreferred, true)) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

if (mb_strlen($message) > 2000) {
    header('Location: http://localhost:8000/payment.php');
    exit;
}

$packages = [
  'initial' => [
    'name' => 'Initial consultation',
    'amount' => 5900,
  ],
  'review' => [
    'name' => 'Consultation + review',
    'amount' => 12900,
  ],
  'support' => [
    'name' => 'Relocation support',
    'amount' => 29000,
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

  'success_url' => 'http://localhost:8000/success.php?session_id={CHECKOUT_SESSION_ID}',
  'cancel_url' => 'http://localhost:8000/payment.php',
]);

header('Location: ' . $checkout_session->url);
exit;