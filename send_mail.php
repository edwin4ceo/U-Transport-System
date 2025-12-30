<?php
// send_mail.php

// 1) Try Composer autoload first
$autoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    // 2) Fallback: support BOTH old-style and new-style PHPMailer folder structures

    // Old-style (your current structure):
    // /PHPMailer/Exception.php, /PHPMailer/PHPMailer.php, /PHPMailer/SMTP.php
    $oldBase = __DIR__ . "/PHPMailer";
    $oldEx   = $oldBase . "/Exception.php";
    $oldPm   = $oldBase . "/PHPMailer.php";
    $oldSm   = $oldBase . "/SMTP.php";

    // New-style:
    // /PHPMailer/src/Exception.php, /PHPMailer/src/PHPMailer.php, /PHPMailer/src/SMTP.php
    $newBase = __DIR__ . "/PHPMailer/src";
    $newEx   = $newBase . "/Exception.php";
    $newPm   = $newBase . "/PHPMailer.php";
    $newSm   = $newBase . "/SMTP.php";

    if (file_exists($oldEx) && file_exists($oldPm) && file_exists($oldSm)) {
        require_once $oldEx;
        require_once $oldPm;
        require_once $oldSm;
    } elseif (file_exists($newEx) && file_exists($newPm) && file_exists($newSm)) {
        require_once $newEx;
        require_once $newPm;
        require_once $newSm;
    } else {
        die("PHPMailer files not found. Please ensure PHPMailer is placed correctly in your project.");
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * SMTP configuration
 * NOTE: Use an App Password if your provider requires it (recommended).
 */
const SMTP_HOST       = "smtp.gmail.com";
const SMTP_PORT       = 587; // 587 for STARTTLS, 465 for SMTPS
const SMTP_USER       = "YOUR_EMAIL@gmail.com";
const SMTP_PASS       = "YOUR_APP_PASSWORD";  // Gmail App Password
const SMTP_FROM_EMAIL = "YOUR_EMAIL@gmail.com";
const SMTP_FROM_NAME  = "U-Transport System";

/**
 * Send password reset OTP email to driver.
 */
function sendDriverOtpEmail(string $toEmail, string $name, string $otp): void
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;

        // Encryption
        if (SMTP_PORT === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = SMTP_PORT;

        // Sender & recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $name ?: "Driver");

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your Password Reset PIN";

        $safeName = htmlspecialchars($name ?: "Driver", ENT_QUOTES, "UTF-8");
        $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, "UTF-8");

        $mail->Body = "
            <p>Hi {$safeName},</p>
            <p>Your password reset PIN is:</p>
            <div style='font-size:28px;font-weight:700;letter-spacing:4px;margin:10px 0;'>{$safeOtp}</div>
            <p>This PIN will expire in 10 minutes.</p>
            <p>If you did not request this, you can ignore this email.</p>
        ";

        $mail->AltBody =
            "Hi {$name},\n\n"
            . "Your password reset PIN is: {$otp}\n\n"
            . "This PIN will expire in 10 minutes.\n"
            . "If you did not request this, you can ignore this email.\n";

        $mail->send();
    } catch (Exception $e) {
        // Optional debug:
        // error_log("Mailer Error: " . $mail->ErrorInfo);
        throw $e;
    }
}
