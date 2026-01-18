<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// 1. CHECK LOGIN
// Redirect to login page if user is not logged in
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// 2. FETCH CURRENT USER DATA
// Get the user's details to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Parse Phone Number (Remove +60 or 60 prefix for display)
$phone_display = $student['phone'];
if(strpos($phone_display, '60') === 0) {
    $phone_display = substr($phone_display, 2);
} elseif(strpos($phone_display, '+60') === 0) {
    $phone_display = substr($phone_display, 3);
}

// ==========================================
// LOGIC: DELETE PROFILE PHOTO
// ==========================================
if(isset($_POST['delete_photo'])){
    $current_img = $student['profile_image'];
    
    // Check if file exists and delete it
    if(!empty($current_img) && file_exists("uploads/" . $current_img)){
        unlink("uploads/" . $current_img);
    }

    // Set database column to NULL
    $del_stmt = $conn->prepare("UPDATE students SET profile_image = NULL WHERE student_id = ?");
    $del_stmt->bind_param("s", $student_id);
    
    if($del_stmt->execute()){
        $_SESSION['swal_success'] = "Profile photo removed.";
        header("Location: passanger_profile_edit.php");
        exit();
    }
}

// ==========================================
// LOGIC: UPDATE PROFILE (Name, Gender, Phone, Photo)
// ==========================================
if(isset($_POST['update_profile'])){
    $name = trim($_POST['name']);
    $gender = $_POST['gender']; 
    $phone_raw = trim($_POST['phone']);
    $phone_final = "60" . $phone_raw; // Add prefix for standard format

    // Handle Image Upload
    $profile_image = $student['profile_image']; // Default to existing image
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $filesize = $_FILES['profile_pic']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(in_array($ext, $allowed)){
            if($filesize < 5000000){ // Limit: 5MB
                $new_filename = "student_" . $student_id . "_" . time() . "." . $ext;
                $upload_path = "uploads/" . $new_filename;
                
                if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)){
                    $profile_image = $new_filename;
                } else {
                    $_SESSION['swal_error'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['swal_error'] = "File size too large (Max 5MB).";
            }
        } else {
            $_SESSION['swal_error'] = "Invalid file format. Only JPG, PNG, GIF allowed.";
        }
    }

    // Execute Database Update
    if(!isset($_SESSION['swal_error'])){
        $update_stmt = $conn->prepare("UPDATE students SET name = ?, gender = ?, phone = ?, profile_image = ? WHERE student_id = ?");
        $update_stmt->bind_param("sssss", $name, $gender, $phone_final, $profile_image, $student_id);
        
        if($update_stmt->execute()){
            $_SESSION['student_name'] = $name; // Update session name
            $_SESSION['swal_success'] = "Profile updated successfully!";
            header("Location: passanger_profile_edit.php"); 
            exit();
        } else {
            $_SESSION['swal_error'] = "Database error: " . $conn->error;
        }
    }
}

