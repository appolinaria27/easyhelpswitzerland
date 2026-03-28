<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$package = $_GET['package'] ?? 'initial';
$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
$phone = $_GET['phone'] ?? '';
$location = $_GET['location'] ?? '';
$preferred = $_GET['preferred'] ?? '';
$message = $_GET['message'] ?? '';

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
  'cancel_url' => 'http://localhost:8000/payment.html?' . http_build_query([
    'package' => $package,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'location' => $location,
    'preferred' => $preferred,
    'message' => $message,
  ]),
]);

header('Location: ' . $checkout_session->url);
exit;