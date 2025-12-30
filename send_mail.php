<?php
// send_mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendDriverOtpEmail($toEmail, $driverName, $otp) {
    $mail = new PHPMailer(true);

    try { 
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kelvinng051129@gmail.com';
        $mail->Password   = 'szvd kjeo jwfx bxnh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Sender and Recipient ---
        $mail->setFrom('no-reply@yourcompany.com', 'Driver Support');
        $mail->addAddress($toEmail, $driverName);

        // --- Email Content ---
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        
        $mail->Body    = "
            <div style='font-family:Arial, sans-serif; padding:20px; color:#333;'>
                <h2 style='color:#f39c12;'>Reset Password Request</h2>
                <p>Hello <strong>$driverName</strong>,</p>
                <p>You requested to reset your password. Use the OTP below to complete the process:</p>
                <h1 style='background:#eee; display:inline-block; padding:10px 20px; letter-spacing:5px; color:#005A9C;'>$otp</h1>
                <p>This code expires in 10 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>