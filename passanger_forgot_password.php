<?php
session_start();
include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

if(isset($_POST['reset_password'])){
    $student_id = $_POST['student_id'];
    $email = $_POST['email']; // Auto-filled
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation: Check MMU Domain
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg'] = "Please confirm if you entered the correct Student ID.";
        $_SESSION['swal_type'] = "error";
    }
    // 2. Validation: Check if passwords match
    elseif($new_password !== $confirm_password){
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "New passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // 3. Verify User Identity
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND student_id = ?");
        $stmt->bind_param("ss", $email, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            // Identity Verified
            $row = $result->fetch_assoc();
            $name = $row['name']; 
            
            // Generate OTP
            $otp = rand(1000, 9999);
            
            // Hash the NEW password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Store Data in Session
            $_SESSION['temp_reset_data'] = [
                'email' => $email,
                'name' => $name,
                'student_id' => $student_id,
                'new_password_hash' => $new_password_hash,
                'otp_code' => $otp,
                'otp_timestamp' => time(),
                'resend_count' => 0
            ];

            // Send OTP Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'soonkit0726@gmail.com'; 
                $mail->Password   = 'oprh ldrk nwvg eyiv';   
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = 'Reset Password Verification Code';
                $mail->Body    = "
                    <h3>Hello $name,</h3>
                    <p>You have requested to reset your password.</p>
                    <p>Here is your verification code:</p>
                    <h2 style='color: #004b82; letter-spacing: 5px;'>$otp</h2>
                    <p>This code will expire in 10 minutes.</p>
                ";
                
                $mail->send();
                
                header("Location: verify_reset_otp.php");
                exit();

            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
            }

        } else {
            $_SESSION['swal_title'] = "Verification Failed";
            $_SESSION['swal_msg'] = "The Student ID provided does not match our records.";
            $_SESSION['swal_type'] = "error";
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Standard Input Styling */
    input[type="email"], input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; 
    }

    /* Password Eye Icon Style */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    .password-wrapper input {
        margin-bottom: 15px; 
        padding-right: 40px; 
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%; 
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
        z-index: 10;
        font-size: 1.1rem;
        user-select: none; 
    }
    .toggle-password:hover { color: #005A9C; }
</style>

<h2>Reset Password</h2>

<p style="margin-bottom: 5px;">Enter your Student ID and new password.</p>
<p style="color: red; font-size: 13px; margin-top: 0; font-weight: 500;">
    * You will need to verify your email in the next step.
</p>
<form action="" method="POST">
    
    <label>Student ID</label>
    <input type="text" name="student_id" id="studentIDInput" required placeholder="e.g. 1234567890" maxlength="10">

    <label>MMU Email</label>
    <input type="email" name="email" id="emailInput" required placeholder="ID@student.mmu.edu.my" readonly style="background-color: #f9f9f9; cursor: not-allowed;">

    <label>New Password</label>
    <div class="password-wrapper">
        <input type="password" name="new_password" id="newPass" required placeholder="Create new password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconNew"></i>
    </div>

    <label>Confirm New Password</label>
    <div class="password-wrapper">
        <input type="password" name="confirm_password" id="confirmPass" required placeholder="Re-enter new password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconConfirm"></i>
    </div>

    <button type="submit" name="reset_password" style="font-size: 15px;">Verify Email to Complete Reset Password</button>
</form>

<div style="margin-top: 15px; text-align: center;">
    <a href="passanger_login.php" style="color: #666; text-decoration: none;">&larr; Back to Login</a>
</div>

<script>
    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');

    // Auto-fill Email from Student ID
    studentIdInput.addEventListener('input', function() {
        const id = this.value;
        if (id.length > 0) {
            emailInput.value = id + "@student.mmu.edu.my";
        } else {
            emailInput.value = "";
        }
    });

    // Password Toggle Function
    function setupPasswordToggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        function show() {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }

        function hide() {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }

        icon.addEventListener('mousedown', show);
        icon.addEventListener('mouseup', hide);
        icon.addEventListener('mouseleave', hide);

        icon.addEventListener('touchstart', function(e) {
            e.preventDefault();
            show();
        });
        icon.addEventListener('touchend', hide);
    }

    setupPasswordToggle('newPass', 'eyeIconNew');
    setupPasswordToggle('confirmPass', 'eyeIconConfirm');
</script>

<?php include "footer.php"; ?>