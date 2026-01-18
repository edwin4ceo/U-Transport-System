<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP (Required for Resend Functionality)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. CHECK ACCESS AUTHORIZATION
// User must have triggered the OTP from the profile edit page first
if (!isset($_SESSION['reset_otp_data'])) {
    header("Location: passanger_profile_edit.php");
    exit();
}

// 2. CALCULATE REMAINING TIME FOR TIMER
$otp_start_time = isset($_SESSION['reset_otp_data']['otp_timestamp']) ? $_SESSION['reset_otp_data']['otp_timestamp'] : time();
$current_time = time();
$time_limit = 600; // 10 Minutes Limit
$remaining_seconds = $time_limit - ($current_time - $otp_start_time);
if ($remaining_seconds < 0) $remaining_seconds = 0;

// Email masking for display (e.g., abc****@student...)
$email_full = $_SESSION['reset_otp_data']['email'];
$email_mask = substr($email_full, 0, 3) . "****" . substr($email_full, strpos($email_full, "@"));

// 3. HANDLE RESEND OTP LOGIC
if (isset($_POST['resend_btn'])) {
    $current_count = isset($_SESSION['reset_otp_data']['resend_count']) ? $_SESSION['reset_otp_data']['resend_count'] : 0;
    
    // Limit resend attempts to prevent abuse
    if ($current_count >= 3) {
        $_SESSION['swal_title'] = "Limit Exceeded";
        $_SESSION['swal_msg'] = "Max resend attempts reached. Please restart the process.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; 
    } else {
        // Generate new OTP
        $new_otp = rand(1000, 9999);
        $_SESSION['reset_otp_data']['otp_code'] = $new_otp;
        $_SESSION['reset_otp_data']['otp_timestamp'] = time();
        $_SESSION['reset_otp_data']['resend_count'] = $current_count + 1;
        
        // Resend Email
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
            $mail->addAddress($_SESSION['reset_otp_data']['email'], $_SESSION['reset_otp_data']['name']);
            
            $mail->isHTML(true);
            $mail->Subject = 'Resend: Verification Code';
            $mail->Body = "<h3>Your new code: <b>$new_otp</b></h3>";
            $mail->send();
            
            $_SESSION['swal_title'] = "Sent";
            $_SESSION['swal_msg'] = "A new code has been sent to your email.";
            $_SESSION['swal_type'] = "success";
        } catch (Exception $e) {
            // Log error if needed
        }
    }
    // Refresh page to update timer
    header("Location: passanger_verify_update_otp.php");
    exit();
}

// 4. HANDLE OTP VERIFICATION LOGIC
if (isset($_POST['verify_btn'])) {
    // Combine the 4 input fields into one string
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['reset_otp_data']['otp_code'];
    
    // Check if code is expired
    if ((time() - $_SESSION['reset_otp_data']['otp_timestamp']) > 600) {
        $_SESSION['swal_title'] = "Expired";
        $_SESSION['swal_msg'] = "The verification code has expired.";
        $_SESSION['swal_type'] = "warning";
    } 
    // Check if code matches
    elseif ($user_entered_code == $correct_code) {
        // SUCCESS: Update Database using the hash stored in session
        $email = $_SESSION['reset_otp_data']['email'];
        $new_pass_hash = $_SESSION['reset_otp_data']['new_password_hash'];
        
        $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $new_pass_hash, $email);
        
        if ($update_stmt->execute()) {
            unset($_SESSION['reset_otp_data']); // Clear session data
            
            // Show Success Alert and Redirect to Profile
            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <style>body{font-family:'Poppins',sans-serif;background-color:#f6f5f7;}</style>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Password updated successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#005A9C'
                }).then(() => {
                    window.location.href = 'passanger_profile_edit.php';
                });
            </script>
            </body>
            </html>";
            exit();
        }
    } else {
        $_SESSION['swal_title'] = "Invalid";
        $_SESSION['swal_msg'] = "Incorrect verification code. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
}
?>

