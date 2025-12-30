<?php
session_start();
require_once "db_connect.php";
require_once "function.php";

$msg = "";
$email = trim($_GET["email"] ?? "");

// If we don't have a reset_driver_id in session, force user to restart flow
if (!isset($_SESSION["reset_driver_id"])) {
    die("Session expired. Please restart the password reset process.");
}
$driver_id = (int)$_SESSION["reset_driver_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pin = trim($_POST["pin"] ?? "");

    if ($pin === "" || !preg_match('/^\d{4}$/', $pin)) {
        $msg = "Please enter a valid 4-digit PIN.";
    } else {
        $pin_hash = hash("sha256", $pin);

        // Read latest OTP record for this driver
        $stmt = $conn->prepare("
            SELECT id, otp_hash, expires_at, attempts
            FROM driver_reset_otps
            WHERE driver_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows !== 1) {
            $msg = "Invalid or expired PIN. Please request a new one.";
        } else {
            $row = $res->fetch_assoc();
            $otp_id = (int)$row["id"];
            $db_hash = $row["otp_hash"];
            $expires_at = $row["expires_at"];
            $attempts = (int)$row["attempts"];

            // Basic rate limit (max 5 attempts)
            if ($attempts >= 5) {
                $msg = "Too many attempts. Please request a new PIN.";
            } elseif (strtotime($expires_at) <= time()) {
                $msg = "PIN expired. Please request a new one.";
            } elseif (!hash_equals($db_hash, $pin_hash)) {

                // Increment attempts on failure
                $upd = $conn->prepare("UPDATE driver_reset_otps SET attempts = attempts + 1 WHERE id = ?");
                $upd->bind_param("i", $otp_id);
                $upd->execute();
                $upd->close();

                $msg = "Incorrect PIN. Please try again.";
            } else {
                // PIN verified: remove OTP record
                $del = $conn->prepare("DELETE FROM driver_reset_otps WHERE id = ?");
                $del->bind_param("i", $otp_id);
                $del->execute();
                $del->close();

                // Create a reset token (valid for 30 minutes)
                $token = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);
                $reset_expires = date("Y-m-d H:i:s", time() + 1800);

                // Remove old tokens for this driver (optional but recommended)
                $del2 = $conn->prepare("DELETE FROM driver_password_resets WHERE driver_id = ?");
                $del2->bind_param("i", $driver_id);
                $del2->execute();
                $del2->close();

                // Store reset token
                $ins = $conn->prepare("
                    INSERT INTO driver_password_resets (driver_id, token_hash, expires_at)
                    VALUES (?, ?, ?)
                ");
                $ins->bind_param("iss", $driver_id, $token_hash, $reset_expires);
                $ins->execute();
                $ins->close();

                // Clear session flag so it cannot be reused
                unset($_SESSION["reset_driver_id"]);

                // Redirect to reset password page with token
                header("Location: driver_reset_password.php?token=" . urlencode($token));
                exit;
            }
        }
        $stmt->close();
    }
}
?>
<!-- Minimal HTML form example (you can style with your UI) -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Verify PIN</title>
</head>
<body>
  <h2>Verify PIN</h2>
  <?php if ($email !== ""): ?>
    <p>We sent a 4-digit PIN to: <b><?php echo htmlspecialchars($email); ?></b></p>
  <?php endif; ?>

  <?php if ($msg !== ""): ?>
    <p style="color:red;"><?php echo htmlspecialchars($msg); ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>4-digit PIN:</label><br>
    <input type="text" name="pin" maxlength="4" autocomplete="one-time-code" required>
    <br><br>
    <button type="submit">Verify</button>
  </form>
</body>
</html>
