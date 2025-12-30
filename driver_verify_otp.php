<?php
session_start();

require_once "db_connect.php";
require_once "function.php";

// 必须有 reset session 才能进来
if (!isset($_SESSION['driver_reset'])) {
    redirect("driver_forgot_password.php");
    exit;
}

$data = $_SESSION['driver_reset'];

if (isset($_POST['verify_otp'])) {

    $inputOtp = trim($_POST['otp'] ?? "");

    // 1. Empty OTP
    if ($inputOtp === "") {
        $_SESSION['swal_title'] = "Missing Code";
        $_SESSION['swal_msg']   = "Please enter the verification code.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2. OTP expired
    elseif (time() > $data['expires']) {
        unset($_SESSION['driver_reset']);

        $_SESSION['swal_title'] = "OTP Expired";
        $_SESSION['swal_msg']   = "The verification code has expired. Please request a new one.";
        $_SESSION['swal_type']  = "error";

        redirect("driver_forgot_password.php");
        exit;
    }
    // 3. OTP mismatch
    elseif ($inputOtp !== $data['otp']) {
        $_SESSION['swal_title'] = "Invalid Code";
        $_SESSION['swal_msg']   = "The verification code is incorrect.";
        $_SESSION['swal_type']  = "error";
    }
    else {
        // 4. OTP correct → update password
        $stmt = $conn->prepare("
            UPDATE drivers 
            SET password = ?
            WHERE driver_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error. Please try again.";
            $_SESSION['swal_type']  = "error";
        } else {
            $stmt->bind_param("si", $data['pwd_hash'], $data['driver_id']);

            if ($stmt->execute()) {

                // Clear reset session
                unset($_SESSION['driver_reset']);

                $_SESSION['swal_title'] = "Password Updated";
                $_SESSION['swal_msg']   = "Your password has been reset successfully. Please login.";
                $_SESSION['swal_type']  = "success";

                redirect("driver_login.php");
                exit;
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Failed to update password.";
                $_SESSION['swal_type']  = "error";
            }

            $stmt->close();
        }
    }
}

include "header.php";
?>

<style>
body { background:#f5f7fb; }

.verify-wrapper{
    min-height:calc(100vh - 140px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 15px;
}
.verify-card{
    background:#fff;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    max-width:380px;
    width:100%;
    padding:26px 24px;
}
.verify-header{text-align:center;margin-bottom:14px;}
.verify-icon{
    width:52px;height:52px;border-radius:50%;
    border:2px solid #005A9C;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 8px;color:#005A9C;font-size:22px;
}
.verify-header h2{margin:0;color:#005A9C;font-weight:700;}
.verify-subtitle{font-size:13px;color:#666;text-align:center;}

.form-group{margin-top:14px;}
.form-group input{
    width:100%;
    padding:10px;
    font-size:18px;
    text-align:center;
    letter-spacing:6px;
    border-radius:10px;
    border:1px solid #ccc;
}
.btn-verify{
    width:100%;
    margin-top:16px;
    border:none;
    padding:10px;
    border-radius:999px;
    background:linear-gradient(135deg,#005A9C,#007BFF);
    color:#fff;
    font-weight:600;
}
</style>

<div class="verify-wrapper">
    <div class="verify-card">
        <div class="verify-header">
            <div class="verify-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2>Verify OTP</h2>
            <p class="verify-subtitle">
                Enter the 4-digit code sent to<br>
                <b><?= htmlspecialchars($data['email']) ?></b>
            </p>
        </div>

        <form method="post">
            <div class="form-group">
                <input type="text" name="otp" maxlength="4" placeholder="••••" required>
            </div>

            <button type="submit" name="verify_otp" class="btn-verify">
                Verify & Reset Password
            </button>
        </form>
    </div>
</div>

<?php include "footer.php"; ?>
