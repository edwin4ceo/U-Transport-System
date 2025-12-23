<?php
session_start();
require_once "db_connect.php";
require_once "function.php";

$token = trim($_GET["token"] ?? "");
if ($token === "") {
    die("Invalid reset link.");
}

$token_hash = hash("sha256", $token);

// Validate reset token
$stmt = $conn->prepare("
    SELECT driver_id 
    FROM driver_password_resets
    WHERE token_hash = ?
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    die("This reset link is invalid or expired.");
}

$driver_id = (int)$res->fetch_assoc()["driver_id"];
$stmt->close();

if (isset($_POST["reset_btn"])) {
    $new_password = $_POST["new_password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (strlen($new_password) < 6) {
        $msg = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm) {
        $msg = "Passwords do not match.";
    } else {
        // Secure password hashing
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update driver password
        $upd = $conn->prepare("
            UPDATE drivers 
            SET password = ? 
            WHERE driver_id = ?
        ");
        $upd->bind_param("si", $hash, $driver_id);
        $upd->execute();
        $upd->close();

        // Invalidate reset token after use
        $del = $conn->prepare("DELETE FROM driver_password_resets WHERE driver_id = ?");
        $del->bind_param("i", $driver_id);
        $del->execute();
        $del->close();

        redirect("driver_login.php");
        exit;
    }
}
?>
