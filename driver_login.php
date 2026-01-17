<?php
// =========================================================
// 1. CACHE CONTROL
// Prevents back button issues (form resubmission/expired pages)
// =========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Start Session
session_start();

// Include necessary files
include "db_connect.php";
include "function.php";

// =========================================================
// 2. CHECK LOGIN STATUS
// =========================================================
if(isset($_SESSION['driver_id']) && !isset($_SESSION['login_success'])){
    echo "<script>window.location.href='driver_dashboard.php';</script>";
    exit();
}

// --- RETRIEVE STICKY DATA ---
$login_email_val = isset($_SESSION['sticky']['login_email']) ? $_SESSION['sticky']['login_email'] : "";
unset($_SESSION['sticky']);

// =========================================================
// 3. LOGIN LOGIC
// =========================================================
if(isset($_POST['login'])){
    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        
        if(password_verify($password, $row['password'])){
            
            // Check Verification Status
            if(isset($row['verification_status'])){
                if ($row['verification_status'] == 'pending'){
                    $_SESSION['swal_title'] = "Account Pending";
                    $_SESSION['swal_msg'] = "Your account is currently under review by the Admin.";
                    $_SESSION['swal_type'] = "info";
                    header("Location: driver_login.php");
                    exit();
                } elseif ($row['verification_status'] == 'rejected'){
                    $_SESSION['swal_title'] = "Account Rejected";
                    $_SESSION['swal_msg'] = "Your application has been rejected.";
                    $_SESSION['swal_type'] = "error";
                    header("Location: driver_login.php");
                    exit();
                }
            }

            // Login Success
            $_SESSION['driver_id'] = $row['driver_id']; 
            $_SESSION['driver_name'] = $row['full_name'];
            $_SESSION['login_success'] = true; 
            $_SESSION['user_name'] = $row['full_name'];
            
            header("Location: driver_login.php"); 
            exit(); 
        } else {
            $_SESSION['swal_title'] = "Login Failed";
            $_SESSION['swal_msg'] = "Incorrect password.";
            $_SESSION['swal_type'] = "error";
            $_SESSION['sticky']['login_email'] = $email;
            header("Location: driver_login.php");
            exit();
        }
    } else {
        $_SESSION['swal_title'] = "Login Failed";
        $_SESSION['swal_msg'] = "No driver account found with this email.";
        $_SESSION['swal_type'] = "warning";
        header("Location: driver_login.php");
        exit();
    }
}
?>

<?php include "header.php"; ?>

