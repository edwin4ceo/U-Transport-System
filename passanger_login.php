<?php
// Start session to manage user login state
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in -> Go to Dashboard
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

// Initialize variables to prevent undefined errors
$reg_name = "";
$reg_student_id = "";
$reg_email = "";
$reg_gender = "";

// ---------------------------------------------------------
// PHP LOGIC: REGISTER
// ---------------------------------------------------------
if(isset($_POST['register'])){
    $reg_name             = strtoupper($_POST['reg_name']); 
    $reg_student_id       = $_POST['reg_student_id'];
    $reg_email            = $_POST['reg_email'];
    $reg_password         = $_POST['reg_password'];
    $reg_confirm_password = $_POST['reg_confirm_password'];
    $reg_gender           = $_POST['reg_gender']; 

    // Validation Logic
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
    else {
        // Check for duplicate email
        $check = $conn->query("SELECT * FROM students WHERE email='$reg_email'");
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
        } 
        else {
            // Generate OTP & Hash Password
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($reg_password, PASSWORD_BCRYPT);

            // Store temporary data for verification
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

            // Send Email via PHPMailer
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
                
                header("Location: verify_email.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
            }
        }
    }
    // JS to keep register form active if error occurs
    echo "<script>window.onload = function() { register(); }</script>";
}

// ---------------------------------------------------------
// PHP LOGIC: LOGIN
// ---------------------------------------------------------
if(isset($_POST['login'])){
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])){
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];
            alert("Login successful! Redirecting...");
            redirect("passenger_home.php"); 
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
    /* --- 1. HEADER FIX (Keep it visible but transparent container) --- */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* --- 2. PAGE WRAPPER --- */
    .wrapper {
        width: 100%;
        min-height: 700px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 30px; 
        position: relative;
        overflow: hidden;
        background-color: #f6f5f7; 
    }

    /* --- 3. TOGGLE BUTTONS (Right Side) --- */
    .nav-button {
        position: absolute;
        top: 10px;
        right: 10%; 
        display: flex;
        gap: 15px;
        z-index: 100;
    }

    /* --- 4. BACK BUTTON (Left Side) - NEW ADDITION --- */
    .back-nav {
        position: absolute;
        top: 10px;
        left: 10%; /* Symmetrical to the right buttons */
        z-index: 100;
    }

    /* Shared Button Style (Applies to Toggle & Back buttons) */
    .btn, .btn-back {
        height: 40px;
        border: none;
        border-radius: 30px !important; 
        background: rgba(0, 90, 156, 0.1); /* Light Blue Transparent */
        color: #005A9C;
        font-weight: 600;
        cursor: pointer;
        transition: .3s;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none; /* For the link */
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
    }

    /* Specific Widths */
    .btn { width: 110px; }
    .btn-back { padding: 0 30px; gap: 8px; } /* Auto width with padding */

    /* Active/Hover State */
    .btn.white-btn, .btn:hover, .btn-back:hover {
        background: #005A9C; 
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
    }

    /* --- 5. FORM CONTAINER --- */
    .form-box {
        position: relative;
        width: 600px; 
        height: 680px; 
        overflow: hidden;
        margin-top: 40px;
        background: transparent !important;
        box-shadow: none !important;
    }

    .login-container, .register-container {
        position: absolute;
        width: 100%;
        top: 0;
        transition: .5s ease-in-out;
        padding: 0 50px; 
    }

    .login-container { left: 0; opacity: 1; }
    .register-container { right: -620px; opacity: 0; pointer-events: none; } 

    /* Titles */
    .top { margin-bottom: 30px; text-align: center; }
    .top span { color: #555; font-size: 14px; margin-bottom: 10px; display: block; }
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

    /* --- 6. INPUT BOX STYLING --- */
    .input-box {
        display: flex;
        align-items: center;
        width: 100%;
        height: 55px;
        background: #e8e8e8 !important; 
        border-radius: 30px !important; 
        margin-bottom: 20px;
        padding: 0 20px;
        border: 1px solid transparent;
        transition: .3s;
    }

    .input-box:focus-within {
        background: #fff !important;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.15);
        border: 1px solid #005A9C;
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

    .input-box .toggle-pass {
        margin-right: 0;
        margin-left: 10px; 
        cursor: pointer;
        color: #999;
    }
    .input-box .toggle-pass:hover { color: #005A9C; }

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
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
        margin-top: 10px;
    }
    .submit:hover { background: #004a80 !important; transform: scale(1.02); }

    .two-col {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-top: 15px;
        padding: 0 10px;
    }
    .two-col a { color: #005A9C; text-decoration: none; }
    
    select.input-field { cursor: pointer; color: #555 !important; }
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

            <form action="" method="POST">
                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="login_email" class="input-field" placeholder="Username or Email" required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="login_password" id="loginPass" class="input-field" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('loginPass', this)"></i>
                </div>

                <input type="submit" name="login" class="submit" value="Sign In">

                <div class="two-col">
                    <div class="one">
                        <input type="checkbox" id="login-check">
                        <label for="login-check"> Remember Me</label>
                    </div>
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

            <form action="" method="POST" onkeydown="return event.key != 'Enter';">
                
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
                    <input type="email" name="reg_email" id="emailInput" class="input-field" value="<?php echo htmlspecialchars($reg_email); ?>" placeholder="ID@student.mmu.edu.my" readonly required>
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-venus-mars"></i>
                    <select name="reg_gender" class="input-field" required>
                        <option value="" disabled <?php echo ($reg_gender == "") ? 'selected' : ''; ?> hidden>Select Gender</option>
                        <option value="Male" <?php echo ($reg_gender == "Male") ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($reg_gender == "Female") ? 'selected' : ''; ?>>Female</option>
                    </select>
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

                <input type="submit" name="register" class="submit" value="Register">
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

    function login() {
        x.style.left = "0px";
        y.style.right = "-620px";
        x.style.opacity = 1;
        x.style.pointerEvents = "auto";
        y.style.opacity = 0;
        y.style.pointerEvents = "none";
        a.className += " white-btn";
        b.className = "btn";
    }

    function register() {
        x.style.left = "-610px";
        y.style.right = "0px";
        x.style.opacity = 0;
        x.style.pointerEvents = "none";
        y.style.opacity = 1;
        y.style.pointerEvents = "auto";
        a.className = "btn";
        b.className += " white-btn";
    }

    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');
    if(studentIdInput){
        studentIdInput.addEventListener('input', function() {
            const id = this.value.trim();
            if (id.length > 0) { emailInput.value = id + "@student.mmu.edu.my"; }
            else { emailInput.value = ""; }
        });
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
</script>