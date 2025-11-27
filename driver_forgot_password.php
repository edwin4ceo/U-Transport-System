<?php
session_start();

include "db_connect.php";
include "function.php";

// If already logged in, you may redirect to dashboard (optional)
// if (isset($_SESSION['driver_id'])) {
//     redirect("driver_dashboard.php");
// }

if (isset($_POST['reset_password'])) {

    $email             = trim($_POST['email']);
    $identification_id = trim($_POST['identification_id']);
    $new_password      = $_POST['new_password'];
    $confirm_password  = $_POST['confirm_password'];

    // 1. All fields required
    if ($email === '' || $identification_id === '' || $new_password === '' || $confirm_password === '') {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "All fields are required. Please fill in every field.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2. Email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please enter a valid email address.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2B. Enforce MMU student email
    elseif (substr($email, -19) !== "@student.mmu.edu.my") {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg']   = "You must use your MMU student email (@student.mmu.edu.my).";
        $_SESSION['swal_type']  = "warning";
    }
    // 3. Password length
    elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "New password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    }
    // 4. Confirm password match
    elseif ($new_password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "New password and confirm password do not match.";
        $_SESSION['swal_type']  = "warning";
    }
    else {
        // 5. Check if driver exists with this email + identification_id
        $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ? AND identification_id = ?");
        if (!$check) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (check driver).";
            $_SESSION['swal_type']  = "error";
        } else {
            $check->bind_param("ss", $email, $identification_id);
            $check->execute();
            $result = $check->get_result();

            if ($result && $result->num_rows === 1) {
                $driver = $result->fetch_assoc();
                $driver_id = $driver['driver_id'];

                // Update password
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);

                $update = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
                if (!$update) {
                    $_SESSION['swal_title'] = "Error";
                    $_SESSION['swal_msg']   = "Database error (update password).";
                    $_SESSION['swal_type']  = "error";
                } else {
                    $update->bind_param("si", $hashed, $driver_id);

                    if ($update->execute()) {
                        $_SESSION['swal_title'] = "Password Updated";
                        $_SESSION['swal_msg']   = "Your password has been reset. Please login with your new password.";
                        $_SESSION['swal_type']  = "success";

                        redirect("driver_login.php");
                    } else {
                        $_SESSION['swal_title'] = "Error";
                        $_SESSION['swal_msg']   = "Failed to update password. Please try again.";
                        $_SESSION['swal_type']  = "error";
                    }

                    $update->close();
                }
            } else {
                $_SESSION['swal_title'] = "Account Not Found";
                $_SESSION['swal_msg']   = "No driver found with this email and identification ID.";
                $_SESSION['swal_type']  = "error";
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

    .forgot-wrapper {
        min-height: calc(100vh - 140px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 15px;
    }

    .forgot-card {
        background-color: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        max-width: 420px;
        width: 100%;
        padding: 26px 24px 20px;
        border: 1px solid #e0e0e0;
    }

    .forgot-header {
        text-align: center;
        margin-bottom: 14px;
    }

    .forgot-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 2px solid #f39c12;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-size: 22px;
        color: #f39c12;
    }

    .forgot-header h2 {
        margin: 0;
        font-size: 22px;
        color: #005A9C;
        font-weight: 700;
    }

    .forgot-subtitle {
        margin-top: 4px;
        color: #666;
        font-size: 13px;
    }

    .form-group {
        text-align: left;
        margin-bottom: 14px;
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

    .btn-reset {
        width: 100%;
        border: none;
        padding: 10px 14px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: #fff;
        margin-top: 6px;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    }

    .btn-reset:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(0,0,0,0.18);
    }

    .btn-reset:active {
        transform: translateY(0);
        box-shadow: 0 6px 12px rgba(0,0,0,0.18);
    }

    .forgot-footer-links {
        margin-top: 14px;
        font-size: 12px;
        text-align: center;
        color: #777;
    }

    .forgot-footer-links a {
        color: #005A9C;
        text-decoration: none;
        font-weight: 500;
    }

    .forgot-footer-links a:hover {
        text-decoration: underline;
    }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2>Reset Password</h2>
            <p class="forgot-subtitle">
                Enter your MMU email, identification ID, and a new password.
            </p>
        </div>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Driver Email (MMU email)</label>
                <input type="email" id="email" name="email" placeholder="e.g. xxx@student.mmu.edu.my" required>
            </div>

            <div class="form-group">
                <label for="identification_id">Identification / Matric ID</label>
                <input type="text" id="identification_id" name="identification_id" placeholder="IC / Passport / Matric" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password (min 6 characters)</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
            </div>

            <button type="submit" name="reset_password" class="btn-reset">
                Reset Password
            </button>
        </form>

        <div class="forgot-footer-links">
            Remembered your password? <a href="driver_login.php">Back to login</a>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const pwd  = document.getElementById('new_password').value;
    const cpwd = document.getElementById('confirm_password').value;

    if (pwd !== cpwd) {
        e.preventDefault();
        alert("New password and confirm password do not match.");
    }
});
</script>

<?php
include "footer.php";
?>