<style>
    /* =========================
       GLOBAL STYLES
       ========================= */
    :root { 
        --primary-color: #004b82; 
        --input-bg: #ffffff; 
        --input-border: #e0e0e0; 
    }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes slideOutLeft { to { opacity: 0; transform: translateX(-100px); } }

    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    
    .wrapper { 
        width: 100%; min-height: 80vh; 
        display: flex; justify-content: center; align-items: flex-start; 
        padding-top: 40px; position: relative; overflow: hidden; 
        background: linear-gradient(to bottom, #f8f9fc, #eef2f7); 
    }
    
    /* Buttons */
    .btn, .btn-back { 
        height: 42px; border: none; border-radius: 50px !important; 
        background: rgba(255,255,255,0.9); color: var(--primary-color); 
        font-weight: 600; cursor: pointer; transition: all 0.3s ease; 
        display: flex; align-items: center; justify-content: center; 
        text-decoration: none; font-size: 14px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        backdrop-filter: blur(5px); 
    }
    .btn { width: 120px; } 
    .btn-back { padding: 0 25px; gap: 8px; }
    
    .btn.white-btn, .btn:hover, .btn-back:hover { 
        background: var(--primary-color); color: #fff; 
        box-shadow: 0 8px 15px rgba(0, 75, 130, 0.2); 
        transform: translateY(-2px); 
    }
    
    /* Form Box */
    .form-box { 
        position: relative; width: 550px; overflow: hidden; margin-top: 60px; 
        background: transparent !important; 
        animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; 
    }
    .form-box.exiting { 
        animation: slideOutLeft 0.4s cubic-bezier(0.55, 0.085, 0.68, 0.53) both !important; 
    }

    .login-container { background: transparent; padding: 0 40px; }
    
    /* Top Section */
    .top { margin-bottom: 30px; text-align: center; }
    .top h2 { font-size: 30px; color: #2d3748 !important; font-weight: 800; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; justify-content: center; gap: 10px; }
    
    /* Inputs */
    .input-box { 
        display: flex; align-items: center; width: 100%; height: 52px; 
        background: var(--input-bg) !important; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important; 
        border-radius: 12px !important; margin-bottom: 20px; padding: 0 15px; 
        border: 1px solid var(--input-border) !important; 
        transition: all 0.3s ease; 
    }
    .input-box:focus-within { 
        border-color: var(--primary-color) !important; 
        box-shadow: 0 0 0 4px rgba(0, 75, 130, 0.1) !important; 
        transform: translateY(-1px); 
    }
    .input-box i { font-size: 16px; color: #a0aec0; margin-right: 12px; } 
    .input-box:focus-within i { color: var(--primary-color); }
    
    .input-field { 
        flex: 1; background: transparent !important; border: none !important; 
        outline: none !important; color: #333 !important; font-size: 15px !important; 
        font-weight: 500; height: 100%; 
    }
    
    .toggle-pass { margin-left: 10px; cursor: pointer; color: #cbd5e0; } 
    .toggle-pass:hover { color: var(--primary-color); }
    
    /* Submit Button */
    .submit { 
        width: 100%; height: 52px; background: var(--primary-color) !important; 
        border: none !important; border-radius: 12px !important; color: #fff !important; 
        font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; 
        box-shadow: 0 4px 12px rgba(0, 75, 130, 0.25); margin-top: 10px; 
        display: flex; align-items: center; justify-content: center; gap: 8px; 
    }
    .submit:hover { 
        background: #00365e !important; transform: translateY(-2px); 
    }
    
    .two-col { display: flex; justify-content: center; font-size: 14px; margin-top: 20px; color: #555; }
    .two-col a { color: #718096; text-decoration: none; font-weight: 500; cursor: pointer; transition: 0.2s; } 
    .two-col a:hover { color: var(--primary-color); }
    
    /* Nav Positioning */
    .nav-button { position: absolute; top: 10px; right: 10%; display: flex; gap: 15px; z-index: 100; animation: fadeInUp 0.8s ease-out both; }
    .back-nav { position: absolute; top: 10px; left: 10%; z-index: 100; animation: fadeInUp 0.8s ease-out both; }
</style>

<div class="wrapper">
    <div class="back-nav"><a href="index.php" class="btn-back"><i class="fa-solid fa-chevron-left"></i> Home</a></div>
    
    <div class="nav-button">
        <button class="btn white-btn">Sign In</button>
        <button class="btn" onclick="goToRegister()">Sign Up</button>
    </div>

    <div class="form-box" id="formBox">
        <div class="login-container">
            <div class="top">
                <h2><i class="fa-solid fa-car"></i> Driver Login</h2>
            </div>
            
            <form action="" method="POST" onsubmit="handleLoading(this)">
                <div class="input-box">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="login_email" class="input-field" placeholder="Email Address" value="<?php echo htmlspecialchars($login_email_val ?? ''); ?>" required>
                </div>
                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="login_password" id="loginPass" class="input-field" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('loginPass', this)"></i>
                </div>
                
                <button type="submit" name="login" class="submit">
                    Sign In <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
                
                <div class="two-col">
                    <a onclick="goToForgot()">Forgot password?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // Smooth transition to Register
    function goToRegister() {
        const box = document.getElementById('formBox');
        box.classList.add('exiting');
        setTimeout(() => { window.location.href = 'driver_register.php'; }, 400);
    }

    // Smooth transition to Forgot Password
    function goToForgot() {
        const box = document.getElementById('formBox');
        box.classList.add('exiting');
        setTimeout(() => { window.location.href = 'driver_forgot_password.php'; }, 400);
    }

    function togglePass(inputId, icon) { 
        const input = document.getElementById(inputId); 
        if (input.type === "password") { input.type = "text"; icon.classList.replace('fa-eye-slash', 'fa-eye'); icon.style.color = "#004b82"; } 
        else { input.type = "password"; icon.classList.replace('fa-eye', 'fa-eye-slash'); icon.style.color = ""; } 
    }
    
    function handleLoading(form) { 
        const btn = form.querySelector('button[type="submit"]'); 
        const originalHtml = btn.innerHTML; 
        if(btn.disabled) return false; 
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...'; 
        setTimeout(() => { btn.disabled = false; btn.innerHTML = originalHtml; }, 10000); 
        return true; 
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82', 
        confirmButtonText: 'OK'
    });
</script>
<?php unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']); endif; ?>

<?php if(isset($_SESSION['login_success'])): ?>
<script>
    Swal.fire({
        title: 'Login Successful!',
        text: 'Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(() => { window.location.replace('driver_dashboard.php'); });
</script>
<?php unset($_SESSION['login_success'], $_SESSION['user_name']); endif; ?>