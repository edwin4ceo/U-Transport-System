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

// FUNCTION: CHECK LOGIN STATUS
// If user is already logged in, redirect them to dashboard immediately
if(isset($_SESSION['student_id'])){
    echo "<script>window.location.href='passenger_home.php';</script>";
    exit();
}

// Initialize variables to avoid PHP notices
$reg_name = "";
$reg_student_id = "";
$reg_email = "";
$reg_gender = "";

// =========================================================
// FUNCTION: REGISTER LOGIC
// Handles the sign-up process, validation, and OTP sending
// =========================================================
// NOTE: We check for 'reg_email' because disabled buttons don't send POST data
if(isset($_POST['reg_email'])){
    $reg_name             = strtoupper($_POST['reg_name']); 
    $reg_student_id       = $_POST['reg_student_id'];
    $reg_email            = $_POST['reg_email'];
    $reg_password         = $_POST['reg_password'];
    $reg_confirm_password = $_POST['reg_confirm_password'];
    $reg_gender           = $_POST['reg_gender']; 

    // SUB-FUNCTION: VALIDATION
    if (empty($reg_student_id)) {
        $_SESSION['swal_title'] = "Invalid Student ID";
        $_SESSION['swal_msg'] = "Student ID cannot be empty.";
        $_SESSION['swal_type'] = "error";
    }
    elseif (preg_match('/\d/', $reg_name)) {
        $_SESSION['swal_title'] = "Invalid Name";
        $_SESSION['swal_msg'] = "Name cannot contain numbers.";
        $_SESSION['swal_type'] = "error";
        $reg_name = ""; 
    }
    elseif (strlen($reg_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg'] = "Password must be at least 6 characters long.";
        $_SESSION['swal_type'] = "error";
    }
    elseif ($reg_password !== $reg_confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "Please ensure your confirm password is entered correctly."; 
        $_SESSION['swal_type'] = "error";
    }
    elseif (!str_contains($reg_email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "Only MMU student emails (@student.mmu.edu.my) are allowed!";
        $_SESSION['swal_type'] = "error";
    }
    elseif (empty($reg_gender)) {
        $_SESSION['swal_title'] = "Gender Required";
        $_SESSION['swal_msg'] = "Please select your gender.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // SUB-FUNCTION: CHECK DUPLICATE EMAIL
        $check = $conn->query("SELECT * FROM students WHERE email='$reg_email'");
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
        } 
        else {
            // SUB-FUNCTION: GENERATE OTP
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($reg_password, PASSWORD_BCRYPT);

            // Save temp data to session
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

            // SUB-FUNCTION: SEND EMAIL
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
                
                // Redirect to OTP Page
                echo "<script>window.location.href='verify_email.php';</script>";
                exit();
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
            }
        }
    }
    // Keep register tab open if error occurs
    echo "<script>window.onload = function() { register(); }</script>";
}

