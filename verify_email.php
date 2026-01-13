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

// Check session
if (!isset($_SESSION['temp_register_data'])) {
    header("Location: passanger_register.php");
    exit();
}

// 1. CALCULATE REMAINING TIME FOR TIMER
$otp_start_time = isset($_SESSION['temp_register_data']['otp_timestamp']) ? $_SESSION['temp_register_data']['otp_timestamp'] : time();
$current_time = time();
$time_limit = 600; // 10 Minutes
$time_diff = $current_time - $otp_start_time;
$remaining_seconds = $time_limit - $time_diff;

if ($remaining_seconds < 0) {
    $remaining_seconds = 0;
}

// 2. HANDLE RESEND CODE
if (isset($_POST['resend_btn'])) {
    $current_count = isset($_SESSION['temp_register_data']['resend_count']) ? $_SESSION['temp_register_data']['resend_count'] : 0;

    if ($current_count >= 3) {
        $_SESSION['swal_title'] = "Limit Exceeded";
        $_SESSION['swal_msg'] = "You have attempted to resend the code 3 times. Please check your email address.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; 
    } 
    else {
        $new_otp = rand(1000, 9999);
        $_SESSION['temp_register_data']['otp_code'] = $new_otp;
        $_SESSION['temp_register_data']['otp_timestamp'] = time();
        $_SESSION['temp_register_data']['resend_count'] = $current_count + 1;

        $remaining_seconds = 600; 
        $email = $_SESSION['temp_register_data']['email'];
        $name  = $_SESSION['temp_register_data']['name'];

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
            $mail->Subject = 'Verify Your Account - U-Transport';
            $mail->Body    = "<h3>Hello $name,</h3><p>Your verification code is: <b style='font-size: 20px;'>$new_otp</b></p>";
            $mail->send();
            
            $_SESSION['swal_title'] = "Code Sent";
            $_SESSION['swal_msg'] = "A new verification code has been sent to your email.";
            $_SESSION['swal_type'] = "success";
        } catch (Exception $e) {
             $_SESSION['swal_title'] = "Email Error";
             $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
             $_SESSION['swal_type'] = "error";
        }
    }
    header("Location: verify_email.php");
    exit();
}

// 3. HANDLE VERIFICATION
if (isset($_POST['verify_btn'])) {
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['temp_register_data']['otp_code'];
    $live_time_diff = time() - $_SESSION['temp_register_data']['otp_timestamp'];

    if ($live_time_diff > 600) {
        $_SESSION['swal_title'] = "Code Expired";
        $_SESSION['swal_msg'] = "The code has expired. Please click 'Resend Code'.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0;
    }
    elseif ($user_entered_code == $correct_code) {
        $name = $_SESSION['temp_register_data']['name'];
        $sid  = $_SESSION['temp_register_data']['student_id'];
        $email = $_SESSION['temp_register_data']['email'];
        $pass = $_SESSION['temp_register_data']['password_hash'];
        $gender = $_SESSION['temp_register_data']['gender'];

        $sql = "INSERT INTO students (name, student_id, email, password, gender) VALUES ('$name','$sid','$email','$pass', '$gender')";
        if ($conn->query($sql)) {
            unset($_SESSION['temp_register_data']);
            $_SESSION['swal_title'] = "Success!";
            $_SESSION['swal_msg'] = "Your account has been verified. Please login.";
            $_SESSION['swal_type'] = "success";
            header("Location: passanger_login.php");
            exit();
        } else {
            $_SESSION['swal_title'] = "Database Error";
            $_SESSION['swal_msg'] = "Error: " . $conn->error;
            $_SESSION['swal_type'] = "error";
        }
    } else {
        $_SESSION['swal_title'] = "Invalid Code";
        $_SESSION['swal_msg'] = "The code is incorrect. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
}
?>

<?php include "header.php"; ?>

<style>
    body { background-color: #f4f7f6; }
    .verify-container { max-width: 420px; margin: 60px auto; padding: 40px 30px; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
    .otp-field { display: flex; justify-content: center; gap: 12px; margin-bottom: 25px; }
    .otp-field input { width: 55px; height: 60px; font-size: 24px; font-weight: 700; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; outline: none; transition: border-color 0.3s; }
    .otp-field input:focus { border-color: #007bff; }
    .timer-display { font-size: 15px; color: #666; margin-bottom: 10px; }
    .timer-display span { font-weight: 700; color: #000; }
    .resend-container { display: none; flex-direction: column; align-items: center; gap: 5px; }
    .resend-btn { background: none; border: none; color: #007bff; cursor: pointer; text-decoration: underline; font-weight: 700; font-size: 15px; }
    .btn-verify { width: 100%; padding: 14px; background-color: #28a745; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: background 0.3s; }
    .btn-verify:hover { background-color: #218838; }
</style>

<div class="verify-container">
    <h2>Verify Your Account</h2>
    <p style="color: #666; margin-bottom: 30px;">We have sent a 4-digit code to:<br> <strong><?php echo htmlspecialchars($_SESSION['temp_register_data']['email']); ?></strong></p>

    <form action="" method="POST" id="otpForm" onkeydown="return event.key != 'Enter';">
        <input type="hidden" name="otp_input" id="full_otp_input">
        <div class="otp-field">
            <input type="number" class="otp-box" maxlength="1" required autofocus>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
        </div>
        
        <div class="action-area" style="margin-bottom: 20px;">
            <div id="timer-box" class="timer-display">
                Code expires in: <span id="time">10:00</span>
            </div>
            <div id="resend-box" class="resend-container">
                <span>Didn't receive the code?</span>
                <button type="submit" name="resend_btn" class="resend-btn" formnovalidate>Resend Code</button>
            </div>
        </div>

        <button type="submit" name="verify_btn" class="btn-verify">Verify and Register</button>
    </form>

    <a href="passanger_register.php" style="display:block; margin-top:25px; font-size:13px; color:#999; text-decoration:none;">Wrong email? Back to registration</a>
</div>

<script>
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    // 关键点 2: JavaScript 全局拦截 Enter 键
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            event.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'OTP Required',
                text: 'Please enter the OTP number and click the Verify button.',
                confirmButtonColor: '#005A9C'
            });
        }
    });

    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            if (input.value.length > 1) input.value = input.value.slice(-1);
            if (input.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus();
            updateHiddenInput();
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && input.value === "" && index > 0) inputs[index - 1].focus();
        });
    });

    function updateHiddenInput() {
        let code = "";
        inputs.forEach((input) => code += input.value);
        hiddenInput.value = code;
    }

    form.addEventListener("submit", () => updateHiddenInput());

    // Timer Logic
    let timeLeft = <?php echo $remaining_seconds; ?>;
    const timeDisplay = document.getElementById('time');
    const timerBox = document.getElementById('timer-box');
    const resendBox = document.getElementById('resend-box');

    function startTimer() {
        if (timeLeft <= 0) { showResend(); return; }
        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                showResend();
            } else {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                timeDisplay.textContent = minutes + ":" + (seconds < 10 ? '0' + seconds : seconds);
                timeLeft--;
            }
        }, 1000);
    }

    function showResend() {
        timerBox.style.display = 'none';
        resendBox.style.display = 'flex';
    }

    startTimer();
</script>

<?php include "footer.php"; ?>