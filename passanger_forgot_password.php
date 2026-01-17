<?php
// =========================================================
// SECTION: CACHE CONTROL
// =========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// FUNCTION: CHECK LOGIN STATUS
if(isset($_SESSION['student_id'])){
    echo "<script>window.location.href='passenger_home.php';</script>";
    exit();
}

// Initialize Sticky Form Variables (Retain data if error occurs)
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : "";
$email      = isset($_POST['email']) ? $_POST['email'] : "";

// =========================================================
// FUNCTION: RESET PASSWORD LOGIC
// =========================================================
if(isset($_POST['action']) && $_POST['action'] === 'reset_password'){
    $student_id       = $_POST['student_id'];
    $email            = $_POST['email']; 
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate Email Domain
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg'] = "Please use your MMU student email (@student.mmu.edu.my).";
        $_SESSION['swal_type'] = "error";
    }
    // 2. Validate Password Match
    elseif($new_password !== $confirm_password){
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "New passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
    // 3. Validate Password Strength
    elseif(strlen($new_password) < 6){
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg'] = "Password must be at least 6 characters long.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // 4. Check if Student ID and Email match in DB
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND student_id = ?");
        $stmt->bind_param("ss", $email, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            $row = $result->fetch_assoc();
            
            // --- NEW CHECK: PREVENT REUSING OLD PASSWORD ---
            if(password_verify($new_password, $row['password'])) {
                $_SESSION['swal_title'] = "Password Exists";
                $_SESSION['swal_msg'] = "You cannot use your current password. Please choose a new one.";
                $_SESSION['swal_type'] = "warning";
            }
            else {
                // Only proceed if password is NEW
                $name = $row['name']; 
                
                // 5. Generate OTP
                $otp = rand(1000, 9999);
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Store temp data for verification step
                $_SESSION['temp_reset_data'] = [
                    'email' => $email,
                    'name' => $name,
                    'student_id' => $student_id,
                    'new_password_hash' => $new_password_hash,
                    'otp_code' => $otp,
                    'otp_timestamp' => time(),
                    'resend_count' => 0
                ];

                // 6. Send OTP Email
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
                    $mail->Body    = "<h3>Hello $name,</h3><p>Your verification code is: <b>$otp</b></p>";
                    $mail->send();
                    
                    // Redirect to OTP page
                    echo "<script>window.location.href='verify_reset_otp.php';</script>";
                    exit();
                } catch (Exception $e) {
                    $_SESSION['swal_title'] = "Email Error";
                    $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                    $_SESSION['swal_type'] = "error";
                }
            }
        } else {
            $_SESSION['swal_title'] = "Verification Failed";
            $_SESSION['swal_msg'] = "The Student ID and Email provided do not match our records.";
            $_SESSION['swal_type'] = "error";
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* 1. ANIMATION: FADE IN UP (Same as Login Page) */
    @keyframes fadeInUpPage {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* GLOBAL FIX: Prevent Text Selection */
    body, h2, h3, p, span, label, a, .top, .back-nav, .warning-text {
        user-select: none; -webkit-user-select: none; cursor: default;
    }
    input { user-select: text !important; -webkit-user-select: text !important; cursor: text !important; }
    a, button, .btn-back, .submit, .toggle-pass { cursor: pointer !important; }

    /* CSS: HEADER OVERRIDE */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* CSS: PAGE WRAPPER */
    .wrapper {
        width: 100%;
        min-height: 800px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 10px; 
        position: relative;
        overflow: hidden;
        background-color: #f6f5f7; 
    }

    /* CSS: BACK BUTTON (With Animation) */
    .back-nav {
        position: absolute;
        top: 0px; 
        left: 10%; 
        z-index: 100;
        
        /* APPLY ANIMATION */
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    .btn-back {
        height: 40px;
        padding: 0 30px;
        gap: 8px;
        border: none;
        border-radius: 30px !important; 
        background: #ffffff; 
        color: #005A9C; 
        font-weight: 600;
        cursor: pointer;
        transition: .3s;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn-back:hover {
        background: #005A9C; 
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
    }

    /* CSS: FORM CONTAINER (With Animation) */
    .reset-box {
        width: 500px; 
        margin-top: 60px; 
        padding: 0 30px;
        
        /* APPLY ANIMATION */
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    @media (max-width: 768px) {
        .reset-box { width: 90%; margin-top: 60px; padding: 0; }
        .back-nav { left: 5%; top: 10px; }
        .btn-back { padding: 0 15px; font-size: 12px; }
    }

    /* CSS: TYPOGRAPHY */
    .top { margin-bottom: 30px; text-align: center; }
    .top h2 { 
        font-size: 30px; 
        color: #333 !important; 
        font-weight: 600; 
        margin: 0;
        padding: 0;
        background: none !important;
        box-shadow: none !important;
        user-select: none; cursor: default;
    }
    
    .warning-text {
        color: #e74c3c; /* Red color */
        font-size: 12px;
        margin-top: 2px; 
        margin-bottom: 15px; 
        margin-left: 20px; 
        text-align: left;
        font-weight: 500;
        display: block;
    }

    /* CSS: INPUT BOXES (Matches Login Style) */
    .input-box {
        display: flex;
        align-items: center;
        width: 100%;
        height: 55px;
        background: #ffffff !important; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important; 
        border-radius: 30px !important; 
        margin-bottom: 20px;
        padding: 0 20px;
        border: 2px solid #c4c4c4 !important; 
        transition: .3s;
    }

    .input-box:focus-within {
        background: #ffffff !important;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.15) !important;
        border: 2px solid #005A9C !important;
    }

    .input-box i {
        font-size: 18px;
        color: #888;
        margin-right: 15px; 
        transition: .3s;
    }

    .input-box:focus-within i { color: #005A9C; }

    /* Fix Blue Autofill Background */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px white inset !important;
        -webkit-text-fill-color: #333 !important;
    }

    .input-field {
        flex: 1; 
        background: transparent !important;
        border: none !important;
        outline: none !important;
        color: #333 !important;
        font-size: 15px !important;
        height: 100%;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
    }
    .input-field::placeholder { color: #999; font-weight: 400; }

    /* Password Toggle */
    .input-box .toggle-pass {
        margin-right: 0;
        margin-left: 10px; 
        cursor: pointer;
        color: #999;
    }
    .input-box .toggle-pass:hover { color: #005A9C; }

    /* CSS: SUBMIT BUTTON */
    .submit {
        width: 100%;
        height: 55px;
        background: #005A9C !important;
        border: none !important;
        border-radius: 30px !important;
        color: #fff !important;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: .3s;
        box-shadow: 0 8px 15px rgba(0, 90, 156, 0.2);
        margin-top: 10px;
        display: flex; align-items: center; justify-content: center;
    }
    .submit:hover { 
        background: #004a80 !important; 
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 90, 156, 0.3);
    }
    .submit:disabled { background: #ccc !important; cursor: not-allowed; transform: none; box-shadow: none; }

</style>

<div class="wrapper">
    
    <div class="back-nav">
        <a href="passanger_login.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <div class="reset-box">
        
        <div class="top">
            <h2>Reset Password</h2>
        </div>

        <form action="" method="POST" onsubmit="handleLoading(this)">
            <input type="hidden" name="action" value="reset_password">

            <div class="input-box">
                <i class="fa-solid fa-id-card"></i>
                <input type="text" name="student_id" id="studentIDInput" class="input-field" value="<?php echo htmlspecialchars($student_id); ?>" placeholder="Student ID" required>
            </div>

            <div class="input-box" style="margin-bottom: 5px;">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" id="emailInput" class="input-field" value="<?php echo htmlspecialchars($email); ?>" placeholder="ID@student.mmu.edu.my" required>
            </div>
            
            <span class="warning-text">* You will need to verify your email in the next step.</span>

            <div class="input-box">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="new_password" id="newPass" class="input-field" placeholder="New Password (Min 6 chars)" minlength="6" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('newPass', this)"></i>
            </div>

            <div class="input-box">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="confirm_password" id="confirmPass" class="input-field" placeholder="Confirm New Password" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('confirmPass', this)"></i>
            </div>

            <button type="submit" name="reset_password" class="submit">Verify Email & Reset</button>
        </form>

    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // FUNCTION: TOGGLE PASSWORD VISIBILITY
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye', 'fa-eye-slash'); 
        }
    }

    // FUNCTION: LOADING SPINNER
    function handleLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        if(btn.disabled) return false;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        
        // Timeout safety
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 10000);
        
        return true;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#005A9C',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            htmlContainer: 'swal2-html-container',
            confirmButton: 'swal2-confirm'
        }
    });
</script>
<?php 
    // Clear session after displaying
    unset($_SESSION['swal_title']);
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_type']);
endif; 
?>