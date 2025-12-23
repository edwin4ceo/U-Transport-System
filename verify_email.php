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

// ==========================================
// 1. CALCULATE REMAINING TIME FOR TIMER
// ==========================================
$otp_start_time = isset($_SESSION['temp_register_data']['otp_timestamp']) ? $_SESSION['temp_register_data']['otp_timestamp'] : time();
$current_time = time();
$time_limit = 600; // 10 Minutes (600 seconds)
$time_diff = $current_time - $otp_start_time;
$remaining_seconds = $time_limit - $time_diff;

// Ensure remaining time is not negative for JS
if ($remaining_seconds < 0) {
    $remaining_seconds = 0;
}

// ==========================================
// 2. HANDLE RESEND CODE
// ==========================================
if (isset($_POST['resend_btn'])) {
    
    // Check Resend Limit (Max 3 times)
    $current_count = isset($_SESSION['temp_register_data']['resend_count']) ? $_SESSION['temp_register_data']['resend_count'] : 0;

    if ($current_count >= 3) {
        // Limit Exceeded
        $_SESSION['swal_title'] = "Limit Exceeded";
        $_SESSION['swal_msg'] = "You have attempted to resend the code 3 times. Please confirm if you entered the correct email address.";
        $_SESSION['swal_type'] = "warning";
        
        // Force timer to 0 so the link stays visible
        $remaining_seconds = 0; 
    } 
    else {
        // Allow Resend
        
        // 1. Generate NEW code
        $new_otp = rand(1000, 9999);
        
        // 2. Update Session Data
        $_SESSION['temp_register_data']['otp_code'] = $new_otp;
        $_SESSION['temp_register_data']['otp_timestamp'] = time(); // Reset timer
        $_SESSION['temp_register_data']['resend_count'] = $current_count + 1; // Increment counter

        // 3. Reset local variable for UI
        $remaining_seconds = 600; 

        $email = $_SESSION['temp_register_data']['email'];
        $name  = $_SESSION['temp_register_data']['name'];

        // 4. Send Email
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
            $mail->Subject = 'Resend: Verify Your Account';
            $mail->Body    = "
                <h3>Hello $name,</h3>
                <p>Here is your new verification code:</p>
                <h2 style='color: #004b82; letter-spacing: 5px;'>$new_otp</h2>
                <p>This code will expire in 10 minutes.</p>
            ";
            
            $mail->send();
            
            // Success Alert
            $_SESSION['swal_title'] = "Code Sent";
            $_SESSION['swal_msg'] = "A new verification code has been sent to your email.";
            $_SESSION['swal_type'] = "success";

        } catch (Exception $e) {
             $_SESSION['swal_title'] = "Email Error";
             $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
             $_SESSION['swal_type'] = "error";
        }
    }
    
    // Redirect to self to refresh the page and show SweetAlert
    header("Location: verify_email.php");
    exit();
}

// ==========================================
// 3. HANDLE VERIFICATION
// ==========================================
if (isset($_POST['verify_btn'])) {
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['temp_register_data']['otp_code'];
    
    // Check real-time expiration logic (double check in PHP)
    $live_time_diff = time() - $_SESSION['temp_register_data']['otp_timestamp'];

    if ($live_time_diff > 600) {
        $_SESSION['swal_title'] = "Code Expired";
        $_SESSION['swal_msg'] = "The code has expired. Please click 'Resend Code'.";
        $_SESSION['swal_type'] = "warning";
        $remaining_seconds = 0; // Update UI to show expired
    }
    elseif ($user_entered_code == $correct_code) {
        // Code Matched - Insert into DB
        $name = $_SESSION['temp_register_data']['name'];
        $sid  = $_SESSION['temp_register_data']['student_id'];
        $email = $_SESSION['temp_register_data']['email'];
        $pass = $_SESSION['temp_register_data']['password_hash'];

        $sql = "INSERT INTO students (name, student_id, email, password) 
                VALUES ('$name','$sid','$email','$pass')";

        if ($conn->query($sql)) {
            unset($_SESSION['temp_register_data']);
            $_SESSION['swal_title'] = "Registration Successful!";
            $_SESSION['swal_msg'] = "Your account has been verified. Please login.";
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
    body {
        background-color: #f4f7f6;
    }
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
    
    /* OTP Input Style */
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
    /* Hide number arrows */
    .otp-field input::-webkit-outer-spin-button,
    .otp-field input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    /* --- THE MIDDLE SECTION (Timer & Resend) --- */
    .action-area {
        min-height: 30px;
        margin-bottom: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 14px;
    }

    /* Timer Style */
    .timer-display {
        color: #555;
        font-weight: 500;
    }
    .timer-display span {
        color: #000000; /* BLACK TEXT for Timer */
        font-weight: 700;
        font-family: monospace;
        font-size: 15px;
    }

    /* Resend Link Style */
    .resend-container {
        display: none; /* Hidden by default */
        flex-direction: column;
        align-items: center;
    }
    .resend-text {
        color: #666;
        margin-bottom: 5px;
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
    .resend-btn:hover {
        color: #0056b3;
    }

    /* Verify Button */
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
    .back-link:hover { text-decoration: underline; }
</style>

<div class="verify-container">
    <h2>Verify Your Account</h2>
    <p class="subtitle">
        We have sent a 4-digit code to:<br> 
        <strong style="color:#333;"><?php echo htmlspecialchars($_SESSION['temp_register_data']['email']); ?></strong>
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
                <span class="resend-text">Didn't receive code or code expired?</span>
                
                <button type="submit" name="resend_btn" class="resend-btn" formnovalidate>Resend Code</button>
            </div>
        </div>

        <button type="submit" name="verify_btn" class="btn-verify">Verify Code</button>
    </form>
    
    <a href="passanger_register.php" class="back-link">Wrong Email? Register Again</a>
</div>

<script>
    // --- OTP INPUT LOGIC ---
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            if (input.value.length > 1) input.value = input.value.slice(0, 1);
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

    // --- COUNTDOWN TIMER LOGIC ---
    // Get remaining seconds from PHP
    let timeLeft = <?php echo $remaining_seconds; ?>;
    
    const timerBox = document.getElementById('timer-box');
    const resendBox = document.getElementById('resend-box');
    const timeDisplay = document.getElementById('time');

    function startTimer() {
        // If time is already 0 (expired), show resend link immediately
        if (timeLeft <= 0) {
            showResend();
            return;
        }

        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                showResend();
            } else {
                // Format M:SS
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                
                // Add leading zero if seconds < 10
                seconds = seconds < 10 ? '0' + seconds : seconds;
                
                timeDisplay.textContent = minutes + ":" + seconds;
                timeLeft--;
            }
        }, 1000); // Update every 1 second
    }

    function showResend() {
        timerBox.style.display = 'none';
        resendBox.style.display = 'flex'; // Use flex to stack text and button
    }

    // Start the timer when page loads
    startTimer();
</script>

<?php include "footer.php"; ?>      