<?php
session_start();
include "db_connect.php";
include "function.php";

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
            // Store the Student Matrix ID in session
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful!");
            redirect("passanger_request_transport.php"); // Redirect to the request page
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