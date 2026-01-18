<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP
// Adjust the path to your PHPMailer files if different
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. CHECK LOGIN
// This page is accessed from the "Forgot Password" link when the user IS logged in.
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// 2. FETCH USER DETAILS
// We need the email and name to send the OTP
$stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $email = $user['email'];
    $name = $user['name'];

    // 3. GENERATE OTP
    $otp = rand(1000, 9999);

    // 4. STORE IN SESSION
    // We store this temporarily to verify on the next page
    $_SESSION['reset_otp_data'] = [
        'student_id' => $student_id,
        'email' => $email,
        'otp_code' => $otp,
        'otp_timestamp' => time()
    ];

    // 5. SEND EMAIL
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'soonkit0726@gmail.com';  // Your Email
        $mail->Password   = 'oprh ldrk nwvg eyiv';    // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password OTP';
        $mail->Body    = "<h3>Hello $name,</h3><p>You requested to reset your password. Your verification code is: <b>$otp</b></p>";
        
        $mail->send();
        
        // Redirect to OTP input page
        echo "<script>window.location.href='passanger_reset_password_otp.php';</script>";
        exit();

    } catch (Exception $e) {
        $_SESSION['swal_error'] = "Failed to send OTP email. Please try again later.";
        header("Location: passanger_profile_edit.php");
        exit();
    }
} else {
    $_SESSION['swal_error'] = "User details not found.";
    header("Location: passanger_profile_edit.php");
    exit();
}
?>