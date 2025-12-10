<?php
session_start();
require_once 'db_connect.php'; // Assumed to be your database connection file

$msg = '';
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $msg = "Please enter your email address.";
        $error = true;
    } else {
        // --- SECURITY: USE PREPARED STATEMENT to check if email exists ---
        $stmt = $conn->prepare("SELECT user_id, full_name, role FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // NOTE: In a REAL SYSTEM, you would implement the following:
            // 1. Generate a unique, cryptographically secure token.
            // 2. Store the token and its expiration time in a dedicated password_resets table.
            // 3. Send an email to $email containing a link to a reset page (e.g., reset_password.php?token=...)

            // --- PLACEHOLDER EMAIL SENDING LOGIC HERE ---
            // For the purpose of this file structure, we show the success message
            // immediately to simulate that the process has been initiated.

            // The user will receive an email with instructions
            $msg = "A password reset link has been sent to your email address (**$email**). Please check your inbox and spam folder.";
            $error = false;

        } else {
            // BEST PRACTICE: To prevent user enumeration attacks (telling an attacker which emails exist),
            // you should display the same success message even if the email doesn't exist.
            // However, for this non-production system, we'll provide a clearer message.
            $msg = "No account found with that email address.";
            $error = true;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | U-Transport</title>
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
        .btn-admin { background-color: #e67e22; width: 100%; }
        .btn-admin:hover { background-color: #d35400; }
        .alert { color: white; padding: 10px; text-align: center; font-size: 0.9rem; margin-bottom: 15px; border-radius: 4px;}
        .alert-error { background-color: #c0392b; }
        .alert-success { background-color: #27ae60; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-key fa-3x" style="color: #e67e22;"></i>
            <h2>Forgot Password</h2>
            <p>Enter your email to reset your password.</p>
        </div>

        <?php if($msg): ?>
            <div class="alert <?php echo $error ? 'alert-error' : 'alert-success'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <label for="email">Registered Email</label>
            <input type="email" name="email" required placeholder="your.email@example.com">

            <button type="submit" class="btn-admin">Send Reset Link</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px; display: flex; justify-content: space-between; font-size: 0.85rem;">
            <a href="admin_login.php" style="color: #3498db;">Back to Login</a>
            <a href="index.php" style="color: #7f8c8d;">Back to Main Site</a>
        </div>
    </div>

</body>
</html>