<?php
require_once __DIR__ . '/security.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

function consultationFail(string $reason, string $errorCode): void
{
    error_log('Consultation form rejected: ' . $reason);
    header('Location: free-consultation.php?error=' . urlencode($errorCode));
    exit;
}

// honeypot (боты заполняют это поле)
if (!empty($_POST['website'])) {
    exit; // тихо игнорируем
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    consultationFail('csrf validation failed', 'invalid_request');
}

$formStartedAt = (int)($_POST['form_started_at'] ?? 0);
$sessionStartedAt = (int)($_SESSION['consultation_form_started_at'] ?? 0);

if ($formStartedAt <= 0 || $sessionStartedAt <= 0 || abs($formStartedAt - $sessionStartedAt) > 10) {
    consultationFail('form timestamp mismatch or missing', 'invalid_request');
}

if ((time() - $sessionStartedAt) < 3) {
    consultationFail('form submitted too quickly', 'too_fast');
}

unset($_SESSION['consultation_form_started_at']);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitDir = __DIR__ . '/rate-limit';

if (!is_dir($rateLimitDir)) {
    mkdir($rateLimitDir, 0750, true);
}

$rateLimitKey = hash('sha256', 'consultation:' . $ip);
$rateLimitFile = $rateLimitDir . '/' . $rateLimitKey . '.json';

$now = time();
$windowSeconds = 600; // 10 минут
$maxRequests = 5;     // максимум 5 отправок за 10 минут

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
    consultationFail('rate limit exceeded', 'rate_limited');
}

$requests[] = $now;

$saved = file_put_contents(
    $rateLimitFile,
    json_encode(array_values($requests), JSON_PRETTY_PRINT),
    LOCK_EX
);

if ($saved === false) {
    consultationFail('rate limit write failed', 'invalid_request');
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

if ($phone !== '' && mb_strlen($phone) > 50) {
    consultationFail('invalid phone', 'invalid_phone');
}

if ($location !== '' && mb_strlen($location) > 100) {
    consultationFail('invalid location', 'invalid_location');
}

if ($topic !== '' && mb_strlen($topic) > 150) {
    consultationFail('invalid topic', 'invalid_topic');
}

if ($messageText !== '' && mb_strlen($messageText) > 2000) {
    consultationFail('invalid message', 'invalid_message');
}

$name = str_replace(["\r", "\n"], ' ', $name);
$email = str_replace(["\r", "\n"], '', $email);

function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) $_ENV['SMTP_PORT'];
    $mail->CharSet    = 'UTF-8';

try {
    $mail = createMailer();
    $mail->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
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

    // Send confirmation email to the customer
    $confirmation = new PHPMailer(true);
    $confirmation->isSMTP();
    $confirmation->Host       = $_ENV['SMTP_HOST'];
    $confirmation->SMTPAuth   = true;
    $confirmation->Username   = $_ENV['SMTP_USER'];
    $confirmation->Password   = $_ENV['SMTP_PASS'];
    $confirmation->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $confirmation->Port       = (int) $_ENV['SMTP_PORT'];
    $confirmation->CharSet    = 'UTF-8';

    $confirmation->setFrom($_ENV['MAIL_FROM'], 'Easy Help Switzerland');
    $confirmation->addAddress($email, $safeName);

    $confirmation->Subject = 'We received your consultation request';
    $confirmation->Body =
        "Dear $safeName,\n\n" .
        "Thank you for reaching out! We have received your free consultation request and will get back to you within 24 hours.\n\n" .
        "Here is a summary of your request:\n" .
        "Topic: $topic\n" .
        "Message:\n$messageText\n\n" .
        "Best regards,\nPolina Kravtsova\nEasy Help Switzerland";

    $confirmation->send();

    echo 'Request is sent. Thank you!';
} catch (Exception $e) {
    error_log('Consultation mail error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo 'Request is not sent, please try again later.';
}
}