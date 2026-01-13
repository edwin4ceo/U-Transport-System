<?php
// Start session at the very top
session_start();

include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name = "";
$student_id = "";
$email = "";
$password = ""; 
$confirm_password = ""; 
$gender_selection = ""; 

if(isset($_POST['register'])){
    $name             = strtoupper($_POST['name']); 
    $student_id       = $_POST['student_id'];
    $email            = $_POST['email'];
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $gender_selection = $_POST['gender']; 

    if (empty($student_id)) {
        $_SESSION['swal_title'] = "Invalid Student ID";
        $_SESSION['swal_msg'] = "Student ID cannot be empty.";
        $_SESSION['swal_type'] = "error";
    }
    elseif (preg_match('/\d/', $name)) {
        $_SESSION['swal_title'] = "Invalid Name";
        $_SESSION['swal_msg'] = "Name cannot contain numbers.";
        $_SESSION['swal_type'] = "error";
        $name = ""; 
    }
    elseif (strlen($password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg'] = "Password must be at least 6 characters long.";
        $_SESSION['swal_type'] = "error";
    }
    elseif ($password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "Please ensure your confirm password is entered correctly."; 
        $_SESSION['swal_type'] = "error";
    }
    elseif (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "Only MMU student emails (@student.mmu.edu.my) are allowed!";
        $_SESSION['swal_type'] = "error";
    }
    else {
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
            $otp_code = rand(1000, 9999);
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $_SESSION['temp_register_data'] = [
                'name' => $name, 
                'student_id' => $student_id,
                'email' => $email,
                'password_hash' => $password_hash,
                'gender' => $gender_selection,
                'otp_code' => $otp_code,
                'otp_timestamp' => time(),
                'resend_count' => 0 
            ];

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
                $mail->Body    = "<h3>Hello $name,</h3><p>Your verification code is: <b>$otp_code</b></p>";
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
}
?>

<?php include "header.php"; ?>

<style>
    footer { width: 100%; margin-top: auto; }
    input[type="text"], input[type="email"], input[type="password"], select {
        width: 100%; padding: 10px; margin-bottom: 11px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-weight: normal; 
    }
    label { display: block; margin-bottom: 4px; font-weight: 500; color: #333; }
    .password-wrapper { position: relative; width: 100%; margin-bottom: 11px; }
    .password-wrapper input { width: 100%; padding-right: 40px; margin-bottom: 0 !important; }
    .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #7f8c8d; z-index: 10; font-size: 1.1rem; }
    .footer-link-container { margin-top: 15px; text-align: center; }
    .footer-link-container p { font-size: 15px; color: #555; margin: 0; }
    .footer-link-container a { text-decoration: none; color: #005A9C; font-weight: 500; font-size: 15px; }
    .footer-link-container a:hover { text-decoration: underline; }
</style>

<h2>Register (MMU Student)</h2>
<p style="margin-bottom: 15px;">Create your account to request and search for rides.</p>

<form action="" method="POST" onkeydown="return event.key != 'Enter';">
    <label>Full Name</label>
    <input type="text" name="name" id="nameInput" value="<?php echo htmlspecialchars($name); ?>" required placeholder="ENTER YOUR FULL NAME" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">

    <label>Student ID</label>
    <input type="text" name="student_id" id="studentIDInput" value="<?php echo htmlspecialchars($student_id); ?>" required placeholder="e.g. 1211101234 or name.style">

    <label>MMU Email</label>
    <input type="email" name="email" id="emailInput" value="<?php echo htmlspecialchars($email); ?>" required placeholder="ID@student.mmu.edu.my">

    <label>Gender</label>
    <select name="gender" required>
        <option value="" disabled <?php echo ($gender_selection == "") ? 'selected' : ''; ?> hidden>Select Gender</option>
        <option value="Male" <?php echo ($gender_selection == "Male") ? 'selected' : ''; ?>>Male</option>
        <option value="Female" <?php echo ($gender_selection == "Female") ? 'selected' : ''; ?>>Female</option>
    </select>

    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="passwordInput" value="<?php echo htmlspecialchars($password); ?>" required placeholder="Min 6 characters" minlength="6">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIcon"></i>
    </div>

    <label>Confirm Password</label>
    <div class="password-wrapper">
        <input type="password" name="confirm_password" id="confirmPasswordInput" value="<?php echo htmlspecialchars($confirm_password); ?>" required placeholder="Re-enter your password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconConfirm"></i>
    </div>

    <button type="submit" name="register" style="margin-top: 10px; font-size: 15px;">Register</button>
</form>

<div class="footer-link-container">
    <p>Already have an account? <a href="passanger_login.php">Login here</a>.</p>
</div>

<script>
    // Prevent Enter key and show SweetAlert in English
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
            event.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Action Required',
                text: 'Please click the Register button to submit your application.',
                confirmButtonColor: '#005A9C'
            });
        }
    });

    const studentIdInput = document.getElementById('studentIDInput');
    const emailInput = document.getElementById('emailInput');
    studentIdInput.addEventListener('input', function() {
        const id = this.value.trim();
        if (id.length > 0) { emailInput.value = id + "@student.mmu.edu.my"; }
        else { emailInput.value = ""; }
    });

    function setupPasswordToggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        function show() { input.type = 'text'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        function hide() { input.type = 'password'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
        icon.addEventListener('mousedown', show);
        icon.addEventListener('mouseup', hide);
        icon.addEventListener('mouseleave', hide);
        icon.addEventListener('touchstart', (e) => { e.preventDefault(); show(); });
        icon.addEventListener('touchend', hide);
    }
    setupPasswordToggle('passwordInput', 'eyeIcon');
    setupPasswordToggle('confirmPasswordInput', 'eyeIconConfirm');
</script>
<?php include "footer.php"; ?>