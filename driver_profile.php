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

/* ----------------------------------------
   Handle profile update (name, ID, phone, license)
----------------------------------------- */
if (isset($_POST['save_profile'])) {

    $full_name             = trim($_POST['full_name']);
    $identification_id     = trim($_POST['identification_id']);
    $phone_number          = trim($_POST['phone_number']);
    $driver_license_number = trim($_POST['driver_license_number']);
    $driver_license_expiry = trim($_POST['driver_license_expiry']);  // format: YYYY-MM-DD

    // 1. Basic Validation: Check for empty fields
    if ($full_name === "" || $identification_id === "" || $phone_number === "" ||
        $driver_license_number === "" || $driver_license_expiry === "") {

        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all personal and license fields.";
        $_SESSION['swal_type']  = "warning";

    } 
    // ---------------------------------------------------------
    // [NEW] 2. Date Validation: Check if license is expired
    // ---------------------------------------------------------
    elseif ($driver_license_expiry < date('Y-m-d')) {
        
        $_SESSION['swal_title'] = "License Expired";
        $_SESSION['swal_msg']   = "Your driving license is expired. Please renew it before updating.";
        $_SESSION['swal_type']  = "warning";

    } 
    // ---------------------------------------------------------
    else {

        // 3. Update Database
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
            $stmt->bind_param(
                "sssssi",
                $full_name,
                $identification_id,
                $phone_number,
                $driver_license_number,
                $driver_license_expiry,
                $driver_id
            );

            if ($stmt->execute()) {
                $_SESSION['swal_title'] = "Profile Updated";
                $_SESSION['swal_msg']   = "Your personal and license information have been updated.";
                $_SESSION['swal_type']  = "success";
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Failed to update profile: " . $stmt->error;
                $_SESSION['swal_type']  = "error";
            }

            $stmt->close();
        } else {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (update profile).";
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

    $current_password = $_POST['current_password'] ?? "";
    $new_password     = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    if ($current_password === "" || $new_password === "" || $confirm_password === "") {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all password fields.";
        $_SESSION['swal_type']  = "warning";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['swal_title'] = "Weak Password";
        $_SESSION['swal_msg']   = "New password must be at least 6 characters.";
        $_SESSION['swal_type']  = "warning";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['swal_title'] = "Password Mismatch";
        $_SESSION['swal_msg']   = "New password and confirm password do not match.";
        $_SESSION['swal_type']  = "warning";
    } else {
        // Get current hashed password
        $stmt = $conn->prepare("SELECT password FROM drivers WHERE driver_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $row    = $result->fetch_assoc();
                $hashed = $row['password'];

                if (!password_verify($current_password, $hashed)) {
                    $_SESSION['swal_title'] = "Incorrect Password";
                    $_SESSION['swal_msg']   = "Your current password is incorrect.";
                    $_SESSION['swal_type']  = "error";
                } else {
                    // Update with new password
                    $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);

                    $update = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
                    if ($update) {
                        $update->bind_param("si", $new_hashed, $driver_id);
                        if ($update->execute()) {
                            $_SESSION['swal_title'] = "Password Updated";
                            $_SESSION['swal_msg']   = "Your password has been changed successfully.";
                            $_SESSION['swal_type']  = "success";
                        } else {
                            $_SESSION['swal_title'] = "Error";
                            $_SESSION['swal_msg']   = "Failed to change password. Please try again.";
                            $_SESSION['swal_type']  = "error";
                        }
                        $update->close();
                    } else {
                        $_SESSION['swal_title'] = "Error";
                        $_SESSION['swal_msg']   = "Database error (update password).";
                        $_SESSION['swal_type']  = "error";
                    }
                }
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Driver not found.";
                $_SESSION['swal_type']  = "error";
            }

            $stmt->close();
        } else {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (select password).";
            $_SESSION['swal_type']  = "error";
        }
    }

    redirect("driver_profile.php");
    exit;
}

