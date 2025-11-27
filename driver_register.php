<?php
session_start();
include "db_connect.php";
include "function.php";

// Process the submitted registration form
if(isset($_POST['register'])){
<<<<<<< HEAD
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
=======

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
>>>>>>> b30ebedb4397c5bcead0da862b38a85bd5291cae
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("driver_register.php");
        exit;
    }

<<<<<<< HEAD
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
=======
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
>>>>>>> b30ebedb4397c5bcead0da862b38a85bd5291cae
        
        // Redirect to driver login page
        redirect("driver_login.php"); 
        exit;
    } else {
<<<<<<< HEAD
        // Error handling
        alert("Driver Registration failed: " . $conn->error);
=======
        // Show error message (development only)
        alert("Driver registration failed: " . $conn->error);
>>>>>>> b30ebedb4397c5bcead0da862b38a85bd5291cae
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
<<<<<<< HEAD
    <input type="text" name="name" required placeholder="Enter your full name">

    <label>IC / Passport Number / License ID</label>
    <input type="text" name="identification_id" required placeholder="e.g. 901020-04-5678">
=======
    <input type="text" name="full_name" required placeholder="Enter your full name">
>>>>>>> b30ebedb4397c5bcead0da862b38a85bd5291cae

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
