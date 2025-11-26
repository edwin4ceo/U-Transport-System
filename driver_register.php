<?php
session_start();
// Ensure the paths to db_connect.php and function.php are correct.
include "db_connect.php"; 
include "function.php";

// Process the submitted registration form
if(isset($_POST['register'])){
    $name               = $_POST['name'];
    // Change field: Student ID -> IC/Passport Number or License ID
    $identification_id  = $_POST['identification_id']; 
    $email              = $_POST['email'];
    $car_model          = $_POST['car_model']; 
    $car_plate_number   = $_POST['car_plate_number']; 
    $password           = password_hash($_POST['password'], PASSWORD_BCRYPT);

// 1. **(Removed)** Email Domain Verification
// Assuming the driver can use any email address, the restriction on @student.mmu.edu.my has been removed.
// If you require other restrictions, please let me know.
    
// 2. Check for duplicate email addresses (operate on the 'drivers' table)
// Assume the drivers table also needs to store an email field.
    $check = $conn->query("SELECT * FROM drivers WHERE email='$email'"); 
    if($check->num_rows > 0){
        // Custom error message
        $_SESSION['swal_title'] = "Registration Failed";
        $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Login Now";
        $_SESSION['swal_btn_link'] = "driver_login.php"; // Should redirect to driver login page
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("driver_register.php");
    }

// 3. Insert new driver information (operate on the 'drivers' table)
    $sql = "INSERT INTO drivers (
                name, 
                identification_id, 
                email, 
                password, 
                car_model, 
                car_plate_number
            ) 
            VALUES (
                '$name',
                '$identification_id',
                '$email',
                '$password',
                '$car_model',
                '$car_plate_number'
            )";

    if($conn->query($sql)){
     // Success: Set a "Congratulations" message for the login page
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg'] = "Driver Registration Successful! Please login to continue.";
        $_SESSION['swal_type'] = "success";
        
       // Redirect to driver login page
        redirect("driver_login.php"); 
    } else {
     // Ensure your drivers table structure matches these fields
        alert("Driver Registration failed: " . $conn->error);
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
    <input type="text" name="name" required placeholder="Enter your full name">

    <label>IC / Passport Number</label>
    <input type="text" name="identification_id" required placeholder="e.g. 901020-04-5678 or A12345678">

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