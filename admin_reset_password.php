<?php
require_once 'db_connect.php';

$alert_script = "";
$token = "";
$valid_link = false; // changed name to reflect we are just checking link format first

// 1. Check if Token exists in URL
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $valid_link = true; // We have a token, so show the form
} else {
    header("Location: admin_login.php");
    exit();
}

// 2. Handle Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verify_email = mysqli_real_escape_string($conn, $_POST['verify_email']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // --- SECURITY CHECK: Validate Token AND Email ---
    // This prevents someone from guessing the 6-digit code. 
    // They must also know the email address it belongs to.
    $check_sql = "SELECT * FROM admin_password_resets 
                  WHERE token = '$token' 
                  AND email = '$verify_email' 
                  AND expires_at > NOW()";
    
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        // Validation Passed
        if ($new_pass === $confirm_pass) {
            
            // OPTIONAL: Add Password Strength Validation
            if (strlen($new_pass) < 6) {
                $alert_script = "Swal.fire({ icon: 'warning', title: 'Weak Password', text: 'Password must be at least 6 characters long.' });";
            } else {
                // Update Password
                $update_sql = "UPDATE admins SET password = '$new_pass' WHERE email = '$verify_email'";
                
                if (mysqli_query($conn, $update_sql)) {
                    // Delete the used token
                    mysqli_query($conn, "DELETE FROM admin_password_resets WHERE email = '$verify_email'");
                    
                    $alert_script = "Swal.fire({ 
                        icon: 'success', 
                        title: 'Password Reset!', 
                        text: 'You can now login with your new password.', 
                        confirmButtonText: 'Login Now' 
                    }).then((result) => { window.location = 'admin_login.php'; });";
                } else {
                    $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: 'Could not update password.' });";
                }
            }
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Mismatch', text: 'New password and confirm password do not match.' });";
        }
    } else {
        // Token invalid, expired, OR email didn't match
        $alert_script = "Swal.fire({ 
            icon: 'error', 
            title: 'Verification Failed', 
            text: 'The email provided does not match the reset request, or the code has expired.' 
        });";
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
            width: 100%; max-width: 400px; margin: 60px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2); text-align: center;
        }
        .btn-save { background-color: #27ae60; color: white; width: 100%; padding: 10px; border:none; border-radius:4px; cursor:pointer; font-size:16px; margin-top: 15px;}
        .btn-save:hover { background-color: #219150; }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        label { display: block; text-align: left; font-weight: bold; font-size: 0.9rem; color: #333; margin-top: 10px; }
    </style>
</head>
<body>

    <?php if ($valid_link): ?>
    <div class="reset-container">
        <h2>Set New Password</h2>
        <p style="color:#666; font-size:0.9rem;">For security, please verify your email and enter a new password.</p>
        
        <form method="POST">
            <label>Verify Email Address</label>
            <input type="email" name="verify_email" required placeholder="Enter your email to confirm">

            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Min 6 characters">
            
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="Retype password">
            
            <button type="submit" class="btn-save">Reset Password</button>
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