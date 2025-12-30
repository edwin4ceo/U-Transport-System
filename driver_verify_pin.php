<?php
session_start();

include "db_connect.php";
include "function.php";

if (!isset($_SESSION["reset_driver_id"])) {
    redirect("driver_forgot_password.php");
    exit;
}

$driver_id = (int)$_SESSION["reset_driver_id"];
$email = $_SESSION["reset_email"] ?? "";

if (isset($_POST["verify_pin"])) {
    $pin = trim($_POST["pin"] ?? "");

    if ($pin === "" || !preg_match('/^\d{4}$/', $pin)) {
        $_SESSION['swal_title'] = "Invalid PIN";
        $_SESSION['swal_msg']   = "Please enter a valid 4-digit PIN.";
        $_SESSION['swal_type']  = "warning";
    } else {
        $pin_hash = hash("sha256", $pin);

        $stmt = $conn->prepare("
            SELECT id, otp_hash, expires_at, attempts
            FROM driver_reset_otps
            WHERE driver_id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error.";
            $_SESSION['swal_type']  = "error";
        } else {
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if (!$res || $res->num_rows !== 1) {
                $_SESSION['swal_title'] = "PIN Invalid";
                $_SESSION['swal_msg']   = "Invalid or expired PIN. Please request a new one.";
                $_SESSION['swal_type']  = "error";
            } else {
                $row = $res->fetch_assoc();
                $otp_id = (int)$row["id"];
                $db_hash = $row["otp_hash"];
                $expires_at = $row["expires_at"];
                $attempts = (int)$row["attempts"];

                if ($attempts >= 5) {
                    $_SESSION['swal_title'] = "Too Many Attempts";
                    $_SESSION['swal_msg']   = "Too many attempts. Please request a new PIN.";
                    $_SESSION['swal_type']  = "error";
                } elseif (strtotime($expires_at) <= time()) {
                    $_SESSION['swal_title'] = "PIN Expired";
                    $_SESSION['swal_msg']   = "PIN expired. Please request a new one.";
                    $_SESSION['swal_type']  = "error";
                } elseif (!hash_equals($db_hash, $pin_hash)) {

                    $upd = $conn->prepare("UPDATE driver_reset_otps SET attempts = attempts + 1 WHERE id = ?");
                    if ($upd) {
                        $upd->bind_param("i", $otp_id);
                        $upd->execute();
                        $upd->close();
                    }

                    $_SESSION['swal_title'] = "Incorrect PIN";
                    $_SESSION['swal_msg']   = "Incorrect PIN. Please try again.";
                    $_SESSION['swal_type']  = "warning";
                } else {
                    // Verified: delete OTP and allow reset
                    $del = $conn->prepare("DELETE FROM driver_reset_otps WHERE id = ?");
                    if ($del) {
                        $del->bind_param("i", $otp_id);
                        $del->execute();
                        $del->close();
                    }

                    $_SESSION["reset_verified"] = true;

                    $_SESSION['swal_title'] = "Verified";
                    $_SESSION['swal_msg']   = "PIN verified. You may now reset your password.";
                    $_SESSION['swal_type']  = "success";

                    redirect("driver_reset_password.php");
                    exit;
                }
            }

            $stmt->close();
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
    .form-group input { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #ccc; font-size:13px; outline:none; transition:border-color 0.2s, box-shadow 0.2s; box-sizing:border-box; }
    .form-group input:focus { border-color:#005A9C; box-shadow:0 0 0 2px rgba(0, 90, 156, 0.15); }
    .btn-reset { width:100%; border:none; padding:10px 14px; border-radius:999px; font-size:14px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#f39c12,#e67e22); color:#fff; margin-top:6px; box-shadow:0 8px 18px rgba(0,0,0,0.16); }
    .forgot-footer-links { margin-top:14px; font-size:12px; text-align:center; color:#777; }
    .forgot-footer-links a { color:#005A9C; text-decoration:none; font-weight:500; }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h2>Verify PIN</h2>
            <p class="forgot-subtitle">
                Enter the 4-digit PIN sent to <b><?php echo htmlspecialchars($email); ?></b>.
            </p>
        </div>

        <form method="post" action="">
            <div class="form-group">
                <label for="pin">4-digit PIN</label>
                <input type="text" id="pin" name="pin" maxlength="4" placeholder="e.g. 1234" required autocomplete="one-time-code">
            </div>

            <button type="submit" name="verify_pin" class="btn-reset">
                Verify
            </button>
        </form>

        <div class="forgot-footer-links">
            Back to <a href="driver_forgot_password.php">Forgot Password</a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
