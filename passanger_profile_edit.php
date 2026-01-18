<?php
// ==========================================
// SECTION 1: SETUP & AUTHENTICATION
// ==========================================

// Start the session to manage user login state
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// Check if the user is logged in. If not, redirect to login page.
if(!isset($_SESSION['student_id'])){
    echo "<script>window.location.href='passanger_login.php';</script>";
    exit();
}

$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: FETCH USER DATA
// ==========================================

// Prepare SQL statement to fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Logic to parse phone number for display
// We want to remove the '+60' or '60' prefix so the user only sees the raw number
$phone_display = $student['phone'];
if(strpos($phone_display, '60') === 0) {
    $phone_display = substr($phone_display, 2);
} elseif(strpos($phone_display, '+60') === 0) {
    $phone_display = substr($phone_display, 3);
}

// ==========================================
// SECTION 3: HANDLE DELETE PHOTO
// ==========================================
if(isset($_POST['delete_photo'])){
    $current_img = $student['profile_image'];
    
    // Remove the physical file from the server if it exists
    if(!empty($current_img) && file_exists("uploads/" . $current_img)){
        unlink("uploads/" . $current_img);
    }
    
    // Update database to set profile_image to NULL
    $del_stmt = $conn->prepare("UPDATE students SET profile_image = NULL WHERE student_id = ?");
    $del_stmt->bind_param("s", $student_id);
    
    if($del_stmt->execute()){
        $_SESSION['swal_success'] = "Profile photo removed successfully.";
        // Refresh the current page to show changes
        header("Location: passanger_profile_edit.php");
        exit();
    }
}

// ==========================================
// SECTION 4: HANDLE PROFILE UPDATE
// ==========================================
if(isset($_POST['update_profile'])){
    // Sanitize inputs
    $name = trim($_POST['name']);
    $gender = $_POST['gender']; 
    $phone_raw = trim($_POST['phone']);
    
    // Basic PHP Validation
    if(empty($name) || empty($phone_raw)){
        $_SESSION['swal_error'] = "Name and Phone Number are required fields.";
    } else {
        // Smart Phone Formatting: Ensure it starts with '60'
        // If user typed '012...', remove '0' -> '12...' then add '60'
        if(substr($phone_raw, 0, 1) === '0') {
            $phone_raw = substr($phone_raw, 1);
        }
        
        // Prevent double prefix (e.g., 6060...)
        if(substr($phone_raw, 0, 2) !== '60') {
            $phone_final = "60" . $phone_raw;
        } else {
            $phone_final = $phone_raw;
        }

        $profile_image = $student['profile_image']; 
        
        // Handle Image File Upload
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_pic']['name'];
            $filesize = $_FILES['profile_pic']['size'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if(in_array($ext, $allowed_ext)){
                if($filesize < 5000000){ // Limit size to 5MB
                    $new_filename = "student_" . $student_id . "_" . time() . "." . $ext;
                    
                    // Upload the file to 'uploads/' directory
                    if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], "uploads/" . $new_filename)){
                        $profile_image = $new_filename;
                    }
                } else {
                    $_SESSION['swal_error'] = "File is too large. Max limit is 5MB.";
                }
            } else {
                $_SESSION['swal_error'] = "Invalid file format. Only JPG, PNG, GIF allowed.";
            }
        }

        // Proceed to update database if no errors
        if(!isset($_SESSION['swal_error'])){
            $update_stmt = $conn->prepare("UPDATE students SET name = ?, gender = ?, phone = ?, profile_image = ? WHERE student_id = ?");
            $update_stmt->bind_param("sssss", $name, $gender, $phone_final, $profile_image, $student_id);
            
            if($update_stmt->execute()){
                $_SESSION['swal_success'] = "Profile updated successfully!";
                
                // [CHANGED] Redirect back to the Main Profile Page
                header("Location: passanger_profile.php"); 
                exit();
            } else {
                $_SESSION['swal_error'] = "Database Error: Could not update profile.";
            }
        }
    }
}

