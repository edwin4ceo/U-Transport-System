<?php
session_start();
include "db_connect.php";
include "function.php";   // Optional: only needed if you use SweetAlert helpers

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

    // 2B. Optional domain restriction (only MMU student email allowed)
    // If you want to allow ANY email, comment out or remove this block.
    elseif (strpos($email, "@student.mmu.edu.my") === false) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "You must use an MMU email (@student.mmu.edu.my) to login.";
        $_SESSION['swal_type']  = "error";
    }

    else {

        // 2C. Check if driver exists in database
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            // 2D. Validate password (requires password_hash in registration)
            if (password_verify($password, $row['password'])) {

                // 2E. Store correct session data (match your drivers table columns)
                $_SESSION['driver_id']    = $row['driver_id'];   // from screenshot
                $_SESSION['driver_name']  = $row['name'];        // from screenshot
                $_SESSION['driver_email'] = $row['email'];

                // Optional SweetAlert success message
                $_SESSION['swal_title'] = "Login Successful";
                $_SESSION['swal_msg']   = "Welcome, " . $_SESSION['driver_name'] . "!";
                $_SESSION['swal_type']  = "success";

                // Redirect to dashboard
                header("Location: driver_dashboard.php");
                exit;
            } else {
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
            $_SESSION['swal_btn_text']   = "Register as Driver";
            $_SESSION['swal_btn_link']   = "driver_register.php";
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
