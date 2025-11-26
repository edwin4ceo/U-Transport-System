<?php
include "db_connect.php";
include "function.php";

// Process the registration form when submitted
if(isset($_POST['register'])){
    $name       = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email      = $_POST['email'];
    // Hash the password for security
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1. Check MMU email domain verification
    if (!str_contains($email, "@student.mmu.edu.my")) {
        alert("Only MMU student emails are allowed!");
        redirect("passanger_register.php"); // Redirect back to this page
    }

    // 2. Check for duplicate email in the database
    $check = $conn->query("SELECT * FROM students WHERE email='$email'");
    if($check->num_rows > 0){
        alert("Email already registered.");
        redirect("passanger_register.php"); // Redirect back to this page
    }

    // 3. Insert new student into the database
    $sql = "INSERT INTO students (name, student_id, email, password) 
            VALUES ('$name','$student_id','$email','$password')";

    if($conn->query($sql)){
        alert("Registration Successful! Please login.");
        redirect("passanger_login.php"); // Redirect to Passenger Login page
    } else {
        alert("Registration failed.");
    }
}
?>

<?php include "header.php"; ?>

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