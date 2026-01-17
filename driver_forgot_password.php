<?php
session_start();
ob_start(); // Buffer output to prevent header errors

require_once __DIR__ . "/db_connect.php";
require_once __DIR__ . "/send_mail.php"; 

// Handle form submission
if (isset($_POST['reset_request'])) {

    $email    = trim($_POST['email'] ?? "");
    $ic       = trim($_POST['identification_id'] ?? "");
    $new_pass = $_POST['new_password'] ?? "";
    $confirm  = $_POST['confirm_password'] ?? "";

    // Helper function for errors
    function fpError($title, $msg) {
        $_SESSION['swal_title'] = $title;
        $_SESSION['swal_msg']   = $msg;
        $_SESSION['swal_type']  = "warning";
        header("Location: driver_forgot_password.php");
        exit;
    }

    // 1. Basic Validation
    if (empty($email) || empty($ic) || empty($new_pass)) {
        fpError("Missing Fields", "Please fill in all fields.");
    } elseif ($new_pass !== $confirm) {
        fpError("Password Mismatch", "Passwords do not match.");
    } else {
        // 2. Verify identity & GET PASSWORD HASH
        $stmt = $conn->prepare("SELECT driver_id, full_name, password FROM drivers WHERE email = ? AND identification_id = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $ic);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();

            // =================================================
            // [NEW FEATURE] Check if new password == old password
            // =================================================
            if (password_verify($new_pass, $row['password'])) {
                // If true, it means they are trying to use the old password again
                fpError("Same as Old Password", "You cannot use your previous password. Please choose a new one.");
            }

            // 3. Generate OTP
            $otp = (string)rand(1000, 9999);

            // Store critical info in Session
            $_SESSION['driver_reset'] = [
                'driver_id' => $row['driver_id'],
                'email'     => $email,
                'otp'       => $otp,
                'pwd_hash'  => password_hash($new_pass, PASSWORD_BCRYPT), // Store hash temporarily
                'expires'   => time() + 600 // Valid for 10 minutes
            ];

            try {
                // 4. Send Email
                sendDriverOtpEmail($email, $row['full_name'], $otp);
                
                // Success: Redirect to OTP page
                header("Location: driver_verify_otp.php"); 
                exit;

            } catch (Exception $e) {
                fpError("Email Error", "System could not send email. Please try again later.");
            }
        } else {
            fpError("Account Not Found", "Email and ID do not match our records.");
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

<?php if(isset($_SESSION['swal_title'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<?php 
    // Clear session to prevent popup on refresh
    unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']);
endif; 
?>

<?php include "footer.php"; ?>