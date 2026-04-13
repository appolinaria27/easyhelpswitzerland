<?php
require_once __DIR__ . '/security.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if (!empty($_POST['website'])) {
    exit;
}

    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        header('Location: free-consultation.php?error=invalid_request');
        exit;
    }
}

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$location    = trim($_POST['location'] ?? '');
$topic       = trim($_POST['topic'] ?? '');
$messageText = trim($_POST['message'] ?? '');

// Validate required fields and email format
if ($name === '' || $email === '') {
    header('Location: free-consultation.php?error=required_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: free-consultation.php?error=invalid_email');
    exit;
}

// Enforce length limits
if (mb_strlen($name) > 100 || mb_strlen($phone) > 50 || mb_strlen($location) > 100
    || mb_strlen($topic) > 200 || mb_strlen($messageText) > 2000) {
    header('Location: free-consultation.php?error=invalid_request');
    exit;
}

// Strip newlines from name to prevent email header injection
$safeName = str_replace(["\r", "\n"], ' ', $name);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($_ENV['MAIL_FROM'], 'Polina Kravtsova Legal Advisory');
    $mail->addAddress($_ENV['ADMIN_EMAIL']);
    $mail->addReplyTo($email, $safeName);

    $mail->Subject = 'New Free Consultation Request';
    $mail->Body =
        "New free consultation request received\n\n" .
        "Name: $safeName\n" .
        "Email: $email\n" .
        "Phone / WhatsApp: $phone\n" .
        "Current location: $location\n" .
        "Topic: $topic\n" .
        "Message:\n$messageText\n";

    $mail->send();

    echo 'Request is sent. Thank you!';
} catch (Exception $e) {
    error_log('Consultation mail error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo 'Request is not sent, please try again later.';
}