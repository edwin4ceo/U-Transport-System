<?php
// Start session to manage user login state
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER LIBRARY ---
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

    // 1. Validation Logic
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
        // 2. Check for duplicate email in database
        $check = $conn->query("SELECT * FROM students WHERE email='$reg_email'");
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
        } 
        else {
            // 3. Generate OTP & Hash Password
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($reg_password, PASSWORD_BCRYPT);

            // 4. Store temporary data for verification page
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

            // 5. Send Email via PHPMailer
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
    // JS to force the register tab to stay open if an error occurs
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
    /* --- 1. HEADER CONTAINER OVERRIDE --- */
    /* The header.php opens a .content-area div with default styling.
       We override it here to be transparent and full-width so our
       custom login form can float freely without being "boxed in".
    */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* --- 2. PAGE WRAPPER (MOVED UP) --- */
    .wrapper {
        width: 100%;
        min-height: 700px; /* Reduced min-height to reduce bottom gap */
        display: flex;
        justify-content: center;
        align-items: flex-start;
        /* REDUCED padding-top to move everything UP closer to header */
        padding-top: 10px; 
        position: relative;
        overflow: hidden;
        background-color: #f6f5f7; 
    }

    /* --- 3. TOP NAVIGATION BUTTONS --- */
    
    /* Toggle Buttons (Sign In / Sign Up) - Right Side */
    .nav-button {
        position: absolute;
        top: 0px; /* Flush with top */
        right: 10%; 
        display: flex;
        gap: 15px;
        z-index: 100;
    }

    /* Back Button - Left Side */
    .back-nav {
        position: absolute;
        top: 0px; /* Flush with top */
        left: 10%; 
        z-index: 100;
    }

    /* Shared Button Styling */
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
    
    /* Specific Widths */
    .btn { width: 110px; }
    .btn-back { padding: 0 30px; gap: 8px; }

    /* Hover & Active States */
    .btn.white-btn, .btn:hover, .btn-back:hover {
        background: #005A9C; 
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.3);
    }

    /* --- 4. MAIN FORM CONTAINER --- */
    .form-box {
        position: relative;
        width: 600px; 
        height: 680px; 
        overflow: hidden;
        /* Adjusted margin-top to pull the form up closer to buttons */
        margin-top: 50px; 
        background: transparent !important;
        box-shadow: none !important;
    }

    /* Mobile Responsive Logic */
    @media (max-width: 768px) {
        .form-box { width: 95%; height: 750px; margin-top: 60px; }
        .nav-button { right: 5%; top: 10px; gap: 10px; }
        .back-nav { left: 5%; top: 10px; }
        .btn { width: 90px; font-size: 12px; }
        .btn-back { padding: 0 15px; font-size: 12px; }
        .login-container, .register-container { padding: 0 20px; }
    }

    /* Sliding Containers */
    .login-container, .register-container {
        position: absolute;
        width: 100%;
        top: 0;
        transition: .5s ease-in-out;
        padding: 0 50px; 
    }

    .login-container { left: 0; opacity: 1; }
    .register-container { right: -120%; opacity: 0; pointer-events: none; } 

    /* Headings */
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

    /* --- 5. INPUT FIELDS (Clean White Design) --- */
    .input-box {
        display: flex;
        align-items: center;
        width: 100%;
        height: 55px;
        background: #ffffff !important; /* Pure White Background */
        box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important; /* Soft Shadow */
        border-radius: 30px !important; 
        margin-bottom: 20px;
        padding: 0 20px;
        border: 1px solid #eeeeee; 
        transition: .3s;
    }

    .input-box:focus-within {
        background: #ffffff !important;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.15) !important;
        border: 1px solid #005A9C;
    }

    /* Icons */
    .input-box i {
        font-size: 18px;
        color: #888;
        margin-right: 15px; 
        transition: .3s;
        position: static !important; 
        transform: none !important;
    }

    .input-box:focus-within i { color: #005A9C; }

    /* Text Input */
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

    /* Toggle Password Eye */
    .input-box .toggle-pass {
        margin-right: 0;
        margin-left: 10px; 
        cursor: pointer;
        color: #999;
    }
    .input-box .toggle-pass:hover { color: #005A9C; }

    /* Submit Button */
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
    /* Loading state style */
    .submit:disabled { background: #ccc !important; cursor: not-allowed; transform: none; box-shadow: none; }

    /* Footer Links */
    .two-col {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-top: 15px;
        padding: 0 10px;
        color: #555;
    }
    .two-col a { color: #005A9C; text-decoration: none; font-weight: 600; }
    .two-col a:hover { text-decoration: underline; }
    
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

                <button type="submit" name="register" class="submit">Register</button>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // --- JS: SLIDING ANIMATION LOGIC ---
    var a = document.getElementById("loginBtn");
    var b = document.getElementById("registerBtn");
    var x = document.getElementById("login");
    var y = document.getElementById("register");

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

    // --- JS: EMAIL AUTOFILL ---
    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');
    if(studentIdInput){
        studentIdInput.addEventListener('input', function() {
            const id = this.value.trim();
            if (id.length > 0) { emailInput.value = id + "@student.mmu.edu.my"; }
            else { emailInput.value = ""; }
        });
    }

    // --- JS: TOGGLE PASSWORD VISIBILITY ---
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

    // --- JS: LOADING EFFECT ON SUBMIT ---
    function handleLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        // Prevent double submission
        if(btn.disabled) return false;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        
        // Re-enable if server takes too long (timeout safety)
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 10000);
        
        return true;
    }
</script>