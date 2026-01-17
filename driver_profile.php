<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Auth Check
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];
$success_msg = "";
$error_msg = "";

// 2. Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- CASE A: Update Personal Info ---
    if (isset($_POST['btn_update_profile'])) {
        // NOTE: phone will come from hidden input (#phone_full) as FULL format: +60xxxxxxxxx
        $phone  = trim($_POST['phone'] ?? '');
        $bio    = $_POST['bio'] ?? '';
        
        // [SMART FEATURE] Capture gender input
        // If locked (readonly), it sends the existing value.
        // If dropdown, it sends the selected value.
        $gender = $_POST['gender'] ?? ''; 

        // We ONLY get Expiry Date now (License Number is read-only)
        $license_exp = $_POST['license_expiry'] ?? '';

        // Validation: Malaysia Mobile (+60) => +601 + (8 or 9 digits)
        if (!preg_match("/^\+601[0-9]{8,9}$/", $phone)) {
            $error_msg = "Invalid phone format! Must start with '+60' followed by Malaysia mobile digits.";
        } else {
            // Upload Logic
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            // Profile Image
            if (!empty($_FILES['profile_image']['name'])) {
                $fileName = time() . "_p_" . basename($_FILES['profile_image']['name']);
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_dir . $fileName)) {
                    $stmt = $conn->prepare("UPDATE drivers SET profile_image = ? WHERE driver_id = ?");
                    $stmt->bind_param("si", $fileName, $driver_id);
                    $stmt->execute();
                }
            }

            // [UPDATE SQL] Includes 'gender' and uses 'phone_number' to match registration
            $stmt = $conn->prepare("UPDATE drivers SET phone_number = ?, gender = ?, bio = ?, license_expiry = ? WHERE driver_id = ?");
            $stmt->bind_param("ssssi", $phone, $gender, $bio, $license_exp, $driver_id);

            if ($stmt->execute()) {
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Error updating profile.";
            }
        }
    }

    // --- CASE B: Change Password ---
    if (isset($_POST['btn_change_pass'])) {
        $current_pass = $_POST['current_pass'] ?? '';
        $new_pass     = $_POST['new_pass'] ?? '';
        $confirm_pass = $_POST['confirm_pass'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM drivers WHERE driver_id = ?");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $db_pass = $res['password'] ?? '';

        if (!password_verify($current_pass, $db_pass)) {
            $error_msg = "Current password is incorrect.";
        } elseif ($new_pass !== $confirm_pass) {
            $error_msg = "New passwords do not match.";
        } elseif (strlen($new_pass) < 6) {
            $error_msg = "Password must be at least 6 characters.";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE driver_id = ?");
            $stmt->bind_param("si", $new_hash, $driver_id);
            if ($stmt->execute()) {
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Error updating password.";
            }
        }
    }
}

