<?php
session_start();
ob_start(); // Buffer output to prevent header errors

require_once __DIR__ . "/db_connect.php";
require_once __DIR__ . "/send_mail.php"; // Ensure mail function is included

// Handle form submission
if (isset($_POST['reset_request'])) {

    $email    = trim($_POST['email'] ?? "");
    $ic       = trim($_POST['identification_id'] ?? "");
    $new_pass = $_POST['new_password'] ?? "";
    $confirm  = $_POST['confirm_password'] ?? "";

    // Basic Validation
    if (empty($email) || empty($ic) || empty($new_pass)) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all fields.";
        $_SESSION['swal_type']  = "warning";
    } elseif ($new_pass !== $confirm) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "Passwords do not match.";
        $_SESSION['swal_type']  = "error";
    } else {
        // Verify identity in database
        $stmt = $conn->prepare("SELECT driver_id, full_name FROM drivers WHERE email = ? AND identification_id = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $ic);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $otp = (string)rand(1000, 9999);

            // Critical: Store User ID and pending password hash in Session
            $_SESSION['driver_reset'] = [
                'driver_id' => $row['driver_id'],
                'email'     => $email,
                'otp'       => $otp,
                'pwd_hash'  => password_hash($new_pass, PASSWORD_BCRYPT), // Store hash temporarily
                'expires'   => time() + 600 // Valid for 10 minutes
            ];

            try {
                sendDriverOtpEmail($email, $row['full_name'], $otp);
                
                // Email sent successfully, redirect to OTP page
                header("Location: driver_verify_otp.php"); 
                exit;

            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg']   = "System could not send email.";
                $_SESSION['swal_type']  = "error";
            }
        } else {
            $_SESSION['swal_title'] = "Account Not Found";
            $_SESSION['swal_msg']   = "Email and ID do not match our records.";
            $_SESSION['swal_type']  = "error";
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon"><i class="fa-solid fa-key"></i></div>
      <h2>Reset Password</h2>
      <p class="auth-subtitle">Enter details to reset your password.</p>
    </div>

    <form method="post">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>
      <div class="form-group">
        <label>Identification ID</label>
        <input type="text" name="identification_id" required value="<?= isset($_POST['identification_id']) ? htmlspecialchars($_POST['identification_id']) : '' ?>">
      </div>

      <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" required minlength="6" placeholder="Min 6 chars">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required placeholder="Re-enter password">
      </div>

      <button type="submit" name="reset_request" class="btn-auth">Send OTP</button>
    </form>
    
    <div class="auth-footer">
      <a href="driver_login.php">Back to Login</a>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>