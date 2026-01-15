<?php
// Start the session to manage user states and messages
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER (From original register logic) ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in -> Go to Dashboard
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

// Initialize variables for the forms to prevent "undefined variable" errors
$reg_name = "";
$reg_student_id = "";
$reg_email = "";
$reg_gender = "";

// ---------------------------------------------------------
// LOGIC 1: HANDLING REGISTRATION (From passanger_register.php)
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
        // Check if email already exists
        $check = $conn->query("SELECT * FROM students WHERE email='$reg_email'");
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
        } 
        else {
            // Proceed with OTP generation
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($reg_password, PASSWORD_BCRYPT);

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
                
                // Redirect to verification page
                header("Location: verify_email.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
            }
        }
    }
    // If registration failed, keep the "right-panel-active" class to stay on register slide
    echo "<script>window.onload = function() { document.getElementById('container').classList.add('right-panel-active'); }</script>";
}

// ---------------------------------------------------------
// LOGIC 2: HANDLING LOGIN (From passanger_login.php)
// ---------------------------------------------------------
if(isset($_POST['login'])){
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    // 1. Check if the email exists in the database
    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            // Login Success
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful! Redirecting...");
            redirect("passenger_home.php"); 
        } 
        else {
            // Wrong Password
            $_SESSION['swal_title'] = "Incorrect Password";
            $_SESSION['swal_msg'] = "The password you entered is incorrect.";
            $_SESSION['swal_type'] = "error";
        }
    } 
    else {
        // Email Not Found
        $_SESSION['swal_title'] = "Email Not Found";
        $_SESSION['swal_msg'] = "This email is not registered in our system.";
        $_SESSION['swal_type'] = "warning";
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Base styles to center the container */
    body {
        background: #f6f5f7;
        font-family: 'Poppins', sans-serif;
    }

    /* Main Container with relative positioning */
    .container-custom {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
                    0 10px 10px rgba(0,0,0,0.22);
        position: relative;
        overflow: hidden;
        width: 850px; /* Wider to fit your fields */
        max-width: 100%;
        min-height: 650px; /* Taller to fit the registration form */
        margin: 50px auto; /* Center on page */
    }

    /* Form Container Base Styles */
    .form-container {
        position: absolute;
        top: 0;
        height: 100%;
        transition: all 0.6s ease-in-out;
    }

    /* Form Styling */
    form {
        background-color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 50px;
        height: 100%;
        text-align: center;
    }

    h1 {
        font-weight: bold;
        margin: 0;
        margin-bottom: 15px;
        color: #333;
    }

    span {
        font-size: 12px;
        margin-bottom: 15px;
        color: #888;
    }

    /* Input Fields Styling */
    input, select {
        background-color: #eee;
        border: none;
        padding: 12px 15px;
        margin: 8px 0;
        width: 100%;
        border-radius: 4px;
        outline: none;
    }

    /* Button Styling (Using your Blue Color) */
    button.btn-action {
        border-radius: 20px;
        border: 1px solid #005A9C;
        background-color: #005A9C;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
        padding: 12px 45px;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: transform 80ms ease-in;
        margin-top: 15px;
        cursor: pointer;
    }

    button.btn-action:active {
        transform: scale(0.95);
    }

    button.btn-action:focus {
        outline: none;
    }

    /* Ghost Button (for the Overlay) */
    button.ghost {
        background-color: transparent;
        border-color: #FFFFFF;
        color: white;
        border-radius: 20px;
        border: 1px solid #FFFFFF;
        font-size: 12px;
        font-weight: bold;
        padding: 12px 45px;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        margin-top: 15px;
    }

    /* Forgot Password Link */
    .forgot-pass {
        color: #333;
        font-size: 14px;
        text-decoration: none;
        margin: 15px 0;
    }

    /* --- Animations and Positioning --- */

    /* Sign In Container (Default Left) */
    .sign-in-container {
        left: 0;
        width: 50%;
        z-index: 2;
    }

    /* Sign Up Container (Default Right, Hidden) */
    .sign-up-container {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
    }

    /* Active State: Sign Up moves in */
    .container-custom.right-panel-active .sign-in-container {
        transform: translateX(100%);
    }

    .container-custom.right-panel-active .sign-up-container {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
        animation: show 0.6s;
    }

    @keyframes show {
        0%, 49.99% { opacity: 0; z-index: 1; }
        50%, 100% { opacity: 1; z-index: 5; }
    }

    /* Overlay Container (The colored sliding part) */
    .overlay-container {
        position: absolute;
        top: 0;
        left: 50%;
        width: 50%;
        height: 100%;
        overflow: hidden;
        transition: transform 0.6s ease-in-out;
        z-index: 100;
    }

    .container-custom.right-panel-active .overlay-container {
        transform: translateX(-100%);
    }

    /* The Overlay Gradient Background */
    .overlay {
        background: #005A9C;
        background: -webkit-linear-gradient(to right, #004a80, #005A9C);
        background: linear-gradient(to right, #004a80, #005A9C);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: 0 0;
        color: #FFFFFF;
        position: relative;
        left: -100%;
        height: 100%;
        width: 200%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
    }

    .container-custom.right-panel-active .overlay {
        transform: translateX(50%);
    }

    /* Overlay Panels (Text inside the slider) */
    .overlay-panel {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 40px;
        text-align: center;
        top: 0;
        height: 100%;
        width: 50%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
    }

    .overlay-left {
        transform: translateX(-20%);
    }

    .container-custom.right-panel-active .overlay-left {
        transform: translateX(0);
    }

    .overlay-right {
        right: 0;
        transform: translateX(0);
    }

    .container-custom.right-panel-active .overlay-right {
        transform: translateX(20%);
    }
    
    /* Password Eye Icon Adjustment */
    .password-wrapper { position: relative; width: 100%; }
    .password-wrapper input { width: 100%; margin: 8px 0; }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
    }
</style>

<div class="container-custom" id="container">
    
    <div class="form-container sign-up-container">
        <form action="" method="POST" onkeydown="return event.key != 'Enter';">
            <h1>Create Account</h1>
            <span>Use your MMU Student ID</span>
            
            <input type="text" name="reg_name" value="<?php echo htmlspecialchars($reg_name); ?>" required placeholder="Full Name" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
            
            <input type="text" name="reg_student_id" id="studentIDInput" value="<?php echo htmlspecialchars($reg_student_id); ?>" required placeholder="Student ID">
            
            <input type="email" name="reg_email" id="emailInput" value="<?php echo htmlspecialchars($reg_email); ?>" required placeholder="ID@student.mmu.edu.my" readonly>
            
            <select name="reg_gender" required style="color: #666;">
                <option value="" disabled <?php echo ($reg_gender == "") ? 'selected' : ''; ?> hidden>Select Gender</option>
                <option value="Male" <?php echo ($reg_gender == "Male") ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($reg_gender == "Female") ? 'selected' : ''; ?>>Female</option>
            </select>

            <div class="password-wrapper">
                <input type="password" name="reg_password" id="regPassword" required placeholder="Password (Min 6 chars)" minlength="6">
                <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePass('regPassword', this)"></i>
            </div>
            
            <div class="password-wrapper">
                <input type="password" name="reg_confirm_password" id="regConfirmPassword" required placeholder="Confirm Password">
                <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePass('regConfirmPassword', this)"></i>
            </div>

            <button type="submit" name="register" class="btn-action">Sign Up</button>
        </form>
    </div>

    <div class="form-container sign-in-container">
        <form action="" method="POST">
            <h1>Sign in</h1>
            <span>Welcome back to U-Transport</span>
            
            <input type="email" name="login_email" id="loginEmailInput" required placeholder="Email">
            
            <div class="password-wrapper">
                <input type="password" name="login_password" id="loginPassword" required placeholder="Password">
                <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePass('loginPassword', this)"></i>
            </div>
            
            <a href="passanger_forgot_password.php" class="forgot-pass">Forgot your password?</a>
            <button type="submit" name="login" class="btn-action">Sign In</button>
        </form>
    </div>

    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1>Welcome Back!</h1>
                <p>To keep connected with us please login with your personal info</p>
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            
            <div class="overlay-panel overlay-right">
                <h1>Hello, Student!</h1>
                <p>Enter your personal details and start your journey with us</p>
                <button class="ghost" id="signUp">Sign Up</button>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // --- Sliding Logic ---
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');

    // Add class to show Register panel
    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });

    // Remove class to show Login panel
    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });

    // --- Auto-fill Email based on Student ID (From your original register code) ---
    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');

    if(studentIdInput){
        studentIdInput.addEventListener('input', function() {
            const id = this.value.trim();
            if (id.length > 0) { emailInput.value = id + "@student.mmu.edu.my"; }
            else { emailInput.value = ""; }
        });
    }

    // --- Password Toggle Function (Generic) ---
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

    // --- Prevent Enter key on Registration form to avoid accidental submit ---
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            // Only block if we are in the registration context (container has active class)
            if(container.classList.contains("right-panel-active")) {
                event.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'Action Required',
                    text: 'Please click the Sign Up button to submit.',
                    confirmButtonColor: '#005A9C'
                });
            }
        }
    });
</script>