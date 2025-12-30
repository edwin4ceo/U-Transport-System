<?php
// send_mail.php

/**
 * Load PHPMailer (supports both Composer and manual folder).
 */
$autoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    // Manual include (your current PHPMailer folder structure)
    require_once __DIR__ . "/PHPMailer/Exception.php";
    require_once __DIR__ . "/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/PHPMailer/SMTP.php";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Load SMTP config from mail_config.php
 */
function mailConfig(): array
{
    $path = __DIR__ . "/mail_config.php";
    if (!file_exists($path)) {
        throw new Exception("Missing mail_config.php. Please create it in the project root.");
    }

    $cfg = require $path;

    $required = ["host", "port", "username", "password", "from_email", "from_name"];
    foreach ($required as $k) {
        if (!isset($cfg[$k]) || $cfg[$k] === "") {
            throw new Exception("mail_config.php is missing: " . $k);
        }
    }

    return $cfg;
}

/**
 * Create a configured mailer instance.
 */
function buildMailer(): PHPMailer
{
    $cfg = mailConfig();

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $cfg["host"];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg["username"];
    $mail->Password   = $cfg["password"];
    $mail->Port       = (int)$cfg["port"];

    // Encryption
    if ((int)$cfg["port"] === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    // Recommended defaults
    $mail->CharSet = "UTF-8";
    $mail->setFrom($cfg["from_email"], $cfg["from_name"]);

    return $mail;
}

/**
 * Send OTP email (can be used for driver or student).
 *
 * IMPORTANT: Do not print raw error messages to users in production.
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp, string $context = "Password Reset"): void
{
    $mail = buildMailer();

    $toName = $toName ?: "User";
    $safeName = htmlspecialchars($toName, ENT_QUOTES, "UTF-8");
    $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, "UTF-8");
    $safeContext = htmlspecialchars($context, ENT_QUOTES, "UTF-8");

    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->Subject = "{$safeContext} Verification Code";

    $mail->Body = "
        <div style='font-family:Arial,sans-serif; line-height:1.6;'>
            <p>Hi {$safeName},</p>
            <p>You requested a verification code for: <b>{$safeContext}</b>.</p>
            <p>Your verification code is:</p>
            <div style='font-size:28px; font-weight:700; letter-spacing:5px; margin:10px 0; color:#004b82;'>
                {$safeOtp}
            </div>
            <p>This code will expire in <b>10 minutes</b>.</p>
            <p>If you did not request this, you can ignore this email.</p>
        </div>
    ";

    $mail->AltBody =
        "Hi {$toName},\n\n"
        . "Your verification code for {$context} is: {$otp}\n"
        . "This code will expire in 10 minutes.\n\n"
        . "If you did not request this, you can ignore this email.\n";

    $mail->send();
}

/**
 * Backward-compatible wrapper for your driver flow (so you don't need to rename everywhere).
 */
function sendDriverOtpEmail(string $toEmail, string $name, string $otp): void
{
    sendOtpEmail($toEmail, $name, $otp, "Driver Password Reset");
}

/**
 * Optional wrapper for student flow if needed.
 */
function sendStudentOtpEmail(string $toEmail, string $name, string $otp): void
{
    sendOtpEmail($toEmail, $name, $otp, "Student Password Reset");
}
