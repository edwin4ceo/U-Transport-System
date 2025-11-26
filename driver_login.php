<?php
session_start();
include "db_connect.php"; // Ensure variable is $conn
include "function.php";   // Contains the nice alert() and redirect() functions

// 1. Redirect if already logged in
if(isset($_SESSION['driver_id'])){
    // Redirect to driver dashboard (You need to create this file later)
    redirect("driver_dashboard.php");
}

// 2. Handle Login Logic
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic Validation
    if (empty($email) || empty($password)) {
        alert("Email or password cannot be empty");
    } 
    // Only allow MMU staff email (assuming drivers are staff?)
    // You can remove this check if drivers use other emails
    elseif (!str_contains($email, "@mmu.edu.my")) {
        // Custom Error for invalid domain
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg'] = "You must use an MMU email (@mmu.edu.my) to login as a driver.";
        $_SESSION['swal_type'] = "error";
    }
    else {
        // Query database (Make sure you have a 'drivers' table)
        // Note: Using prepared statements is safer, but keeping it consistent with your project style for now
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $row['password'])) {
                // Login successful
                $_SESSION['driver_id'] = $row['id'];
                $_SESSION['driver_name'] = $row['name'];
                $_SESSION['driver_email'] = $row['email'];

                // Success Alert & Redirect
                alert("Login successful! Welcome Driver.");
                redirect("driver_dashboard.php");
            } else {
                // Wrong Password
                $_SESSION['swal_title'] = "Incorrect Password";
                $_SESSION['swal_msg'] = "The password you entered is incorrect.";
                $_SESSION['swal_type'] = "error";
                $_SESSION['swal_btn_text'] = "Try Again";
            }
        } else {
            // Email Not Found
            $_SESSION['swal_title'] = "Account Not Found";
            $_SESSION['swal_msg'] = "No driver account found with this email.";
            $_SESSION['swal_type'] = "warning";
            $_SESSION['swal_btn_text'] = "Register as Driver";
            $_SESSION['swal_btn_link'] = "driver_register.php"; // You need to create this page
            $_SESSION['swal_show_cancel'] = true;
            $_SESSION['swal_cancel_text'] = "Try Again";
        }
        $stmt->close();
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

<h2>Driver Login</h2>
<p>Welcome, Driver! Please login to manage rides.</p>

<form action="" method="POST">
    <label>Email</label>
    <input type="email" name="email" required placeholder="example@mmu.edu.my">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Your Password">

    <button type="submit" name="login">Login</button>
</form>

<div style="margin-top: 15px; display: flex; justify-content: space-between;">
    <a href="driver_register.php">Become a Driver</a>
    <a href="forgot_password.php">Forgot Password?</a>
</div>

<?php include "footer.php"; ?>