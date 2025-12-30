<?php
// send_mail.php

// Include PHPMailer classes manually
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendDriverOtpEmail($toEmail, $driverName, $otp) {
    $mail = new PHPMailer(true);

    try {
        // --- Core Configuration ---
        // 🔴 Critical: Set to 0 for production to hide debug info from users
        $mail->SMTPDebug = 0; 
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // Use your App Password here
        $mail->Username   = 'your_email@gmail.com'; 
        $mail->Password   = 'your_16_digit_app_password';
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Content Settings ---
        $mail->setFrom('no-reply@u-transport.com', 'U-Transport Admin');
        $mail->addAddress($toEmail, $driverName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Verification';
        
        // HTML Styling for the email
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #005A9C; text-align: center;'>Password Reset Request</h2>
                <p>Hello <strong>$driverName</strong>,</p>
                <p>We received a request to reset your driver account password. Please use the following OTP code to proceed:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #f39c12; background: #f9f9f9; padding: 10px 20px; border-radius: 5px; border: 1px dashed #f39c12;'>
                        $otp
                    </span>
                </div>
                
                <p style='color: #666; font-size: 14px;'>This code is valid for 10 minutes. If you did not request this change, please ignore this email.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='text-align: center; color: #999; font-size: 12px;'>&copy; 2024 U-Transport System</p>
            </div>
        ";
        
        // Plain text alternative (for email clients that don't support HTML)
        $mail->AltBody = "Hello $driverName, your OTP code is: $otp";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log errors to server file, but don't show to user
        error_log("Mailer Error: " . $mail->ErrorInfo);
        // Throw exception so main program knows sending failed
        throw new Exception("Email sending failed");
    }
}
?>