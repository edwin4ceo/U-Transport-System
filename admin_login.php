<?php
session_start();
require_once 'db_connect.php';

$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM Users WHERE email = '$email' AND role = 'admin'";
    $result = mysqli_query($connection, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        // DEV MODE: Using plain text for testing as agreed previously
        if ($password === $user['password_hash']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_msg = "Invalid password.";
        }
    } else {
        $error_msg = "Access Denied. No admin account found.";
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
        .error { color: red; text-align: center; font-size: 0.9rem; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-user-shield fa-3x" style="color: #e74c3c;"></i>
            <h2>FMD Staff Portal</h2>
            <p>U-Transport System Management</p>
        </div>

        <?php if($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <label for="email">Email</label>
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">

            <label for="password">Password</label>
            <input type="password" name="password" required placeholder="Enter password">

            <button type="submit" class="btn-admin">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px; display: flex; justify-content: space-between; font-size: 0.85rem;">
            <a href="forgot_password.php" style="color: #3498db;">Forgot Password?</a>
            <a href="index.php" style="color: #7f8c8d;">Back to Main Site</a>
        </div>
    </div>

</body>
</html>