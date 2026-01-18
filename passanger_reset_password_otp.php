<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// 1. CHECK IF OTP DATA EXISTS
// If not, redirect back to profile edit (prevents direct access)
if(!isset($_SESSION['reset_otp_data'])){
    header("Location: passanger_profile_edit.php");
    exit();
}

// Prepare email mask for display (e.g. 121****@student...)
$email_full = $_SESSION['reset_otp_data']['email'];
$at_pos = strpos($email_full, "@");
$email_mask = substr($email_full, 0, 3) . "****" . substr($email_full, $at_pos);

// 2. HANDLE FORM SUBMISSION
if(isset($_POST['verify_reset'])){
    $input_otp = $_POST['otp1'].$_POST['otp2'].$_POST['otp3'].$_POST['otp4'];
    $session_otp = $_SESSION['reset_otp_data']['otp_code'];
    $new_pass = $_POST['new_password'];
    $cfm_pass = $_POST['confirm_password'];
    $student_id = $_SESSION['reset_otp_data']['student_id'];

    // Validation
    if($input_otp != $session_otp){
        $_SESSION['swal_error'] = "Invalid OTP Code. Please try again.";
    } 
    elseif($new_pass !== $cfm_pass){
        $_SESSION['swal_error'] = "Passwords do not match.";
    }
    elseif(strlen($new_pass) < 6){
        $_SESSION['swal_error'] = "Password must be at least 6 characters.";
    } 
    else {
        // 3. CHECK: New Password cannot be the same as Current Password
        // We need to fetch the current password hash from DB first
        $chk = $conn->prepare("SELECT password FROM students WHERE student_id = ?");
        $chk->bind_param("s", $student_id);
        $chk->execute();
        $res = $chk->get_result()->fetch_assoc();
        
        if(password_verify($new_pass, $res['password'])){
            $_SESSION['swal_error'] = "New password cannot be the same as your current password.";
        } else {
            // 4. UPDATE PASSWORD
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $new_hash, $student_id);
            
            if($stmt->execute()){
                // Clear the OTP session data
                unset($_SESSION['reset_otp_data']);
                
                // Set success message for the profile edit page
                $_SESSION['swal_success'] = "Password updated successfully!";
                
                // Redirect back
                header("Location: passanger_profile_edit.php");
                exit();
            }
        }
    }
}

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Styling similar to login OTP page */
    .otp-wrapper {
        display: flex; justify-content: center; align-items: center; 
        min-height: 80vh; background: #f6f5f7; font-family: 'Poppins', sans-serif;
    }
    .otp-box {
        background: #fff; padding: 40px; border-radius: 24px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; width: 400px;
    }
    .otp-box h2 { color: #004b82; margin-bottom: 10px; font-weight: 700; }
    .otp-box p { color: #64748b; font-size: 14px; margin-bottom: 30px; }
    
    .otp-inputs { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
    .otp-field {
        width: 50px; height: 60px; font-size: 24px; text-align: center;
        border: 2px solid #e2e8f0; border-radius: 12px; transition: 0.2s; outline: none;
    }
    .otp-field:focus { border-color: #004b82; box-shadow: 0 4px 10px rgba(0,75,130,0.1); }

    .pass-input-group { margin-bottom: 15px; text-align: left; position: relative; }
    .pass-input-group label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px; }
    .pass-input {
        width: 100%; height: 45px; padding: 0 15px; border: 1.5px solid #e2e8f0;
        border-radius: 10px; box-sizing: border-box; font-size: 14px; font-family: 'Poppins', sans-serif;
    }
    .pass-input:focus { border-color: #004b82; outline: none; }

    /* Toggle Icon inside input */
    .toggle-pass {
        position: absolute; right: 15px; top: 38px; color: #999; cursor: pointer;
    }
    .toggle-pass:hover { color: #004b82; }

    .btn-verify {
        width: 100%; padding: 12px; background: #004b82; color: white;
        border: none; border-radius: 50px; font-weight: 600; cursor: pointer;
        margin-top: 10px; transition: 0.2s; font-size: 15px;
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2);
    }
    .btn-verify:hover { background: #003660; transform: translateY(-2px); }
</style>

<div class="otp-wrapper">
    <div class="otp-box">
        <h2>Verify & Reset</h2>
        <p>Enter the code sent to <b><?php echo $email_mask; ?></b> and set your new password.</p>
        
        <form method="POST">
            <div class="otp-inputs">
                <input type="text" name="otp1" class="otp-field" maxlength="1" oninput="moveToNext(this, 'otp2')" required autofocus>
                <input type="text" name="otp2" id="otp2" class="otp-field" maxlength="1" oninput="moveToNext(this, 'otp3')" required>
                <input type="text" name="otp3" id="otp3" class="otp-field" maxlength="1" oninput="moveToNext(this, 'otp4')" required>
                <input type="text" name="otp4" id="otp4" class="otp-field" maxlength="1" required>
            </div>

            <div class="pass-input-group">
                <label>New Password</label>
                <input type="password" name="new_password" id="newPass" class="pass-input" placeholder="Min 6 chars" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('newPass', this)"></i>
            </div>
            
            <div class="pass-input-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="cfmPass" class="pass-input" placeholder="Repeat password" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('cfmPass', this)"></i>
            </div>

            <button type="submit" name="verify_reset" class="btn-verify">Reset Password</button>
        </form>
    </div>
</div>

<script>
    // Auto-focus next OTP field
    function moveToNext(current, nextFieldID) {
        if (current.value.length >= 1) {
            document.getElementById(nextFieldID).focus();
        }
    }

    // Toggle Password Visibility
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        }
    }
</script>

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