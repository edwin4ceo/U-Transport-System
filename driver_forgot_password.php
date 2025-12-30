<?php
session_start();
include "db_connect.php";
include "function.php";

// --- INCLUDE PHPMAILER ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in
if (isset($_SESSION['driver_id'])) {
    redirect("driver_dashboard.php"); // 
    exit;
}

if (isset($_POST['reset_password'])) {

    $driver_id = trim($_POST['driver_id'] ?? "");
    $email     = trim($_POST['email'] ?? ""); // auto-filled
    $new_password = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    // 1) Basic validation
    if ($driver_id === "" || $email === "") {
        $_SESSION['swal_title'] = "Missing Info";
        $_SESSION['swal_msg']   = "Please enter your Driver ID.";
        $_SESSION['swal_type']  = "error";
    }
    // 2) Password match
    elseif ($new_password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "New passwords do not match. Please try again.";
        $_SESSION['swal_type']  = "error";
    }
    // 3) Password length (optional but recommended)
    elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "Password must be at least 6 characters.";
        $_SESSION['swal_type']  = "error";
    }
    else {

        /**
         * 4) Verify identity
         */
        $stmt = $conn->prepare("SELECT driver_id, full_name, email FROM drivers WHERE driver_id = ? AND email = ? LIMIT 1");
        $stmt->bind_param("ss", $driver_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $row  = $result->fetch_assoc();
            $name = $row['full_name'];

            // Generate OTP (better than rand)
            $otp = random_int(1000, 9999);

            // Hash the NEW password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Store Data in Session
            $_SESSION['temp_driver_reset_data'] = [
                'email' => $email,
                'name' => $name,
                'driver_id' => $driver_id,
                'new_password_hash' => $new_password_hash,
                'otp_code' => $otp,
                'otp_timestamp' => time(),
                'resend_count' => 0
            ];

            // Send OTP Email
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kelvinng051129@gmail.com';
                $mail->Password   = 'pzugwxelatppzoig';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                // $mail->SMTPDebug  = 2;
                // $mail->Debugoutput = 'error_log';

                $mail->setFrom('kelvinng051129@gmail.com', 'U-Transport System');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = 'Driver Reset Password Verification Code';
                $mail->Body    = "
                    <h3>Hello $name,</h3>
                    <p>You have requested to reset your driver account password.</p>
                    <p>Here is your verification code:</p>
                    <h2 style='color:#004b82; letter-spacing:5px;'>$otp</h2>
                    <p>This code will expire in <b>10 minutes</b>.</p>
                    <br>
                    <p style='font-size:12px;color:#666;'>If you did not request this, please ignore this email.</p>
                ";

                $mail->send();

                header("Location: verify_driver_reset_otp.php");
                exit();

            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Email Error";
                $_SESSION['swal_msg']   = "Mailer Error: {$mail->ErrorInfo}";
                $_SESSION['swal_type']  = "error";
            }

        } else {
            $_SESSION['swal_title'] = "Verification Failed";
            $_SESSION['swal_msg']   = "The Driver ID or email does not match our records.";
            $_SESSION['swal_type']  = "error";
        }
    }
}
?>

<?php include "header.php"; ?>

<style>
    input[type="email"], input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .password-wrapper {
        position: relative;
        width: 100%;
    }
    .password-wrapper input {
        margin-bottom: 15px;
        padding-right: 40px;
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
        z-index: 10;
        font-size: 1.1rem;
        user-select: none;
    }
    .toggle-password:hover { color: #005A9C; }

    .form-card{
        max-width: 420px;
        margin: 30px auto;
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 10px;
        background: #fff;
    }
    .muted { color:#666; font-size:13px; }
</style>

<div class="form-card">
    <h2>Driver Forgot Password</h2>

    <p class="muted" style="margin-bottom:6px;">Enter your Driver ID and new password.</p>
    <p style="color:red;font-size:13px;margin-top:0;font-weight:500;">
        * You will need to verify your email in the next step.
    </p>

    <form action="" method="POST">

        <label>Driver ID</label>
        <input type="text" name="driver_id" id="driverIDInput" required placeholder="e.g. DRV0001" maxlength="20">

        <label>Email</label>
        <input type="email" name="email" id="emailInput" required placeholder="Auto-filled email" readonly style="background:#f9f9f9; cursor:not-allowed;">

        <label>New Password</label>
        <div class="password-wrapper">
            <input type="password" name="new_password" id="newPass" required placeholder="Create new password">
            <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconNew"></i>
        </div>

        <label>Confirm New Password</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirmPass" required placeholder="Re-enter new password">
            <i class="fa-solid fa-eye-slash toggle-password" id="eyeIconConfirm"></i>
        </div>

        <button type="submit" name="reset_password" style="font-size:15px;width:100%;">
            Verify Email to Complete Reset Password
        </button>
    </form>

    <div style="margin-top: 15px; text-align: center;">
        <a href="driver_login.php" style="color:#666;text-decoration:none;">&larr; Back to Driver Login</a>
    </div>
</div>

<script>
    const driverIdInput = document.getElementById('driverIDInput');
    const emailInput = document.getElementById('emailInput');

    driverIdInput.addEventListener('input', function() {
        const id = this.value.trim();
        if (id.length > 0) {
            emailInput.value = ""; 
        } else {
            emailInput.value = "";
        }
    });

    function setupPasswordToggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        function show() {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
        function hide() {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }

        icon.addEventListener('mousedown', show);
        icon.addEventListener('mouseup', hide);
        icon.addEventListener('mouseleave', hide);

        icon.addEventListener('touchstart', function(e){ e.preventDefault(); show(); });
        icon.addEventListener('touchend', hide);
    }

    setupPasswordToggle('newPass', 'eyeIconNew');
    setupPasswordToggle('confirmPass', 'eyeIconConfirm');
</script>

<?php include "footer.php"; ?>
