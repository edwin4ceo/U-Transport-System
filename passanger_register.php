<?php
session_start();
include "db_connect.php";
include "function.php";

// Process the registration form when submitted
if(isset($_POST['register'])){
    $name       = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1. Check MMU email domain verification
    if (!str_contains($email, "@student.mmu.edu.my")) {
        // Custom Error Alert
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "Only MMU student emails (@student.mmu.edu.my) are allowed!";
        $_SESSION['swal_type'] = "error";
        redirect("passanger_register.php");
    }

    // 2. Check for duplicate email
    $check = $conn->query("SELECT * FROM students WHERE email='$email'");
    if($check->num_rows > 0){
        // Custom Error Alert
        $_SESSION['swal_title'] = "Registration Failed";
        $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Login Now";
        $_SESSION['swal_btn_link'] = "passanger_login.php";
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("passanger_register.php");
    }

    // 3. Insert new student
    $sql = "INSERT INTO students (name, student_id, email, password) 
            VALUES ('$name','$student_id','$email','$password')";

    if($conn->query($sql)){
        // SUCCESS: Set the "Congratulations" message for the Login Page
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg'] = "Registration Successful! Please login to continue.";
        $_SESSION['swal_type'] = "success";
        
        // Redirect to Login Page (The alert will pop up there)
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
</style>

<h2>Register (MMU Student)</h2>
<p>Create your account to request and search for rides.</p>

<form action="" method="POST">
    <label>Full Name</label>
    <input type="text" name="name" required placeholder="Enter your full name">

    <label>Student ID</label>
    <input type="text" name="student_id" required placeholder="e.g. 1234567890">

    <label>MMU Email (@student.mmu.edu.my)</label>
    <input type="email" name="email" required placeholder="example@student.mmu.edu.my">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Create a password">

    <button type="submit" name="register">Register</button>
</form>

<div style="margin-top: 15px;">
    <p>Already have an account? <a href="passanger_login.php">Login here</a>.</p>
</div>

<?php include "footer.php"; ?>