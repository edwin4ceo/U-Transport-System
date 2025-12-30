<?php
// send_mail.php

// 确保引用路径正确
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendDriverOtpEmail($toEmail, $driverName, $otp) {
    $mail = new PHPMailer(true);

    try {
        // 🔴 开启调试模式：这会把连接过程打印在屏幕上
        $mail->SMTPDebug = 2; 
        $mail->Debugoutput = 'html';

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // 👇👇 请再次检查这里的账号密码 👇👇
        $mail->Username   = 'kelvinng051129@gmail.com'; 
        $mail->Password   = 'szvd kjeo jwfx bxnh'; // 不是登录密码！
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@test.com', 'System Admin');
        $mail->addAddress($toEmail, $driverName);

        $mail->isHTML(true);
        $mail->Subject = 'Verification Code';
        $mail->Body    = "Your OTP is: <b>$otp</b>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // 🔴 强制停止并打印错误，方便你看
        echo "<h1>发送失败!</h1>";
        echo "错误信息: " . $mail->ErrorInfo;
        exit;
    }
}
?>