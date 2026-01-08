<?php
session_start();
require_once 'db_connect.php';

$alert_script = ""; // Variable to hold our SweetAlert script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. Validate Email Format First (Supervisor Requirement)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please enter a valid email address (e.g. admin@mmu.edu.my).',
                confirmButtonColor: '#2c3e50'
            });";
    } else {
        $sql = "SELECT * FROM Users WHERE email = '$email' AND role = 'admin'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Checking password (Plain text as per your current Dev environment)
            if ($password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = 'admin';
                
                // Success Login
                header("Location: admin_dashboard.php");
                exit();
            } else {
                // Wrong Password Pop-up
                $alert_script = "
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Incorrect password. Please try again.',
                    confirmButtonColor: '#c0392b'
                });";
            }
        } else {
            // Wrong Email/User Not Found Pop-up
            $alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'No admin account found with that email.',
                confirmButtonColor: '#c0392b'
            });";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FMD Staff</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c3e50; color: #333; }
        .login-container {
            width: 100%; max-width: 400px; margin: 80px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .login-header { text-align: center; margin-bottom: 20px; }
        .login-header h2 { color: #2c3e50; }
        .btn-admin { background-color: #c0392b; width: 100%; }
        .btn-admin:hover { background-color: #a93226; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-user-shield fa-3x" style="color: #e74c3c;"></i>
            <h2>FMD Staff Portal</h2>
            <p>U-Transport System Management</p>
        </div>

        <form action="admin_login.php" method="POST">
            <label for="email">Email</label>
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">

            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="adminPass" required placeholder="Enter password">
                <i class="fa-solid fa-eye toggle-password" id="toggleAdmin" onclick="togglePassword('adminPass', 'toggleAdmin')"></i>
            </div>
            <script>
                function togglePassword(inputId, iconId) {
                    const input = document.getElementById(inputId);
                    const icon = document.getElementById(iconId);
                    if (input.type === "password") {
                        input.type = "text";
                        icon.classList.remove("fa-eye");
                        icon.classList.add("fa-eye-slash");
                    } else {
                        input.type = "password";
                        icon.classList.remove("fa-eye-slash");
                        icon.classList.add("fa-eye");
                    }
                }
            </script>

            <button type="submit" class="btn-admin">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px; display: flex; justify-content: space-between; font-size: 0.85rem;">
            <a href="forgot_password.php" style="color: #3498db;">Forgot Password?</a>
            <a href="index.php" style="color: #7f8c8d;">Back to Main Site</a>
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