// 3. Fetch Data
$stmt = $conn->prepare("
    SELECT 
        d.*, 
        v.vehicle_model, 
        v.plate_number,
        v.vehicle_color
    FROM drivers d
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE d.driver_id = ?
");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 4. Completeness Calculation
$total_fields = 9; 
$filled = 0;
if (!empty($user['full_name'])) $filled++;
if (!empty($user['email'])) $filled++;
$phone_val = $user['phone_number'] ?? $user['phone'] ?? '';
if (!empty($phone_val)) $filled++;
if (!empty($user['gender'])) $filled++; // Gender count
if (!empty($user['bio'])) $filled++;
if (!empty($user['vehicle_model'])) $filled++;
if (!empty($user['profile_image'])) $filled++;
if (!empty($user['license_number'])) $filled++;
if (!empty($user['license_expiry'])) $filled++;

$percentage = round(($filled / $total_fields) * 100);
$bar_color = ($percentage == 100) ? '#48bb78' : '#ecc94b';

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.profile-wrapper { 
    max-width: 1200px; width: 95%; margin: 0 auto 40px; 
    padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.completeness-card {
    background: white; border-radius: 12px; padding: 20px 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px;
    display: flex; align-items: center; justify-content: space-between; border: 1px solid #eef2f6;
}
.progress-bar-bg { width: 200px; height: 10px; background: #edf2f7; border-radius: 5px; overflow: hidden; }
.progress-fill { height: 100%; transition: width 0.5s ease; }
.form-card { 
    background: white; border-radius: 16px; padding: 40px; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid #eef2f6;
}
.card-title {
    font-size: 20px; font-weight: 700; color: #2d3748; margin-bottom: 25px; 
    padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;
}
.avatar-section { display: flex; flex-direction: column; align-items: center; margin-bottom: 35px; }
.avatar-wrapper { 
    width: 120px; height: 120px; border-radius: 50%; 
    background: #e2e8f0; overflow: hidden; position: relative; border: 4px solid white; 
    box-shadow: 0 8px 16px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;
}
.avatar-wrapper:hover { transform: scale(1.05); }
.avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
.avatar-overlay {
    position: absolute; bottom: 0; left: 0; width: 100%; 
    background: rgba(0,0,0,0.6); color: white; font-size: 11px; 
    text-align: center; padding: 4px 0; opacity: 0; transition: opacity 0.2s;
}
.avatar-wrapper:hover .avatar-overlay { opacity: 1; }
.driver-name-display { font-size: 20px; font-weight: 700; margin-top: 15px; color: #1a202c; }
.driver-email-display { font-size: 14px; color: #718096; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; }
.form-input { 
    width: 100%; padding: 12px 16px; border: 1px solid #cbd5e0; border-radius: 10px; 
    font-size: 15px; outline: none; transition: border 0.2s; background: #fff;
}
.form-input:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }
.form-input[readonly] { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
textarea.form-input { resize: vertical; min-height: 100px; font-family: inherit; }
.btn-save {
    background: #004b82; color: white; border: none; padding: 14px 30px; 
    border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer;
    display: block; width: 100%; box-shadow: 0 4px 6px rgba(0, 75, 130, 0.2); transition: background 0.2s;
}
.btn-save:hover { background: #00365e; }
.btn-warn {
    background: white; color: #e53e3e; border: 1px solid #e53e3e; padding: 14px 30px; 
    border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer;
    display: block; width: 100%; transition: background 0.2s;
}
.btn-warn:hover { background: #fff5f5; }
.vehicle-readonly-box {
    background: #f8fafc; border: 1px dashed #cbd5e0; border-radius: 10px; padding: 20px; margin-bottom: 25px;
}
.vehicle-readonly-title { font-size: 13px; font-weight: 700; color: #004b82; margin-bottom: 15px; text-transform: uppercase; }
@media (max-width: 768px) {
    .grid-2 { grid-template-columns: 1fr; gap: 15px; }
    .progress-bar-bg { width: 100px; }
}
</style>

<div class="profile-wrapper">

    <?php if ($success_msg): ?>
        <script>Swal.fire('Saved', '<?php echo $success_msg; ?>', 'success');</script>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <script>Swal.fire('Error', '<?php echo $error_msg; ?>', 'error');</script>
    <?php endif; ?>

    <div class="completeness-card">
        <div>
            <h3 style="margin:0; color:#2d3748; font-size:16px;">Profile Strength</h3>
            <p style="margin:4px 0 0; color:#718096; font-size:13px;"><?php echo $percentage; ?>% Completed</p>
        </div>
        <div style="text-align:right; display:flex; align-items:center; gap:15px;">
            <div class="progress-bar-bg">
                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $bar_color; ?>;"></div>
            </div>
            <div style="font-size:18px; font-weight:800; color:<?php echo $bar_color; ?>;"><?php echo $percentage; ?>%</div>
        </div>
    </div>

    <form class="form-card" method="POST" enctype="multipart/form-data">
        <div class="card-title">Edit Profile</div>

        <div class="avatar-section">
            <label for="profile_input" class="avatar-wrapper">
                <?php
                $p_img = !empty($user['profile_image'])
                    ? "uploads/" . $user['profile_image']
                    : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
                ?>
                <img src="<?php echo $p_img; ?>" id="avatar_preview">
                <div class="avatar-overlay">CHANGE</div>
            </label>
            <input type="file" id="profile_input" name="profile_image" style="display:none;" accept="image/*" onchange="previewImage(this, 'avatar_preview')">
            <div class="driver-name-display"><?php echo htmlspecialchars($user['full_name'] ?? 'Driver'); ?></div>
            <div class="driver-email-display"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" readonly title="Contact Admin to change">
            </div>

            <div class="form-group">
                <label class="form-label">Gender</label>
                <?php if (!empty($user['gender'])): ?>
                    <input type="text" name="gender" class="form-input" 
                           value="<?php echo htmlspecialchars($user['gender']); ?>" 
                           readonly 
                           title="Contact Admin to change">
                <?php else: ?>
                    <select name="gender" class="form-input" required style="cursor:pointer;">
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Phone Number</label>

                <div style="display:flex; gap:10px;">
                    <input type="text" class="form-input"
                           value="+60"
                           readonly
                           style="max-width:90px; text-align:center;">

                    <input type="text"
                           id="phone_input"
                           class="form-input"
                           placeholder="123456789"
                           value="<?php 
                                $raw_phone = $user['phone_number'] ?? $user['phone'] ?? '';
                                echo !empty($raw_phone) ? htmlspecialchars(substr($raw_phone, 3)) : ''; 
                           ?>"
                           required
                           inputmode="numeric"
                           oninput="formatPhone()">
                </div>

                <input type="hidden" name="phone" id="phone_full">
            </div>

            <div class="form-group">
                <label class="form-label">Driving License Number</label>
                <input type="text" class="form-input"
                       value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>"
                       readonly title="Contact Admin to change">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">License Expiry Date</label>
            <input type="date" name="license_expiry" class="form-input"
                   value="<?php echo htmlspecialchars($user['license_expiry'] ?? ''); ?>"
                   required>
        </div>

        <div class="form-group">
            <label class="form-label">Bio / About Me</label>
            <textarea name="bio" class="form-input" placeholder="Introduce yourself to passengers..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>

        <div class="vehicle-readonly-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div class="vehicle-readonly-title"><i class="fa-solid fa-car"></i> Assigned Vehicle</div>
                <a href="driver_vehicle.php" style="font-size:12px; color:#3182ce; font-weight:600; text-decoration:none;">Change Vehicle <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="grid-2" style="margin-bottom:0;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Vehicle Model</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['vehicle_model'] ?? 'No Vehicle Assigned'); ?>" readonly>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Plate Number</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['plate_number'] ?? '---'); ?>" readonly>
                </div>
            </div>
        </div>

        <button type="submit" name="btn_update_profile" class="btn-save">Save Changes</button>
    </form>

    <form class="form-card" method="POST">
        <div class="card-title" style="color:#e53e3e;">Security Settings</div>

        <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_pass" class="form-input" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_pass" class="form-input" placeholder="Min 6 characters" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_pass" class="form-input" required>
            </div>
        </div>

        <button type="submit" name="btn_change_pass" class="btn-warn">Update Password</button>
    </form>

</div>

<script>
function previewImage(input, imgId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById(imgId);
            img.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function formatPhone() {
    var input  = document.getElementById('phone_input');
    var hidden = document.getElementById('phone_full');
    if (!input || !hidden) return;
    input.value = (input.value || '').replace(/\D/g, '');
    hidden.value = '+60' + input.value;
}

function validatePhoneFull() {
    var hidden = document.getElementById('phone_full');
    var val = (hidden && hidden.value) ? hidden.value.trim() : '';
    var regex = /^\+601[0-9]{8,9}$/;

    if (val.length > 0 && !regex.test(val)) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Phone Format',
            text: 'Please enter a valid Malaysia mobile number (e.g. +60123456789).',
            confirmButtonColor: '#004b82'
        });
        document.getElementById('phone_input').value = '';
        hidden.value = '';
        return false;
    }
    return true;
}

document.addEventListener("DOMContentLoaded", function() {
    formatPhone();
});

document.addEventListener("submit", function(e) {
    var submitter = e.submitter;
    if (submitter && submitter.name === "btn_update_profile") {
        formatPhone();
        if (!validatePhoneFull()) {
            e.preventDefault();
        }
    }
});
</script>

<?php include "footer.php"; ?>