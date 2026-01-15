<?php
session_start();
require_once 'db_connect.php';

// INCLUDE HEADER (Make sure this file exists and handles the menu)
require_once 'admin_header.php';

// 1. SECURITY CHECK: Allow both Admin and Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$alert_script = "";
$show_edit_mode = false; // Default: Show View Mode

// 2. HANDLE UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get inputs (We update Username, NOT Full Name)
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validation
    if (!ctype_digit($phone) || strlen($phone) < 10 || strlen($phone) > 12) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be 10-12 digits and contain numbers only.' });";
        $show_edit_mode = true; 
    } 
    elseif (!empty($new_pass) && ($new_pass !== $confirm_pass)) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Mismatch', text: 'New passwords do not match.' });";
        $show_edit_mode = true;
    } 
    else {
        // Prepare Password Update (Only if filled)
        $pass_query = "";
        if (!empty($new_pass)) {
            // NOTE: If you use hashing, use password_hash($new_pass, PASSWORD_DEFAULT)
            $pass_query = ", password='$new_pass'"; 
        }

        // UPDATE QUERY: Updates Username and Phone (Full Name is ignored)
        $sql = "UPDATE admins SET username='$username', phone_number='$phone' $pass_query WHERE id='$user_id'";

        if (mysqli_query($conn, $sql)) {
            $alert_script = "Swal.fire({ icon: 'success', title: 'Profile Updated', text: 'Your details have been saved successfully.', showConfirmButton: false, timer: 1500 });";
            $show_edit_mode = false; // Switch back to view mode
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Error', text: '" . mysqli_error($conn) . "' });";
        }
    }
}

// 3. FETCH CURRENT DETAILS
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Determine Role Label (Capitalize first letter: 'Admin' or 'Staff')
$role_label = ucfirst($user['role']); 
?>

<main class="dashboard-container">
    <div class="container">
        
        <h2 style="color: #2c3e50; margin-bottom: 20px;">
            <i class="fa-solid fa-id-card"></i> My <?php echo $role_label; ?> Profile
        </h2>

        <div class="profile-card">
            
            <div id="view-mode" style="display: <?php echo $show_edit_mode ? 'none' : 'block'; ?>;">
                <div class="profile-info-row">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                <div class="profile-info-row">
                    <label>Username:</label>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="profile-info-row">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="profile-info-row">
                    <label>Phone:</label>
                    <span><?php echo htmlspecialchars($user['phone_number']); ?></span>
                </div>
                <div class="profile-info-row">
                    <label>Role:</label>
                    <span class="role-badge"><?php echo $role_label; ?></span>
                </div>

                <div style="margin-top: 25px; text-align: center;">
                    <button class="btn-edit" onclick="toggleEditMode(true)">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                    </button>
                </div>
            </div>

            <div id="edit-mode" style="display: <?php echo $show_edit_mode ? 'block' : 'none'; ?>;">
                <form method="POST" onsubmit="return validateFrontend()">
                    
                    <div class="form-group">
                        <label>Full Name (Cannot be changed)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled style="background: #f0f0f0; color: #777; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address (Cannot be changed)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f0f0f0; color: #777; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Change Password (Leave blank to keep current)</p>

                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" placeholder="********">
                            <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('new_password', this)"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="********">
                            <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn-cancel" onclick="toggleEditMode(false)">Cancel</button>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</main>

<style>
    /* Profile Styles */
    .profile-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
    .profile-info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
    .profile-info-row label { font-weight: bold; color: #555; }
    .profile-info-row span { color: #333; font-weight: 500; }
    .role-badge { background: #e3f2fd; color: #005A9C; padding: 4px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; color: #555; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-group input:focus { border-color: #3498db; outline: none; }

    .btn-edit { background-color: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; transition: 0.3s; }
    .btn-edit:hover { background-color: #2980b9; }

    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .btn-save { flex: 1; background-color: #27ae60; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-save:hover { background-color: #219150; }
    .btn-cancel { flex: 1; background-color: #95a5a6; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-cancel:hover { background-color: #7f8c8d; }

    .password-wrapper { position: relative; }
    .toggle-password { position: absolute; right: 15px; top: 38%; cursor: pointer; color: #999; }
</style>

<script>
    function toggleEditMode(showEdit) {
        document.getElementById('view-mode').style.display = showEdit ? 'none' : 'block';
        document.getElementById('edit-mode').style.display = showEdit ? 'block' : 'none';
    }

    function togglePassword(fieldId, icon) {
        const input = document.getElementById(fieldId);
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

    function validateFrontend() {
        var phone = document.getElementById("phone").value;
        var newPass = document.getElementById("new_password").value;
        var confirmPass = document.getElementById("confirm_password").value;
        
        // Phone Validation (Digits only, length 10-12)
        var phonePattern = /^[0-9]+$/; 
        if (!phone.match(phonePattern) || phone.length < 10 || phone.length > 12) {
            Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be 10-12 digits (numbers only).' });
            return false;
        }

        // Password Validation
        if (newPass !== "") {
            if (newPass !== confirmPass) {
                Swal.fire({ icon: 'error', title: 'Password Mismatch', text: 'New passwords do not match.' });
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