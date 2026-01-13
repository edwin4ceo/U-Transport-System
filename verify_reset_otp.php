<?php
session_start();
include "db_connect.php";
include "function.php";

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['temp_reset_data'])) {
    header("Location: passanger_forgot_password.php");
    exit();
}

$otp_start_time = isset($_SESSION['temp_reset_data']['otp_timestamp']) ? $_SESSION['temp_reset_data']['otp_timestamp'] : time();
$current_time = time();
$time_limit = 600; 
$remaining_seconds = $time_limit - ($current_time - $otp_start_time);
if ($remaining_seconds < 0) $remaining_seconds = 0;

if (isset($_POST['resend_btn'])) {
    $current_count = isset($_SESSION['temp_reset_data']['resend_count']) ? $_SESSION['temp_reset_data']['resend_count'] : 0;
    if ($current_count >= 3) {
        $_SESSION['swal_title'] = "Limit Exceeded";
        $_SESSION['swal_msg'] = "You have attempted to resend the code 3 times.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; 
    } else {
        $new_otp = rand(1000, 9999);
        $_SESSION['temp_reset_data']['otp_code'] = $new_otp;
        $_SESSION['temp_reset_data']['otp_timestamp'] = time();
        $_SESSION['temp_reset_data']['resend_count'] = $current_count + 1;
        
        $email = $_SESSION['temp_reset_data']['email'];
        $name  = $_SESSION['temp_reset_data']['name'];
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'soonkit0726@gmail.com'; 
            $mail->Password = 'oprh ldrk nwvg eyiv';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Resend: Reset Password Code';
            $mail->Body = "<h3>Hello $name,</h3><p>Your password reset code is: <b>$new_otp</b></p>";
            $mail->send();
            $_SESSION['swal_title'] = "Code Sent";
            $_SESSION['swal_msg'] = "A new verification code has been sent.";
            $_SESSION['swal_type'] = "success";
        } catch (Exception $e) {
             $_SESSION['swal_title'] = "Error";
             $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
             $_SESSION['swal_type'] = "error";
        }
    }
    header("Location: verify_reset_otp.php");
    exit();
}

if (isset($_POST['verify_btn'])) {
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['temp_reset_data']['otp_code'];
    if ((time() - $_SESSION['temp_reset_data']['otp_timestamp']) > 600) {
        $_SESSION['swal_title'] = "Expired";
        $_SESSION['swal_msg'] = "The verification code has expired.";
        $_SESSION['swal_type'] = "warning";
    } elseif ($user_entered_code == $correct_code) {
        $email = $_SESSION['temp_reset_data']['email'];
        $new_pass_hash = $_SESSION['temp_reset_data']['new_password_hash'];
        $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $new_pass_hash, $email);
        if ($update_stmt->execute()) {
            unset($_SESSION['temp_reset_data']);
            $_SESSION['swal_title'] = "Success!";
            $_SESSION['swal_msg'] = "Password updated. Please login.";
            $_SESSION['swal_type'] = "success";
            header("Location: passanger_login.php");
            exit();
        }
    } else {
        $_SESSION['swal_title'] = "Invalid";
        $_SESSION['swal_msg'] = "Incorrect verification code.";
        $_SESSION['swal_type'] = "error";
    }
}
?>

<?php include "header.php"; ?>

<style>
    body { background-color: #f4f7f6; }
    .verify-container { max-width: 420px; margin: 60px auto; padding: 40px 30px; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
    .otp-field { display: flex; justify-content: center; gap: 12px; margin-bottom: 25px; }
    .otp-field input { width: 55px; height: 60px; font-size: 24px; font-weight: 700; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; outline: none; }
</style>

<div class="verify-container">
    <h2>Verify Password Reset</h2>
    <p>Please enter the code sent to:<br> <strong><?php echo htmlspecialchars($_SESSION['temp_reset_data']['email']); ?></strong></p>

    <form action="" method="POST" id="otpForm" onkeydown="return event.key != 'Enter';">
        <input type="hidden" name="otp_input" id="full_otp_input">
        <div class="otp-field">
            <input type="number" class="otp-box" maxlength="1" required autofocus>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
        </div>
        <div class="action-area" style="margin-bottom: 20px;">
            <div id="timer-box">Code expires in: <span id="time" style="font-weight:700;">10:00</span></div>
            <div id="resend-box" style="display:none; flex-direction:column;">
                <span>Didn't receive the code?</span>
                <button type="submit" name="resend_btn" style="background:none; border:none; color:#007bff; cursor:pointer; text-decoration:underline; font-weight:700;" formnovalidate>Resend Code</button>
            </div>
        </div>
        <button type="submit" name="verify_btn" style="width:100%; padding:14px; background-color:#28a745; color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">Confirm Update Password</button>
    </form>
</div>

<script>
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");

    // 全局拦截 Enter
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            event.preventDefault();
            Swal.fire({ icon: 'info', title: 'Action Required', text: 'Please enter the OTP number.', confirmButtonColor: '#005A9C' });
        }
    });

    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            if (input.value.length > 1) input.value = input.value.slice(-1);
            if (input.value.length === 1 && index < inputs.length - 1) inputs[index+1].focus();
            updateHiddenInput();
        });
        input.addEventListener("keydown", (e) => { if (e.key === "Backspace" && input.value === "" && index > 0) inputs[index-1].focus(); });
    });
    function updateHiddenInput() {
        let code = "";
        inputs.forEach((input) => code += input.value);
        hiddenInput.value = code;
    }
    document.getElementById("otpForm").addEventListener("submit", () => updateHiddenInput());

    let timeLeft = <?php echo $remaining_seconds; ?>;
    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            document.getElementById('timer-box').style.display = 'none';
            document.getElementById('resend-box').style.display = 'flex';
        } else {
            let m = Math.floor(timeLeft / 60);
            let s = timeLeft % 60;
            document.getElementById('time').textContent = m + ":" + (s < 10 ? '0' + s : s);
            timeLeft--;
        }
    }, 1000);
</script>

<?php include "footer.php"; ?>