/* ----------------------------------------
   Fetch latest driver info for display
----------------------------------------- */
$full_name             = "";
$email                 = "";
$identification_id     = "";
$phone_number          = "";
$driver_license_number = "";
$driver_license_expiry = "";
$created_at            = "";

$stmt = $conn->prepare("
    SELECT full_name, email, identification_id, phone_number,
           license_number, license_expiry, created_at
    FROM drivers
    WHERE driver_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row                   = $result->fetch_assoc();
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
.profile-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 900px;
    margin: 0 auto;
}

.profile-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}
.profile-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1.5fr 2fr;
    gap: 18px;
}

.profile-card, .form-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

.profile-card-title {
    font-size: 15px;
    font-weight: 600;
    color: #004b82;
    margin-bottom: 10px;
}

.summary-row {
    margin-bottom: 8px;
}

.summary-label {
    font-size: 12px;
    color: #888;
}

.summary-value {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.form-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #004b82;
    margin-bottom: 8px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px 16px;
}

.form-group-full {
    grid-column: 1 / 3;
}

.form-group label {
    display: block;
    font-size: 12px;
    color: #444;
    margin-bottom: 4px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus {
    border-color: #005a9c;
    box-shadow: 0 0 0 2px rgba(0,90,156,0.18);
}

.btn-primary-pill {
    border: none;
    padding: 9px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, #005a9c, #27ae60);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    transition: 0.15s;
}
.btn-primary-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.2);
}

.btn-secondary-pill {
    border: 1px solid #ccc;
    background: #fff;
    color: #444;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}

/* Password form */
.password-section {
    margin-top: 18px;
    border-top: 1px dashed #e0e0e0;
    padding-top: 14px;
}
.password-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

@media (max-width: 900px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-wrapper {
        padding: 24px 10px 30px;
    }
    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-group-full {
        grid-column: 1 / 2;
    }
}
</style>

<div class="profile-wrapper">
    <div class="profile-header-title" style="margin-bottom:16px;">
        <h1>Driver Profile</h1>
        <p>View and update your personal details, driving license information, and account password.</p>
    </div>

    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-card-title">Profile Summary</div>

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
                <div class="summary-value">
                    <?php echo $phone_number ? htmlspecialchars($phone_number) : "Not set"; ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Identification / Student ID</div>
                <div class="summary-value"><?php echo htmlspecialchars($identification_id); ?></div>
            </div>

            <div class="summary-row">
                <div class="summary-label">License Number</div>
                <div class="summary-value"><?php echo htmlspecialchars($driver_license_number); ?></div>
            </div>

            <div class="summary-row">
                <div class="summary-label">License Expiry Date</div>
                <div class="summary-value">
                    <?php echo $driver_license_expiry ? htmlspecialchars($driver_license_expiry) : "Not set yet"; ?>
                </div>
            </div>

            <?php if ($created_at): ?>
            <div class="summary-row">
                <div class="summary-label">Driver Since</div>
                <div class="summary-value">
                    <?php echo htmlspecialchars(date("d M Y", strtotime($created_at))); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <div class="form-section-title">Personal & License Information</div>
            <form method="post" action="">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="identification_id">Identification / Student ID</label>
                        <input type="text" id="identification_id" name="identification_id"
                               value="<?php echo htmlspecialchars($identification_id); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (MMU)</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number"
                               value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="driver_license_number">Driving License Number</label>
                        <input type="text" id="driver_license_number" name="driver_license_number"
                               value="<?php echo htmlspecialchars($driver_license_number); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="driver_license_expiry">License Expiry Date</label>
                        <input type="date" id="driver_license_expiry" name="driver_license_expiry"
                               value="<?php echo htmlspecialchars($driver_license_expiry); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="submit" name="save_profile" class="btn-primary-pill">
                        Save Profile
                    </button>
                </div>
            </form>

            <div class="password-section">
                <div class="form-section-title">Change Password</div>
                <form method="post" action="">
                    <div class="password-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password (min 6 characters)</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                    </div>

                    <div style="margin-top: 14px;">
                        <button type="submit" name="change_password" class="btn-secondary-pill">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
include "footer.php";
?>