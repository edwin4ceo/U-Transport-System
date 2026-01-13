<?php
session_start(); 
require_once 'db_connect.php';

// --- STRICT SECURITY CHECK ---
// ONLY 'admin' can access. 'staff' must be blocked.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If user is staff, this line sends them away
    header("Location: admin_dashboard.php"); // Redirect to dashboard instead of login loop
    exit();
}

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
        // 2. Check if email/username exists
        $check = mysqli_query($conn, "SELECT * FROM admins WHERE email = '$email' OR username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Duplicate Found', text: 'Username or Email already exists.' });";
        } else {
            // 3. Register New Staff
            // Hashing password for security (optional but recommended)
            // For now, keeping it consistent with your admin logic (plain or hashed)
            // Ideally use: $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admins (full_name, username, email, phone_number, password, role) 
                    VALUES ('$full_name', '$username', '$email', '$phone', '$password', 'staff')"; // Role is 'staff'

            if (mysqli_query($conn, $sql)) {
                $alert_script = "Swal.fire({ icon: 'success', title: 'Success', text: 'New Staff account created!' }).then(() => { window.location.href = 'admin_dashboard.php'; });";
            } else {
                $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: '" . mysqli_error($conn) . "' });";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Staff | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c3e50; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .reg-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); width: 100%; max-width: 500px; }
        .reg-header { text-align: center; margin-bottom: 25px; }
        .reg-header h2 { margin: 10px 0 5px; color: #2c3e50; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; color: #555; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-reg { width: 100%; padding: 12px; background-color: #27ae60; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-reg:hover { background-color: #219150; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; }
        .back-link:hover { color: #2c3e50; }
    </style>
</head>
<body>

    <div class="reg-container">
        <div class="reg-header">
            <i class="fa-solid fa-user-shield fa-3x" style="color: #27ae60;"></i>
            <h2>Register New Staff</h2>
            <p style="color: #7f8c8d;">Create an account for your team member.</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. Ali Bin Abu">
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="e.g. ali_staff">
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

            <button type="submit" class="btn-reg">Create Account</button>
        </form>

        <a href="admin_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
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