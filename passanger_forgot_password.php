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

    // 1. Validation: Check MMU Domain
    // PHP-side validation in case JS is bypassed
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg'] = "Please confirm if you entered the correct @student.mmu.edu.my email address.";
        $_SESSION['swal_type'] = "error";
    }
    // 2. Validation: Check if passwords match
    elseif($new_password !== $confirm_password){
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "New passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // 3. Verify User Identity
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
                $_SESSION['swal_title'] = "Password Reset Successful";
                $_SESSION['swal_msg'] = "You can now login with your new password.";
                $_SESSION['swal_type'] = "success";
                redirect("passanger_login.php");
            } else {
                alert("Database error: " . $conn->error);
            }
        } else {
            $_SESSION['swal_title'] = "Verification Failed";
            $_SESSION['swal_msg'] = "The Email and Student ID provided do not match our records.";
            $_SESSION['swal_type'] = "error";
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Standard Input Styling (No more seamless wrapper) */
    input[type="email"], input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Ensures padding doesn't affect width */
    }

    /* Password Eye Icon Style */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    /* Override margin-bottom for inputs inside wrapper to avoid double margins */
    .password-wrapper input {
        margin-bottom: 15px; 
        padding-right: 40px; 
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%; /* Center vertically based on input height */
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
    <input type="email" name="email" id="emailInput" required placeholder="e.g. 1231201234@student.mmu.edu.my">

    <label>Student ID</label>
    <input type="text" name="student_id" id="studentIDInput" required placeholder="Auto-filled from email">

    <label>New Password</label>
    <div class="password-wrapper">
        <input type="password" name="new_password" id="newPass" required placeholder="Create new password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconNew"></i>
    </div>

    <label>Confirm New Password</label>
    <div class="password-wrapper">
        <input type="password" name="confirm_password" id="confirmPass" required placeholder="Re-enter new password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconConfirm"></i>
    </div>

    <button type="submit" name="reset_password">Reset Password</button>
</form>

<div style="margin-top: 15px; text-align: center;">
    <a href="passanger_login.php" style="color: #666; text-decoration: none;">&larr; Back to Login</a>
</div>

<script>
    const emailInput = document.getElementById('emailInput');
    const studentIdInput = document.getElementById('studentIDInput');

    // 1. Auto-fill Student ID from Full Email
    emailInput.addEventListener('input', function() {
        const val = this.value;
        // If user types '@', take the part before it
        if (val.includes('@')) {
            studentIdInput.value = val.split('@')[0];
        } else {
            studentIdInput.value = val;
        }
    });

    // 2. Domain Validation on Blur (User clicks away)
    emailInput.addEventListener('blur', function() {
        const val = this.value;
        const requiredDomain = "@student.mmu.edu.my";

        // Only check if field is not empty
        if (val.length > 0) {
            if (!val.endsWith(requiredDomain)) {
                // Show SweetAlert Warning
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email Format',
                    text: 'Please confirm if you entered the correct @student.mmu.edu.my address.',
                    confirmButtonColor: '#005A9C'
                });
            }
        }
    });

    // 3. Password Toggle Function
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

        icon.addEventListener('mousedown', show);
        icon.addEventListener('mouseup', hide);
        icon.addEventListener('mouseleave', hide);

        icon.addEventListener('touchstart', function(e) {
            e.preventDefault();
            show();
        });
        icon.addEventListener('touchend', hide);
    }

    setupPasswordToggle('newPass', 'eyeIconNew');
    setupPasswordToggle('confirmPass', 'eyeIconConfirm');
</script>

<?php include "footer.php"; ?>