<?php
// FUNCTION: START SESSION
session_start();

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check session
if (!isset($_SESSION['temp_register_data'])) {
    header("Location: passanger_login.php");
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
            
            // --- SWEETALERT SUCCESS & REDIRECT ---
            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <style>body { font-family: 'Poppins', sans-serif; background-color: #f6f5f7; }</style>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: 'Registration Successful!',
                    text: 'Your account has been verified. Please login.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#005A9C'
                }).then(() => {
                    window.location.href = 'passanger_login.php';
                });
            </script>
            </body></html>";
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

    /* CSS: FORM CONTAINER (CARD STYLE) */
    .verify-box {
        width: 500px; margin-top: 60px; padding: 40px 30px; 
        background: #ffffff; border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        text-align: center;
        position: relative;
    }

    @media (max-width: 768px) {
        .verify-box { width: 90%; margin-top: 60px; padding: 30px 20px; }
    }

    /* CSS: TYPOGRAPHY (FIX: No Blinking Cursor) */
    .top h2 { 
        font-size: 30px; color: #333 !important; font-weight: 600; margin-bottom: 10px;
        user-select: none; /* Prevents text selection/cursor */
        cursor: default;
    }
    .top p {
        font-size: 15px; color: #666; margin-bottom: 30px; line-height: 1.6;
        user-select: none; /* Prevents text selection/cursor */
        cursor: default;
    }
    .top strong { color: #005A9C; }

    /* CSS: OTP INPUT FIELDS (FIX: No Arrows) */
    .otp-field { display: flex; justify-content: center; gap: 15px; margin-bottom: 15px; } /* Closer to timer */
    
    .otp-field input { 
        width: 60px; height: 60px; 
        font-size: 24px; font-weight: 700; text-align: center; color: #333;
        border: 1px solid #c4c4c4; /* Darker border matching login */
        border-radius: 12px; outline: none; background: #fff;
        transition: .3s;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        -moz-appearance: textfield; /* Firefox remove arrows */
    }
    
    /* Chrome, Safari, Edge, Opera - Remove Arrows */
    .otp-field input::-webkit-outer-spin-button,
    .otp-field input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .otp-field input:focus { 
        border-color: #005A9C; box-shadow: 0 4px 10px rgba(0, 90, 156, 0.2); transform: translateY(-2px);
    }

    /* CSS: ACTION AREA (Timer & Resend) */
    .action-area { 
        margin-bottom: 25px; font-size: 14px; color: #666; 
        display: flex; justify-content: center; /* Centered */
    }
    
    #timer-box { user-select: none; cursor: default; }
    #time { font-weight: 700; color: #333; }
    
    .resend-btn { 
        background: none; border: none; color: #005A9C; cursor: pointer; 
        text-decoration: none; font-weight: 600; font-size: 14px; 
        transition: .3s; 
    }
    .resend-btn:hover { text-decoration: underline; color: #004a80; }

    /* CSS: VERIFY BUTTON (Same as Login) */
    .btn-verify {
        width: 100%; height: 55px; background: #005A9C !important; border: none !important; border-radius: 30px !important;
        color: #fff !important; font-size: 16px; font-weight: 600; cursor: pointer; transition: .3s;
        box-shadow: 0 8px 15px rgba(0, 90, 156, 0.2); display: flex; align-items: center; justify-content: center;
    }
    .btn-verify:hover { background: #004a80 !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 90, 156, 0.3); }

    /* CSS: BACK LINK (Blue Bold Style) */
    .back-link { 
        display: block; margin-top: 20px; font-size: 14px; 
        color: #005A9C; /* Blue */
        font-weight: 600; /* Bold */
        text-decoration: none; transition: .3s; 
    }
    .back-link:hover { text-decoration: underline; }

</style>

<div class="wrapper">
    <div class="verify-box">
        
        <div class="top">
            <h2>Verify Your Account</h2>
            <p>We have sent a 4-digit code to:<br> <strong><?php echo htmlspecialchars($_SESSION['temp_register_data']['email']); ?></strong></p>
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

            <button type="submit" name="verify_btn" class="btn-verify">Verify & Register</button>
        </form>

        <a href="passanger_login.php" class="back-link">Wrong email? Back to registration</a>
    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    // Prevent Enter key accidental submission unless button clicked
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            event.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Action Required',
                text: 'Please enter the OTP number and click the Verify button.',
                confirmButtonColor: '#005A9C'
            });
        }
    });

    // Auto-focus logic for OTP boxes
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
    unset($_SESSION['swal_title']);
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_type']);
endif; 
?>