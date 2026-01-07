<?php
session_start();
include "db_connect.php";
include "function.php";

// If form submitted
if (isset($_POST['register'])) {

    // 1. Get form values (driver)
    $full_name         = trim($_POST['full_name'] ?? "");
    $identification_id = trim($_POST['identification_id'] ?? "");
    $email             = trim($_POST['email'] ?? "");
    $phone_number      = trim($_POST['phone_number'] ?? "");
    $license_number    = trim($_POST['license_number'] ?? "");
    $license_expiry    = trim($_POST['license_expiry'] ?? "");
    $password_plain    = $_POST['password'] ?? "";

    // 1b. Get form values (vehicle)
    $car_model        = trim($_POST['car_model'] ?? "");
    $car_plate_number = trim($_POST['car_plate_number'] ?? "");
    $vehicle_color    = trim($_POST['vehicle_color'] ?? "");
    $vehicle_type     = trim($_POST['vehicle_type'] ?? "");
    $seat_count_raw   = trim($_POST['seat_count'] ?? "");
    $seat_count       = $seat_count_raw === "" ? 0 : (int)$seat_count_raw;

    // Hash password
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

    // 2. Basic validation
    if (
        $full_name === "" ||
        $identification_id === "" ||
        $email === "" ||
        $phone_number === "" ||    
        $license_number === "" ||  
        $license_expiry === "" ||  
        $password_plain === "" ||
        $car_model === "" ||
        $car_plate_number === "" ||
        $vehicle_color === "" ||
        $vehicle_type === "" ||
        $seat_count <= 0
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all required fields, including vehicle details.";
        $_SESSION['swal_type']  = "warning";
        redirect("driver_register.php");
        exit;
    }

    // Check license expiry
    if ($license_expiry < date('Y-m-d')) {
        $_SESSION['swal_title'] = "License Expired";
        $_SESSION['swal_msg']   = "Your driving license has expired. You cannot register.";
        $_SESSION['swal_type']  = "error";
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

    // 3. Check duplicate email
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
        $_SESSION['swal_title'] = "Email Exists";
        $_SESSION['swal_msg']   = "This email is already registered as a driver.";
        $_SESSION['swal_type']  = "warning";
        $check->close();
        redirect("driver_register.php");
        exit;
    }
    $check->close();

    // 4. Insert into drivers table (UPDATED)
    // We explicitly set verification_status to 'pending' here
    $insert_driver = $conn->prepare("
        INSERT INTO drivers (
            full_name, 
            identification_id, 
            email, 
            phone_number, 
            license_number, 
            license_expiry, 
            password,
            verification_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    if (!$insert_driver) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Database error (insert driver).";
        $_SESSION['swal_type']  = "error";
        redirect("driver_register.php");
        exit;
    }

    $insert_driver->bind_param(
        "sssssss",
        $full_name,
        $identification_id,
        $email,
        $phone_number, 
        $license_number, 
        $license_expiry, 
        $password_hash
    );

    if (!$insert_driver->execute()) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Failed to create driver account: " . $conn->error;
        $_SESSION['swal_type']  = "error";
        $insert_driver->close();
        redirect("driver_register.php");
        exit;
    }

    $driver_id = $conn->insert_id;
    $insert_driver->close();

    // 5. Insert vehicle record
    $insert_vehicle = $conn->prepare("
        INSERT INTO vehicles (
            driver_id,
            vehicle_model,
            plate_number,
            vehicle_color,
            vehicle_type,
            seat_count
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($insert_vehicle) {
        $insert_vehicle->bind_param(
            "issssi",
            $driver_id,
            $car_model,
            $car_plate_number,
            $vehicle_color,
            $vehicle_type,
            $seat_count
        );
        $insert_vehicle->execute(); 
        $insert_vehicle->close();
    }

    // 6. Success message (UPDATED)
    // Inform user they need approval
    $_SESSION['swal_title'] = "Registration Successful";
    $_SESSION['swal_msg']   = "Account created! Please wait for Admin approval before logging in.";
    $_SESSION['swal_type']  = "info"; // Use info or success

    redirect("driver_login.php");
    exit;
}

include "header.php";
?>

<style>
.auth-wrapper {
    min-height: calc(100vh - 140px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 12px;
    background: #f5f7fb;
}
.auth-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 14px 40px rgba(0,0,0,0.08);
    padding: 26px 34px 30px;
    max-width: 640px;
    width: 100%;
}
.auth-title {
    text-align: center;
    margin-bottom: 8px;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}
.auth-subtitle {
    text-align: center;
    font-size: 13px;
    color: #666;
    margin-bottom: 18px;
}
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px 16px;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-size: 12px;
    color: #444;
    margin-bottom: 4px;
    font-weight: 500;
}
.form-group input {
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group input:focus {
    border-color: #005a9c;
    box-shadow: 0 0 0 2px rgba(0,90,156,0.18);
}
.btn-primary-wide {
    width: 100%;
    border: none;
    margin-top: 18px;
    padding: 10px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, #005a9c, #27ae60);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(0,0,0,0.18);
}
.btn-primary-wide:hover {
    transform: translateY(-1px);
}
.auth-footer {
    text-align: center;
    margin-top: 12px;
    font-size: 12px;
}
.auth-footer a {
    color: #005a9c;
    text-decoration: none;
    font-weight: 500;
}
@media (max-width: 640px) {
    .auth-card { padding: 22px 18px 24px; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="auth-title">Driver Registration</h2>
        <p class="auth-subtitle">
            Create your driver account and register your vehicle.
        </p>

        <form method="post" action="driver_register.php">
            <h3 style="font-size:14px;font-weight:600;color:#004b82;margin-bottom:6px;">
                Driver Information
            </h3>
            <div class="form-grid-2" style="margin-bottom:12px;">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="identification_id">Identification / Student ID</label>
                    <input type="text" id="identification_id" name="identification_id" required>
                </div>

                <div class="form-group">
                    <label for="email">Driver Email (MMU)</label>
                    <input type="email" id="email" name="email"
                           placeholder="example@student.mmu.edu.my" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           placeholder="e.g. 012-3456789" required>
                </div>

                <div class="form-group">
                    <label for="license_number">Driving License Number</label>
                    <input type="text" id="license_number" name="license_number"
                           placeholder="e.g. D 23456789" required>
                </div>

                <div class="form-group">
                    <label for="license_expiry">License Expiry Date</label>
                    <input type="date" id="license_expiry" name="license_expiry" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="password">Password (min 6 characters)</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
            </div>

            <h3 style="font-size:14px;font-weight:600;color:#004b82;margin:8px 0 6px;">
                Vehicle Information
            </h3>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="car_model">Vehicle Model</label>
                    <input type="text" id="car_model" name="car_model"
                           placeholder="e.g. TOYOTA ALPHARD" required>
                </div>

                <div class="form-group">
                    <label for="car_plate_number">Car Plate Number</label>
                    <input type="text" id="car_plate_number" name="car_plate_number"
                           placeholder="e.g. WXX 1234" required>
                </div>

                <div class="form-group">
                    <label for="vehicle_color">Vehicle Color</label>
                    <input type="text" id="vehicle_color" name="vehicle_color"
                           placeholder="e.g. White" required>
                </div>

                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <input type="text" id="vehicle_type" name="vehicle_type"
                           placeholder="e.g. Sedan, MPV" required>
                </div>

                <div class="form-group">
                    <label for="seat_count">Seat Count</label>
                    <input type="number" id="seat_count" name="seat_count"
                           min="1" max="8" placeholder="e.g. 4" required>
                </div>
            </div>

            <button type="submit" name="register" class="btn-primary-wide">
                Create Driver Account
            </button>
        </form>

        <div class="auth-footer">
            Already a driver? <a href="driver_login.php">Login here</a>
        </div>
    </div>
</div>

<?php
include "footer.php";
?>