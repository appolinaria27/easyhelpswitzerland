<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$topic = trim($_POST['topic'] ?? '');
$messageText = trim($_POST['message'] ?? '');

if ($name === '' || $email === '') {
    die('Не заполнены обязательные поля.');
}

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
    $mail->addReplyTo($email, $name);

 $mail->Subject = 'New Free Consultation Request';

$mail->Body =
    "New free consultation request received\n\n" .
    "Name: $name\n" .
    "Email: $email\n" .
    "Phone / WhatsApp: $phone\n" .
    "Current location: $location\n" .
    "Topic: $topic\n" .
    "Message:\n$messageText\n";

    $mail->send();

    echo 'Request is sent. Thank you!';
} catch (Exception $e) {
    echo 'Request is not sent, please try again: ' . $mail->ErrorInfo;
}