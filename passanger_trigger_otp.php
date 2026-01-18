<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. CHECK LOGIN STATUS
// Ensure the user is logged in to access this script
if(!isset($_SESSION['student_id'])){
    header("Location: passanger_login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

// 2. RECEIVE POST DATA
// This script expects data sent from the 'passanger_profile_edit.php' form
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $new_pass = $_POST['new_password'];
    $cfm_pass = $_POST['confirm_password'];

    // 3. FETCH USER DATA FOR VALIDATION
    // We need the current password hash to ensure the new password is different
    $stmt = $conn->prepare("SELECT name, email, password FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $email = $user['email'];
        $name = $user['name'];
        $current_hash = $user['password'];

        // 4. VALIDATE PASSWORDS
        
        // Check if passwords match
        if($new_pass !== $cfm_pass){
            $_SESSION['swal_error'] = "Passwords do not match.";
            header("Location: passanger_profile_edit.php");
            exit();
        }
        
        // Check password length
        if(strlen($new_pass) < 6){
            $_SESSION['swal_error'] = "Password too short (min 6 characters).";
            header("Location: passanger_profile_edit.php");
            exit();
        }
        
        // CRITICAL CHECK: New password cannot be the same as the current password
        if(password_verify($new_pass, $current_hash)){
            $_SESSION['swal_error'] = "New password cannot be the same as your current password.";
            header("Location: passanger_profile_edit.php");
            exit();
        }

        // 5. GENERATE OTP & PREPARE PASSWORD HASH
        // We generate the hash NOW and store it in the session temporarily.
        // It will only be saved to the database after OTP verification.
        $otp = rand(1000, 9999);
        $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);

        // 6. STORE DATA IN SESSION
        // This data will be used by 'passanger_verify_update_otp.php'
        $_SESSION['reset_otp_data'] = [
            'student_id' => $student_id,
            'email' => $email,
            'name'  => $name, // Needed for resend email
            'new_password_hash' => $new_pass_hash, // The pending new password
            'otp_code' => $otp,
            'otp_timestamp' => time(),
            'resend_count' => 0
        ];

        // 7. SEND OTP EMAIL
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'soonkit0726@gmail.com'; // YOUR EMAIL
            $mail->Password   = 'oprh ldrk nwvg eyiv';   // YOUR APP PASSWORD
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
            $mail->addAddress($email, $name);
            
            $mail->isHTML(true);
            $mail->Subject = 'Verify Password Update';
            $mail->Body    = "<h3>Hello $name,</h3><p>You requested to update your password. Your verification code is: <b>$otp</b></p>";
            
            $mail->send();
            
            // Redirect to the OTP Verification Page
            echo "<script>window.location.href='passanger_verify_update_otp.php';</script>";
            exit();

        } catch (Exception $e) {
            $_SESSION['swal_error'] = "Failed to send OTP email.";
            header("Location: passanger_profile_edit.php");
            exit();
        }
    }
} else {
    // If user tries to access this page directly without submitting the form
    header("Location: passanger_profile_edit.php");
    exit();
}
?>