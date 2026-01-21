<?php
session_start();
require_once 'db_connect.php';

// INCLUDE HEADER (Make sure this file exists and handles the menu)
require_once 'admin_header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$alert_script = "";
$show_edit_mode = false; 

// 2. HANDLE UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validation
    if (!ctype_digit($phone) || strlen($phone) < 10 || strlen($phone) > 12) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Phone', text: 'Phone number must be 10-12 digits.' });";
        $show_edit_mode = true; 
    } 
    elseif (!empty($new_pass) && ($new_pass !== $confirm_pass)) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Mismatch', text: 'New passwords do not match.' });";
        $show_edit_mode = true;
    } 
    else {
        $pass_query = "";
        if (!empty($new_pass)) {
            // Ideally use password_hash here if your system supports it
            $pass_query = ", password='$new_pass'"; 
        }

        $sql = "UPDATE admins SET username='$username', phone_number='$phone' $pass_query WHERE id='$user_id'";

        if (mysqli_query($conn, $sql)) {
            $alert_script = "Swal.fire({ icon: 'success', title: 'Profile Updated', text: 'Your details have been saved.', showConfirmButton: false, timer: 1500 });";
            $show_edit_mode = false; 
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Error', text: '" . mysqli_error($conn) . "' });";
        }
    }
}

// 3. FETCH DETAILS
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$role_label = ucfirst($user['role']); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header { margin-bottom: 20px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 10px; }

        /* Card Style */
        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        /* Profile Header Section */
        .profile-header-bg {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            height: 100px;
        }
        .profile-avatar-section {
            text-align: center;
            margin-top: -50px;
            margin-bottom: 20px;
        }
        .avatar-circle {
            width: 100px; height: 100px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 2.5rem; color: #3498db;
            border: 4px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .user-name-display { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 10px 0 5px; }
        .user-role-badge { 
            background: #e0f2fe; color: #0284c7; 
            padding: 4px 12px; border-radius: 20px; 
            font-size: 0.85rem; font-weight: 600; 
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* Content Area */
        .card-body { padding: 0 40px 40px; }

        /* View Mode Grid */
        .view-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
        }
        .info-label { font-size: 0.85rem; color: #6b7280; font-weight: 600; display: block; margin-bottom: 5px; }
        .info-value { font-size: 1rem; color: #111827; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .info-value i { color: #9ca3af; width: 20px; }

        /* Edit Form Styles */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem; }
        .form-control {
            width: 100%; padding: 10px 12px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 0.95rem; outline: none; transition: 0.2s;
            background: white; box-sizing: border-box;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .form-control:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }

        /* Buttons */
        .btn-main {
            background: #2c3e50; color: white; border: none;
            padding: 12px 25px; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-main:hover { background: #1a252f; transform: translateY(-1px); }
        
        .btn-save { background: #27ae60; width: 100%; justify-content: center; }
        .btn-save:hover { background: #219150; }
        
        .btn-cancel { background: white; color: #6b7280; border: 1px solid #d1d5db; width: 100%; justify-content: center; }
        .btn-cancel:hover { background: #f9fafb; color: #374151; }

        .password-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .section-title { font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 15px; }

        /* Responsive */
        @media (max-width: 640px) {
            .view-grid, .form-grid { grid-template-columns: 1fr; }
            .card-body { padding: 0 20px 30px; }
        }
    </style>
</head>
<body>

    <main class="dashboard-container">
        <div class="profile-container">
            
            <div class="page-header">
                <h2 class="page-title"><i class="fa-solid fa-user-circle"></i> My Profile</h2>
            </div>

            <div class="profile-card">
                <div class="profile-header-bg"></div>
                <div class="profile-avatar-section">
                    <div class="avatar-circle">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="user-name-display"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <span class="user-role-badge"><?php echo $role_label; ?></span>
                </div>

                <div class="card-body">
                    
                    <div id="view-mode" style="display: <?php echo $show_edit_mode ? 'none' : 'block'; ?>;">
                        <div class="view-grid">
                            <div class="info-box">
                                <span class="info-label">Full Name</span>
                                <div class="info-value"><i class="fa-regular fa-id-card"></i> <?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Username</span>
                                <div class="info-value"><i class="fa-solid fa-at"></i> <?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Email Address</span>
                                <div class="info-value"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Phone Number</span>
                                <div class="info-value"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['phone_number']); ?></div>
                            </div>
                        </div>

                        <div style="margin-top: 30px; text-align: center;">
                            <button class="btn-main" onclick="toggleEditMode(true)">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                            </button>
                        </div>
                    </div>

                    <div id="edit-mode" style="display: <?php echo $show_edit_mode ? 'block' : 'none'; ?>;">
                        <form method="POST" onsubmit="return validateFrontend()">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                                </div>
                            </div>

                            <div class="password-section">
                                <div class="section-title"><i class="fa-solid fa-lock"></i> Change Password</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Retype new password">
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid" style="margin-top: 20px;">
                                <button type="button" class="btn-main btn-cancel" onclick="toggleEditMode(false)">Cancel</button>
                                <button type="submit" class="btn-main btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleEditMode(showEdit) {
            document.getElementById('view-mode').style.display = showEdit ? 'none' : 'block';
            document.getElementById('edit-mode').style.display = showEdit ? 'block' : 'none';
        }

        function validateFrontend() {
            var phone = document.getElementById("phone").value;
            var newPass = document.getElementById("new_password").value;
            var confirmPass = document.getElementById("confirm_password").value;
            
            // Phone Validation
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

        <?php if(!empty($alert_script)) echo $alert_script; ?>
    </script>

</body>
</html>