<?php
// Start session at the very top
session_start();

include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER ---
// Ensure the folder 'PHPMailer' exists in your project directory
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables to keep form data (Sticky Form)
$name = "";
$student_id = "";
$email = "";

// Process the registration form when submitted
if(isset($_POST['register'])){
    // Get values from form
    // MODIFIED: Force name to be Uppercase immediately
    $name             = strtoupper($_POST['name']); 
    $student_id       = $_POST['student_id'];
    $email            = $_POST['email'];
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- 1. VALIDATION CHECKS ---

    // Validate Student ID (Must be exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $student_id)) {
        $_SESSION['swal_title'] = "Invalid Student ID";
        $_SESSION['swal_msg'] = "Student ID must be exactly 10 digits.";
        $_SESSION['swal_type'] = "error";
    }
    
    // Validate Name (Must not contain numbers)
    elseif (preg_match('/\d/', $name)) {
        $_SESSION['swal_title'] = "Invalid Name";
        $_SESSION['swal_msg'] = "Name cannot contain numbers.";
        $_SESSION['swal_type'] = "error";
        $name = ""; 
    }

    // Validate Password Match
    elseif ($password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "Passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
    }

    // --- 2. DATABASE CHECKS ---

    // Check MMU email domain verification
    elseif (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "Only MMU student emails (@student.mmu.edu.my) are allowed!";
        $_SESSION['swal_type'] = "error";
    }

    else {
        // Check for duplicate email in Database
        $check = $conn->query("SELECT * FROM students WHERE email='$email'");
        
        if($check->num_rows > 0){
            $_SESSION['swal_title'] = "Registration Failed";
            $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
            $_SESSION['swal_type'] = "warning";
            $_SESSION['swal_btn_text'] = "Login Now";
            $_SESSION['swal_btn_link'] = "passanger_login.php";
            $_SESSION['swal_show_cancel'] = true;
            $_SESSION['swal_cancel_text'] = "Try Again";
        } 
        else {
            // --- 3. SEND OTP INSTEAD OF INSERTING ---
            
            // Generate 4-digit code
            $otp_code = rand(1000, 9999);

            // Hash the password NOW
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Store user data in SESSION temporarily
            $_SESSION['temp_register_data'] = [
                'name' => $name, // This is now Uppercase
                'student_id' => $student_id,
                'email' => $email,
                'password_hash' => $password_hash,
                'otp_code' => $otp_code
            ];

            // Setup PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                
                // Credentials
                $mail->Username   = 'soonkit0726@gmail.com';  
                $mail->Password   = 'oprh ldrk nwvg eyiv';    
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
                $mail->addAddress($email, $name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - U-Transport';
                $mail->Body    = "
                    <h3>Hello $name,</h3>
                    <p>Thank you for registering. Your verification code is:</p>
                    <h2 style='color: #2c3e50; letter-spacing: 5px; font-size: 24px;'>$otp_code</h2>
                    <p>Please enter this code in the website to complete your registration.</p>
                ";
                $mail->AltBody = "Your verification code is: $otp_code";

                $mail->send();

                // Redirect to Verification Page
                header("Location: verify_email.php");
                exit();

            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type'] = "error";
            }
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Footer Style */
    footer {
        width: 100%;
        margin-top: auto; 
    }

    /* Style for the password wrapper to position the eye icon */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    
    .password-wrapper input {
        width: 100%;
        padding-right: 40px; /* Make space for the eye icon */
    }

    /* Style for the eye icon */
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%; /* Adjust vertical alignment */
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
        z-index: 10;
        font-size: 1.1rem;
        user-select: none; /* Prevent selection while holding */
        -webkit-user-select: none;
    }

    .toggle-password:hover {
        color: #005A9C;
    }
</style>

<h2>Register (MMU Student)</h2>
<p>Create your account to request and search for rides.</p>

<form action="" method="POST">
    <label>Full Name</label>
    <input type="text" name="name" id="nameInput" value="<?php echo htmlspecialchars($name); ?>" required placeholder="ENTER YOUR FULL NAME" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">

    <label>Student ID (10 Digits)</label>
    <input type="text" name="student_id" id="studentIDInput" value="<?php echo htmlspecialchars($student_id); ?>" maxlength="10" required placeholder="e.g. 1234567890">

    <label>MMU Email (@student.mmu.edu.my)</label>
    <input type="email" name="email" id="emailInput" value="<?php echo htmlspecialchars($email); ?>" required placeholder="ID@student.mmu.edu.my" readonly style="background-color: #f9f9f9; cursor: not-allowed;">

    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="passwordInput" required placeholder="Create a password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIcon"></i>
    </div>

    <label>Confirm Password</label>
    <div class="password-wrapper">
        <input type="password" name="confirm_password" id="confirmPasswordInput" required placeholder="Re-enter your password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconConfirm"></i>
    </div>

    <button type="submit" name="register">Register</button>
</form>

<div style="margin-top: 15px;">
    <p>Already have an account? <a href="passanger_login.php">Login here</a>.</p>
</div>

<script>
    // 1. Auto-fill Email based on Student ID
    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');

    studentIdInput.addEventListener('input', function() {
        const id = this.value;
        if (id.length > 0) {
            emailInput.value = id + "@student.mmu.edu.my";
        } else {
            emailInput.value = "";
        }
    });

    // 2. Helper Function to Toggle Password Visibility
    function setupPasswordToggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        function show() {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }

        function hide() {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }

        // Mouse Events (Desktop)
        icon.addEventListener('mousedown', show);
        icon.addEventListener('mouseup', hide);
        icon.addEventListener('mouseleave', hide);

        // Touch Events (Mobile)
        icon.addEventListener('touchstart', function(e) {
            e.preventDefault(); 
            show();
        });
        icon.addEventListener('touchend', hide);
    }

    // Initialize toggle for Main Password
    setupPasswordToggle('passwordInput', 'eyeIcon');

    // Initialize toggle for Confirm Password
    setupPasswordToggle('confirmPasswordInput', 'eyeIconConfirm');

</script>

<?php include "footer.php"; ?>