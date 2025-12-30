<?php
// send_mail.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure you already have PHPMailer loaded in your project.
// require 'vendor/autoload.php'; // if using composer

function sendDriverOtpEmail(string $toEmail, string $name, string $otp): void
{
    $mail = new PHPMailer(true);

    // Configure your SMTP settings here
    // IMPORTANT: keep credentials in env/config, not hardcoded in production
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($toEmail, $name);

    $mail->isHTML(true);
    $mail->Subject = "Your Password Reset PIN";

    // Keep the content short and clear
    $safeName = htmlspecialchars($name);
    $safeOtp  = htmlspecialchars($otp);

    $mail->Body = "
        <p>Hi {$safeName},</p>
        <p>Your password reset PIN is:</p>
        <h2 style='letter-spacing:2px;'>{$safeOtp}</h2>
        <p>This PIN will expire in 10 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
    ";

    $mail->AltBody = "Hi {$name}, your password reset PIN is {$otp}. This PIN expires in 10 minutes.";

    $mail->send();
}
