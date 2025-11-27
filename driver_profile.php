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
   Handle profile update (driver + vehicle)
----------------------------------------- */
if (isset($_POST['save_profile'])) {

    // Driver fields
    $full_name         = trim($_POST['full_name']);
    $identification_id = trim($_POST['identification_id']);

    // Vehicle fields
    $vehicle_model  = trim($_POST['vehicle_model']);
    $plate_number   = trim($_POST['plate_number']);
    $vehicle_type   = trim($_POST['vehicle_type']);
    $vehicle_color  = trim($_POST['vehicle_color']);
    $seat_count_raw = trim($_POST['seat_count']);

    $seat_count = $seat_count_raw === "" ? null : (int)$seat_count_raw;

    if (
        $full_name === "" ||
        $identification_id === "" ||
        $vehicle_model === "" ||
        $plate_number === ""
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all required profile and vehicle fields.";
        $_SESSION['swal_type']  = "warning";
    } else {

        // 1) Update driver basic info
        $stmt = $conn->prepare("
            UPDATE drivers
            SET full_name = ?, identification_id = ?
            WHERE driver_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param(
                "ssi",
                $full_name,
                $identification_id,
                $driver_id
            );

            $ok_driver = $stmt->execute();
            $stmt->close();
        } else {
            $ok_driver = false;
        }

        // 2) Insert or update vehicle info (1 vehicle per driver)
        $ok_vehicle = false;

        if ($ok_driver) {
            // Check if this driver already has a vehicle
            $check = $conn->prepare("
                SELECT vehicle_id 
                FROM vehicles 
                WHERE driver_id = ? 
                LIMIT 1
            ");
            if ($check) {
                $check->bind_param("i", $driver_id);
                $check->execute();
                $result = $check->get_result();

                if ($result && $result->num_rows === 1) {
                    // Update existing vehicle
                    $row        = $result->fetch_assoc();
                    $vehicle_id = $row['vehicle_id'];

                    $update = $conn->prepare("
                        UPDATE vehicles
                        SET vehicle_model = ?, 
                            plate_number  = ?, 
                            vehicle_type  = ?, 
                            vehicle_color = ?, 
                            seat_count    = ?
                        WHERE vehicle_id = ?
                    ");

                    if ($update) {
                        // seat_count can be null
                        if ($seat_count === null) {
                            $seat_param = null;
                        } else {
                            $seat_param = $seat_count;
                        }

                        $update->bind_param(
                            "ssssii",
                            $vehicle_model,
                            $plate_number,
                            $vehicle_type,
                            $vehicle_color,
                            $seat_param,
                            $vehicle_id
                        );

                        $ok_vehicle = $update->execute();
                        $update->close();
                    }

                } else {
                    // No vehicle yet: insert one
                    $insert = $conn->prepare("
                        INSERT INTO vehicles
                        (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    if ($insert) {
                        if ($seat_count === null) {
                            $seat_param = null;
                        } else {
                            $seat_param = $seat_count;
                        }

                        $insert->bind_param(
                            "issssi",
                            $driver_id,
                            $vehicle_model,
                            $plate_number,
                            $vehicle_type,
                            $vehicle_color,
                            $seat_param
                        );

                        $ok_vehicle = $insert->execute();
                        $insert->close();
                    }
                }

                $check->close();
            }
        }

        if ($ok_driver && $ok_vehicle) {
            $_SESSION['swal_title'] = "Profile Updated";
            $_SESSION['swal_msg']   = "Your profile and vehicle information have been updated.";
            $_SESSION['swal_type']  = "success";
        } elseif ($ok_driver && !$ok_vehicle) {
            $_SESSION['swal_title'] = "Partial Update";
            $_SESSION['swal_msg']   = "Driver info updated, but vehicle info failed. Please try again.";
            $_SESSION['swal_type']  = "warning";
        } else {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Failed to update profile. Please try again.";
            $_SESSION['swal_type']  = "error";
        }
    }

    // Avoid resubmission on refresh
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
   Fetch latest driver + vehicle info
----------------------------------------- */
$full_name         = "";
$email             = "";
$identification_id = "";
$created_at        = "";

$vehicle_model  = "";
$plate_number   = "";
$vehicle_type   = "";
$vehicle_color  = "";
$seat_count     = null;

$stmt = $conn->prepare("
    SELECT 
        d.full_name,
        d.email,
        d.identification_id,
        d.created_at,
        v.vehicle_model,
        v.plate_number,
        v.vehicle_type,
        v.vehicle_color,
        v.seat_count
    FROM drivers d
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE d.driver_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row               = $result->fetch_assoc();
        $full_name         = $row['full_name'];
        $email             = $row['email'];
        $identification_id = $row['identification_id'];
        $created_at        = $row['created_at'];

        $vehicle_model  = $row['vehicle_model'];
        $plate_number   = $row['plate_number'];
        $vehicle_type   = $row['vehicle_type'];
        $vehicle_color  = $row['vehicle_color'];
        $seat_count     = $row['seat_count'];
    }
    $stmt->close();
}

include "header.php";
?>

<style>
.profile-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 10px;
}

.profile-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
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
    grid-template-columns: 2fr 3fr;
    gap: 18px;
}

/* Summary card */
.profile-summary-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

.profile-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.profile-summary-title {
    font-size: 15px;
    font-weight: 600;
    color: #004b82;
}

.profile-summary-tag {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 999px;
    background: #eaf7ff;
    color: #0077c2;
    font-weight: 500;
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

/* Form card */
.profile-form-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
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

/* Use flex so 3 inputs align nicely */
.password-grid {
    display: flex;
    gap: 14px;
}

.password-grid .form-group {
    flex: 1;
}

.password-grid .form-group input {
    width: 100%;
}

@media (max-width: 900px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-wrapper {
        padding: 24px 10px 30px;
    }
    .password-grid {
        flex-direction: column;
    }
}

@media (max-width: 600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-group-full {
        grid-column: 1 / 2;
    }
}
</style>

<div class="profile-wrapper">

    <div class="profile-header">
        <div class="profile-header-title">
            <h1>Driver Profile</h1>
            <p>View and update your personal details, vehicle information, and password.</p>
        </div>
    </div>

    <div class="profile-grid">
        <!-- Left: Summary -->
        <div class="profile-summary-card">
            <div class="profile-summary-header">
                <div class="profile-summary-title">Profile Summary</div>
                <span class="profile-summary-tag">Driver</span>
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
                <div class="summary-label">Identification / Matric ID</div>
                <div class="summary-value"><?php echo htmlspecialchars($identification_id); ?></div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Vehicle Model</div>
                <div class="summary-value">
                    <?php echo $vehicle_model ? htmlspecialchars($vehicle_model) : "Not set yet"; ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Plate Number</div>
                <div class="summary-value">
                    <?php echo $plate_number ? htmlspecialchars($plate_number) : "Not set yet"; ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Vehicle Type</div>
                <div class="summary-value">
                    <?php echo $vehicle_type ? htmlspecialchars($vehicle_type) : "Not set yet"; ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Vehicle Color</div>
                <div class="summary-value">
                    <?php echo $vehicle_color ? htmlspecialchars($vehicle_color) : "Not set yet"; ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-label">Seat Count</div>
                <div class="summary-value">
                    <?php echo $seat_count !== null ? htmlspecialchars($seat_count) : "Not set yet"; ?>
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

        <!-- Right: Forms -->
        <div class="profile-form-card">

            <!-- Profile & vehicle form -->
            <div class="form-section-title">Profile & Vehicle Information</div>
            <form method="post" action="">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="identification_id">Identification / Matric ID</label>
                        <input type="text" id="identification_id" name="identification_id"
                               value="<?php echo htmlspecialchars($identification_id); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (MMU)</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_model">Vehicle Model</label>
                        <input type="text" id="vehicle_model" name="vehicle_model"
                               value="<?php echo htmlspecialchars($vehicle_model); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="plate_number">Plate Number</label>
                        <input type="text" id="plate_number" name="plate_number"
                               value="<?php echo htmlspecialchars($plate_number); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type (optional)</label>
                        <input type="text" id="vehicle_type" name="vehicle_type"
                               value="<?php echo htmlspecialchars($vehicle_type); ?>">
                    </div>

                    <div class="form-group">
                        <label for="vehicle_color">Vehicle Color (optional)</label>
                        <input type="text" id="vehicle_color" name="vehicle_color"
                               value="<?php echo htmlspecialchars($vehicle_color); ?>">
                    </div>

                    <div class="form-group">
                        <label for="seat_count">Seat Count (optional)</label>
                        <input type="number" id="seat_count" name="seat_count" min="1" max="20"
                               value="<?php echo $seat_count !== null ? htmlspecialchars($seat_count) : ""; ?>">
                    </div>
                </div>

                <div style="margin-top: 14px; display:flex; gap:10px;">
                    <button type="submit" name="save_profile" class="btn-primary-pill">
                        Save Profile
                    </button>
                </div>
            </form>

            <!-- Password change form -->
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
