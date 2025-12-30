<?php
// send_mail.php

// ---- Load PHPMailer (Composer first, fallback to manual files) ----
$autoload = __DIR__ . "/vendor/autoload.php";
if (is_file($autoload)) {
    require_once $autoload;
} else {
    // Manual include (your PHPMailer folder must contain these 3 files)
    require_once __DIR__ . "/PHPMailer/Exception.php";
    require_once __DIR__ . "/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/PHPMailer/SMTP.php";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Toggle SMTP debug here (set to true only when troubleshooting)
const MAIL_DEBUG = false;

/**
 * Load SMTP config from mail_config.php (same directory as this file).
 */
function mailConfig(): array
{
    $path = __DIR__ . "/mail_config.php";
    if (!is_file($path)) {
        throw new Exception("Missing mail_config.php in: " . __DIR__);
    }

    $cfg = require $path;

    $required = ["host", "port", "username", "password", "from_name"];
    foreach ($required as $k) {
        if (!isset($cfg[$k]) || trim((string)$cfg[$k]) === "") {
            throw new Exception("mail_config.php is missing/empty: " . $k);
        }
    }

    // Force FROM email to match username (Gmail SMTP best practice)
    $cfg["from_email"] = $cfg["username"];

    return $cfg;
}

/**
 * Create a configured mailer instance.
 */
function buildMailer(): PHPMailer
{
    $cfg = mailConfig();

    $mail = new PHPMailer(true);

    if (MAIL_DEBUG) {
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = 'html';
    }

    $mail->isSMTP();
    $mail->Host       = $cfg["host"];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg["username"];
    $mail->Password   = $cfg["password"];
    $mail->Port       = (int)$cfg["port"];

    // Encryption
    $mail->SMTPSecure = ((int)$cfg["port"] === 465)
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->CharSet = "UTF-8";
    $mail->setFrom($cfg["from_email"], $cfg["from_name"]);

    return $mail;
}

/**
 * Send OTP email.
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp, string $context = "Password Reset"): void
{
    $mail = buildMailer();

    $toName = $toName ?: "User";

    $safeName    = htmlspecialchars($toName, ENT_QUOTES, "UTF-8");
    $safeOtp     = htmlspecialchars($otp, ENT_QUOTES, "UTF-8");
    $safeContext = htmlspecialchars($context, ENT_QUOTES, "UTF-8");

    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->Subject = "{$safeContext} Verification Code";

    $mail->Body = "
        <h3>Hello {$safeName},</h3>
        <p>You requested a verification code for <b>{$safeContext}</b>.</p>
        <p>Here is your verification code:</p>
        <h2 style='color:#004b82; letter-spacing:5px;'>{$safeOtp}</h2>
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
    ";

    $mail->AltBody =
        "Hello {$toName},\n\n"
        . "Your verification code for {$context} is: {$otp}\n"
        . "This code will expire in 10 minutes.\n\n"
        . "If you did not request this, you can ignore this email.\n";

    $mail->send();
}

function sendDriverOtpEmail(string $toEmail, string $name, string $otp): void
{
    sendOtpEmail($toEmail, $name, $otp, "Driver Password Reset");
}

function sendStudentOtpEmail(string $toEmail, string $name, string $otp): void
{
    sendOtpEmail($toEmail, $name, $otp, "Student Password Reset");
}
