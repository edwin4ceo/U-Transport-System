<?php
session_start();
include "db_connect.php";
include "function.php";

// If form submitted
if (isset($_POST['register'])) {

    // 1. Get form values
    $full_name         = trim($_POST['full_name'] ?? "");
    $identification_id = trim($_POST['identification_id'] ?? "");
    $email             = trim($_POST['email'] ?? "");
    $car_model         = trim($_POST['car_model'] ?? "");
    $car_plate_number  = trim($_POST['car_plate_number'] ?? "");
    $password_plain    = $_POST['password'] ?? "";

    // Hash password
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

    // 2. Basic validation
    if (
        $full_name === "" ||
        $identification_id === "" ||
        $email === "" ||
        $password_plain === ""
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all required fields.";
        $_SESSION['swal_type']  = "warning";
        redirect("driver_register.php");
        exit;
    }

    // Optional: require MMU email
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please use your MMU student email.";
        $_SESSION['swal_type']  = "warning";
        redirect("driver_register.php");
        exit;
    }

    // 3. Check duplicate email in drivers table
    $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ?");
    if (!$check) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Database error (check email).";
        $_SESSION['swal_type']  = "error";
        redirect("driver_register.php");
        exit;
    }

    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        // Email already used
        $_SESSION['swal_title'] = "Email Exists";
        $_SESSION['swal_msg']   = "This email is already registered as a driver.";
        $_SESSION['swal_type']  = "warning";
        $check->close();
        redirect("driver_register.php");
        exit;
    }
    $check->close();

    // 4. Insert into drivers table (WITHOUT car_model / car_plate_number)
    //    drivers table: driver_id, full_name, identification_id, email,
    //    driving_license_no, license_expiry, password, created_at
    $insert_driver = $conn->prepare("
        INSERT INTO drivers (full_name, identification_id, email, password)
        VALUES (?, ?, ?, ?)
    ");

    if (!$insert_driver) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Database error (insert driver).";
        $_SESSION['swal_type']  = "error";
        redirect("driver_register.php");
        exit;
    }

    $insert_driver->bind_param(
        "ssss",
        $full_name,
        $identification_id,
        $email,
        $password_hash
    );

    if (!$insert_driver->execute()) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Failed to create driver account.";
        $_SESSION['swal_type']  = "error";
        $insert_driver->close();
        redirect("driver_register.php");
        exit;
    }

    // Get new driver_id
    $driver_id = $conn->insert_id;
    $insert_driver->close();

    // 5. Insert vehicle record (if user filled car model & plate)
    if ($car_model !== "" && $car_plate_number !== "") {
        // vehicles table: vehicle_id, driver_id, vehicle_model, plate_number, ...
        $insert_vehicle = $conn->prepare("
            INSERT INTO vehicles (driver_id, vehicle_model, plate_number)
            VALUES (?, ?, ?)
        ");

        if ($insert_vehicle) {
            $insert_vehicle->bind_param("iss", $driver_id, $car_model, $car_plate_number);
            $insert_vehicle->execute(); // 即使失败，driver 账号也已经创建，不强制报错
            $insert_vehicle->close();
        }
    }

    // 6. Success message
    $_SESSION['swal_title'] = "Registration Successful";
    $_SESSION['swal_msg']   = "Your driver account has been created. You can now log in.";
    $_SESSION['swal_type']  = "success";

    redirect("driver_login.php");
    exit;
}

// If not submitted, just show the form HTML (rest of your page)
include "header.php";
// ... your registration form HTML here ...
?>
