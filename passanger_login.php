<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect if already logged in 
//if(isset($_SESSION['student_id'])){
//    redirect("passanger_request_transport.php");
//}

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email exists in the students table
    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();

        // Verify the encrypted password
        if(password_verify($password, $row['password'])){
            // Login successful
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful!");
            redirect("passanger_request_transport.php"); 
        } 
        else {
            alert("Incorrect password.");
        }
    } 
    else {
        alert("Email not found.");
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

<h2>Login</h2>
<p>Welcome back! Please login to continue.</p>

<form action="" method="POST">
    <label>Email</label>
    <input type="email" name="email" required placeholder="Your MMU Email">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Your Password">

    <button type="submit" name="login">Login</button>
</form>

<div style="margin-top: 15px; display: flex; justify-content: space-between;">
    <a href="passanger_register.php">Create an Account</a>
    <a href="forgot_password.php">Forgot Password?</a>
</div>

<?php include "footer.php"; ?>