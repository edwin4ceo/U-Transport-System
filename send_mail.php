<?php
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendDriverOtpEmail($toEmail, $driverName, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0; 
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        $mail->Username   = 'kelvinng051129@gmail.com'; 
        $mail->Password   = 'szvd kjeo jwfx bxnh';
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@u-transport.com', 'U-Transport Admin');
        $mail->addAddress($toEmail, $driverName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Verification';
        
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
        
        $mail->AltBody = "HI $driverName, your OTP code is: $otp";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        throw new Exception("Email sending failed");
    }
}
?>