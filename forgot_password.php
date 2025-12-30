<?php
session_start();
require_once "db_connect.php";
require_once "function.php";
require_once "send_mail.php";

$msg = "";
$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $msg = "Please enter your email address.";
        $error = true;
    } else {
        // Always respond with a generic message to prevent email enumeration
        $genericMsg = "If the email exists, a PIN has been sent. Please check your inbox and spam folder.";

        $stmt = $conn->prepare("
            SELECT driver_id, full_name 
            FROM drivers 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
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

            // Remove any previous OTP for this driver (keep only the newest OTP)
            $del = $conn->prepare("DELETE FROM driver_reset_otps WHERE driver_id = ?");
            $del->bind_param("i", $driver_id);
            $del->execute();
            $del->close();

            // Store new OTP
            $ins = $conn->prepare("
                INSERT INTO driver_reset_otps (driver_id, otp_hash, expires_at) 
                VALUES (?, ?, ?)
            ");
            $ins->bind_param("iss", $driver_id, $otp_hash, $expires_at);
            $ins->execute();
            $ins->close();

            // Store the driver_id temporarily in session for verification step
            $_SESSION["reset_driver_id"] = $driver_id;

            // Send OTP email (do not reveal errors to the user)
            try {
                sendDriverOtpEmail($email, $name, $otp);
            } catch (Exception $e) {
                // Intentionally silent (security)
            }
        }

        $stmt->close();

        // Always redirect to verification page (even if email doesn't exist)
        header("Location: driver_verify_pin.php?email=" . urlencode($email));
        exit;
    }
}
?>
