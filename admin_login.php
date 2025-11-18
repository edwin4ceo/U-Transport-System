<?php
session_start();
require_once 'db_connect.php'; // Ensure you have this file from our previous chat

$error_msg = '';

// Handle Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];

    // 1. Check if user exists and is an ADMIN
    $sql = "SELECT * FROM Users WHERE email = '$email' AND role = 'admin'";
    $result = mysqli_query($connection, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // 2. Verify Password (assuming you use password_hash. If plain text, use: if ($password == $user['password_hash']))
        // For this specific FYP test with the SQL above, we will assume verify is true if you use the hash provided, 
        // OR you can temporarily use plain text comparison for testing:
        // if ($password === $user['password_hash']) { ... }
        
        if (password_verify($password, $user['password_hash'])) {
            // Login Success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'admin';
            
            // Redirect to Admin Dashboard
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_msg = "Invalid password.";
        }
    } else {
        $error_msg = "Access Denied. No admin account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | U-Transport</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* Specific styles for Admin Login to make it look distinct/professional */
        body { background-color: #2c3e50; color: #333; }
        .login-container {
            width: 100%; max-width: 400px; margin: 80px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .login-header { text-align: center; margin-bottom: 20px; }
        .login-header h2 { color: #2c3e50; }
        .login-header i { font-size: 40px; color: #e74c3c; }
        .btn-admin { background-color: #c0392b; width: 100%; }
        .btn-admin:hover { background-color: #a93226; }
        .error { color: red; text-align: center; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-user-shield"></i>
            <h2>Admin Portal</h2>
            <p>U-Transport System</p>
        </div>

        <?php if($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <label for="email">Admin Email</label>
            <input type="email" name="email" required placeholder="admin@mmu.edu.my">

            <label for="password">Password</label>
            <input type="password" name="password" required placeholder="Enter password">

            <button type="submit" class="btn-admin">Login to Dashboard</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px; font-size: 0.8rem;">
            <a href="index.php">Back to Main Site</a>
        </div>
    </div>

</body>
</html>