<?php
// FUNCTION: START SESSION
// Starts the session to handle user login states
session_start();

// SECTION: INCLUDES
// Connect to database and load helper functions
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP
// Required libraries for sending verification emails
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =========================================================
// FUNCTION: CHECK LOGIN STATUS
// =========================================================
// Logic: If user is logged in, normally we redirect them to home immediately.
// BUT, if they just logged in successfully (login_success is set), we must NOT redirect yet.
// We need to stay on this page to let the HTML/JS render the SweetAlert success popup first.
if(isset($_SESSION['student_id']) && !isset($_SESSION['login_success'])){
    echo "<script>window.location.href='passenger_home.php';</script>";
    exit();
}

// --- STICKY DATA RETRIEVAL (FROM SESSION) ---
// Retrieve previous inputs if an error occurred (so user doesn't re-type)
$reg_name       = isset($_SESSION['sticky']['reg_name']) ? $_SESSION['sticky']['reg_name'] : "";
$reg_student_id = isset($_SESSION['sticky']['reg_student_id']) ? $_SESSION['sticky']['reg_student_id'] : "";
$reg_email      = isset($_SESSION['sticky']['reg_email']) ? $_SESSION['sticky']['reg_email'] : "";
$reg_gender     = isset($_SESSION['sticky']['reg_gender']) ? $_SESSION['sticky']['reg_gender'] : "";
$login_email_val= isset($_SESSION['sticky']['login_email']) ? $_SESSION['sticky']['login_email'] : "";

// Clear sticky data immediately so it doesn't persist on manual refresh
unset($_SESSION['sticky']);

// =========================================================
// FUNCTION: REGISTER LOGIC
// Handles the sign-up process, validation, and OTP sending
// =========================================================
if(isset($_POST['action']) && $_POST['action'] === 'register'){
    // Retrieve inputs
    $reg_name             = strtoupper($_POST['reg_name']); 
    $reg_student_id       = $_POST['reg_student_id'];
    $reg_email            = $_POST['reg_email'];
    $reg_gender           = $_POST['reg_gender'];
    $reg_password         = $_POST['reg_password'];
    $reg_confirm_password = $_POST['reg_confirm_password'];

    // Helper function to handle errors and redirect (PRG Pattern)
    function registerError($title, $msg, $data) {
        $_SESSION['swal_title'] = $title;
        $_SESSION['swal_msg'] = $msg;
        $_SESSION['swal_type'] = "error";
        $_SESSION['sticky'] = $data; // Save inputs to sticky session
        $_SESSION['active_tab'] = 'register'; // Keep register tab open
        header("Location: passanger_login.php"); // Redirect to self
        exit();
    }

    // VALIDATION CHECKS
    if (empty($reg_student_id)) {
        registerError("Invalid Student ID", "Student ID cannot be empty.", $_POST);
    }
    elseif (preg_match('/\d/', $reg_name)) {
        registerError("Invalid Name", "Name cannot contain numbers.", $_POST);
    }
    elseif (strlen($reg_password) < 6) {
        registerError("Weak Password", "Password must be at least 6 characters long.", $_POST);
    }
    elseif ($reg_password !== $reg_confirm_password) {
        registerError("Password Mismatch", "Please ensure your confirm password is entered correctly.", $_POST);
    }
    elseif (!str_contains($reg_email, "@student.mmu.edu.my")) {
        registerError("Invalid Email", "Only MMU student emails (@student.mmu.edu.my) are allowed!", $_POST);
    }
    elseif (empty($reg_gender)) {
        // Gender specific warning
        $_SESSION['swal_title'] = "Gender Required";
        $_SESSION['swal_msg'] = "Please select your gender to proceed.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['sticky'] = $_POST;
        $_SESSION['active_tab'] = 'register';
        header("Location: passanger_login.php");
        exit();
    }
    else {
        // CHECK DUPLICATE EMAIL
        $check = $conn->query("SELECT * FROM students WHERE email='$reg_email'");
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
            $_SESSION['sticky'] = $_POST;
            $_SESSION['active_tab'] = 'register';
            header("Location: passanger_login.php");
            exit();
        } 
        else {
            // GENERATE OTP & HASH PASSWORD
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($reg_password, PASSWORD_BCRYPT);

            // Store temporary registration data in session
            $_SESSION['temp_register_data'] = [
                'name' => $reg_name, 
                'student_id' => $reg_student_id,
                'email' => $reg_email,
                'password_hash' => $password_hash,
                'gender' => $reg_gender,
                'otp_code' => $otp_code,
                'otp_timestamp' => time(),
                'resend_count' => 0 
            ];

            // SEND EMAIL
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
                $mail->addAddress($reg_email, $reg_name);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - U-Transport';
                $mail->Body    = "<h3>Hello $reg_name,</h3><p>Your verification code is: <b>$otp_code</b></p>";
                $mail->send();
                
                // Redirect to Verify OTP Page
                header("Location: verify_email.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
                $_SESSION['sticky'] = $_POST;
                $_SESSION['active_tab'] = 'register';
                header("Location: passanger_login.php");
                exit();
            }
        }
    }
}

