<?php
session_start();
ob_start();

require_once __DIR__ . "/db_connect.php";
require_once __DIR__ . "/send_mail.php"; 

// Handle form submission
if (isset($_POST['reset_request'])) {

    $email    = trim($_POST['email'] ?? "");
    $ic       = trim($_POST['identification_id'] ?? "");
    $new_pass = $_POST['new_password'] ?? "";
    $confirm  = $_POST['confirm_password'] ?? "";

    function fpError($title, $msg) {
        $_SESSION['swal_title'] = $title;
        $_SESSION['swal_msg']   = $msg;
        $_SESSION['swal_type']  = "warning";
        header("Location: driver_forgot_password.php");
        exit;
    }

    if (empty($email) || empty($ic) || empty($new_pass)) {
        fpError("Missing Fields", "Please fill in all fields.");
    } elseif ($new_pass !== $confirm) {
        fpError("Password Mismatch", "Passwords do not match.");
    } else {
        $stmt = $conn->prepare("SELECT driver_id, full_name FROM drivers WHERE email = ? AND identification_id = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $ic);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $otp = (string)rand(1000, 9999);

            $_SESSION['driver_reset'] = [
                'driver_id' => $row['driver_id'],
                'email'     => $email,
                'otp'       => $otp,
                'pwd_hash'  => password_hash($new_pass, PASSWORD_BCRYPT),
                'expires'   => time() + 600
            ];

            try {
                sendDriverOtpEmail($email, $row['full_name'], $otp);
                header("Location: driver_verify_otp.php"); 
                exit;
            } catch (Exception $e) {
                fpError("Email Error", "System could not send email.");
            }
        } else {
            fpError("Account Not Found", "Email and ID do not match our records.");
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* =========================
       FORGOT PASSWORD STYLES (MATCHING REGISTER UI)
       ========================= */
    :root {
        --brand-primary: #004b82;
        --brand-hover: #00365e;
        --bg-surface: #f4f6f8;
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-sub: #64748b;
        --border-light: #e2e8f0;
        --input-focus-shadow: rgba(0, 75, 130, 0.15);
    }

    body { background-color: var(--bg-surface); font-family: 'Inter', sans-serif; }

    /* Animations */
    @keyframes slideInRight { 
        from { opacity: 0; transform: translateX(40px); } 
        to { opacity: 1; transform: translateX(0); } 
    }
    @keyframes slideOutRight { 
        to { opacity: 0; transform: translateX(40px); } 
    }
    @keyframes fadeInUp { 
        from { opacity: 0; transform: translateY(30px); } 
        to { opacity: 1; transform: translateY(0); } 
    }

    .wrapper {
        min-height: calc(100vh - 100px);
        display: flex;
        justify-content: center;
        /* [修复] 减少了顶部 padding */
        padding: 40px 20px;
        position: relative;
    }

    /* Main Card */
    .form-box {
        background: #ffffff;
        width: 100%; max-width: 500px;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        padding: 40px;
        position: relative;
        /* Default Entry Animation */
        animation: slideInRight 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        border: 1px solid rgba(255,255,255,0.5);
        /* [修复] 减少了 margin-top */
        margin-top: 20px;
    }
    .form-box.exiting {
        animation: slideOutRight 0.4s cubic-bezier(0.55, 0.085, 0.68, 0.53) both !important;
    }

    /* Back Button */
    .back-nav { 
        position: absolute; 
        /* [修复] 位置往上提 */
        top: 20px; 
        left: 10%; 
        z-index: 50; 
        animation: fadeInUp 0.8s ease-out both; 
    }
    .btn-back { 
        height: 42px; border: none; border-radius: 50px !important; 
        background: rgba(255,255,255,0.9); color: var(--brand-primary); 
        font-weight: 600; cursor: pointer; transition: all 0.3s ease; 
        display: flex; align-items: center; justify-content: center; 
        text-decoration: none; font-size: 14px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); backdrop-filter: blur(5px); 
        padding: 0 25px; gap: 8px; 
    }
    .btn-back:hover { 
        background: var(--brand-primary); color: #fff; 
        box-shadow: 0 8px 15px rgba(0, 75, 130, 0.2); transform: translateY(-2px); 
    }

    .top { text-align: center; margin-bottom: 30px; }
    .top h2 { font-size: 26px; color: var(--text-main); font-weight: 800; margin-bottom: 10px; }
    .top p { color: var(--text-sub); font-size: 14px; margin: 0; }

    /* Inputs (Matching Register Style - No Icons) */
    .input-group { display: flex; flex-direction: column; margin-bottom: 20px; position: relative; }
    .input-label { 
        font-size: 13px; font-weight: 600; color: var(--text-sub); 
        margin-bottom: 6px; 
    }
    
    .input-field {
        width: 100%; padding: 12px 16px; 
        border: 2px solid var(--border-light);
        border-radius: 10px;
        font-size: 14px; color: var(--text-main);
        background: #fff;
        transition: all 0.2s;
        box-sizing: border-box;
        font-family: inherit;
    }
    .input-field:focus {
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 4px var(--input-focus-shadow);
        outline: none;
    }
    .input-field::placeholder { color: #cbd5e0; }

    /* Submit Button */
    .submit { 
        width: 100%; height: 52px; background: var(--brand-primary); 
        border: none; border-radius: 12px; color: #fff; 
        font-size: 16px; font-weight: 700; cursor: pointer; 
        transition: all 0.3s; box-shadow: 0 10px 20px rgba(0, 75, 130, 0.15); 
        margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .submit:hover { 
        background: var(--brand-hover); transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(0, 75, 130, 0.25);
    }

    /* Password Toggle */
    .toggle-pass {
        position: absolute; right: 16px; top: 38px; /* Adjusted for label height */
        color: #94a3b8; cursor: pointer; transition: 0.2s;
    }
    .toggle-pass:hover { color: var(--brand-primary); }
</style>

<div class="wrapper">
    <div class="back-nav">
        <a href="javascript:void(0)" onclick="backToLogin()" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <div class="form-box" id="formBox">
        <div class="top">
            <h2>Reset Password</h2>
            <p>Enter your details to reset your password.</p>
        </div>

        <form method="post" onsubmit="handleLoading(this)">
            
            <div class="input-group">
                <label class="input-label">Email Address</label>
                <input type="email" name="email" class="input-field" placeholder="Enter your email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="input-group">
                <label class="input-label">Identification ID</label>
                <input type="text" name="identification_id" class="input-field" placeholder="Enter your ID" required value="<?= isset($_POST['identification_id']) ? htmlspecialchars($_POST['identification_id']) : '' ?>">
            </div>

            <hr style="border:0; border-top:1px dashed #e0e0e0; margin: 25px 0;">

            <div class="input-group">
                <label class="input-label">New Password</label>
                <input type="password" name="new_password" id="newPass" class="input-field" placeholder="Min 6 characters" minlength="6" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('newPass', this)"></i>
            </div>

            <div class="input-group">
                <label class="input-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confPass" class="input-field" placeholder="Re-enter password" required>
                <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('confPass', this)"></i>
            </div>

            <button type="submit" name="reset_request" class="submit">
                Send OTP <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // Smooth Return to Login
    function backToLogin() {
        const box = document.getElementById('formBox');
        box.classList.add('exiting');
        setTimeout(() => { window.location.href = 'driver_login.php'; }, 400);
    }

    function togglePass(id, icon) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            icon.style.color = "#004b82";
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            icon.style.color = "#cbd5e0";
        }
    }

    function handleLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }, 10000);
        return true;
    }
</script>

<?php 
if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<?php 
    unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']);
endif; 
include "footer.php"; 
?>