<?php
session_start();

include "db_connect.php";
include "function.php";
require_once "send_mail.php";

if (isset($_POST['send_pin'])) {

    $email             = trim($_POST['email'] ?? '');
    $identification_id = trim($_POST['identification_id'] ?? '');

    // 1) Required
    if ($email === '' || $identification_id === '') {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Email and Identification ID are required.";
        $_SESSION['swal_type']  = "warning";
    }
    // 2) Email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please enter a valid email address.";
        $_SESSION['swal_type']  = "warning";
    }
    // 3) Enforce MMU student email
    elseif (substr($email, -19) !== "@student.mmu.edu.my") {
        $_SESSION['swal_title'] = "Invalid Email Domain";
        $_SESSION['swal_msg']   = "You must use your MMU student email (@student.mmu.edu.my).";
        $_SESSION['swal_type']  = "warning";
    }
    else {

        // Always show generic message to prevent email enumeration
        $genericTitle = "PIN Sent";
        $genericMsg   = "If the account exists, a PIN has been sent. Please check your inbox and spam folder.";

        // Check driver by email + identification_id
        $stmt = $conn->prepare("
            SELECT driver_id, full_name 
            FROM drivers 
            WHERE email = ? AND identification_id = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("ss", $email, $identification_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $driver = $res->fetch_assoc();
                $driver_id = (int)$driver["driver_id"];
                $name = $driver["full_name"] ?? "Driver";

                // Generate 4-digit OTP (valid for 10 minutes)
                $otp = str_pad((string)random_int(0, 9999), 4, "0", STR_PAD_LEFT);
                $otp_hash = hash("sha256", $otp);
                $expires_at = date("Y-m-d H:i:s", time() + 600);

                // Delete previous OTPs for this driver
                $del = $conn->prepare("DELETE FROM driver_reset_otps WHERE driver_id = ?");
                if ($del) {
                    $del->bind_param("i", $driver_id);
                    $del->execute();
                    $del->close();
                }

                // Insert new OTP
                $ins = $conn->prepare("
                    INSERT INTO driver_reset_otps (driver_id, otp_hash, expires_at, attempts) 
                    VALUES (?, ?, ?, 0)
                ");
                if ($ins) {
                    $ins->bind_param("iss", $driver_id, $otp_hash, $expires_at);
                    $ins->execute();
                    $ins->close();
                }

                // Store driver id in session for verification step
                $_SESSION["reset_driver_id"] = $driver_id;
                $_SESSION["reset_email"] = $email;

                // Send email (silent on failure)
                try {
                    sendDriverOtpEmail($email, $name, $otp);
                } catch (Exception $e) {
                    // intentionally silent
                }
            }

            $stmt->close();
        }

        // Always redirect to verify page (generic)
        $_SESSION['swal_title'] = $genericTitle;
        $_SESSION['swal_msg']   = $genericMsg;
        $_SESSION['swal_type']  = "success";

        redirect("driver_verify_pin.php");
        exit;
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
    .form-group input { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #ccc; font-size:13px; outline:none; transition:border-color 0.2s, box-shadow 0.2s; box-sizing:border-box; }
    .form-group input:focus { border-color:#005A9C; box-shadow:0 0 0 2px rgba(0, 90, 156, 0.15); }
    .btn-reset { width:100%; border:none; padding:10px 14px; border-radius:999px; font-size:14px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#f39c12,#e67e22); color:#fff; margin-top:6px; transition:transform 0.1s ease, box-shadow 0.1s ease; box-shadow:0 8px 18px rgba(0,0,0,0.16); }
    .btn-reset:hover { transform:translateY(-1px); box-shadow:0 10px 22px rgba(0,0,0,0.18); }
    .btn-reset:active { transform:translateY(0); box-shadow:0 6px 12px rgba(0,0,0,0.18); }
    .forgot-footer-links { margin-top:14px; font-size:12px; text-align:center; color:#777; }
    .forgot-footer-links a { color:#005A9C; text-decoration:none; font-weight:500; }
    .forgot-footer-links a:hover { text-decoration:underline; }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2>Forgot Password</h2>
            <p class="forgot-subtitle">
                Enter your MMU email and identification ID to receive a 4-digit PIN.
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

            <button type="submit" name="send_pin" class="btn-reset">
                Send PIN
            </button>
        </form>

        <div class="forgot-footer-links">
            Remembered your password? <a href="driver_login.php">Back to login</a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
require_once "send_mail.php";

error_log(">>> BEFORE sendOtpEmail <<<");

sendOtpEmail("kelvinng051129@gmail.com", "Test", "1234");

error_log(">>> AFTER sendOtpEmail <<<");

exit;