// =========================================================
// FUNCTION: LOGIN LOGIC
// Handles user authentication
// =========================================================
if(isset($_POST['action']) && $_POST['action'] === 'login'){
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        // Verify Password
        if(password_verify($password, $row['password'])){
            // Login Success: Set Session
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];
            
            // SET SUCCESS FLAG FOR JS REDIRECT (This triggers the success alert)
            $_SESSION['login_success'] = true;
            $_SESSION['user_name'] = $row['name'];
            
            // Redirect to self to clear POST data and show alert
            header("Location: passanger_login.php");
            exit(); 
        } else {
            // Incorrect Password
            $_SESSION['swal_title'] = "Incorrect Password";
            $_SESSION['swal_msg'] = "The password you entered is incorrect.";
            $_SESSION['swal_type'] = "error";
            $_SESSION['sticky']['login_email'] = $email; // Retain email
            $_SESSION['active_tab'] = 'login';
            header("Location: passanger_login.php");
            exit();
        }
    } else {
        // Email Not Found
        $_SESSION['swal_title'] = "Email Not Found";
        $_SESSION['swal_msg'] = "This email is not registered.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['active_tab'] = 'login';
        // Do NOT retain email for security/UX preference
        header("Location: passanger_login.php");
        exit();
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* IMPORT POPPINS FONT (Ensures consistency for SweetAlert too) */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    /* GLOBAL FIX: Force Font & Prevent Selection */
    body, h2, h3, p, span, label, a, .top, .back-nav, .nav-button, .role-card, .swal2-popup {
        font-family: 'Poppins', sans-serif !important; 
        user-select: none;           
        -webkit-user-select: none;   
        cursor: default;             
    }

    /* === SWEETALERT CUSTOMIZATION (Unified Style) === */
    /* 1. Make the popup rounder */
    .swal2-popup {
        border-radius: 20px !important;
        padding: 30px !important;
    }
    /* 2. Make the title bolder */
    .swal2-title {
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 24px !important;
    }
    /* 3. Make the content text standard grey */
    .swal2-html-container {
        font-size: 15px !important;
        color: #666 !important;
    }
    /* 4. Style the Confirm Button (Blue & Rounded) */
    .swal2-confirm {
        border-radius: 10px !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        font-size: 15px !important;
        background-color: #005A9C !important;
        box-shadow: none !important;
    }
    .swal2-confirm:focus {
        box-shadow: 0 0 0 3px rgba(0, 90, 156, 0.3) !important;
    }
    /* ================================================= */

    input, select { 
        user-select: text !important; -webkit-user-select: text !important; cursor: text !important; 
        font-family: 'Poppins', sans-serif !important;
    }
    
    a, button, .btn, .btn-back, .submit, .toggle-pass, .gender-option label { 
        cursor: pointer !important; 
    }

    /* CSS: HEADER OVERRIDE */
    .content-area {
        background: transparent !important; box-shadow: none !important; border: none !important;
        width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important;
    }

    /* CSS: PAGE LAYOUT */
    .wrapper {
        width: 100%; min-height: 700px; display: flex; justify-content: center; align-items: flex-start;
        padding-top: 10px; position: relative; overflow: hidden; background-color: #f6f5f7; 
    }

    /* CSS: NAVIGATION BUTTONS */
    .nav-button { position: absolute; top: 0px; right: 10%; display: flex; gap: 15px; z-index: 100; }
    .back-nav { position: absolute; top: 0px; left: 10%; z-index: 100; }

    /* CSS: BUTTON STYLING */
    .btn, .btn-back {
        height: 40px; border: none; border-radius: 30px !important; 
        background: #ffffff; color: #005A9C; font-weight: 600; cursor: pointer; transition: .3s;
        display: flex; align-items: center; justify-content: center; text-decoration: none;
        font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn { width: 110px; }
    .btn-back { padding: 0 30px; gap: 8px; }
    .btn.white-btn, .btn:hover, .btn-back:hover {
        background: #005A9C; color: #fff; box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
    }

    /* CSS: FORM CONTAINER */
    .form-box {
        position: relative; width: 600px; height: 720px; overflow: hidden;
        margin-top: 50px; background: transparent !important; box-shadow: none !important;
    }

    /* CSS: MOBILE RESPONSIVE */
    @media (max-width: 768px) {
        .form-box { width: 95%; height: 800px; margin-top: 60px; }
        .nav-button { right: 5%; top: 10px; gap: 10px; }
        .back-nav { left: 5%; top: 10px; }
        .btn { width: 90px; font-size: 12px; }
        .btn-back { padding: 0 15px; font-size: 12px; }
        .login-container, .register-container { padding: 0 20px; }
    }

    /* CSS: SLIDING ANIMATION */
    .login-container, .register-container {
        position: absolute; width: 100%; top: 0; transition: .5s ease-in-out; padding: 0 50px; 
    }
    .login-container { left: 0; opacity: 1; }
    .register-container { right: -120%; opacity: 0; pointer-events: none; } 

    /* CSS: TYPOGRAPHY */
    .top { margin-bottom: 20px; text-align: center; }
    .top span { color: #555; font-size: 14px; margin-bottom: 5px; display: block; }
    .top span a { color: #005A9C; text-decoration: none; font-weight: 600; }
    .top h2 { 
        font-size: 32px; color: #333 !important; font-weight: 600; margin: 0; padding: 0; 
        background: none !important; box-shadow: none !important;
    }

    /* CSS: INPUT BOXES (2px Border for Consistency) */
    .input-box {
        display: flex; align-items: center; width: 100%; height: 55px;
        background: #ffffff !important; box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important; 
        border-radius: 30px !important; margin-bottom: 20px; padding: 0 20px;
        border: 2px solid #c4c4c4 !important; transition: .3s;
    }
    .input-box:focus-within {
        background: #ffffff !important; box-shadow: 0 4px 10px rgba(0, 90, 156, 0.15) !important; 
        border: 2px solid #005A9C !important;
    }
    .input-box i { font-size: 18px; color: #888; margin-right: 15px; transition: .3s; }
    .input-box:focus-within i { color: #005A9C; }

    /* CSS: GENDER SELECTION */
    .gender-box { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 20px; width: 100%; }
    .gender-option { flex: 1; position: relative; }
    .gender-option input[type="radio"] { display: none; }
    .gender-option label {
        display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; height: 55px;
        background: #ffffff; border: 2px solid #c4c4c4; border-radius: 30px;
        font-size: 15px; color: #555; cursor: pointer; transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    label[for="gender-male"]:hover { background: #f0f8ff; border-color: #005A9C; }
    #gender-male:checked + label {
        background: #005A9C; color: #ffffff; border-color: #005A9C; box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3); transform: translateY(-2px);
    }
    label[for="gender-female"]:hover { background: #fff0f5; border-color: #E91E63; }
    #gender-female:checked + label {
        background: #E91E63; color: #ffffff; border-color: #E91E63; box-shadow: 0 4px 10px rgba(233, 30, 99, 0.3); transform: translateY(-2px);
    }

    /* CSS: AUTOFILL FIX */
    input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus, input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px white inset !important; -webkit-text-fill-color: #333 !important; transition: background-color 5000s ease-in-out 0s;
    }
    .input-field {
        flex: 1; background: transparent !important; border: none !important; outline: none !important;
        color: #333 !important; font-size: 15px !important; height: 100%; padding: 0 !important; margin: 0 !important; box-shadow: none !important;
    }
    .input-field::placeholder { color: #999; font-weight: 400; }
    .input-box .toggle-pass { margin-right: 0; margin-left: 10px; cursor: pointer; color: #999; }
    .input-box .toggle-pass:hover { color: #005A9C; }

    /* CSS: SUBMIT BUTTON */
    .submit {
        width: 100%; height: 55px; background: #005A9C !important; border: none !important; border-radius: 30px !important;
        color: #fff !important; font-size: 16px; font-weight: 600; cursor: pointer; transition: .3s;
        box-shadow: 0 8px 15px rgba(0, 90, 156, 0.2); margin-top: 10px; display: flex; align-items: center; justify-content: center;
    }
    .submit:hover { background: #004a80 !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 90, 156, 0.3); }
    .submit:disabled { background: #ccc !important; cursor: not-allowed; transform: none; box-shadow: none; }

    /* CSS: FOOTER LINKS */
    .two-col { display: flex; justify-content: flex-end; font-size: 14px; margin-top: 15px; padding: 0 10px; color: #555; position: relative; z-index: 10; }
    .two-col a { color: #005A9C; text-decoration: none; font-weight: 600; }
    .two-col a:hover { text-decoration: underline; }
</style>

<div class="wrapper">
    
    <div class="back-nav">
        <a href="index.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="nav-button">
        <button class="btn white-btn" id="loginBtn" onclick="login()">Sign In</button>
        <button class="btn" id="registerBtn" onclick="register()">Sign Up</button>
    </div>

    <div class="form-box">
        
        <div class="login-container" id="login">
            <div class="top">
                <span>Don't have an account? <a href="#" onclick="register()">Sign Up</a></span>
                <h2>Login</h2>
            </div>

            <form action="" method="POST" onsubmit="handleLoading(this)">
                <input type="hidden" name="action" value="login">
                
                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="login_email" class="input-field" placeholder="Username or Email" value="<?php echo htmlspecialchars($login_email_val); ?>" required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="login_password" id="loginPass" class="input-field" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('loginPass', this)"></i>
                </div>

                <button type="submit" name="login" class="submit">Sign In</button>

                <div class="two-col">
                    <div class="two">
                        <a href="passanger_forgot_password.php">Forgot password?</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="register-container" id="register">
            <div class="top">
                <span>Have an account? <a href="#" onclick="login()">Login</a></span>
                <h2>Sign Up</h2>
            </div>

            <form action="" method="POST" onsubmit="handleLoading(this)">
                <input type="hidden" name="action" value="register">
                
                <div class="input-box">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="reg_name" class="input-field" value="<?php echo htmlspecialchars($reg_name); ?>" placeholder="Full Name" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="reg_student_id" id="studentIDInput" class="input-field" value="<?php echo htmlspecialchars($reg_student_id); ?>" placeholder="Student ID" required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="reg_email" id="emailInput" class="input-field" value="<?php echo htmlspecialchars($reg_email); ?>" placeholder="ID@student.mmu.edu.my" required>
                </div>

                <div class="gender-box">
                    <div class="gender-option">
                        <input type="radio" name="reg_gender" id="gender-male" value="Male" <?php echo ($reg_gender == 'Male') ? 'checked' : ''; ?>>
                        <label for="gender-male">
                            <i class="fa-solid fa-mars"></i> Male
                        </label>
                    </div>
                    <div class="gender-option">
                        <input type="radio" name="reg_gender" id="gender-female" value="Female" <?php echo ($reg_gender == 'Female') ? 'checked' : ''; ?>>
                        <label for="gender-female">
                            <i class="fa-solid fa-venus"></i> Female
                        </label>
                    </div>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="reg_password" id="regPass" class="input-field" placeholder="Password (Min 6 chars)" minlength="6" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('regPass', this)"></i>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="reg_confirm_password" id="regConfPass" class="input-field" placeholder="Confirm Password" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('regConfPass', this)"></i>
                </div>

                <button type="submit" name="register" class="submit">Register</button>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    var a = document.getElementById("loginBtn");
    var b = document.getElementById("registerBtn");
    var x = document.getElementById("login");
    var y = document.getElementById("register");

    // Default: Check if we should show Register tab (from Sticky session)
    <?php if(isset($_SESSION['active_tab']) && $_SESSION['active_tab'] == 'register'): ?>
        window.onload = function() { register(); };
        <?php unset($_SESSION['active_tab']); ?>
    <?php endif; ?>

    function login() {
        x.style.left = "0px";
        y.style.right = "-120%"; 
        x.style.opacity = 1;
        x.style.pointerEvents = "auto";
        y.style.opacity = 0;
        y.style.pointerEvents = "none";
        a.className += " white-btn";
        b.className = "btn";
    }

    function register() {
        x.style.left = "-120%"; 
        y.style.right = "0px";
        x.style.opacity = 0;
        x.style.pointerEvents = "none";
        y.style.opacity = 1;
        y.style.pointerEvents = "auto";
        a.className = "btn";
        b.className += " white-btn";
    }

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

    function handleLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        if(btn.disabled) return false;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        
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
        confirmButtonColor: '#005A9C', // Always use theme Blue
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
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

<?php if(isset($_SESSION['login_success'])): ?>
<script>
    Swal.fire({
        title: 'Login Successful!',
        text: 'Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false,
        confirmButtonColor: '#005A9C', // Always use theme Blue
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm'
        }
    }).then(function() {
        window.location.href = 'passenger_home.php';
    });
</script>
<?php 
    // Clear success flags
    unset($_SESSION['login_success']);
    unset($_SESSION['user_name']);
endif; 
?>