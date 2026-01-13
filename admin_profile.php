<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$alert_script = "";
$show_edit_mode = false; // Default: Show View Mode

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. PHP Validation for Phone
    if (!ctype_digit($phone) || strlen($phone) < 10 || strlen($phone) > 12) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be 10-12 digits and contain numbers only.' });";
        $show_edit_mode = true; // Keep edit mode open on error
    } 
    // 2. PHP Validation for Password Match
    elseif (!empty($new_pass) && ($new_pass !== $confirm_pass)) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Error', text: 'New password and confirm password do not match.' });";
        $show_edit_mode = true; // Keep edit mode open on error
    } 
    else {
        // Update Logic
        if (!empty($new_pass)) {
            $sql = "UPDATE admins SET full_name='$full_name', phone_number='$phone', password='$new_pass' WHERE id='$user_id'";
        } else {
            $sql = "UPDATE admins SET full_name='$full_name', phone_number='$phone' WHERE id='$user_id'";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['full_name'] = $full_name;
            $alert_script = "Swal.fire({ icon: 'success', title: 'Updated!', text: 'Profile updated successfully.', confirmButtonColor: '#2c3e50' });";
            $show_edit_mode = false; // Switch back to View Mode on success
        } else {
            $error = mysqli_error($conn);
            $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: '$error' });";
            $show_edit_mode = true;
        }
    }
}

// Fetch Admin Details
$query = "SELECT * FROM admins WHERE id='$user_id'";
$result = mysqli_query($conn, $query);

if($result && mysqli_num_rows($result) > 0){
    $admin = mysqli_fetch_assoc($result);
} else {
    echo "Error: Admin profile not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-card { 
            background: white; 
            padding: 40px; 
            max-width: 600px; 
            margin: 30px auto; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        
        /* View Mode Styles */
        .profile-header { text-align: center; margin-bottom: 30px; }
        .profile-avatar { 
            width: 100px; height: 100px; 
            background-color: #ecf0f1; border-radius: 50%; 
            display: inline-flex; align-items: center; justify-content: center; 
            font-size: 3rem; color: #2c3e50; margin-bottom: 15px;
        }
        .profile-name { font-size: 1.5rem; color: #2c3e50; font-weight: bold; margin: 0; }
        .profile-role { color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        .info-row { 
            display: flex; justify-content: space-between; 
            padding: 15px 0; border-bottom: 1px solid #eee; 
        }
        .info-label { font-weight: 600; color: #555; }
        .info-value { color: #333; }
        .info-row:last-child { border-bottom: none; }

        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color:#333; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        
        .btn-action { 
            padding: 12px 25px; border: none; border-radius: 6px; 
            cursor: pointer; font-size: 16px; font-weight: 500; transition: 0.3s;
        }
        .btn-primary { background-color: #2c3e50; color: white; width: 100%; display: block; }
        .btn-primary:hover { background-color: #1a252f; }
        
        .btn-secondary { background-color: #95a5a6; color: white; margin-top: 10px; width: 100%; }
        .btn-secondary:hover { background-color: #7f8c8d; }

        /* Hidden Utility */
        .hidden { display: none; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; width:90%; margin:0 auto;">
            <h1 style="margin:0;"><i class="fa-solid fa-user-circle"></i> Admin Profile</h1>
            <a href="admin_dashboard.php" style="color: white; text-decoration: none;">Back to Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="profile-card">
                
                <div id="view-mode" class="<?php echo $show_edit_mode ? 'hidden' : ''; ?>">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($admin['full_name']); ?></h2>
                        <span class="profile-role">System Administrator</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fa-solid fa-user-tag"></i> Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa-solid fa-phone"></i> Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['phone_number']); ?></span>
                    </div>

                    <div style="margin-top: 30px;">
                        <button onclick="toggleMode()" class="btn-action btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <div id="edit-mode" class="<?php echo $show_edit_mode ? '' : 'hidden'; ?>">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h2 style="color:#2c3e50; margin:0;">Edit Details</h2>
                        <p style="color:#7f8c8d; margin-top:5px;">Update your personal information</p>
                    </div>

                    <form method="POST" onsubmit="return validateFrontend()">
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email (Read Only)</label>
                            <input type="text" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled style="background: #f9f9f9; color:#777;">
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" required>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">
                        <p style="font-size: 0.9rem; color:#e67e22; margin-bottom: 15px;">
                            <i class="fa-solid fa-lock"></i> Change Password (Optional)
                        </p>

                        <div class="form-group">
                            <label>New Password</label>
                            <div style="position:relative;">
                                <input type="password" name="new_password" id="new_password" placeholder="Leave blank to keep current">
                                <i class="fa-solid fa-eye" id="toggleNew" style="position: absolute; right: 10px; top: 40px; cursor: pointer; color: #aaa;" onclick="togglePass('new_password', 'toggleNew')"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Retype new password">
                                <i class="fa-solid fa-eye" id="toggleConfirm" style="position: absolute; right: 10px; top: 40px; cursor: pointer; color: #aaa;" onclick="togglePass('confirm_password', 'toggleConfirm')"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-action btn-primary">Save Changes</button>
                        <button type="button" onclick="toggleMode()" class="btn-action btn-secondary">Cancel</button>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        // Toggle between View and Edit modes
        function toggleMode() {
            const viewMode = document.getElementById('view-mode');
            const editMode = document.getElementById('edit-mode');
            
            if (viewMode.classList.contains('hidden')) {
                viewMode.classList.remove('hidden');
                editMode.classList.add('hidden');
            } else {
                viewMode.classList.add('hidden');
                editMode.classList.remove('hidden');
            }
        }

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