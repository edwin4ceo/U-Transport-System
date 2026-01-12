<?php
session_start();

include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// [SECURITY] CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ----------------------------------------
   Handle profile update
----------------------------------------- */
if (isset($_POST['save_profile'])) {

    // [SECURITY] CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['swal_title'] = "Security Error";
        $_SESSION['swal_msg']   = "Invalid request token.";
        $_SESSION['swal_type']  = "error";
        redirect("driver_profile.php");
        exit;
    }

    $full_name             = trim($_POST['full_name']);
    $identification_id     = trim($_POST['identification_id']);
    $phone_number          = trim($_POST['phone_number']);
    
    // [LOGIC] Force Uppercase for License Number
    $driver_license_number = strtoupper(trim($_POST['driver_license_number'])); 
    $driver_license_expiry = trim($_POST['driver_license_expiry']);

    // --- [Backend Validation] ---

    // 1. Check for empty fields
    if ($full_name === "" || $identification_id === "" || $phone_number === "" ||
        $driver_license_number === "" || $driver_license_expiry === "") {

        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all fields.";
        $_SESSION['swal_type']  = "warning";

    } 
    // 2. Student ID validation
    elseif (!preg_match('/^\d{10}$/', $identification_id)) {
        $_SESSION['swal_title'] = "Invalid Student ID";
        $_SESSION['swal_msg']   = "Student ID must be exactly 10 digits.";
        $_SESSION['swal_type']  = "warning";
    }
    // 3. Phone Number validation
    elseif (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone_number)) {
        $_SESSION['swal_title'] = "Invalid Phone Format";
        $_SESSION['swal_msg']   = "Phone format must be like 012-3456789.";
        $_SESSION['swal_type']  = "warning";
    }
    // 4. License Number validation
    elseif (!preg_match('/^[A-Z]\s\d{8}$/', $driver_license_number)) {
        $_SESSION['swal_title'] = "Invalid License Format";
        $_SESSION['swal_msg']   = "License number must start with 1 Letter, followed by a space, then 8 Digits (e.g., A 12345678).";
        $_SESSION['swal_type']  = "warning";
    }
    // 5. License Expiry validation
    elseif ($driver_license_expiry < date('Y-m-d')) {
        $_SESSION['swal_title'] = "License Expired";
        $_SESSION['swal_msg']   = "Your driving license is expired. Please renew it.";
        $_SESSION['swal_type']  = "warning";
    } else {
        // [DATABASE] Update Driver Information
        $stmt = $conn->prepare("
            UPDATE drivers
            SET full_name = ?, 
                identification_id = ?, 
                phone_number = ?,
                license_number = ?, 
                license_expiry = ?
            WHERE driver_id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("sssssi", $full_name, $identification_id, $phone_number, $driver_license_number, $driver_license_expiry, $driver_id);

            if ($stmt->execute()) {
                $_SESSION['swal_title'] = "Profile Updated";
                $_SESSION['swal_msg']   = "Your information has been updated successfully.";
                $_SESSION['swal_type']  = "success";
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Failed to update. ID or Phone might already exist.";
                $_SESSION['swal_type']  = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['swal_title'] = "System Error";
            $_SESSION['swal_msg']   = "Database connection failed.";
            $_SESSION['swal_type']  = "error";
        }
    }
    redirect("driver_profile.php");
    exit;
}