// ==========================================
// SECTION 5: HANDLE PASSWORD CHANGE
// ==========================================
if(isset($_POST['change_password'])){
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $cfm_pass = $_POST['confirm_password'];

    // Verify current password first
    if(password_verify($old_pass, $student['password'])){
        // Validation checks
        if(password_verify($new_pass, $student['password'])){
            $_SESSION['swal_error'] = "New password cannot be the same as the current password.";
        } elseif($new_pass !== $cfm_pass){
            $_SESSION['swal_error'] = "New passwords do not match.";
        } elseif(strlen($new_pass) < 6){
            $_SESSION['swal_error'] = "Password must be at least 6 characters long.";
        } else {
            // Hash the new password and update DB
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?")->execute([$new_hash, $student_id]);
            $_SESSION['swal_success'] = "Password changed successfully!";
            
            // Stay on Edit page for password change (usually safer so user can see confirmation)
            header("Location: passanger_profile_edit.php");
            exit();
        }
    } else {
        $_SESSION['swal_error'] = "Current password is incorrect.";
    }
}

include "header.php"; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- Global Styles --- */
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
    
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    
    .edit-wrapper { max-width: 800px; margin: 0 auto; padding: 40px 20px; background: #f5f7fb; font-family: 'Poppins', sans-serif; animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
    
    /* --- Back Button --- */
    .btn-back-container { display: flex; justify-content: flex-start; margin-bottom: 20px; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; transition: 0.2s; font-size: 15px; }
    .btn-back:hover { color: #004b82; transform: translateX(-3px); }
    
    /* --- Page Header --- */
    .page-header { margin-bottom: 30px; text-align: center; }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .page-header p { margin: 8px 0 0; font-size: 15px; color: #64748b; }
    
    /* --- Card Container --- */
    .edit-card { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 40px; margin-bottom: 25px; text-align: left !important; }
    .card-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 10px; }
    
    /* --- Input Fields --- */
    .form-group { margin-bottom: 15px; text-align: left; } 
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; text-align: left; }
    .form-control { width: 100%; height: 52px; padding: 0 15px; font-size: 15px; border: 1.5px solid #e2e8f0; border-radius: 12px; transition: all 0.3s ease-in-out; box-sizing: border-box; background: #fff; color: #333; font-family: 'Poppins', sans-serif; }
    .form-control:focus { border-color: #004b82; outline: none; box-shadow: 0 4px 15px rgba(0,75,130,0.1); transform: translateY(-1px); }
    .form-control:disabled, .form-control[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; transform: none; }
    
    .form-row-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 600px) { .form-row-split { grid-template-columns: 1fr; gap: 15px; } }
    
    /* --- Phone Input Group --- */
    .phone-group-container { display: flex; align-items: stretch; width: 100%; height: 52px; border: 1.5px solid #e2e8f0; border-radius: 12px; background-color: #fff; overflow: hidden; transition: all 0.3s ease-in-out; }
    .phone-group-container:focus-within { border-color: #004b82; box-shadow: 0 4px 15px rgba(0,75,130,0.1); transform: translateY(-1px); }
    .phone-prefix { background: #f1f5f9; color: #475569; font-weight: 600; width: 60px; display: flex; align-items: center; justify-content: center; border-right: 1.5px solid #e2e8f0; font-size: 15px; flex-shrink: 0; }
    .phone-input-clean { flex-grow: 1; border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important; height: auto !important; margin: 0 !important; padding: 0 15px !important; line-height: normal !important; font-size: 15px; color: #333; font-family: 'Poppins', sans-serif; }
    
    /* --- Password Fields --- */
    .password-wrapper { position: relative; width: 100%; height: 52px; }
    .form-control[type="password"], .form-control[type="text"] { padding-right: 50px; }
    .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; border-radius: 50%; transition: background 0.2s; }
    .toggle-password:hover { background-color: #f1f5f9; }
    .toggle-password i { font-size: 16px; color: #94a3b8; line-height: 1; display: block; }
    .toggle-password:hover i { color: #004b82; }
    
    .forgot-link-wrapper { text-align: right; margin-top: 5px; }
    .forgot-pass-btn { background: none; border: none; padding: 0; font-size: 13px; color: #004b82; text-decoration: none; font-weight: 600; cursor: pointer; transition: 0.2s; font-family: 'Poppins', sans-serif; }
    .forgot-pass-btn:hover { text-decoration: underline; color: #003660; }
    
    /* --- Avatar Styles --- */
    .avatar-upload-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 25px; position: relative; }
    .avatar-wrapper { position: relative; width: 120px; height: 120px; }
    .avatar-preview { width: 100%; height: 100%; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 8px 25px rgba(0,75,130,0.15); object-fit: cover; background: #e0f2fe; transition: transform 0.3s; cursor: pointer; }
    .avatar-wrapper:hover .avatar-preview { transform: scale(1.02); filter: brightness(0.95); }
    .avatar-edit-badge { position: absolute; bottom: 0px; right: 0px; background: #004b82; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; border: 3px solid #fff; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 10; }
    .avatar-edit-badge:hover { background: #003660; transform: scale(1.1); }
    
    .avatar-menu { position: absolute; top: 10px; left: 100%; margin-left: 15px; width: 180px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; opacity: 0; visibility: hidden; transform: translateX(-15px); transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1); z-index: 999; overflow: hidden; text-align: left; }
    .avatar-menu.active { opacity: 1; visibility: visible; transform: translateX(0); }
    @media (max-width: 600px) { .avatar-menu { top: 100%; left: 50%; margin-left: 0; transform: translateX(-50%) translateY(10px); } .avatar-menu.active { transform: translateX(-50%) translateY(0); } }
    
    .menu-item { padding: 12px 15px; font-size: 14px; color: #4a5568; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; gap: 12px; }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:hover { background-color: #f0f9ff; color: #004b82; font-weight: 600; }
    .menu-item.delete { color: #ef4444; }
    .menu-item.delete:hover { background-color: #fef2f2; color: #dc2626; }
    
    /* --- Image Modal --- */
    .image-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); z-index: 2000; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .image-modal-overlay.show { display: flex !important; opacity: 1; }
    .modal-visual-wrap { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
    .image-modal-content { width: 220px; height: 220px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 20px 50px rgba(0,0,0,0.5); object-fit: cover; margin: 0; }
    .btn-close-bottom { margin-top: 30px; width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
    .btn-close-bottom:hover { background: rgba(255, 255, 255, 0.4); transform: scale(1.1); }
    
    /* --- Action Buttons --- */
    .btn-pill-action { display: block !important; width: auto !important; min-width: 220px !important; margin: 20px auto 0 !important; padding: 12px 40px !important; background-color: #004b82 !important; color: white !important; border: none !important; border-radius: 50px !important; font-size: 15px !important; font-weight: 600 !important; cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important; transition: all 0.3s ease !important; }
    .btn-pill-action:hover { background-color: #003660 !important; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3) !important; }
    
    /* --- Custom Gender Dropdown --- */
    .custom-dropdown { position: relative; width: 100%; background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0; cursor: pointer; user-select: none; transition: border-color 0.2s; }
    .custom-dropdown:hover { border-color: #b0c4de; }
    .custom-dropdown.active { border-color: #004b82; }
    .dropdown-trigger { display: flex; justify-content: space-between; align-items: center; height: 49px; padding: 0 15px; font-size: 15px; color: #333; }
    .trigger-arrow { color: #64748b; transition: transform 0.3s ease; font-size: 14px; }
    .custom-dropdown.active .trigger-arrow { transform: rotate(180deg); color: #004b82; }
    .dropdown-options { position: absolute; top: calc(100% + 5px); left: 0; width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1); z-index: 999; overflow: hidden; }
    .custom-dropdown.active .dropdown-options { opacity: 1; visibility: visible; transform: translateY(0); }
    .option-item { padding: 12px 15px; font-size: 14px; color: #4a5568; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f8fafc; }
    .option-item:last-child { border-bottom: none; }
    .option-item:hover { background-color: #f0f9ff; color: #004b82; font-weight: 600; }
    .option-item.selected { background-color: #e0f2fe; color: #004b82; font-weight: 700; }
</style>

<div class="edit-wrapper">
    <div class="btn-back-container">
        <a href="passanger_profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
    </div>

    <div class="page-header">
        <h1>Edit Profile</h1>
        <p>Update your personal information and security settings.</p>
    </div>

    <div class="edit-card">
        <div class="card-title"><i class="fa-regular fa-id-card"></i> Personal Details</div>
        
        <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="update_profile" value="1">

            <div class="avatar-upload-container">
                <div class="avatar-wrapper">
                    <?php 
                        $img_src = (!empty($student['profile_image']) && file_exists("uploads/" . $student['profile_image'])) 
                            ? "uploads/" . $student['profile_image'] 
                            : "https://ui-avatars.com/api/?name=".urlencode($student['name'])."&background=random&color=fff";
                    ?>
                    <img src="<?php echo $img_src; ?>" id="avatarPreview" class="avatar-preview" alt="Profile" onclick="openImageModal(this.src)">
                    <div class="avatar-edit-badge" onclick="toggleAvatarMenu(event)"><i class="fa-solid fa-pen"></i></div>
                    
                    <input type='file' id="imageUpload" name="profile_pic" accept=".png, .jpg, .jpeg" style="display:none;" onchange="previewImage(this);" />
                    
                    <div id="avatarMenu" class="avatar-menu">
                        <div class="menu-item" onclick="openImageModal(document.getElementById('avatarPreview').src)"><i class="fa-solid fa-expand"></i> View Photo</div>
                        <div class="menu-item" onclick="document.getElementById('imageUpload').click()"><i class="fa-solid fa-upload"></i> Upload Photo</div>
                        <div class="menu-item delete" onclick="confirmDeletePhoto()"><i class="fa-solid fa-trash"></i> Remove Photo</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="inputName" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Student ID (Fixed)</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email Address (Fixed)</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <div class="phone-group-container">
                    <div class="phone-prefix">+60</div>
                    <input type="text" name="phone" id="inputPhone" class="phone-input-clean" 
                           value="<?php echo htmlspecialchars($phone_display); ?>" 
                           placeholder="123456789" required maxlength="10" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <div class="custom-dropdown" id="genderDropdown">
                    <input type="hidden" name="gender" id="genderHiddenInput" value="<?php echo htmlspecialchars($student['gender']); ?>">
                    <div class="dropdown-trigger">
                        <span id="genderText"><?php echo !empty($student['gender']) ? htmlspecialchars($student['gender']) : 'Select Gender'; ?></span>
                        <i class="fa-solid fa-chevron-down trigger-arrow"></i>
                    </div>
                    <div class="dropdown-options">
                        <div class="option-item" onclick="selectGender('Male')">Male</div>
                        <div class="option-item" onclick="selectGender('Female')">Female</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-pill-action" onclick="validateAndSubmit(event)">Save Changes</button>
        </form>
    </div>

    <form id="deletePhotoForm" method="POST" style="display:none;">
        <input type="hidden" name="delete_photo" value="1">
    </form>

    <div class="edit-card">
        <div class="card-title"><i class="fa-solid fa-lock"></i> Security (Change Password)</div>
        <form action="" method="POST" id="passwordForm">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label>Current Password</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" id="oldPass" class="form-control" placeholder="Enter current password" required>
                    <div class="toggle-password" onclick="togglePass('oldPass', this)"><i class="fa-solid fa-eye-slash"></i></div>
                </div>
                <div class="forgot-link-wrapper">
                    <button type="button" onclick="triggerForgotFlow()" class="forgot-pass-btn">Forgot Password?</button>
                </div>
            </div>

            <div class="form-row-split">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 6 chars" required>
                        <div class="toggle-password" onclick="togglePass('newPass', this)"><i class="fa-solid fa-eye-slash"></i></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="cfmPass" class="form-control" placeholder="Repeat password" required>
                        <div class="toggle-password" onclick="togglePass('cfmPass', this)"><i class="fa-solid fa-eye-slash"></i></div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-pill-action">Update Password</button>
        </form>
    </div>
</div>

<div id="imageModalOverlay" class="image-modal-overlay" onclick="closeImageModal()">
    <div class="modal-visual-wrap" onclick="event.stopPropagation()">
        <img id="fullImage" class="image-modal-content" src="" alt="Full Profile">
        <button class="btn-close-bottom" onclick="closeImageModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>

<script>
    // --- 1. Form Validation & Explicit Submission ---
    function validateAndSubmit(e) {
        // Prevent default submission to allow JS validation
        e.preventDefault();

        // Get values
        var name = document.getElementById('inputName').value.trim();
        var phone = document.getElementById('inputPhone').value.trim();
        
        // Check Empty Fields
        if(name === "") {
            Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Please enter your Full Name.', confirmButtonColor: '#004b82' });
            return;
        }
        if(phone === "") {
            Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Please enter your Phone Number.', confirmButtonColor: '#004b82' });
            return;
        }

        // Show Loading Indicator
        Swal.fire({
            title: 'Saving Changes...',
            text: 'Please wait while we update your profile.',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                // Force submitting the form programmatically
                document.getElementById('profileForm').submit();
            }
        });
    }

    // --- 2. Avatar Preview Logic ---
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { 
                document.getElementById('avatarPreview').src = e.target.result; 
            }
            reader.readAsDataURL(input.files[0]);
        }
        // Hide menu after selection
        document.getElementById('avatarMenu').classList.remove('active');
    }

    // --- 3. Toggle Avatar Menu ---
    function toggleAvatarMenu(event) {
        event.stopPropagation();
        document.getElementById('avatarMenu').classList.toggle('active');
    }

    // --- 4. Image Modal Logic ---
    function openImageModal(src) {
        document.getElementById('fullImage').src = src;
        document.getElementById('imageModalOverlay').classList.add('show');
        document.getElementById('avatarMenu').classList.remove('active'); 
    }
    function closeImageModal() { 
        document.getElementById('imageModalOverlay').classList.remove('show'); 
    }

    // --- 5. Delete Photo Logic ---
    function confirmDeletePhoto() {
        document.getElementById('avatarMenu').classList.remove('active');
        Swal.fire({
            title: 'Remove Photo?', 
            text: "Are you sure you want to remove your profile picture?", 
            icon: 'warning', 
            showCancelButton: true,
            confirmButtonColor: '#ef4444', 
            cancelButtonColor: '#64748b', 
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) { 
                document.getElementById('deletePhotoForm').submit(); 
            }
        });
    }

    // --- 6. Password Visibility Toggle ---
    function togglePass(inputId, container) {
        const input = document.getElementById(inputId);
        const icon = container.querySelector('i');
        if (input.type === "password") {
            input.type = "text"; 
            icon.classList.replace('fa-eye-slash', 'fa-eye'); 
        } else {
            input.type = "password"; 
            icon.classList.replace('fa-eye', 'fa-eye-slash'); 
        }
    }

    // --- 7. Custom Dropdown Logic ---
    const dropdown = document.getElementById('genderDropdown');
    const trigger = dropdown.querySelector('.dropdown-trigger');
    const genderText = document.getElementById('genderText');
    const hiddenInput = document.getElementById('genderHiddenInput');
    const options = document.querySelectorAll('.option-item');

    trigger.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        dropdown.classList.toggle('active'); 
    });

    function selectGender(val) {
        genderText.innerText = val; 
        hiddenInput.value = val;
        options.forEach(opt => opt.classList.remove('selected'));
        event.target.classList.add('selected');
        dropdown.classList.remove('active'); 
        event.stopPropagation();
    }

    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('active');
        const avatarMenu = document.getElementById('avatarMenu');
        const avatarBadge = document.querySelector('.avatar-edit-badge');
        if (!avatarMenu.contains(e.target) && !avatarBadge.contains(e.target)) avatarMenu.classList.remove('active');
        if(e.target === document.getElementById('imageModalOverlay')) closeImageModal();
    });

    // --- 8. Forgot Password Flow ---
    function triggerForgotFlow() {
        var newPass = document.getElementById('newPass').value;
        var cfmPass = document.getElementById('cfmPass').value;
        
        // Check if new password fields are filled
        if(newPass === "" || cfmPass === "") { 
            Swal.fire({ title: 'Input Required', text: 'Please enter New Password first to proceed with reset.', icon: 'warning', confirmButtonColor: '#004b82' }); 
            return; 
        }
        if(newPass.length < 6) { 
            Swal.fire({ title: 'Weak Password', text: 'Password must be at least 6 characters.', icon: 'warning', confirmButtonColor: '#004b82' }); 
            return; 
        }
        if(newPass !== cfmPass) { 
            Swal.fire({ title: 'Mismatch', text: 'New Passwords do not match.', icon: 'error', confirmButtonColor: '#ef4444' }); 
            return; 
        }

        var form = document.getElementById('passwordForm');
        var originalAction = form.action;
        
        // Point to the TRIGGER script for OTP
        form.action = "passanger_trigger_otp.php"; 
        
        // Temporarily bypass 'required' on old_password because user forgot it
        document.getElementById('oldPass').removeAttribute('required');
        
        Swal.fire({ 
            title: 'Sending OTP...', 
            text: 'Please wait while we check your request.', 
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); } 
        });
        
        form.submit();
        
        // Restore form state
        form.action = originalAction;
        document.getElementById('oldPass').setAttribute('required', 'required');
    }
</script>

<?php 
// --- SECTION 8: SERVER-SIDE ALERTS --- 
if(isset($_SESSION['swal_success'])): ?>
    <script>
        Swal.fire({ 
            title: 'Success!', 
            text: '<?php echo $_SESSION['swal_success']; ?>', 
            icon: 'success', 
            confirmButtonColor: '#004b82', 
            timer: 2000 
        });
    </script>
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['swal_error'])): ?>
    <script>
        Swal.fire({ 
            title: 'Error!', 
            text: '<?php echo $_SESSION['swal_error']; ?>', 
            icon: 'error', 
            confirmButtonColor: '#ef4444' 
        });
    </script>
    <?php unset($_SESSION['swal_error']); ?>
<?php endif; ?>

<?php include "footer.php"; ?>