<?php
session_start();
include "db_connect.php"; 
include "function.php";   // Optional: only needed if you want custom SweetAlert functions

// 1. If already logged in, redirect to dashboard
if (isset($_SESSION['driver_id'])) {
    header("Location: driver_dashboard.php");
    exit;
}

// 2. Login form submitted
if (isset($_POST['login'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // 2A. Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Email and password cannot be empty.";
        $_SESSION['swal_type']  = "warning";
    } 

    // Optional domain restriction (remove this if drivers can use ANY email)
    elseif (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "You must use an MMU email (@student.mmu.edu.my) to login.";
        $_SESSION['swal_type']  = "error";
    }

    else {

        // 2B. Check if driver exists
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            // 2C. Validate password (requires password_hash in registration)
            if (password_verify($password, $row['password'])) {

                // 2D. Store correct session data
                // IMPORTANT: make sure the column names match your database
                $_SESSION['driver_id']    = $row['driver_id'];   // or $row['id'] if your column is "id"
                $_SESSION['driver_name']  = $row['fullname'];    // or $row['name'] depending on your DB
                $_SESSION['driver_email'] = $row['email'];

                // Optional success alert
                $_SESSION['swal_title'] = "Login Successful";
                $_SESSION['swal_msg']   = "Welcome, " . $_SESSION['driver_name'] . "!";
                $_SESSION['swal_type']  = "success";

                // Redirect to dashboard
                header("Location: driver_dashboard.php");
                exit;
            } 
            else {
                // Password incorrect
                $_SESSION['swal_title'] = "Incorrect Password";
                $_SESSION['swal_msg']   = "The password you entered is incorrect.";
                $_SESSION['swal_type']  = "error";
            }

        } else {
            // Driver email not found
            $_SESSION['swal_title'] = "Account Not Found";
            $_SESSION['swal_msg']   = "No driver account found with this email.";
            $_SESSION['swal_type']  = "warning";

            // Optional: show "Register" button
            $_SESSION['swal_btn_text'] = "Register as Driver";
            $_SESSION['swal_btn_link'] = "driver_register.php";
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
    <input type="email" name="email" required placeholder="example@student.mmu.edu.my">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Your Password">

    <button type="submit" name="login">Login</button>
</form>

<div style="margin-top: 15px; display: flex; justify-content: space-between;">
    <a href="driver_register.php">Become a Driver</a>
    <a href="forgot_password.php">Forgot Password?</a>
</div>

<?php include "footer.php"; ?>