/* ----------------------------------------
   Handle password change
----------------------------------------- */
if (isset($_POST['change_password'])) {

    // [SECURITY] CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['swal_title'] = "Security Error";
        $_SESSION['swal_msg']   = "Invalid request token.";
        $_SESSION['swal_type']  = "error";
        redirect("driver_profile.php");
        exit;
    }

    $current_password = $_POST['current_password'] ?? "";
    $new_password     = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    if ($current_password === "" || $new_password === "" || $confirm_password === "") {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all password fields.";
        $_SESSION['swal_type']  = "warning";
    } 
    elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "New password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    } 
    elseif ($new_password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "New password and confirm password do not match.";
        $_SESSION['swal_type']  = "warning";
    }
    // [LOGIC] Check if New Password is same as Current Password
    elseif ($current_password === $new_password) {
        $_SESSION['swal_title'] = "Action Failed";
        $_SESSION['swal_msg']   = "New password cannot be the same as your current password.";
        $_SESSION['swal_type']  = "warning";
    }
    else {
        $stmt = $conn->prepare("SELECT password FROM drivers WHERE driver_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Verify old password
                if (!password_verify($current_password, $row['password'])) {
                    $_SESSION['swal_title'] = "Incorrect Password";
                    $_SESSION['swal_msg']   = "Your current password is incorrect.";
                    $_SESSION['swal_type']  = "error";
                } else {
                    // Update to new password
                    $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $update = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
                    if ($update) {
                        $update->bind_param("si", $new_hashed, $driver_id);
                        if ($update->execute()) {
                            $_SESSION['swal_title'] = "Password Updated";
                            $_SESSION['swal_msg']   = "Your password has been changed successfully.";
                            $_SESSION['swal_type']  = "success";
                        }
                        $update->close();
                    }
                }
            }
            $stmt->close();
        }
    }
    redirect("driver_profile.php");
    exit;
}

// [DATABASE] Fetch latest driver info
$full_name = $email = $identification_id = $phone_number = $driver_license_number = $driver_license_expiry = $created_at = "";
$stmt = $conn->prepare("SELECT full_name, email, identification_id, phone_number, license_number, license_expiry, created_at FROM drivers WHERE driver_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $full_name             = $row['full_name'];
        $email                 = $row['email'];
        $identification_id     = $row['identification_id'];
        $phone_number          = $row['phone_number'];
        $driver_license_number = $row['license_number'];
        $driver_license_expiry = $row['license_expiry'];
        $created_at            = $row['created_at'];
    }
    $stmt->close();
}

include "header.php";
?>

