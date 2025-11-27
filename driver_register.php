<?php
session_start();
include "db_connect.php";
include "function.php";

// Process the submitted registration form
if(isset($_POST['register'])){
    // Map HTML form inputs to variables
    $full_name          = $_POST['name'];              // Form: name -> DB: full_name
    $license_number     = $_POST['identification_id']; // Form: identification_id -> DB: license_number
    $email              = $_POST['email'];
    $car_model          = $_POST['car_model']; 
    $car_plate_number   = $_POST['car_plate_number']; 
    $password_hash      = password_hash($_POST['password'], PASSWORD_BCRYPT); // DB: password_hash

    // 1. Check for duplicate email addresses
    $check = $conn->query("SELECT * FROM drivers WHERE email='$email'"); 
    if($check->num_rows > 0){
        // Custom error message for duplicate email
        $_SESSION['swal_title'] = "Registration Failed";
        $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Login Now";
        $_SESSION['swal_btn_link'] = "driver_login.php";
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("driver_register.php");
    }

    // 2. Insert new driver information
    // CRITICAL FIX: Updated column names to match your database structure exactly
    // (full_name, license_number, password_hash, etc.)
    $sql = "INSERT INTO drivers (
                full_name, 
                license_number, 
                email, 
                password_hash, 
                car_model, 
                car_plate_number
            ) 
            VALUES (
                '$full_name',
                '$license_number',
                '$email',
                '$password_hash',
                '$car_model',
                '$car_plate_number'
            )";

    if($conn->query($sql)){
        // Success: Set a "Congratulations" message
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg'] = "Driver Registration Successful! Please login.";
        $_SESSION['swal_type'] = "success";
        
        // Redirect to driver login page
        redirect("driver_login.php"); 
    } else {
        // Error handling
        alert("Driver Registration failed: " . $conn->error);
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* Ensure footer stays at the bottom */
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
    <input type="text" name="name" required placeholder="Enter your full name">

    <label>IC / Passport Number / License ID</label>
    <input type="text" name="identification_id" required placeholder="e.g. 901020-04-5678">

    <label>Email Address</label>
    <input type="email" name="email" required placeholder="your.email@example.com">

    <label>Car Model</label>
    <input type="text" name="car_model" required placeholder="e.g. Perodua Myvi">
    
    <label>Car Plate Number</label>
    <input type="text" name="car_plate_number" required placeholder="e.g. WAA 1234 X">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Create a password">

    <button type="submit" name="register">Register</button>
</form>

<div style="margin-top: 15px;">
    <p>Already have an account? <a href="driver_login.php">Login here</a>.</p>
</div>

<?php include "footer.php"; ?>