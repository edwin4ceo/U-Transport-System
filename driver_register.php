<?php
session_start();

include "db_connect.php"; // DB connection ($conn)
include "function.php";   // redirect() + SweetAlert helpers (if any)

// If already logged in, redirect to dashboard
if (isset($_SESSION['driver_id'])) {
    redirect("driver_dashboard.php");
}

// Handle form submission
if (isset($_POST['register'])) {

    $full_name         = trim($_POST['full_name']);
    $identification_id = trim($_POST['identification_id']);
    $email             = trim($_POST['email']);
    $car_model         = trim($_POST['car_model']);
    $car_plate_number  = trim($_POST['car_plate_number']);
    $password_plain    = $_POST['password'];
    $confirm_password  = $_POST['confirm_password'];

    // 1. All fields required
    if (
        $full_name === '' ||
        $identification_id === '' ||
        $email === '' ||
        $car_model === '' ||
        $car_plate_number === '' ||
        $password_plain === '' ||
        $confirm_password === ''
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "All fields are required. Please fill in every field.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2. Email format validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please enter a valid email address.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2B. Email must be MMU student email
    elseif (substr($email, -19) !== "@student.mmu.edu.my") {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg']   = "You must use your MMU student email (@student.mmu.edu.my) to register.";
        $_SESSION['swal_type']  = "warning";
    }
    // 3. Password length validation
    elseif (strlen($password_plain) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "Password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    }
    // 4. Confirm password validation
    elseif ($password_plain !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "Password and confirm password do not match.";
        $_SESSION['swal_type']  = "warning";
    }
    else {

        // 5. Check if email already exists
        $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ?");
        if (!$check) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (check email).";
            $_SESSION['swal_type']  = "error";
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result && $result->num_rows > 0) {
                // Email already used
                $_SESSION['swal_title'] = "Cannot Register";
                $_SESSION['swal_msg']   = "Cannot register with this email. This email is already used by another driver.";
                $_SESSION['swal_type']  = "error";
            } else {
                // 6. Insert new driver
                $password_hashed = password_hash($password_plain, PASSWORD_BCRYPT);

                $insert = $conn->prepare("
                    INSERT INTO drivers
                    (full_name, identification_id, email, car_model, car_plate_number, password)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if (!$insert) {
                    $_SESSION['swal_title'] = "Error";
                    $_SESSION['swal_msg']   = "Database error (insert).";
                    $_SESSION['swal_type']  = "error";
                } else {
                    $insert->bind_param(
                        "ssssss",
                        $full_name,
                        $identification_id,
                        $email,
                        $car_model,
                        $car_plate_number,
                        $password_hashed
                    );

                    if ($insert->execute()) {
                        $_SESSION['swal_title'] = "Registration Successful";
                        $_SESSION['swal_msg']   = "Your driver account has been created. Please login.";
                        $_SESSION['swal_type']  = "success";

                        redirect("driver_login.php");
                    } else {
                        $_SESSION['swal_title'] = "Error";
                        $_SESSION['swal_msg']   = "Failed to create account. Please try again.";
                        $_SESSION['swal_type']  = "error";
                    }

                    $insert->close();
                }
            }

            $check->close();
        }
    }
}

include "header.php";
?>

<style>
    body {
        background: #f5f7fb;
    }

    .register-wrapper {
        min-height: calc(100vh - 140px); /* leave space for header + footer */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 15px;
    }

    /* Card not too long: reduced width and padding */
    .register-card {
        background-color: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        max-width: 440px;
        width: 100%;
        padding: 24px 22px 18px;
        border: 1px solid #e0e0e0;
    }

    .register-header {
        text-align: center;
        margin-bottom: 14px;
    }

    .register-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        border: 2px solid #27ae60;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-size: 24px;
        color: #27ae60;
    }

    .register-header h2 {
        margin: 0;
        font-size: 22px;
        color: #005A9C;
        font-weight: 700;
    }

    .register-subtitle {
        margin-top: 4px;
        color: #666;
        font-size: 13px;
    }

    .register-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 14px;
        margin-top: 16px;
    }

    .form-group-full {
        grid-column: 1 / 3; /* span two columns */
    }

    .form-group {
        text-align: left;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        margin-bottom: 4px;
        color: #333;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-group input:focus {
        border-color: #005A9C;
        box-shadow: 0 0 0 2px rgba(0, 90, 156, 0.15);
    }

    .btn-register {
        width: 100%;
        border: none;
        padding: 10px 14px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #005A9C, #27ae60);
        color: #fff;
        margin-top: 12px;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    }

    .btn-register:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(0,0,0,0.18);
    }

    .btn-register:active {
        transform: translateY(0);
        box-shadow: 0 6px 12px rgba(0,0,0,0.18);
    }

    .register-footer-links {
        margin-top: 14px;
        font-size: 12px;
        text-align: center;
        color: #777;
    }

    .register-footer-links a {
        color: #005A9C;
        text-decoration: none;
        font-weight: 500;
    }

    .register-footer-links a:hover {
        text-decoration: underline;
    }

    @media (max-width: 600px) {
        .register-card {
            padding: 20px 16px 14px;
        }
        .register-grid {
            grid-template-columns: 1fr;
        }
        .form-group-full {
            grid-column: 1 / 2;
        }
    }
</style>

<div class="register-wrapper">
    <div class="register-card">
        <div class="register-header">
            <div class="register-icon">
                <i class="fa-solid fa-car-side"></i>
            </div>
            <h2>Driver Registration</h2>
            <p class="register-subtitle">Create your driver account to offer transport services.</p>
        </div>

        <form id="driverRegisterForm" method="post" action="">
            <div class="register-grid">
                <div class="form-group form-group-full">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="identification_id">Identification / Matric ID</label>
                    <input type="text" id="identification_id" name="identification_id" placeholder="IC / Passport / Matric" required>
                </div>

                <div class="form-group">
                    <label for="email">Driver Email (MMU student email)</label>
                    <input type="email" id="email" name="email" placeholder="e.g. yourname@student.mmu.edu.my" required>
                </div>

                <div class="form-group">
                    <label for="car_model">Car Model</label>
                    <input type="text" id="car_model" name="car_model" placeholder="e.g. Perodua Myvi" required>
                </div>

                <div class="form-group">
                    <label for="car_plate_number">Car Plate Number</label>
                    <input type="text" id="car_plate_number" name="car_plate_number" placeholder="e.g. WXX 1234" required>
                </div>

                <div class="form-group">
                    <label for="password">Password (min 6 characters)</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">
                Create Driver Account
            </button>
        </form>

        <div class="register-footer-links">
            Already a driver? <a href="driver_login.php">Login here</a>
        </div>
    </div>
</div>

<!-- Front-end validation: block submit if passwords do not match -->
<script>
document.getElementById('driverRegisterForm').addEventListener('submit', function(e) {
    const pwd  = document.getElementById('password').value;
    const cpwd = document.getElementById('confirm_password').value;

    if (pwd !== cpwd) {
        e.preventDefault();
        alert("Password and confirm password do not match.");
    }
});
</script>

<?php
include "footer.php";
?>
