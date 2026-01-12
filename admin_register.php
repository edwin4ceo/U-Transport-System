<?php
require_once 'db_connect.php';

$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation
    if (!preg_match('/@mmu\.edu\.my$/i', $email)) {
        $alert_script = "Swal.fire({ icon: 'warning', title: 'Restricted Domain', text: 'Registration is restricted to @mmu.edu.my accounts only.', confirmButtonColor: '#f39c12' });";
    }
    elseif ($password !== $confirm_password) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Error', text: 'Passwords do not match.' });";
    }
    else {
        // 2. Check if email exists
        $check = mysqli_query($conn, "SELECT * FROM admins WHERE email = '$email' OR username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Account Exists', text: 'Email or Username already taken.' });";
        } else {
            // 3. Insert into Database
            // Note: Storing plain text password as per your current system
            $sql = "INSERT INTO admins (username, email, full_name, phone_number, password, role) 
                    VALUES ('$username', '$email', '$full_name', '$phone', '$password', 'admin')";
            
            if (mysqli_query($conn, $sql)) {
                $alert_script = "Swal.fire({ 
                    icon: 'success', 
                    title: 'Registration Successful', 
                    text: 'You can now login as staff.',
                    confirmButtonText: 'Go to Login'
                }).then((result) => {
                    if (result.isConfirmed) { window.location = 'admin_login.php'; }
                });";
            } else {
                $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: '".mysqli_error($conn)."' });";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff | FMD</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c3e50; font-family: sans-serif; }
        .reg-container {
            width: 100%; max-width: 500px; margin: 40px auto;
            background: #fff; padding: 30px; border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color:#333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-reg { background-color: #27ae60; color: white; width: 100%; padding: 12px; border:none; border-radius:4px; cursor:pointer; font-size:16px; margin-top:10px;}
        .btn-reg:hover { background-color: #219150; }
        .login-link { text-align: center; margin-top: 15px; font-size: 0.9rem; }
        .login-link a { color: #2c3e50; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="reg-container">
        <h2 style="text-align:center; color:#2c3e50; margin-top:0;">Register New Staff</h2>
        <p style="text-align:center; color:#7f8c8d; margin-bottom:20px;">Create an admin account for U-Transport System</p>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. Ahmad Ali">
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="e.g. ahmad_admin">
            </div>

            <div class="form-group">
                <label>Staff Email (@mmu.edu.my)</label>
                <input type="email" name="email" required placeholder="staff@mmu.edu.my">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" required placeholder="e.g. 0123456789">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="********">
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="********">
            </div>

            <button type="submit" class="btn-reg">Register Staff</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="admin_login.php">Login here</a>
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