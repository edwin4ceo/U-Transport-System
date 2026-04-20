<?php
session_start();
include "db_connect.php";
include "function.php";

// Helper function: Save form data to Session for "Sticky Form"
function saveFormData($data, $exclude_keys = []) {
    $_SESSION['form_data'] = $data;
    // Remove specific keys we want to force re-entry (e.g. invalid phone)
    foreach ($exclude_keys as $key) {
        unset($_SESSION['form_data'][$key]);
    }
    // Always remove password for security
    unset($_SESSION['form_data']['password']);
}

// Check if form is submitted
if (isset($_POST['register'])) {

    // 1. Get form values (Driver)
    $full_name         = trim($_POST['full_name'] ?? "");
    $identification_id = trim($_POST['identification_id'] ?? "");
    $email             = trim($_POST['email'] ?? "");
    $phone_raw         = trim($_POST['phone_number'] ?? ""); 
    $gender            = $_POST['gender'] ?? ""; 
    $license_number    = trim($_POST['license_number'] ?? "");
    $license_expiry    = trim($_POST['license_expiry'] ?? "");
    $password_plain    = $_POST['password'] ?? "";

    // 1b. Get form values (Vehicle)
    $car_model        = trim($_POST['car_model'] ?? "");
    $car_plate_number = trim($_POST['car_plate_number'] ?? "");
    $vehicle_color    = trim($_POST['vehicle_color'] ?? "");
    $vehicle_type     = trim($_POST['vehicle_type'] ?? "");
    $seat_count_raw   = trim($_POST['seat_count'] ?? "");
    $seat_count       = $seat_count_raw === "" ? 0 : (int)$seat_count_raw;
    
    // --- [VALIDATION 1] Phone Number Format ---
    // Remove non-numeric characters
    $phone_clean = preg_replace('/[^0-9]/', '', $phone_raw);

    // Check Malaysia format (starts with 01, 10-11 digits)
    if (!preg_match('/^01[0-9]{8,9}$/', $phone_clean)) {
        $_SESSION['swal_title'] = "Invalid Phone";
        $_SESSION['swal_msg']   = "Please enter a valid Malaysia mobile number (e.g. 0123456789).";
        $_SESSION['swal_type']  = "warning";

        // Save data but REMOVE 'phone_number' so user must re-enter it
        saveFormData($_POST, ['phone_number']);
        header("Location: driver_register.php");
        exit;
    }
    $phone_number = $phone_clean; 

    // --- [VALIDATION 2] Empty Fields Check ---
    if (
        $full_name === "" || $identification_id === "" || $email === "" || 
        $phone_number === "" || $gender === "" || $license_number === "" || 
        $license_expiry === "" || $password_plain === "" ||
        $car_model === "" || $car_plate_number === "" || $vehicle_color === "" || 
        $vehicle_type === "" || $seat_count <= 0
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all required fields.";
        $_SESSION['swal_type']  = "warning";
        
        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    // --- [VALIDATION 3] License Expiry Check ---
    // Must be valid for at least 3 months from today
    $min_validity_date = date('Y-m-d', strtotime('+3 months'));
    
    if ($license_expiry < $min_validity_date) {
        $_SESSION['swal_title'] = "License Issue";
        $_SESSION['swal_msg']   = "Your license must be valid for at least 3 months from today.";
        $_SESSION['swal_type']  = "warning";

        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    // --- [VALIDATION 4] Password Length ---
    if (strlen($password_plain) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "Password must be at least 6 characters long.";
        $_SESSION['swal_type']  = "warning";

        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    // --- [VALIDATION 5] MMU Email Domain ---
    if (!str_contains($email, "@student.mmu.edu.my")) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please use your MMU student email.";
        $_SESSION['swal_type']  = "warning";

        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    } 

    // --- [VALIDATION 6] Duplicate Email Check ---
    $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ?");
    if (!$check) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Database error.";
        $_SESSION['swal_type']  = "error";
        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $_SESSION['swal_title'] = "Email Exists";
        $_SESSION['swal_msg']   = "This email is already registered.";
        $_SESSION['swal_type']  = "warning";
        $check->close();

        saveFormData($_POST, ['email']); // Force re-entry of email
        header("Location: driver_register.php");
        exit;
    }
    $check->close();

    // --- INSERT DATA ---
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

    $insert_driver = $conn->prepare("
        INSERT INTO drivers (
            full_name, identification_id, email, phone_number, gender, 
            license_number, license_expiry, password, verification_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    if (!$insert_driver) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Database insert error.";
        $_SESSION['swal_type']  = "error";
        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    $insert_driver->bind_param(
        "ssssssss", 
        $full_name, $identification_id, $email, $phone_number, $gender, 
        $license_number, $license_expiry, $password_hash
    );

    if (!$insert_driver->execute()) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Failed to create account: " . $conn->error;
        $_SESSION['swal_type']  = "error";
        $insert_driver->close();
        saveFormData($_POST);
        header("Location: driver_register.php");
        exit;
    }

    $driver_id = $conn->insert_id;
    $insert_driver->close();

    // Insert Vehicle Record
    $insert_vehicle = $conn->prepare("
        INSERT INTO vehicles (
            driver_id, vehicle_model, plate_number, vehicle_color, vehicle_type, seat_count
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($insert_vehicle) {
        $insert_vehicle->bind_param(
            "issssi",
            $driver_id, $car_model, $car_plate_number, $vehicle_color, $vehicle_type, $seat_count
        );
        $insert_vehicle->execute(); 
        $insert_vehicle->close();   
    }

    // Success: Clear Session Data
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }

    $_SESSION['swal_title'] = "Registration Successful";
    $_SESSION['swal_msg']   = "Account created! Please wait for Admin approval.";
    $_SESSION['swal_type']  = "info"; 

    header("Location: driver_login.php");
    exit;
}

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .auth-wrapper {
        min-height: calc(100vh - 140px);
        display: flex; align-items: center; justify-content: center; 
        padding: 32px 12px; background: #f5f7fb;
    }
    .auth-card {
        background: #ffffff; border-radius: 18px;
        box-shadow: 0 14px 40px rgba(0,0,0,0.08);
        padding: 26px 34px 30px; max-width: 640px; width: 100%;
    }
    .auth-title {
        text-align: center; margin-bottom: 8px; font-size: 22px;
        font-weight: 700; color: #004b82;
    }
    .auth-subtitle {
        text-align: center; font-size: 13px; color: #666; margin-bottom: 18px;
    }
    .form-grid-2 {
        display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px 16px;
    }
    .form-group { display: flex; flex-direction: column; }
    .form-group label {
        font-size: 12px; color: #444; margin-bottom: 4px; font-weight: 500;
    }
    .form-group input, .form-group select { 
        padding: 8px 10px; border-radius: 8px; border: 1px solid #ccc;
        font-size: 13px; outline: none; width: 100%; box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s; background-color: #fff;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #005a9c; box-shadow: 0 0 0 2px rgba(0,90,156,0.18);
    }
    .btn-primary-wide {
        width: 100%; border: none; margin-top: 18px; padding: 10px 16px;
        border-radius: 999px; background: linear-gradient(135deg, #005a9c, #27ae60);
        color: #fff; font-size: 14px; font-weight: 600; cursor: pointer;
        box-shadow: 0 10px 22px rgba(0,0,0,0.18);
    }
    .btn-primary-wide:hover { transform: translateY(-1px); }
    .auth-footer { text-align: center; margin-top: 12px; font-size: 12px; }
    .auth-footer a { color: #005a9c; text-decoration: none; font-weight: 500; }
    @media (max-width: 640px) {
        .auth-card { padding: 22px 18px 24px; }
        .form-grid-2 { grid-template-columns: 1fr; }
    }
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="auth-title">Driver Registration</h2>
        <p class="auth-subtitle">Create your driver account and register your vehicle.</p>

        <form method="post" action="driver_register.php">
            <h3 style="font-size:14px;font-weight:600;color:#004b82;margin-bottom:6px;">
                Driver Information
            </h3>
            
            <div class="form-grid-2" style="margin-bottom:12px;">
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled <?php echo !isset($_SESSION['form_data']['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option value="Male" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="identification_id">Identification / Student ID</label>
                    <input type="text" id="identification_id" name="identification_id" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['identification_id'] ?? ''); ?>">
                </div>
                    
                <div class="form-group">
                    <label for="email">Driver Email (MMU)</label>
                    <input type="email" id="email" name="email"
                           placeholder="example@student.mmu.edu.my" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           placeholder="e.g. 012-3456789" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="license_number">Driving License Number</label>
                    <input type="text" id="license_number" name="license_number"
                           placeholder="e.g. D 23456789" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['license_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="license_expiry">License Expiry Date</label>
                    <input type="date" id="license_expiry" name="license_expiry" 
                           min="<?php echo date('Y-m-d'); ?>" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['license_expiry'] ?? ''); ?>">
                    <small style="color:#666; font-size:11px;">Must be valid for at least 3 months</small>
                </div>

                <div class="form-group">
                    <label for="password">Password (min 6 characters)</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" minlength="6" required 
                               style="padding-right: 35px;">
                        <i class="fas fa-eye" id="togglePassword" 
                           style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">
                        </i>
                    </div>
                </div>

            </div>

            <h3 style="font-size:14px;font-weight:600;color:#004b82;margin:8px 0 6px;">
                Vehicle Information
            </h3>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="car_model">Vehicle Model</label>
                    <input type="text" id="car_model" name="car_model"
                           placeholder="e.g. TOYOTA ALPHARD" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['car_model'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="car_plate_number">Car Plate Number</label>
                    <input type="text" id="car_plate_number" name="car_plate_number"
                           placeholder="e.g. WXX 1234" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['car_plate_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_color">Vehicle Color</label>
                    <input type="text" id="vehicle_color" name="vehicle_color"
                           placeholder="e.g. White" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['vehicle_color'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <input type="text" id="vehicle_type" name="vehicle_type"
                           placeholder="e.g. Sedan, MPV" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['vehicle_type'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="seat_count">Seat Count</label>
                    <input type="number" id="seat_count" name="seat_count"
                           min="1" max="7" placeholder="e.g. 4" required
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['seat_count'] ?? ''); ?>">
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

<script>
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if(togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            // Switch type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Switch eye icon
            this.classList.toggle('fa-eye-slash');
        });
    }
</script>

<?php 
include "footer.php"; 

// Cleanup: Clear sticky form data after page load
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>