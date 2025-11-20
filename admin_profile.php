<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: admin_login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($connection, $_POST['full_name']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $new_pass = $_POST['password'];

    $sql = "UPDATE users SET full_name='$full_name', phone_number='$phone' WHERE user_id='$user_id'";
    
    if (!empty($new_pass)) {
        // In production, use password_hash($new_pass, PASSWORD_DEFAULT)
        $sql = "UPDATE users SET full_name='$full_name', phone_number='$phone', password_hash='$new_pass' WHERE user_id='$user_id'";
    }

    if (mysqli_query($connection, $sql)) {
        $_SESSION['full_name'] = $full_name; // Update session name
        $msg = "<div class='alert alert-success'>Profile updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-error'>Error updating profile.</div>";
    }
}

// Fetch current data
$query = "SELECT * FROM users WHERE user_id='$user_id'";
$result = mysqli_query($connection, $query);
$admin = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .form-box { background: white; padding: 30px; max-width: 500px; margin: 20px auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .alert-success { color: green; background: #d4edda; padding: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <h1><i class="fa-solid fa-user-gear"></i> Edit Profile</h1>
            <a href="admin_dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-box">
                <?php echo $msg; ?>
                <form method="POST">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo $admin['full_name']; ?>" required>
                    
                    <label>Email (Cannot Change)</label>
                    <input type="text" value="<?php echo $admin['email']; ?>" disabled style="background: #eee;">

                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo $admin['phone_number']; ?>" required>

                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" name="password" placeholder="********">

                    <button type="submit" style="background-color: #2c3e50; margin-top: 10px;">Update Profile</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>