// =========================================================
// FUNCTION: LOGIN LOGIC
// Handles user authentication and redirection
// =========================================================
// NOTE: Check for 'login_email' to avoid disabled button issues
if(isset($_POST['login_email'])){
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
            
            // --- MODIFIED: SWEETALERT SUCCESS & REDIRECT ---
            // Displays a success modal before redirecting
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
                        title: 'Login Successful!',
                        text: 'Welcome back, " . htmlspecialchars($row['name']) . "!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        confirmButtonColor: '#005A9C'
                    }).then(function() {
                        window.location.href = 'passenger_home.php';
                    });
                </script>
            </body>
            </html>";
            exit(); 
        } else {
            $_SESSION['swal_title'] = "Incorrect Password";
            $_SESSION['swal_msg'] = "The password you entered is incorrect.";
            $_SESSION['swal_type'] = "error";
        }
    } else {
        $_SESSION['swal_title'] = "Email Not Found";
        $_SESSION['swal_msg'] = "This email is not registered.";
        $_SESSION['swal_type'] = "warning";
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* CSS: HEADER OVERRIDE */
    /* Force the header container to be transparent */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* CSS: PAGE LAYOUT */
    .wrapper {
        width: 100%;
        min-height: 700px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 10px; /* Moves content up */
        position: relative;
        overflow: hidden;
        background-color: #f6f5f7; 
    }

    /* CSS: NAVIGATION BUTTONS */
    .nav-button {
        position: absolute;
        top: 0px; 
        right: 10%; 
        display: flex;
        gap: 15px;
        z-index: 100;
    }

    .back-nav {
        position: absolute;
        top: 0px; 
        left: 10%; 
        z-index: 100;
    }

    /* CSS: BUTTON STYLING */
    .btn, .btn-back {
        height: 40px;
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
    
    .btn { width: 110px; }
    .btn-back { padding: 0 30px; gap: 8px; }

    .btn.white-btn, .btn:hover, .btn-back:hover {
        background: #005A9C; 
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
    }

    /* CSS: FORM CONTAINER */
    .form-box {
        position: relative;
        width: 600px; 
        height: 720px; 
        overflow: hidden;
        margin-top: 50px; 
        background: transparent !important;
        box-shadow: none !important;
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
        position: absolute;
        width: 100%;
        top: 0;
        transition: .5s ease-in-out;
        padding: 0 50px; 
    }

    .login-container { left: 0; opacity: 1; }
    .register-container { right: -120%; opacity: 0; pointer-events: none; } 

    /* CSS: TYPOGRAPHY */
    .top { margin-bottom: 20px; text-align: center; }
    .top span { color: #555; font-size: 14px; margin-bottom: 5px; display: block; }
    .top span a { color: #005A9C; text-decoration: none; font-weight: 600; cursor: pointer; }
    
    .top h2 { 
        font-size: 32px; 
        color: #333 !important; 
        font-weight: 600; 
        margin: 0;
        padding: 0;
        background: none !important;
        box-shadow: none !important;
    }

    /* CSS: INPUT BOXES (Darker Border #c4c4c4) */
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
        border: 1px solid #c4c4c4 !important; /* Darker border */
        transition: .3s;
    }

    .input-box:focus-within {
        background: #ffffff !important;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.15) !important;
        border: 1px solid #005A9C !important;
    }

    .input-box i {
        font-size: 18px;
        color: #888;
        margin-right: 15px; 
        transition: .3s;
        position: static !important; 
        transform: none !important;
    }

    .input-box:focus-within i { color: #005A9C; }

    /* =========================================
       CSS: STYLISH GENDER SELECTION (PINK & BLUE)
       ========================================= */
    .gender-box {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 20px;
        width: 100%;
    }

    .gender-option {
        flex: 1; 
        position: relative;
    }

    /* Hide the actual radio button */
    .gender-option input[type="radio"] {
        display: none;
    }

    /* Style the label to look like a button */
    .gender-option label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        height: 55px;
        background: #ffffff;
        border: 1px solid #c4c4c4;
        border-radius: 30px;
        font-size: 15px;
        color: #555;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    /* --- MALE BUTTON STYLES --- */
    /* Hover */
    label[for="gender-male"]:hover {
        background: #f0f8ff; /* Light Blue Tint */
        border-color: #005A9C;
    }
    /* Selected (Blue) */
    #gender-male:checked + label {
        background: #005A9C; /* Theme Blue */
        color: #ffffff;
        border-color: #005A9C;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
        transform: translateY(-2px);
    }

    /* --- FEMALE BUTTON STYLES --- */
    /* Hover */
    label[for="gender-female"]:hover {
        background: #fff0f5; /* Light Pink Tint */
        border-color: #E91E63;
    }
    /* Selected (Pink) */
    #gender-female:checked + label {
        background: #E91E63; /* Hot Pink */
        color: #ffffff;
        border-color: #E91E63;
        box-shadow: 0 4px 10px rgba(233, 30, 99, 0.3);
        transform: translateY(-2px);
    }

    /* CSS: AUTOFILL FIX */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px white inset !important;
        -webkit-text-fill-color: #333 !important;
        transition: background-color 5000s ease-in-out 0s;
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

    /* CSS: FOOTER LINKS */
    .two-col {
        display: flex;
        justify-content: flex-end; 
        font-size: 14px;
        margin-top: 15px;
        padding: 0 10px;
        color: #555;
    }
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
                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="login_email" class="input-field" placeholder="Username or Email" required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="login_password" id="loginPass" class="input-field" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('loginPass', this)"></i>
                </div>

                <button type="submit" name="login" class="submit">Sign In</button>

                <div class="two-col">
                    <div class="two">
                        <label><a href="passanger_forgot_password.php">Forgot password?</a></label>
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
                        <input type="radio" name="reg_gender" id="gender-male" value="Male" required>
                        <label for="gender-male">
                            <i class="fa-solid fa-mars"></i> Male
                        </label>
                    </div>
                    <div class="gender-option">
                        <input type="radio" name="reg_gender" id="gender-female" value="Female">
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

    // FUNCTION: SHOW LOGIN FORM
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

    // FUNCTION: SHOW REGISTER FORM
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

    // FUNCTION: HANDLE BUTTON LOADING STATE
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
        confirmButtonColor: '#005A9C'
    });
</script>
<?php 
    // Clear session after displaying
    unset($_SESSION['swal_title']);
    unset($_SESSION['swal_msg']);
    unset($_SESSION['swal_type']);
endif; 
?>