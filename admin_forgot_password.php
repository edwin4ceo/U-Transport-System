<?php
session_start();
require_once 'db_connect.php';

$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // 1. Check if Email Exists in Admins Table
    $check_sql = "SELECT * FROM admins WHERE email = '$email'";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        // 2. Generate Token & Expiry (1 Hour)
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // 3. Save Token to Database
        // First, delete any old tokens for this email to keep it clean
        mysqli_query($conn, "DELETE FROM admin_password_resets WHERE email='$email'");
        
        $insert_sql = "INSERT INTO admin_password_resets (email, token, expires_at) VALUES ('$email', '$token', '$expiry')";
        
        if (mysqli_query($conn, $insert_sql)) {
            // 4. GENERATE LINK
            // NOTE: Change 'localhost' to your actual domain if live.
            $reset_link = "http://localhost/U-Transport-System/admin_reset_password.php?token=" . $token;

            // --- SIMULATION MODE (For Localhost) ---
            // Instead of mailing, we show the link in the alert so you can click it.
            $alert_script = "
                Swal.fire({
                    icon: 'success',
                    title: 'Reset Link Generated!',
                    html: 'Since this is localhost, copy this link:<br><a href=\"$reset_link\" style=\"color:blue; font-weight:bold;\">Click Here to Reset</a>',
                    footer: 'In a real site, this would be sent to your email.'
                });
            ";
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Error', text: 'Could not generate token.' });";
        }
    } else {
        // Security: Don't reveal if email exists, but for admin panel it's usually okay to be specific
        $alert_script = "Swal.fire({ icon: 'error', title: 'Not Found', text: 'No admin account found with that email.' });";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c3e50; font-family: sans-serif; }
        .reset-container {
            width: 100%; max-width: 400px; margin: 80px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2); text-align: center;
        }
        .btn-reset { background-color: #e67e22; color: white; width: 100%; padding: 10px; border:none; border-radius:4px; cursor:pointer; font-size:16px;}
        .btn-reset:hover { background-color: #d35400; }
        input[type="email"] { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="reset-container">
        <i class="fa-solid fa-lock fa-3x" style="color: #2c3e50; margin-bottom: 10px;"></i>
        <h2 style="color: #2c3e50;">Admin Password Reset</h2>
        <p style="color: #666; font-size: 0.9rem;">Enter your admin email to receive a reset link.</p>

        <form method="POST">
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">
            <button type="submit" class="btn-reset">Send Reset Link</button>
        </form>

        <div style="margin-top: 20px;">
            <a href="admin_login.php" style="color: #7f8c8d; text-decoration: none;">Back to Login</a>
        </div>
    </div>

    <?php if(!empty($alert_script)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $alert_script; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>