<?php include "header.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* CSS: HEADER OVERRIDE */
    .content-area { 
        background: transparent !important; box-shadow: none !important; border: none !important; 
        width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; 
    }

    /* CSS: PAGE WRAPPER */
    .wrapper { 
        width: 100%; min-height: 700px; display: flex; justify-content: center; align-items: flex-start; 
        padding-top: 10px; position: relative; overflow: hidden; background-color: #f6f5f7; 
    }

    /* CSS: CENTER BOX */
    .verify-box { 
        width: 500px; margin-top: 60px; padding: 40px 30px; 
        background: #ffffff; border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        text-align: center; position: relative; 
    }

    @media (max-width: 768px) { .verify-box { width: 90%; margin-top: 60px; padding: 30px 20px; } }

    /* CSS: TEXT STYLES */
    .top h2 { font-size: 30px; color: #333 !important; font-weight: 600; margin-bottom: 10px; cursor: default; }
    .top p { font-size: 15px; color: #666; margin-bottom: 30px; line-height: 1.6; cursor: default; }
    .top strong { color: #005A9C; }

    /* CSS: 4 OTP INPUT BOXES */
    .otp-field { display: flex; justify-content: center; gap: 15px; margin-bottom: 15px; }
    .otp-field input { 
        width: 60px; height: 60px; 
        font-size: 24px; font-weight: 700; text-align: center; color: #333;
        border: 1px solid #c4c4c4; border-radius: 12px; outline: none; background: #fff;
        transition: .3s; box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
        -moz-appearance: textfield; 
    }
    .otp-field input:focus { 
        border-color: #005A9C; box-shadow: 0 4px 10px rgba(0, 90, 156, 0.2); transform: translateY(-2px); 
    }
    
    /* CSS: TIMER & RESEND */
    .action-area { margin-bottom: 25px; font-size: 14px; color: #666; display: flex; justify-content: center; }
    .resend-btn { 
        background: none; border: none; color: #005A9C; cursor: pointer; 
        text-decoration: none; font-weight: 600; font-size: 14px; transition: .3s; 
    }
    .resend-btn:hover { text-decoration: underline; color: #004a80; }

    /* CSS: VERIFY BUTTON */
    .btn-verify {
        width: 100%; height: 55px; background: #005A9C !important; border: none !important; border-radius: 30px !important;
        color: #fff !important; font-size: 16px; font-weight: 600; cursor: pointer; transition: .3s;
        box-shadow: 0 8px 15px rgba(0, 90, 156, 0.2); display: flex; align-items: center; justify-content: center;
    }
    .btn-verify:hover { background: #004a80 !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 90, 156, 0.3); }

    /* CSS: CANCEL LINK */
    .back-link { 
        display: block; margin-top: 20px; font-size: 14px; 
        color: #005A9C; font-weight: 600; text-decoration: none; transition: .3s; 
    }
    .back-link:hover { text-decoration: underline; }
    
    /* Remove input spinners */
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<div class="wrapper">
    <div class="verify-box">
        
        <div class="top">
            <h2>Verify Update</h2>
            <p>Enter the code sent to: <strong><?php echo $email_mask; ?></strong><br>to confirm your password change.</p>
        </div>

        <form action="" method="POST" id="otpForm" onkeydown="return event.key != 'Enter';">
            <input type="hidden" name="otp_input" id="full_otp_input">
            
            <div class="otp-field">
                <input type="number" class="otp-box" maxlength="1" required autofocus>
                <input type="number" class="otp-box" maxlength="1" required>
                <input type="number" class="otp-box" maxlength="1" required>
                <input type="number" class="otp-box" maxlength="1" required>
            </div>
            
            <div class="action-area">
                <div id="timer-box">
                    Code expires in: <span id="time">10:00</span>
                </div>
                <div id="resend-box" style="display: none; flex-direction: column; gap: 5px;">
                    <span>Didn't receive the code?</span>
                    <button type="submit" name="resend_btn" class="resend-btn" formnovalidate>Resend Code</button>
                </div>
            </div>

            <button type="submit" name="verify_btn" class="btn-verify">Confirm Update</button>
        </form>

        <a href="passanger_profile_edit.php" class="back-link">Cancel</a>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // JS: Handle OTP Box Navigation (Auto-focus next box)
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            // Keep only last char if multiple entered
            if (input.value.length > 1) input.value = input.value.slice(-1);
            // Move to next box
            if (input.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus();
            updateHiddenInput();
        });

        input.addEventListener("keydown", (e) => {
            // Move to prev box on Backspace
            if (e.key === "Backspace" && input.value === "" && index > 0) inputs[index - 1].focus();
        });
    });

    // Update the hidden input field with combined value
    function updateHiddenInput() {
        let code = "";
        inputs.forEach((input) => code += input.value);
        hiddenInput.value = code;
    }

    form.addEventListener("submit", () => updateHiddenInput());

    // JS: Timer Logic
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

<?php if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#005A9C'
    });
</script>
<?php 
    // Clear session messages
    unset($_SESSION['swal_title']);
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_type']);
endif; 
?>