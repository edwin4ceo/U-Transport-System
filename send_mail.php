<?php
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function sendDriverOtpEmail($toEmail, $driverName, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = getenv('MAIL_DEBUG') ? 2 : 0;
        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = getenv('MAIL_USER') ?: 'soonkit0726@gmail.com';
        $mail->Password   = getenv('MAIL_PASS') ?: 'oprh ldrk nwvg eyiv';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;
        $mail->SMTPAutoTLS = true;

        if (getenv('MAIL_DEBUG')) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $mail->setFrom($mail->Username, 'U-Transport System');
        $mail->addAddress($toEmail, $driverName);

        $safeName = htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8');

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Verification Code';

        $mail->Body    = "
            <div style='font-family: \"Segoe UI\", Arial, sans-serif; font-size: 16px; color: #333; line-height: 1.6;'>
                <p>Hello <strong>$safeName</strong>,</p>

                <p>You have requested to reset your password.</p>

                <p>Here is your verification code:</p>

                <h2 style='color: #005A9C; font-size: 32px; font-weight: bold; margin: 20px 0;'>
                    $otp
                </h2>

                <p>This code will expire in 10 minutes.</p>

                <br>
                <p style='color: #666; font-size: 14px;'>
                    If you did not request this change, please ignore this email.
                </p>
            </div>
        ";

        $mail->AltBody = "Hello $safeName, your verification code is: $otp. This code expires in 10 minutes.";

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        error_log("Mailer ErrorInfo: " . $mail->ErrorInfo);
        throw new \Exception("Email sending failed: " . $e->getMessage());
    }
}
?>