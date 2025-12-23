<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
require_once "db_connect.php";
require_once "function.php";

$email = trim($_GET["email"] ?? "");
if ($email === "") {
    redirect("driver_forgot_password.php");
    exit;
}

if (isset($_POST["verify_btn"])) {
    $otp_input = trim($_POST["otp_input"] ?? "");

    if (!preg_match('/^\d{4}$/', $otp_input)) {
        $_SESSION['swal_title'] = "Invalid PIN";
        $_SESSION['swal_msg']   = "Please enter a valid 4-digit PIN.";
        $_SESSION['swal_type']  = "error";
    } else {
        // Find driver by email
        $stmt = $conn->prepare("
            SELECT driver_id 
            FROM drivers 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $driver_id = (int)$res->fetch_assoc()["driver_id"];

            // Get latest OTP
            $q = $conn->prepare("
                SELECT id, otp_hash, expires_at, attempts
                FROM driver_reset_otps
                WHERE driver_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $q->bind_param("i", $driver_id);
            $q->execute();
            $otpRes = $q->get_result();

            if ($otpRes->num_rows === 1) {
                $row = $otpRes->fetch_assoc();

                if ((int)$row["attempts"] >= 5) {
                    $_SESSION['swal_msg'] = "Too many attempts. Please request a new PIN.";
                } elseif (strtotime($row["expires_at"]) <= time()) {
                    $_SESSION['swal_msg'] = "This PIN has expired.";
                } elseif (!hash_equals($row["otp_hash"], hash("sha256", $otp_input))) {
                    // Increment failed attempts
                    $up = $conn->prepare("
                        UPDATE driver_reset_otps 
                        SET attempts = attempts + 1 
                        WHERE id = ?
                    ");
                    $up->bind_param("i", $row["id"]);
                    $up->execute();
                    $up->close();

                    $_SESSION['swal_msg'] = "Incorrect PIN.";
                } else {
                    // OTP verified, generate reset token
                    $token = bin2hex(random_bytes(32));
                    $token_hash = hash("sha256", $token);
                    $expires_at = date("Y-m-d H:i:s", time() + 900);

                    // Remove old reset tokens
                    $del = $conn->prepare("DELETE FROM driver_password_resets WHERE driver_id = ?");
                    $del->bind_param("i", $driver_id);
                    $del->execute();
                    $del->close();

                    // Store new reset token
                    $ins = $conn->prepare("
                        INSERT INTO driver_password_resets (driver_id, token_hash, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $ins->bind_param("iss", $driver_id, $token_hash, $expires_at);
                    $ins->execute();
                    $ins->close();

                    // OTP is single-use
                    $del2 = $conn->prepare("DELETE FROM driver_reset_otps WHERE driver_id = ?");
                    $del2->bind_param("i", $driver_id);
                    $del2->execute();
                    $del2->close();

                    header("Location: driver_reset_password.php?token=" . urlencode($token));
                    exit;
                }
            } else {
                $_SESSION['swal_msg'] = "PIN not found. Please request again.";
            }
            $q->close();
        } else {
            $_SESSION['swal_msg'] = "Invalid request.";
        }

        $_SESSION['swal_title'] = "Verification Failed";
        $_SESSION['swal_type']  = "error";

        $stmt->close();
    }
}
?>
