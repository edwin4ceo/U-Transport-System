<?php
session_start();
require_once 'db_connect.php';

$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // 1. Check if Email Exists
    $check_sql = "SELECT * FROM admins WHERE email = '$email'";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        
        // --- NEW: Generate a simple 6-digit code ---
        $token = rand(100000, 999999); 

        // 2. Save Code to Database
        // Delete old codes for this email first
        mysqli_query($conn, "DELETE FROM admin_password_resets WHERE email='$email'");
        
        $insert_sql = "INSERT INTO admin_password_resets (email, token, expires_at) 
                       VALUES ('$email', '$token', DATE_ADD(NOW(), INTERVAL 1 HOUR))";
        
        if (mysqli_query($conn, $insert_sql)) {
            // 3. GENERATE SHORT LINK
            $folder_name = basename(__DIR__); 
            $reset_link = "http://localhost/$folder_name/admin_reset_password.php?token=" . $token;

            // Show Simple Alert
            $alert_script = "
                Swal.fire({
                    icon: 'success',
                    title: 'Reset Code Generated',
                    html: '<h3>Code: $token</h3><br>Click below to reset:<br><a href=\"$reset_link\" class=\"btn-link\">Reset Password</a>',
                    showConfirmButton: false,
                });
            ";
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Error', text: 'Database error.' });";
        }
    } else {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Not Found', text: 'No admin account found.' });";
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
        
        /* Style for the link inside SweetAlert */
        .btn-link {
            background-color: #27ae60; color: white; padding: 10px 20px; 
            text-decoration: none; border-radius: 5px; display: inline-block; 
            margin-top: 10px; font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="reset-container">
        <i class="fa-solid fa-lock fa-3x" style="color: #2c3e50; margin-bottom: 10px;"></i>
        <h2 style="color: #2c3e50;">Admin Password Reset</h2>
        <p style="color: #666; font-size: 0.9rem;">Enter your email to get a reset code.</p>

        <form method="POST">
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">
            <button type="submit" class="btn-reset">Get Reset Code</button>
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