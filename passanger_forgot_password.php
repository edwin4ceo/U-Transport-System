<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect if already logged in
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

if(isset($_POST['reset_password'])){
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation: Check if passwords match
    if($new_password !== $confirm_password){
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "New passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // 2. Verify User Identity (Check if Email AND Student ID match)
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND student_id = ?");
        $stmt->bind_param("ss", $email, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            // Identity Verified: Update Password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $new_password_hash, $email);
            
            if($update_stmt->execute()){
                // Success
                $_SESSION['swal_title'] = "Password Reset Successful";
                $_SESSION['swal_msg'] = "You can now login with your new password.";
                $_SESSION['swal_type'] = "success";
                redirect("passanger_login.php");
            } else {
                alert("Database error: " . $conn->error);
            }
        } else {
            // Identity Verification Failed
            $_SESSION['swal_title'] = "Verification Failed";
            $_SESSION['swal_msg'] = "The Email and Student ID provided do not match our records.";
            $_SESSION['swal_type'] = "error";
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Force footer to bottom for short page */
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    /* Password Eye Icon Style */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    .password-wrapper input {
        width: 100%;
        padding-right: 40px; 
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
        z-index: 10;
        font-size: 1.1rem;
        user-select: none; 
    }
    .toggle-password:hover { color: #005A9C; }
</style>

<h2>Reset Password</h2>
<p>Enter your details to verify your identity and set a new password.</p>

<form action="" method="POST">
    <label>MMU Email</label>
    <input type="email" name="email" required placeholder="Enter your registered email">

    <label>Student ID</label>
    <input type="text" name="student_id" required placeholder="Enter your 10-digit Student ID">

    <label>New Password</label>
    <div class="password-wrapper">
        <input type="password" name="new_password" id="newPass" required placeholder="Create new password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIcon"></i>
    </div>

    <label>Confirm New Password</label>
    <input type="password" name="confirm_password" required placeholder="Re-enter new password">

    <button type="submit" name="reset_password">Reset Password</button>
</form>

<div style="margin-top: 15px; text-align: center;">
    <a href="passanger_login.php" style="color: #666; text-decoration: none;">&larr; Back to Login</a>
</div>

<script>
    const passwordInput = document.getElementById('newPass');
    const eyeIcon = document.getElementById('eyeIcon');

    function showPassword() {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }

    function hidePassword() {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    }

    // Mouse Events
    eyeIcon.addEventListener('mousedown', showPassword);
    eyeIcon.addEventListener('mouseup', hidePassword);
    eyeIcon.addEventListener('mouseleave', hidePassword);

    // Touch Events (Mobile)
    eyeIcon.addEventListener('touchstart', function(e) {
        e.preventDefault();
        showPassword();
    });
    eyeIcon.addEventListener('touchend', hidePassword);
</script>

<?php include "footer.php"; ?>