<style>
/* CSS Styles */
.profile-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 900px; margin: 0 auto; }
.profile-header-title h1 { margin: 0; font-size: 22px; font-weight: 700; color: #004b82; }
.profile-header-title p { margin: 0; font-size: 13px; color: #666; }
.profile-grid { display: grid; grid-template-columns: 1.5fr 2fr; gap: 18px; }
.profile-card, .form-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 8px 24px rgba(0,0,0,0.06); padding: 18px 18px 16px; }
.profile-card-title { font-size: 15px; font-weight: 600; color: #004b82; margin-bottom: 10px; }
.summary-row { margin-bottom: 8px; }
.summary-label { font-size: 12px; color: #888; }
.summary-value { font-size: 13px; font-weight: 500; color: #333; }
.form-section-title { font-size: 14px; font-weight: 600; color: #004b82; margin-bottom: 8px; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px 16px; }
.form-group-full { grid-column: 1 / 3; }

.form-group label { display: block; font-size: 12px; color: #444; margin-bottom: 4px; font-weight: 500; }
.form-group input { width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 13px; outline: none; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; }
.form-group input:focus { border-color: #005a9c; box-shadow: 0 0 0 2px rgba(0,90,156,0.18); }

.btn-primary-pill { border: none; padding: 9px 16px; border-radius: 999px; background: linear-gradient(135deg, #005a9c, #27ae60); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 8px 18px rgba(0,0,0,0.16); transition: 0.15s; }
.btn-primary-pill:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0,0,0,0.2); }
.btn-secondary-pill { border: 1px solid #ccc; background: #fff; color: #444; border-radius: 999px; padding: 8px 14px; font-size: 12px; font-weight: 500; cursor: pointer; }
.password-section { margin-top: 18px; border-top: 1px dashed #e0e0e0; padding-top: 14px; }
.password-grid { display: flex; flex-direction: column; gap: 10px; }

/* [NEW] Style for the Header with Icon */
.password-header {
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 8px;
}
.master-eye-icon {
    cursor: pointer;
    color: #888;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}
.master-eye-icon:hover {
    color: #005a9c;
}

@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } .profile-wrapper { padding: 24px 10px 30px; } .form-grid { grid-template-columns: 1fr; } .form-group-full { grid-column: 1 / 2; } }
</style>

<div class="profile-wrapper">
    <div class="profile-header-title" style="margin-bottom:16px;">
        <h1>Driver Profile</h1>
        <p>View and update your personal details, driving license information, and account password.</p>
    </div>

    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-card-title">Profile Summary</div>
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 80px; height: 80px; background: #eef2f7; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: #999; font-size: 24px; font-weight: bold;">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Full Name</div>
                <div class="summary-value"><?php echo htmlspecialchars($full_name); ?></div>
            </div>
             <div class="summary-row">
                <div class="summary-label">Email (MMU)</div>
                <div class="summary-value"><?php echo htmlspecialchars($email); ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Phone Number</div>
                <div class="summary-value"><?php echo $phone_number ? htmlspecialchars($phone_number) : "Not set"; ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">License Number</div>
                <div class="summary-value"><?php echo $driver_license_number ? htmlspecialchars($driver_license_number) : "Not set"; ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">License Expiry</div>
                <div class="summary-value" style="<?php echo ($driver_license_expiry < date('Y-m-d')) ? 'color:red; font-weight:bold;' : ''; ?>">
                    <?php echo $driver_license_expiry ? htmlspecialchars($driver_license_expiry) : "Not set yet"; ?>
                    <?php if($driver_license_expiry < date('Y-m-d')) echo "(Expired)"; ?>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-section-title">Personal & License Information</div>
            
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="identification_id">Identification / Student ID</label>
                        <input type="text" id="identification_id" name="identification_id" 
                               value="<?php echo htmlspecialchars($identification_id); ?>" 
                               pattern="\d{10}" 
                               title="Must be exactly 10 digits"
                               placeholder="12xxxxxxxxx" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (MMU)</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly style="background-color: #f9f9f9; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" 
                               value="<?php echo htmlspecialchars($phone_number); ?>" 
                               pattern="01[0-9]-[0-9]{7,8}" 
                               title="Format: 012-3456789"
                               placeholder="0123456789" 
                               maxlength="12"
                               oninput="formatPhoneNumber(this)"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="driver_license_number">Driving License No.</label>
                        <input type="text" id="driver_license_number" name="driver_license_number" 
                               value="<?php echo htmlspecialchars($driver_license_number); ?>" 
                               pattern="[A-Za-z]\s\d{8}" 
                               title="Format: One Letter, a Space, followed by 8 Digits (e.g. A 12345678)"
                               placeholder="A 12345678" required>
                    </div>

                    <div class="form-group">
                        <label for="driver_license_expiry">License Expiry Date</label>
                        <input type="date" id="driver_license_expiry" name="driver_license_expiry" value="<?php echo htmlspecialchars($driver_license_expiry); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="submit" name="save_profile" class="btn-primary-pill">Save Profile</button>
                </div>
            </form>

            <div class="password-section">
                <div class="password-header">
                    <div class="form-section-title" style="margin:0;">Change Password</div>
                    
                    <span class="master-eye-icon" onclick="toggleAllPasswords(this)" title="Show/Hide All Passwords">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </div>
                
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="password-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="pass-input" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password (min 6 chars)</label>
                            <input type="password" id="new_password" name="new_password" class="pass-input" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="pass-input" required minlength="6">
                        </div>
                    </div>

                    <div style="margin-top: 14px;">
                        <button type="submit" name="change_password" class="btn-secondary-pill">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Auto-format Phone Number
 */
function formatPhoneNumber(input) {
    let num = input.value.replace(/\D/g, '');
    if (num.length > 3) {
        num = num.substring(0, 3) + '-' + num.substring(3, 11);
    }
    input.value = num;
}

/**
 * [NEW] Toggle ALL Passwords using Icon
 */
function toggleAllPasswords(iconSpan) {
    // Select all inputs with class 'pass-input'
    const inputs = document.querySelectorAll('.pass-input');
    
    // SVG strings
    const eyeOff = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    const eyeOn = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;

    // Determine state based on the first input
    let isPassword = inputs[0].type === "password";

    inputs.forEach(input => {
        if (isPassword) {
            input.type = "text";
        } else {
            input.type = "password";
        }
    });

    // Update Icon
    if (isPassword) {
        iconSpan.innerHTML = eyeOn; // Show Eye Open
    } else {
        iconSpan.innerHTML = eyeOff; // Show Eye Closed
    }
}
</script>

<?php include "footer.php"; ?>