<?php
session_start();
ob_start();
require_once __DIR__ . "/db_connect.php";

// Security Check: Redirect to home if step 1 wasn't completed
if (!isset($_SESSION['driver_reset'])) {
    header("Location: driver_forgot_password.php");
    exit;
}

// Handle OTP Verification
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
        // --- OTP Correct, Update Database ---
        $driver_id = $_SESSION['driver_reset']['driver_id'];
        $new_hash  = $_SESSION['driver_reset']['pwd_hash']; // Retrieve the password hash stored in step 1

        $stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
        $stmt->bind_param("si", $new_hash, $driver_id);

        if ($stmt->execute()) {
            unset($_SESSION['driver_reset']); // Destroy Reset Session
            
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Reuse styles to match previous page */
    body { background-color: #f4f6f8; font-family: 'Inter', sans-serif; }

    .auth-wrapper {
        min-height: calc(100vh - 100px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .auth-card {
        background: #ffffff;
        width: 100%; max-width: 450px;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        padding: 40px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.5);
    }

    .auth-icon {
        width: 60px; height: 60px;
        background: rgba(0, 75, 130, 0.1);
        color: #004b82;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
        margin: 0 auto 20px auto;
    }

    .auth-header h2 { margin: 0 0 10px 0; color: #1e293b; }
    .auth-subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; }

    .otp-input {
        width: 80%;
        padding: 15px;
        font-size: 24px;
        letter-spacing: 12px;
        text-align: center;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-weight: bold;
        color: #004b82;
        outline: none;
        transition: 0.3s;
    }
    .otp-input:focus { border-color: #004b82; box-shadow: 0 0 0 4px rgba(0, 75, 130, 0.1); }

    .btn-auth {
        width: 100%;
        padding: 14px;
        background: #004b82;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 25px;
        transition: 0.3s;
    }
    .btn-auth:hover { background: #00365e; transform: translateY(-2px); }

    .auth-footer { margin-top: 20px; font-size: 13px; }
    .auth-footer a:hover { color: #004b82 !important; text-decoration: underline; }
</style>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <h2>Security Verification</h2>
      <p class="auth-subtitle">Enter the 4-digit code sent to<br><b><?= htmlspecialchars($_SESSION['driver_reset']['email']) ?></b></p>
    </div>

    <form method="post">
      <div class="form-group" style="text-align:center;">
        <input type="text" name="otp_code" maxlength="4" required class="otp-input" placeholder="0000">
      </div>

      <button type="submit" name="verify_otp" class="btn-auth">Verify & Change Password</button>
    </form>

    <div class="auth-footer">
      <a href="driver_forgot_password.php" style="color:#999;">Resend / Start Over</a>
    </div>
  </div>
</div>

<?php 
// --- Display Error/Success Messages ---
if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<?php 
    unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']);
endif; 

include "footer.php"; 
?>