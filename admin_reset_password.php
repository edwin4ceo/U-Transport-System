<?php
require_once 'db_connect.php';

$alert_script = "";
$token = "";
$valid_token = false;

// 1. Check if Token exists in URL
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // 2. Validate Token and Expiry
    $check_sql = "SELECT * FROM admin_password_resets WHERE token = '$token' AND expires_at > NOW()";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        $valid_token = true;
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];
    } else {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Link', text: 'This reset link is invalid or has expired.', confirmButtonText: 'Back to Login' }).then((result) => { window.location = 'admin_login.php'; });";
    }
} else {
    header("Location: admin_login.php");
    exit();
}

// 3. Handle Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        // NOTE: Saving as plain text to match your current system. 
        // If you upgrade to hashing later, use: $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $update_sql = "UPDATE admins SET password = '$new_pass' WHERE email = '$email'";
        
        if (mysqli_query($conn, $update_sql)) {
            // Delete the used token
            mysqli_query($conn, "DELETE FROM admin_password_resets WHERE email = '$email'");
            
            $alert_script = "Swal.fire({ icon: 'success', title: 'Success!', text: 'Your password has been updated.', confirmButtonText: 'Login Now' }).then((result) => { window.location = 'admin_login.php'; });";
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: 'Could not update password.' });";
        }
    } else {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Mismatch', text: 'Passwords do not match.' });";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Admin</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c3e50; font-family: sans-serif; }
        .reset-container {
            width: 100%; max-width: 400px; margin: 80px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2); text-align: center;
        }
        .btn-save { background-color: #27ae60; color: white; width: 100%; padding: 10px; border:none; border-radius:4px; cursor:pointer; font-size:16px; margin-top: 15px;}
        .btn-save:hover { background-color: #219150; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>

    <?php if ($valid_token): ?>
    <div class="reset-container">
        <h2>Set New Password</h2>
        <p>For account: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        
        <form method="POST">
            <input type="password" name="new_password" required placeholder="New Password">
            <input type="password" name="confirm_password" required placeholder="Confirm Password">
            <button type="submit" class="btn-save">Update Password</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if(!empty($alert_script)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $alert_script; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>