<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: admin_login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$alert_script = "";

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. PHP Validation for Phone
    if (!ctype_digit($phone) || strlen($phone) < 10 || strlen($phone) > 12) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be 10-12 digits and contain numbers only.' });";
    } 
    // 2. PHP Validation for Password Match
    elseif (!empty($new_pass) && ($new_pass !== $confirm_pass)) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Error', text: 'New password and confirm password do not match.' });";
    } 
    else {
        // Update Logic
        if (!empty($new_pass)) {
            // NOTE: Using plain text based on your current DB. Change to password_hash() for production.
            $sql = "UPDATE users SET full_name='$full_name', phone_number='$phone', password_hash='$new_pass' WHERE user_id='$user_id'";
        } else {
            $sql = "UPDATE users SET full_name='$full_name', phone_number='$phone' WHERE user_id='$user_id'";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['full_name'] = $full_name;
            $alert_script = "Swal.fire({ icon: 'success', title: 'Updated!', text: 'Profile updated successfully.', confirmButtonColor: '#2c3e50' }).then(() => { window.location='admin_profile.php'; });";
        } else {
            $error = mysqli_error($conn);
            $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: '$error' });";
        }
    }
}

// Fetch Data
$query = "SELECT * FROM users WHERE user_id='$user_id'";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-box { background: white; padding: 30px; max-width: 500px; margin: 20px auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn-update { background-color: #2c3e50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; margin-top:10px;}
        .btn-update:hover { background-color: #1a252f; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width:90%; margin:0 auto;">
            <h1 style="margin:0;"><i class="fa-solid fa-user-gear"></i> Edit Profile</h1>
            <a href="admin_dashboard.php" style="color: white; text-decoration: none;">Back to Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-box">
                <form method="POST" onsubmit="return validateFrontend()">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email (Cannot Change)</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled style="background: #eee;">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" required>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                    <div class="form-group">
                        <label>New Password (Leave blank to keep current)</label>
                        <div style="position:relative;">
                            <input type="password" name="new_password" id="new_password" placeholder="********">
                            <i class="fa-solid fa-eye" id="toggleNew" style="position: absolute; right: 10px; top: 12px; cursor: pointer; color: #aaa;" onclick="togglePass('new_password', 'toggleNew')"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div style="position:relative;">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="********">
                            <i class="fa-solid fa-eye" id="toggleConfirm" style="position: absolute; right: 10px; top: 12px; cursor: pointer; color: #aaa;" onclick="togglePass('confirm_password', 'toggleConfirm')"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-update">Update Profile</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Toggle Password Visibility
        function togglePass(inputId, iconId) {
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

        // Frontend Validation
        function validateFrontend() {
            var phone = document.getElementById("phone").value;
            var newPass = document.getElementById("new_password").value;
            var confirmPass = document.getElementById("confirm_password").value;
            var phonePattern = /^[0-9]+$/; 

            // Phone Validation
            if (!phone.match(phonePattern)) {
                Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must contain only digits (0-9).' });
                return false;
            }
            if (phone.length < 10 || phone.length > 12) {
                Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be between 10 and 12 digits.' });
                return false;
            }

            // Password Validation
            if (newPass !== "") {
                if (newPass !== confirmPass) {
                    Swal.fire({ icon: 'error', title: 'Password Mismatch', text: 'The New Password and Confirm Password do not match.' });
                    return false;
                }
            }
            return true;
        }

        // Output PHP Alert if exists
        <?php if(!empty($alert_script)) echo $alert_script; ?>
    </script>
</body>
</html>