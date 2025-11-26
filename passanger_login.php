<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect if already logged in
if(isset($_SESSION['student_id'])){
    redirect("passanger_request_transport.php");
}

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Check if the email exists in the database
    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        // Email found, now check password
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            // CASE A: Success
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful! Redirecting...");
            redirect("passanger_request_transport.php"); 
        } 
        else {
            // CASE B: Wrong Password
            $_SESSION['swal_title'] = "Incorrect Password";
            $_SESSION['swal_msg'] = "The password you entered is incorrect.";
            $_SESSION['swal_type'] = "error";
            $_SESSION['swal_btn_text'] = "Try Again";
        }
    } 
    else {
        // CASE C: Email Not Found (WITH TWO BUTTONS)
        $_SESSION['swal_title'] = "Email Not Found";
        $_SESSION['swal_msg'] = "This email is not registered in our system.";
        $_SESSION['swal_type'] = "warning";
        
        // Button 1: Register Now (Redirects)
        $_SESSION['swal_btn_text'] = "Register Now";
        $_SESSION['swal_btn_link'] = "passanger_register.php";
        
        // Button 2: Try Again (Closes Popup)
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
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