<?php
session_start();

include "db_connect.php";
include "function.php";

if (!isset($_SESSION["reset_driver_id"]) || empty($_SESSION["reset_verified"])) {
    redirect("driver_forgot_password.php");
    exit;
}

$driver_id = (int)$_SESSION["reset_driver_id"];

if (isset($_POST['reset_password'])) {

    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password === '' || $confirm_password === '') {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all fields.";
        $_SESSION['swal_type']  = "warning";
    }
    elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "New password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    }
    elseif ($new_password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "New password and confirm password do not match.";
        $_SESSION['swal_type']  = "warning";
    }
    else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);

        $update = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
        if (!$update) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (update password).";
            $_SESSION['swal_type']  = "error";
        } else {
            $update->bind_param("si", $hashed, $driver_id);

            if ($update->execute()) {
                // Clear reset session
                unset($_SESSION["reset_driver_id"], $_SESSION["reset_verified"], $_SESSION["reset_email"]);

                $_SESSION['swal_title'] = "Password Updated";
                $_SESSION['swal_msg']   = "Your password has been reset. Please login with your new password.";
                $_SESSION['swal_type']  = "success";

                redirect("driver_login.php");
                exit;
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Failed to update password. Please try again.";
                $_SESSION['swal_type']  = "error";
            }

            $update->close();
        }
    }
}

include "header.php";
?>

<style>
    body { background: #f5f7fb; }
    .forgot-wrapper { min-height: calc(100vh - 140px); display:flex; align-items:center; justify-content:center; padding:40px 15px; }
    .forgot-card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.08); max-width:420px; width:100%; padding:26px 24px 20px; border:1px solid #e0e0e0; }
    .forgot-header { text-align:center; margin-bottom:14px; }
    .forgot-icon { width:52px; height:52px; border-radius:50%; border:2px solid #f39c12; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-size:22px; color:#f39c12; }
    .forgot-header h2 { margin:0; font-size:22px; color:#005A9C; font-weight:700; }
    .forgot-subtitle { margin-top:4px; color:#666; font-size:13px; }
    .form-group { text-align:left; margin-bottom:14px; }
    .form-group label { display:block; font-size:13px; margin-bottom:4px; color:#333; font-weight:500; }
    .form-group input { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #ccc; font-size:13px; outline:none; box-sizing:border-box; }
    .btn-reset { width:100%; border:none; padding:10px 14px; border-radius:999px; font-size:14px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#f39c12,#e67e22); color:#fff; margin-top:6px; box-shadow:0 8px 18px rgba(0,0,0,0.16); }
    .forgot-footer-links { margin-top:14px; font-size:12px; text-align:center; color:#777; }
    .forgot-footer-links a { color:#005A9C; text-decoration:none; font-weight:500; }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon"><i class="fa-solid fa-lock"></i></div>
            <h2>Reset Password</h2>
            <p class="forgot-subtitle">
                Create a new password for your account.
            </p>
        </div>

        <form method="post" action="">
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
            Back to <a href="driver_login.php">Login</a>
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

<?php include "footer.php"; ?>
