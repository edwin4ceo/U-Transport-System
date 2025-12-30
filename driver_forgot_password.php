<?php
session_start();

require_once "db_connect.php";
require_once "function.php";
require_once "send_mail.php"; 

// if (isset($_SESSION['driver_id'])) {
//     redirect("driver_dashboard.php");
//     exit;
// }

if (isset($_POST['reset_password'])) {

    $email        = trim($_POST['email'] ?? "");
    $ic           = trim($_POST['identification_id'] ?? "");
    $new_password = $_POST['new_password'] ?? "";
    $confirm      = $_POST['confirm_password'] ?? "";

    /* ---------- Validation ---------- */
    if ($email === "" || $ic === "" || $new_password === "" || $confirm === "") {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "All fields are required.";
        $_SESSION['swal_type']  = "warning";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['swal_title'] = "Invalid Email";
        $_SESSION['swal_msg']   = "Please enter a valid email address.";
        $_SESSION['swal_type']  = "error";
    }
    elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "Password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    }
    elseif ($new_password !== $confirm) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "Passwords do not match.";
        $_SESSION['swal_type']  = "error";
    }
    else {
        /* ---------- Verify driver ---------- */
        $stmt = $conn->prepare("
            SELECT driver_id, full_name 
            FROM drivers 
            WHERE email = ? AND identification_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error.";
            $_SESSION['swal_type']  = "error";
        } else {
            $stmt->bind_param("ss", $email, $ic);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $row       = $res->fetch_assoc();
                $driver_id = (int)$row['driver_id'];
                $name      = $row['full_name'] ?? "Driver";

                /* ---------- OTP ---------- */
                $otp = (string)random_int(1000, 9999);

                $_SESSION['driver_reset'] = [
                    'driver_id' => $driver_id,
                    'email'     => $email,
                    'name'      => $name,
                    'otp'       => $otp,
                    'expires'   => time() + 600, // 10 min
                    'pwd_hash'  => password_hash($new_password, PASSWORD_BCRYPT)
                ];

                try {
                    sendDriverOtpEmail($email, $name, $otp);

                    header("Location: driver_verify_otp.php");
                    exit;
                } catch (Exception $e) {
                    // error_log($e->getMessage());

                    $_SESSION['swal_title'] = "Email Error";
                    $_SESSION['swal_msg']   = "Unable to send verification email. Please try again.";
                    $_SESSION['swal_type']  = "error";
                }
            } else {
                $_SESSION['swal_title'] = "Account Not Found";
                $_SESSION['swal_msg']   = "Email and identification ID do not match our records.";
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

.forgot-wrapper{
    min-height:calc(100vh - 140px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 15px;
}
.forgot-card{
    background:#fff;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    max-width:420px;
    width:100%;
    padding:26px 24px 20px;
}
.forgot-header{text-align:center;margin-bottom:14px;}
.forgot-icon{
    width:52px;height:52px;border-radius:50%;
    border:2px solid #f39c12;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 8px;color:#f39c12;font-size:22px;
}
.forgot-header h2{margin:0;color:#005A9C;font-weight:700;}
.forgot-subtitle{font-size:13px;color:#666;}

.form-group{margin-bottom:14px;}
.form-group label{font-size:13px;font-weight:500;}
.form-group input{
    width:100%;padding:8px 10px;
    border-radius:8px;border:1px solid #ccc;font-size:13px;
}
.btn-reset{
    width:100%;border:none;padding:10px;border-radius:999px;
    background:linear-gradient(135deg,#f39c12,#e67e22);
    color:#fff;font-weight:600;
}
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon"><i class="fa-solid fa-key"></i></div>
            <h2>Driver Reset Password</h2>
            <p class="forgot-subtitle">Verify your identity and receive an OTP.</p>
        </div>

        <form method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Identification ID</label>
                <input type="text" name="identification_id" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" name="reset_password" class="btn-reset">
                Send OTP
            </button>
        </form>
    </div>
</div>

<?php include "footer.php"; ?>
