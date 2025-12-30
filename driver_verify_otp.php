<?php
session_start();
ob_start();
require_once __DIR__ . "/db_connect.php";

if (!isset($_SESSION['driver_reset'])) {
    header("Location: driver_forgot_password.php");
    exit;
}

if (isset($_POST['verify_otp'])) {
    $input_otp = trim($_POST['otp_code']);
    $session_otp = $_SESSION['driver_reset']['otp'];
    $expires = $_SESSION['driver_reset']['expires'];

    if (time() > $expires) {
        $_SESSION['swal_title'] = "Expired";
        $_SESSION['swal_msg']   = "OTP has expired. Please try again.";
        $_SESSION['swal_type']  = "error";
        unset($_SESSION['driver_reset']); // Clear Session
        header("Location: driver_forgot_password.php");
        exit;
    } elseif ($input_otp === $session_otp) {
        $driver_id = $_SESSION['driver_reset']['driver_id'];
        $new_hash  = $_SESSION['driver_reset']['pwd_hash']; 

        $stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
        $stmt->bind_param("si", $new_hash, $driver_id);

        if ($stmt->execute()) {
            unset($_SESSION['driver_reset']); // Reset Session
            
            $_SESSION['swal_title'] = "Success!";
            $_SESSION['swal_msg']   = "Password updated. Please login.";
            $_SESSION['swal_type']  = "success";
            
            header("Location: driver_login.php");
            exit;
        } else {
            $_SESSION['swal_title'] = "Database Error";
            $_SESSION['swal_msg']   = "Could not update password.";
            $_SESSION['swal_type']  = "error";
        }
    } else {
        $_SESSION['swal_title'] = "Invalid OTP";
        $_SESSION['swal_msg']   = "The code you entered is incorrect.";
        $_SESSION['swal_type']  = "error";
    }
}

include "header.php";
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <h2>Security Verification</h2>
      <p class="auth-subtitle">Enter the 4-digit code sent to <b><?= htmlspecialchars($_SESSION['driver_reset']['email']) ?></b></p>
    </div>

    <form method="post">
      <div class="form-group" style="text-align:center;">
        <label>Enter OTP Code</label>
        <input type="text" name="otp_code" maxlength="4" required 
               style="text-align:center; font-size:24px; letter-spacing: 10px; font-weight:bold; width: 60%; margin: 0 auto; display:block;"
               placeholder="0 0 0 0">
      </div>

      <button type="submit" name="verify_otp" class="btn-auth">Verify & Change Password</button>
    </form>

    <div class="auth-footer">
      <a href="driver_forgot_password.php" style="color:#999;">Resend / Start Over</a>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>