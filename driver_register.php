<?php
session_start();
// Ensure the paths to db_connect.php and function.php are correct.
include "db_connect.php"; 
include "function.php";

// Process the submitted registration form
if(isset($_POST['register'])){

    $full_name       = $_POST['full_name'];
    $email           = $_POST['email'];
    $phone           = $_POST['phone'];
    $license_number  = $_POST['license_number'];
    $license_expiry  = $_POST['license_expiry'];   // date
    $password_plain  = $_POST['password'];
    $password_hash   = password_hash($password_plain, PASSWORD_BCRYPT);

    // 1. Check for duplicate email addresses (operate on the 'drivers' table)
    $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if($result && $result->num_rows > 0){
        // Custom error message
        $_SESSION['swal_title']       = "Registration Failed";
        $_SESSION['swal_msg']         = "This email is already registered. Please login instead.";
        $_SESSION['swal_type']        = "warning";
        $_SESSION['swal_btn_text']    = "Login Now";
        $_SESSION['swal_btn_link']    = "driver_login.php"; // redirect to driver login page
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("driver_register.php");
        exit;
    }

    // 2. Insert new driver information into `drivers`
    // 现在完全配合你资料表的栏位
    $sql = "INSERT INTO drivers (
                full_name,
                email,
                password_hash,
                phone,
                license_number,
                license_expiry
            ) VALUES (?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssss",
        $full_name,
        $email,
        $password_hash,
        $phone,
        $license_number,
        $license_expiry
    );

    if($stmt->execute()){
        // Success: set a "Congratulations" message for the login page
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg']   = "Driver registration successful! Please login to continue.";
        $_SESSION['swal_type']  = "success";
        
        // Redirect to driver login page
        redirect("driver_login.php"); 
        exit;
    } else {
        // Show error message (development only)
        alert("Driver registration failed: " . $conn->error);
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

<h2>Register (U-Transport Driver)</h2>
<p>Create your account to offer rides and earn money.</p>

<form action="" method="POST">
    <label>Full Name</label>
    <input type="text" name="full_name" required placeholder="Enter your full name">

    <label>Email Address</label>
    <input type="email" name="email" required placeholder="your.email@example.com">

    <label>Phone Number</label>
    <input type="text" name="phone" required placeholder="e.g. 012-3456789">

    <label>Driving License Number</label>
    <input type="text" name="license_number" required placeholder="e.g. B1234567">

    <label>Driving License Expiry Date</label>
    <input type="date" name="license_expiry" required>

    <label>Password</label>
    <input type="password" name="password" required placeholder="Create a password">

    <button type="submit" name="register">Register</button>
</form>

<div style="margin-top: 15px;">
    <p>Already have an account? <a href="driver_login.php">Login here</a>.</p>
</div>

<?php include "footer.php"; ?>
