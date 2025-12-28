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

// Check session (Ensure we are in the Reset Password flow)
if (!isset($_SESSION['temp_reset_data'])) {
    header("Location: passanger_forgot_password.php");
    exit();
}

// ==========================================
// 1. CALCULATE REMAINING TIME FOR TIMER
// ==========================================
$otp_start_time = isset($_SESSION['temp_reset_data']['otp_timestamp']) ? $_SESSION['temp_reset_data']['otp_timestamp'] : time();
$current_time = time();
$time_limit = 600; // 10 Minutes
$time_diff = $current_time - $otp_start_time;
$remaining_seconds = $time_limit - $time_diff;

if ($remaining_seconds < 0) {
    $remaining_seconds = 0;
}

// ==========================================
// 2. HANDLE RESEND CODE
// ==========================================
if (isset($_POST['resend_btn'])) {
    
    $current_count = isset($_SESSION['temp_reset_data']['resend_count']) ? $_SESSION['temp_reset_data']['resend_count'] : 0;

    if ($current_count >= 3) {
        $_SESSION['swal_title'] = "Limit Exceeded";
        $_SESSION['swal_msg'] = "You have attempted to resend the code 3 times. Please try again later.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; 
    } 
    else {
        // Generate NEW code
        $new_otp = rand(1000, 9999);
        
        // Update Session
        $_SESSION['temp_reset_data']['otp_code'] = $new_otp;
        $_SESSION['temp_reset_data']['otp_timestamp'] = time(); 
        $_SESSION['temp_reset_data']['resend_count'] = $current_count + 1; 

        $remaining_seconds = 600; 

        $email = $_SESSION['temp_reset_data']['email'];
        $name  = $_SESSION['temp_reset_data']['name'];

        // Send Email
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
            $mail->Subject = 'Resend: Reset Password Code';
            $mail->Body    = "
                <h3>Hello $name,</h3>
                <p>Here is your new verification code for password reset:</p>
                <h2 style='color: #004b82; letter-spacing: 5px;'>$new_otp</h2>
                <p>This code will expire in 10 minutes.</p>
            ";
            
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
    
    // Refresh page
    header("Location: verify_reset_otp.php");
    exit();
}

// ==========================================
// 3. HANDLE VERIFICATION
// ==========================================
if (isset($_POST['verify_btn'])) {
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['temp_reset_data']['otp_code'];
    
    $live_time_diff = time() - $_SESSION['temp_reset_data']['otp_timestamp'];

    if ($live_time_diff > 600) {
        $_SESSION['swal_title'] = "Code Expired";
        $_SESSION['swal_msg'] = "The code has expired. Please click 'Resend Code'.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; 
    }
    elseif ($user_entered_code == $correct_code) {
        // Code Matched - UPDATE Password in DB
        $email = $_SESSION['temp_reset_data']['email'];
        $new_pass_hash = $_SESSION['temp_reset_data']['new_password_hash'];

        // Prepare UPDATE statement
        $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $new_pass_hash, $email);

        if ($update_stmt->execute()) {
            unset($_SESSION['temp_reset_data']); // Clear session
            
            // Success Message
            $_SESSION['swal_title'] = "Congratulations!";
            $_SESSION['swal_msg'] = "Verification complete. Your password has been updated. Please login.";
            $_SESSION['swal_type'] = "success";
            
            redirect("passanger_login.php");
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
    .verify-container {
        max-width: 420px;
        margin: 60px auto;
        padding: 40px 30px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        text-align: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .verify-container h2 {
        color: #2c3e50;
        margin-bottom: 8px;
        font-weight: 800;
        font-size: 24px;
    }
    .verify-container p.subtitle {
        color: #7f8c8d;
        margin-bottom: 30px;
        font-size: 14px;
        line-height: 1.5;
    }
    .otp-field {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 25px;
    }
    .otp-field input {
        width: 55px;
        height: 60px;
        font-size: 24px;
        font-weight: 700;
        text-align: center;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        background: #fafafa;
        transition: all 0.3s ease;
        outline: none;
        color: #333;
    }
    .otp-field input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        background: #fff;
        transform: translateY(-2px);
    }
    /* Hide scroll arrows */
    .otp-field input::-webkit-outer-spin-button,
    .otp-field input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    
    .action-area {
        min-height: 30px;
        margin-bottom: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 14px;
    }
    .timer-display span {
        color: #000;
        font-weight: 700;
        font-family: monospace;
        font-size: 15px;
    }
    .resend-container {
        display: none; 
        flex-direction: column;
        align-items: center;
    }
    .resend-btn {
        background: none;
        border: none;
        color: #007bff;
        font-weight: 700;
        cursor: pointer;
        text-decoration: underline;
        padding: 0;
        font-size: 14px;
    }
    .btn-verify {
        width: 100%;
        padding: 14px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s, background 0.3s;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
    }
    .btn-verify:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }
    .back-link {
        display: block;
        margin-top: 20px;
        font-size: 13px;
        color: #999;
        text-decoration: none;
    }
</style>

<div class="verify-container">
    <h2>Verify Password Reset</h2>
    <p class="subtitle">
        We have sent a reset code to:<br> 
        <strong style="color:#333;"><?php echo htmlspecialchars($_SESSION['temp_reset_data']['email']); ?></strong>
    </p>

    <form action="" method="POST" id="otpForm">
        <input type="hidden" name="otp_input" id="full_otp_input">

        <div class="otp-field">
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
        </div>
        
        <div class="action-area">
            <div id="timer-box" class="timer-display">
                Code expires in: <span id="time">10:00</span>
            </div>

            <div id="resend-box" class="resend-container">
                <span class="resend-text">Didn't receive code?</span>
                <button type="submit" name="resend_btn" class="resend-btn" formnovalidate>Resend Code</button>
            </div>
        </div>

        <button type="submit" name="verify_btn" class="btn-verify">Verify & Update Password</button>
    </form>
    
    <a href="passanger_forgot_password.php" class="back-link">Change Email or Details?</a>
</div>

<script>
    // --- OTP INPUT LOGIC ---
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            // FIX: Use slice(-1) to take the LAST character entered (the new one)
            // This prevents the number pad from getting stuck on the old number
            if (input.value.length > 1) {
                input.value = input.value.slice(-1); 
            }
            
            // Move to next input if current is filled
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener("keydown", (e) => {
            // Handle Backspace: Move to previous input if empty
            if (e.key === "Backspace" && input.value === "" && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // UX Improvement: Select all text on click to allow easy overwrite
        input.addEventListener("focus", (e) => {
            input.select();
        });
    });

    function updateHiddenInput() {
        let code = "";
        inputs.forEach((input) => code += input.value);
        hiddenInput.value = code;
    }
    
    form.addEventListener("submit", () => updateHiddenInput());

    // --- COUNTDOWN TIMER LOGIC ---
    let timeLeft = <?php echo $remaining_seconds; ?>;
    
    const timerBox = document.getElementById('timer-box');
    const resendBox = document.getElementById('resend-box');
    const timeDisplay = document.getElementById('time');

    function startTimer() {
        if (timeLeft <= 0) {
            showResend();
            return;
        }

        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                showResend();
            } else {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                timeDisplay.textContent = minutes + ":" + seconds;
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