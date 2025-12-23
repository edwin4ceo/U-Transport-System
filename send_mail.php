<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manual include (because you have /PHPMailer folder, not Composer vendor/)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function sendDriverOtpEmail(string $toEmail, string $toName, string $otp): bool
{
    $mail = new PHPMailer(true);

    // SMTP configuration (MMU student email is commonly Microsoft 365)
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '1231200805@student.mmu.edu.my';   // <-- change to your school email
    $mail->Password   = 'Jun567*';      // <-- change (may fail if SMTP AUTH is disabled / MFA)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender & recipient
    $mail->setFrom($mail->Username, 'U-Transport');
    $mail->addAddress($toEmail, $toName ?: $toEmail);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'U-Transport Driver Password Reset PIN';

    $safeName = htmlspecialchars($toName ?: 'Driver', ENT_QUOTES, 'UTF-8');
    $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
        <p>Hello {$safeName},</p>
        <p>Your password reset PIN is:</p>
        <h2 style='letter-spacing:4px;'>{$safeOtp}</h2>
        <p>This PIN is valid for 10 minutes.</p>
    ";

    $mail->AltBody = "Your PIN: {$otp} (valid for 10 minutes)";

    // Optional debug (turn on only when testing)
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'html';

    $mail->send();
    return true;
}
