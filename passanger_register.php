<?php
// Start session at the very top
session_start();

include "db_connect.php";
include "function.php";

// Process the registration form when submitted
if(isset($_POST['register'])){
    $name             = $_POST['name'];
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
        redirect("passanger_register.php");
    }

    // Validate Name (Must not contain numbers)
    if (preg_match('/\d/', $name)) {
        $_SESSION['swal_title'] = "Invalid Name";
        $_SESSION['swal_msg'] = "Name cannot contain numbers.";
        $_SESSION['swal_type'] = "error";
        redirect("passanger_register.php");
    }

    // Validate Password Match
    if ($password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg'] = "Passwords do not match. Please try again.";
        $_SESSION['swal_type'] = "error";
        redirect("passanger_register.php");
    }

    // Hash the password after validation
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // --- 2. DATABASE CHECKS ---

    // Check MMU email domain verification
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "Only MMU student emails (@student.mmu.edu.my) are allowed!";
        $_SESSION['swal_type'] = "error";
        redirect("passanger_register.php");
    }

    // Check for duplicate email
    $check = $conn->query("SELECT * FROM students WHERE email='$email'");
    if($check->num_rows > 0){
        $_SESSION['swal_title'] = "Registration Failed";
        $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Login Now";
        $_SESSION['swal_btn_link'] = "passanger_login.php";
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("passanger_register.php");
    }

    // --- 3. INSERT DATA ---
    
    $sql = "INSERT INTO students (name, student_id, email, password) 
            VALUES ('$name','$student_id','$email','$password_hash')";

    if($conn->query($sql)){
        // SUCCESS
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg'] = "Registration Successful! Please login to continue.";
        $_SESSION['swal_type'] = "success";
        
        redirect("passanger_login.php");
    } else {
        alert("Registration failed: " . $conn->error);
    }
}
?>

<?php include "header.php"; ?>

<style>
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
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
    <input type="text" name="name" id="nameInput" required placeholder="Enter your full name">

    <label>Student ID (10 Digits)</label>
    <input type="text" name="student_id" id="studentIDInput" maxlength="10" required placeholder="e.g. 1234567890">

    <label>MMU Email (@student.mmu.edu.my)</label>
    <input type="email" name="email" id="emailInput" required placeholder="ID@student.mmu.edu.my" readonly style="background-color: #f9f9f9; cursor: not-allowed;">

    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="passwordInput" required placeholder="Create a password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIcon"></i>
    </div>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required placeholder="Re-enter your password">

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
        // Only fill if ID contains numbers, otherwise clear or keep partial
        if (id.length > 0) {
            emailInput.value = id + "@student.mmu.edu.my";
        } else {
            emailInput.value = "";
        }
    });

    // 2. Password "Press and Hold" to View
    const passwordInput = document.getElementById('passwordInput');
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

    // Mouse Events (Desktop)
    eyeIcon.addEventListener('mousedown', showPassword);
    eyeIcon.addEventListener('mouseup', hidePassword);
    eyeIcon.addEventListener('mouseleave', hidePassword); // Hide if mouse drags out

    // Touch Events (Mobile)
    eyeIcon.addEventListener('touchstart', function(e) {
        e.preventDefault(); // Prevent ghost clicks
        showPassword();
    });
    eyeIcon.addEventListener('touchend', hidePassword);

</script>

<?php include "footer.php"; ?>