// ==========================================
// LOGIC: CHANGE PASSWORD
// ==========================================
if(isset($_POST['change_password'])){
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $cfm_pass = $_POST['confirm_password'];

    // 1. Check if current password is correct
    if(password_verify($old_pass, $student['password'])){
        
        // 2. CHECK: New Password CANNOT be the same as Current Password
        if(password_verify($new_pass, $student['password'])){
            $_SESSION['swal_error'] = "New password cannot be the same as your current password.";
        }
        else {
            // 3. Check if new password matches confirm password
            if($new_pass === $cfm_pass){
                // 4. Check password length
                if(strlen($new_pass) >= 6){
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $pass_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                    $pass_stmt->bind_param("ss", $new_hash, $student_id);
                    
                    if($pass_stmt->execute()){
                        $_SESSION['swal_success'] = "Password changed successfully!";
                        header("Location: passanger_profile_edit.php");
                        exit();
                    }
                } else {
                    $_SESSION['swal_error'] = "New password must be at least 6 characters.";
                }
            } else {
                $_SESSION['swal_error'] = "New passwords do not match.";
            }
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
    /* REMOVE SPINNERS from Number Inputs */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    
    /* 1. Global & Animation */
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    
    .edit-wrapper {
        max-width: 800px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 2. BACK BUTTON */
    .btn-back-container { display: flex; justify-content: flex-start; margin-bottom: 20px; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; transition: 0.2s; font-size: 15px; }
    .btn-back:hover { color: #004b82; transform: translateX(-3px); }

    .page-header { margin-bottom: 30px; text-align: center; }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .page-header p { margin: 8px 0 0; font-size: 15px; color: #64748b; }

    /* 3. Cards */
    .edit-card { 
        background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        border: 1px solid #e2e8f0; padding: 40px; margin-bottom: 25px; 
        text-align: left !important;
    }
    .card-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 10px; }

    /* 4. Form Elements */
    .form-group { margin-bottom: 15px; text-align: left; } 
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; text-align: left; }
    
    .form-control {
        width: 100%; height: 52px; padding: 0 15px; font-size: 15px;
        border: 1.5px solid #e2e8f0; border-radius: 12px; transition: all 0.3s ease-in-out;
        box-sizing: border-box; background: #fff; color: #333; font-family: 'Poppins', sans-serif;
    }
    .form-control:focus { border-color: #004b82; outline: none; box-shadow: 0 4px 15px rgba(0,75,130,0.1); transform: translateY(-1px); }
    .form-control:disabled, .form-control[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; transform: none; }

    /* GRID LAYOUT FOR PASSWORD FIELDS */
    .form-row-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 600px) { .form-row-split { grid-template-columns: 1fr; gap: 15px; } }

    /* 5. Phone Input Group */
    .phone-group-container {
        display: flex; align-items: stretch; width: 100%; height: 52px;
        border: 1.5px solid #e2e8f0; border-radius: 12px; background-color: #fff;
        overflow: hidden; transition: all 0.3s ease-in-out;
    }
    .phone-group-container:focus-within { border-color: #004b82; box-shadow: 0 4px 15px rgba(0,75,130,0.1); transform: translateY(-1px); }
    .phone-prefix {
        background: #f1f5f9; color: #475569; font-weight: 600; width: 60px; 
        display: flex; align-items: center; justify-content: center;
        border-right: 1.5px solid #e2e8f0; font-size: 15px; flex-shrink: 0;
    }
    .phone-input-clean {
        flex-grow: 1; border: none !important; outline: none !important; box-shadow: none !important;
        background: transparent !important; height: auto !important; margin: 0 !important;
        padding: 0 15px !important; line-height: normal !important;
        font-size: 15px; color: #333; font-family: 'Poppins', sans-serif;
    }

    /* 6. PASSWORD TOGGLE ICON */
    .password-wrapper { position: relative; width: 100%; height: 52px; }
    .form-control[type="password"], .form-control[type="text"] { padding-right: 50px; }
    .toggle-password {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 10; border-radius: 50%; transition: background 0.2s;
    }
    .toggle-password:hover { background-color: #f1f5f9; }
    .toggle-password i { font-size: 16px; color: #94a3b8; line-height: 1; display: block; }
    .toggle-password:hover i { color: #004b82; }

    /* 7. FORGOT PASSWORD LINK */
    .forgot-link-wrapper {
        text-align: right;
        margin-top: 5px;
    }
    .forgot-pass-link {
        font-size: 13px;
        color: #004b82;
        text-decoration: none;
        font-weight: 600;
        transition: 0.2s;
    }
    .forgot-pass-link:hover {
        text-decoration: underline;
        color: #003660;
    }

    /* ================================================= */
    /* 8. AVATAR UPLOAD & MENU SYSTEM                    */
    /* ================================================= */
    .avatar-upload-container { 
        display: flex; flex-direction: column; align-items: center; 
        margin-bottom: 25px; position: relative; 
    }
    .avatar-wrapper { position: relative; width: 120px; height: 120px; }
    
    .avatar-preview { 
        width: 100%; height: 100%; border-radius: 50%; border: 4px solid #fff; 
        box-shadow: 0 8px 25px rgba(0,75,130,0.15); object-fit: cover; background: #e0f2fe; 
        transition: transform 0.3s; cursor: pointer; 
    }
    .avatar-wrapper:hover .avatar-preview { transform: scale(1.02); filter: brightness(0.95); }
    
    /* THE PEN ICON */
    .avatar-edit-badge {
        position: absolute; bottom: 0px; right: 0px; 
        background: #004b82; color: white;
        width: 36px; height: 36px; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: 0.2s; border: 3px solid #fff; 
        font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 10;
    }
    .avatar-edit-badge:hover { background: #003660; transform: scale(1.1); }

    /* THE MENU (RIGHT SIDE) */
    .avatar-menu {
        position: absolute;
        top: 10px; left: 100%; margin-left: 15px; 
        width: 180px; 
        background: #fff; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;
        opacity: 0; visibility: hidden; 
        transform: translateX(-15px); 
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1); 
        z-index: 999; overflow: hidden; text-align: left;
    }
    .avatar-menu.active { opacity: 1; visibility: visible; transform: translateX(0); }

    @media (max-width: 600px) {
        .avatar-menu { top: 100%; left: 50%; margin-left: 0; transform: translateX(-50%) translateY(10px); }
        .avatar-menu.active { transform: translateX(-50%) translateY(0); }
    }

    .menu-item {
        padding: 12px 15px; font-size: 14px; color: #4a5568; 
        cursor: pointer; transition: background 0.2s; 
        border-bottom: 1px solid #f8fafc;
        display: flex; align-items: center; gap: 12px;
    }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:hover { background-color: #f0f9ff; color: #004b82; font-weight: 600; }
    .menu-item.delete { color: #ef4444; }
    .menu-item.delete:hover { background-color: #fef2f2; color: #dc2626; }

    /* ================================================= */
    /* 9. VIEW-ONLY MODAL (CENTERED + BOTTOM X)          */
    /* ================================================= */
    .image-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); 
        backdrop-filter: blur(12px); 
        z-index: 2000;
        
        display: none; 
        justify-content: center; 
        align-items: center;     
        
        opacity: 0; transition: opacity 0.3s ease;
    }
    .image-modal-overlay.show { display: flex !important; opacity: 1; }

    .modal-visual-wrap {
        display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;
    }

    .image-modal-content { 
        width: 220px; height: 220px; 
        border-radius: 50%; 
        border: 4px solid rgba(255,255,255,0.2);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
        object-fit: cover;
        margin: 0;
    }

    /* THE CLOSE BUTTON (DIRECTLY BELOW IMAGE) */
    .btn-close-bottom {
        margin-top: 30px; 
        width: 50px; height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2); 
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white; font-size: 20px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s ease;
    }
    .btn-close-bottom:hover { background: rgba(255, 255, 255, 0.4); transform: scale(1.1); }

    /* 10. Buttons */
    .btn-pill-action {
        display: block !important; width: auto !important; min-width: 220px !important; margin: 20px auto 0 !important;
        padding: 12px 40px !important; background-color: #004b82 !important; color: white !important;
        border: none !important; border-radius: 50px !important; font-size: 15px !important; font-weight: 600 !important;
        cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    .btn-pill-action:hover { background-color: #003660 !important; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3) !important; }

    /* 11. DROPDOWN */
    .custom-dropdown {
        position: relative; width: 100%; background: #fff; border-radius: 12px;
        border: 1.5px solid #e2e8f0; cursor: pointer; user-select: none; transition: border-color 0.2s;
    }
    .custom-dropdown:hover { border-color: #b0c4de; }
    .custom-dropdown.active { border-color: #004b82; }
    .dropdown-trigger {
        display: flex; justify-content: space-between; align-items: center; height: 49px; padding: 0 15px; font-size: 15px; color: #333;
    }
    .trigger-arrow { color: #64748b; transition: transform 0.3s ease; font-size: 14px; }
    .custom-dropdown.active .trigger-arrow { transform: rotate(180deg); color: #004b82; }
    .dropdown-options {
        position: absolute; top: calc(100% + 5px); left: 0; width: 100%; background: #fff; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;
        opacity: 0; visibility: hidden; transform: translateY(-10px);
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1); z-index: 999; overflow: hidden;
    }
    .custom-dropdown.active .dropdown-options { opacity: 1; visibility: visible; transform: translateY(0); }
    .option-item {
        padding: 12px 15px; font-size: 14px; color: #4a5568; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid #f8fafc;
    }
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
        
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_profile" value="1">

            <div class="avatar-upload-container">
                <div class="avatar-wrapper">
                    <?php 
                        if(!empty($student['profile_image']) && file_exists("uploads/" . $student['profile_image'])){
                            $img_src = "uploads/" . $student['profile_image'];
                        } else {
                            $img_src = "https://ui-avatars.com/api/?name=".urlencode($student['name'])."&background=random&color=fff";
                        }
                    ?>
                    <img src="<?php echo $img_src; ?>" id="avatarPreview" class="avatar-preview" alt="Profile" onclick="openImageModal(this.src)">
                    
                    <div class="avatar-edit-badge" onclick="toggleAvatarMenu(event)">
                        <i class="fa-solid fa-pen"></i>
                    </div>

                    <input type='file' id="imageUpload" name="profile_pic" accept=".png, .jpg, .jpeg" style="display:none;" onchange="previewImage(this);" />
                    
                    <div id="avatarMenu" class="avatar-menu">
                        <div class="menu-item" onclick="openImageModal(document.getElementById('avatarPreview').src)">
                            <i class="fa-solid fa-expand"></i> View Photo
                        </div>
                        <div class="menu-item" onclick="document.getElementById('imageUpload').click()">
                            <i class="fa-solid fa-upload"></i> Upload Photo
                        </div>
                        <div class="menu-item delete" onclick="confirmDeletePhoto()">
                            <i class="fa-solid fa-trash"></i> Remove Photo
                        </div>
                    </div>
                </div>
            </div>

            <form id="deletePhotoForm" method="POST" style="display:none;">
                <input type="hidden" name="delete_photo" value="1">
            </form>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
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
                    <input type="text" name="phone" class="phone-input-clean" value="<?php echo htmlspecialchars($phone_display); ?>" placeholder="123456789" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                        <div class="option-item <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>" onclick="selectGender('Male')">Male</div>
                        <div class="option-item <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>" onclick="selectGender('Female')">Female</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-pill-action">Save Changes</button>
        </form>
    </div>

    <div class="edit-card">
        <div class="card-title"><i class="fa-solid fa-lock"></i> Security (Change Password)</div>
        
        <form action="" method="POST">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label>Current Password</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" id="oldPass" class="form-control" placeholder="Enter current password" required>
                    <div class="toggle-password" onclick="togglePass('oldPass', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </div>
                </div>
                <div class="forgot-link-wrapper">
                    <a href="passanger_send_reset_otp.php" class="forgot-pass-link">Forgot Password?</a>
                </div>
            </div>

            <div class="form-row-split">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 6 chars" required>
                        <div class="toggle-password" onclick="togglePass('newPass', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="cfmPass" class="form-control" placeholder="Repeat password" required>
                        <div class="toggle-password" onclick="togglePass('cfmPass', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </div>
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
        
        <button class="btn-close-bottom" onclick="closeImageModal()">
            <i class="fa-solid fa-xmark"></i>
        </button>
        
    </div>
</div>

<script>
    // 1. Avatar Preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('avatarPreview').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
        document.getElementById('avatarMenu').classList.remove('active');
    }

    // 2. Toggle Avatar Menu (Right Side)
    function toggleAvatarMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('avatarMenu');
        menu.classList.toggle('active');
    }

    // 3. View Only Modal Logic
    function openImageModal(src) {
        document.getElementById('fullImage').src = src;
        document.getElementById('imageModalOverlay').classList.add('show');
        document.getElementById('avatarMenu').classList.remove('active'); 
    }
    function closeImageModal() {
        document.getElementById('imageModalOverlay').classList.remove('show');
    }

    // 4. Confirm Delete Logic
    function confirmDeletePhoto() {
        document.getElementById('avatarMenu').classList.remove('active');
        
        Swal.fire({
            title: 'Remove Photo?',
            text: "Are you sure you want to delete your profile picture?",
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

    // 5. Password Toggle Logic
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

    // 6. Custom Dropdown Logic (Gender)
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

    // Global Click Listener
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('active');
        
        const avatarMenu = document.getElementById('avatarMenu');
        const avatarBadge = document.querySelector('.avatar-edit-badge');
        if (!avatarMenu.contains(e.target) && !avatarBadge.contains(e.target)) {
            avatarMenu.classList.remove('active');
        }
        
        const modalOverlay = document.getElementById('imageModalOverlay');
        if(e.target === modalOverlay) closeImageModal();
    });
</script>

<?php if(isset($_SESSION['swal_success'])): ?>
    <script>
        Swal.fire({ title: 'Success!', text: '<?php echo $_SESSION['swal_success']; ?>', icon: 'success', confirmButtonColor: '#004b82', timer: 2000 });
    </script>
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['swal_error'])): ?>
    <script>
        Swal.fire({ title: 'Error!', text: '<?php echo $_SESSION['swal_error']; ?>', icon: 'error', confirmButtonColor: '#ef4444' });
    </script>
    <?php unset($_SESSION['swal_error']); ?>
<?php endif; ?>

<?php include "footer.php"; ?>