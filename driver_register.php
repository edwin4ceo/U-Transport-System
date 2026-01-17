<?php
// =========================================================
// 1. SESSION & CACHE
// =========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
include "db_connect.php";
include "function.php";

// If already logged in, redirect
if(isset($_SESSION['driver_id'])){
    echo "<script>window.location.href='driver_dashboard.php';</script>";
    exit();
}

// --- RETRIEVE STICKY DATA (Retain Inputs) ---
$val_name      = $_SESSION['sticky']['full_name'] ?? "";
$val_id        = $_SESSION['sticky']['identification_id'] ?? "";
$val_email     = $_SESSION['sticky']['email'] ?? "";
$val_phone     = $_SESSION['sticky']['phone_number'] ?? "";
$val_gender    = $_SESSION['sticky']['gender'] ?? ""; 
$val_license   = $_SESSION['sticky']['license_number'] ?? "";
$val_lic_exp   = $_SESSION['sticky']['license_expiry'] ?? "";

// Vehicle Data
$val_car_model = $_SESSION['sticky']['car_model'] ?? "";
$val_car_plate = $_SESSION['sticky']['car_plate_number'] ?? "";
$val_car_color = $_SESSION['sticky']['vehicle_color'] ?? "";
$val_car_type  = $_SESSION['sticky']['vehicle_type'] ?? "";
$val_seats     = $_SESSION['sticky']['seat_count'] ?? "";
$val_road_tax  = $_SESSION['sticky']['road_tax_expiry'] ?? "";
$val_insurance = $_SESSION['sticky']['insurance_expiry'] ?? "";

unset($_SESSION['sticky']); 

// =========================================================
// 2. REGISTRATION LOGIC
// =========================================================
if (isset($_POST['register'])) {

    // 1. Get form values
    $full_name         = trim($_POST['full_name'] ?? "");
    $identification_id = trim($_POST['identification_id'] ?? "");
    $email             = trim($_POST['email'] ?? "");
    $phone_number      = trim($_POST['phone_number'] ?? "");
    $gender            = $_POST['gender'] ?? ""; 
    $license_number    = trim($_POST['license_number'] ?? "");
    $license_expiry    = trim($_POST['license_expiry'] ?? "");
    $password_plain    = $_POST['password'] ?? "";

    // Vehicle values
    $car_model        = trim($_POST['car_model'] ?? "");
    $car_plate_number = trim($_POST['car_plate_number'] ?? "");
    $vehicle_color    = trim($_POST['vehicle_color'] ?? "");
    $vehicle_type     = trim($_POST['vehicle_type'] ?? "");
    $seat_count_raw   = trim($_POST['seat_count'] ?? "");
    $seat_count       = $seat_count_raw === "" ? 0 : (int)$seat_count_raw;
    
    $road_tax_expiry  = trim($_POST['road_tax_expiry'] ?? "");
    $insurance_expiry = trim($_POST['insurance_expiry'] ?? "");

    // Helper: Save input to session and redirect
    function regError($title, $msg) {
        $_SESSION['swal_title'] = $title;
        $_SESSION['swal_msg']   = $msg;
        $_SESSION['swal_type']  = "warning";
        $_SESSION['sticky']     = $_POST; 
        header("Location: driver_register.php");
        exit;
    }

    // 2. Basic validation
    if (
        $full_name === "" || $identification_id === "" || $email === "" ||
        $phone_number === "" || $gender === "" || $license_number === "" || 
        $license_expiry === "" || $password_plain === "" || 
        $car_model === "" || $car_plate_number === "" || $vehicle_color === "" || 
        $vehicle_type === "" || $seat_count <= 0 || 
        $road_tax_expiry === "" || $insurance_expiry === ""
    ) {
        regError("Missing Fields", "Please fill in all required fields.");
    }

    // Expiry Validations
    $today = date('Y-m-d');
    if ($license_expiry < $today) regError("License Expired", "Your driving license has expired.");
    if ($road_tax_expiry < $today) regError("Road Tax Expired", "Your road tax has expired.");
    if ($insurance_expiry < $today) regError("Insurance Expired", "Your insurance has expired.");

    if (!str_contains($email, "@student.mmu.edu.my")) {
        regError("Invalid Email", "Please use your MMU student email (@student.mmu.edu.my).");
    }

    // 3. Check duplicate email
    $check = $conn->prepare("SELECT driver_id FROM drivers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        regError("Email Exists", "This email is already registered.");
    }
    $check->close();

    // 4. Insert Driver
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);
    
    $insert_driver = $conn->prepare("
        INSERT INTO drivers (full_name, identification_id, email, phone_number, gender, license_number, license_expiry, password, verification_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $insert_driver->bind_param("ssssssss", $full_name, $identification_id, $email, $phone_number, $gender, $license_number, $license_expiry, $password_hash);

    if ($insert_driver->execute()) {
        $driver_id = $conn->insert_id;
        $insert_driver->close();

        // 5. Insert Vehicle
        $insert_vehicle = $conn->prepare("
            INSERT INTO vehicles (driver_id, vehicle_model, plate_number, vehicle_color, vehicle_type, seat_count, road_tax_expiry, insurance_expiry) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert_vehicle->bind_param("issssi ss", $driver_id, $car_model, $car_plate_number, $vehicle_color, $vehicle_type, $seat_count, $road_tax_expiry, $insurance_expiry);
        
        if ($insert_vehicle->execute()) {
            $insert_vehicle->close();
            $_SESSION['swal_title'] = "Registration Successful";
            $_SESSION['swal_msg']   = "Account created! Please wait for Admin approval.";
            $_SESSION['swal_type']  = "success";
            header("Location: driver_login.php");
            exit;
        } else {
            regError("Vehicle Error", "Failed to save vehicle details.");
        }

    } else {
        regError("System Error", "Database insertion failed: " . $conn->error);
    }
}

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* =========================
       PAGE STYLES & ANIMATIONS
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

    body {
        background-color: var(--bg-surface);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
    }

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

    .reg-wrapper {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 20px 20px 80px; 
        position: relative; 
    }

    /* [UPDATED] Back Button - Matches Login Page */
    .back-nav { 
        position: absolute; 
        top: 10px; 
        left: 10%; 
        z-index: 50; 
        animation: fadeInUp 0.8s ease-out both; 
    }
    
    .btn-back { 
        height: 42px; 
        border: none; 
        border-radius: 50px !important; 
        background: rgba(255,255,255,0.9); 
        color: var(--brand-primary); 
        font-weight: 600; 
        cursor: pointer; 
        transition: all 0.3s ease; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        text-decoration: none; 
        font-size: 14px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        backdrop-filter: blur(5px); 
        padding: 0 25px; 
        gap: 8px; 
    }
    
    .btn-back:hover { 
        background: var(--brand-primary); 
        color: #fff; 
        box-shadow: 0 8px 15px rgba(0, 75, 130, 0.2); 
        transform: translateY(-2px); 
    }

    /* Main Card */
    .reg-card {
        background: var(--card-bg);
        width: 100%; max-width: 900px;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        padding: 30px 40px;
        position: relative;
        animation: slideInRight 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
        border: 1px solid rgba(255,255,255,0.5);
        margin-top: 40px; 
    }
    .reg-card.exiting { animation: slideOutRight 0.4s ease-in both !important; }

    /* Header Styling */
    .reg-header { text-align: center; margin-bottom: 25px; }
    .reg-header h1 { 
        color: var(--brand-primary); font-size: 28px; font-weight: 800; 
        margin: 0 0 5px; letter-spacing: -0.5px; 
    }
    .reg-header p { color: var(--text-sub); font-size: 15px; margin: 0; }

    /* Form Section Dividers */
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-light);
    }
    .form-section:last-of-type { border-bottom: none; padding-bottom: 0; margin-bottom: 20px; }
    
    .section-head {
        display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
    }
    .section-icon {
        width: 32px; height: 32px; background: #eef4ff; color: var(--brand-primary);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 15px;
    }
    .section-title {
        font-size: 15px; font-weight: 700; color: var(--text-main);
        text-transform: uppercase; letter-spacing: 0.5px;
    }

    /* Grid System */
    .form-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
    }

    /* Inputs */
    .input-group { display: flex; flex-direction: column; }
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

    /* Gender Selector */
    .gender-options { display: flex; gap: 15px; }
    .gender-btn { flex: 1; position: relative; }
    .gender-btn input { position: absolute; opacity: 0; width: 0; height: 0; }
    .gender-btn span {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px; border: 2px solid var(--border-light);
        border-radius: 10px; font-weight: 600; color: var(--text-sub);
        cursor: pointer; transition: all 0.2s; background: white; font-size: 14px;
    }
    .gender-btn input:checked + span {
        border-color: var(--brand-primary);
        background: #eff6ff; color: var(--brand-primary);
    }
    .gender-btn:hover span { border-color: #cbd5e0; }

    /* Password Toggle */
    .password-wrapper { position: relative; }
    .toggle-pass {
        position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; cursor: pointer; transition: 0.2s;
    }
    .toggle-pass:hover { color: var(--brand-primary); }

    /* Submit Button */
    .btn-submit {
        width: 100%;
        background: var(--brand-primary);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px; font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 10px 20px rgba(0, 75, 130, 0.15);
        margin-top: 20px;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-submit:hover {
        background: var(--brand-hover);
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(0, 75, 130, 0.25);
    }

    .footer-text {
        text-align: center; margin-top: 25px; font-size: 14px; color: var(--text-sub);
    }
    .footer-text a {
        color: var(--brand-primary); font-weight: 700; text-decoration: none;
    }
    .footer-text a:hover { text-decoration: underline; }

    /* Responsive */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; gap: 16px; }
        .reg-card { padding: 30px 20px; }
        .back-nav { top: 15px; left: 5%; }
    }
</style>

<div class="reg-wrapper">
    <div class="back-nav">
        <a href="javascript:void(0)" onclick="backToLogin()" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <div class="reg-card" id="regCard">
        <div class="reg-header">
            <h1>Driver Registration</h1>
            <p>Become a partner. Start your journey with U-Transport.</p>
        </div>

        <form method="POST" action="" onsubmit="handleLoading(this)">
            
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon"><i class="fa-regular fa-user"></i></div>
                    <div class="section-title">Personal Details</div>
                </div>
                
                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label">Full Name</label>
                        <input type="text" name="full_name" class="input-field" placeholder="Full Name as per IC" value="<?php echo htmlspecialchars($val_name); ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label class="input-label">Student ID / IC</label>
                        <input type="text" name="identification_id" class="input-field" placeholder="e.g. 121110xxxx" value="<?php echo htmlspecialchars($val_id); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Email Address (MMU)</label>
                        <input type="email" name="email" class="input-field" placeholder="@student.mmu.edu.my" value="<?php echo htmlspecialchars($val_email); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Phone Number</label>
                        <input type="tel" name="phone_number" class="input-field" placeholder="e.g. 012-3456789" value="<?php echo htmlspecialchars($val_phone); ?>" required>
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label class="input-label">Gender</label>
                        <div class="gender-options">
                            <label class="gender-btn">
                                <input type="radio" name="gender" value="Male" <?php if(isset($val_gender) && $val_gender=='Male') echo 'checked'; ?> required>
                                <span><i class="fa-solid fa-mars"></i> Male</span>
                            </label>
                            <label class="gender-btn">
                                <input type="radio" name="gender" value="Female" <?php if(isset($val_gender) && $val_gender=='Female') echo 'checked'; ?> required>
                                <span><i class="fa-solid fa-venus"></i> Female</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="section-title">License & Security</div>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label">License Number</label>
                        <input type="text" name="license_number" class="input-field" placeholder="License No." value="<?php echo htmlspecialchars($val_license); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">License Expiry Date</label>
                        <input type="date" name="license_expiry" class="input-field" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($val_lic_exp); ?>" required>
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label class="input-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="regPass" class="input-field" placeholder="Min 6 characters" minlength="6" required>
                            <i class="fa-solid fa-eye-slash toggle-pass" onclick="togglePass('regPass', this)"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon"><i class="fa-solid fa-car-side"></i></div>
                    <div class="section-title">Vehicle Information</div>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label class="input-label">Vehicle Model</label>
                        <input type="text" name="car_model" class="input-field" placeholder="e.g. Perodua Bezza" value="<?php echo htmlspecialchars($val_car_model); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Plate Number</label>
                        <input type="text" name="car_plate_number" class="input-field" placeholder="e.g. VAB 1234" value="<?php echo htmlspecialchars($val_car_plate); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Vehicle Color</label>
                        <input type="text" name="vehicle_color" class="input-field" placeholder="e.g. Silver" value="<?php echo htmlspecialchars($val_car_color); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Vehicle Type</label>
                        <select name="vehicle_type" class="input-field" required>
                            <option value="" disabled <?php if(!$val_car_type) echo 'selected'; ?>>Select Type</option>
                            <option value="Sedan" <?php if(isset($val_car_type) && $val_car_type=='Sedan') echo 'selected'; ?>>Sedan</option>
                            <option value="Hatchback" <?php if(isset($val_car_type) && $val_car_type=='Hatchback') echo 'selected'; ?>>Hatchback</option>
                            <option value="SUV" <?php if(isset($val_car_type) && $val_car_type=='SUV') echo 'selected'; ?>>SUV</option>
                            <option value="MPV" <?php if(isset($val_car_type) && $val_car_type=='MPV') echo 'selected'; ?>>MPV</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Seat Count (Pax)</label>
                        <input type="number" name="seat_count" class="input-field" placeholder="e.g. 4" min="1" max="8" value="<?php echo htmlspecialchars($val_seats); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Road Tax Expiry</label>
                        <input type="date" name="road_tax_expiry" class="input-field" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($val_road_tax); ?>" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Insurance Expiry</label>
                        <input type="date" name="insurance_expiry" class="input-field" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($val_insurance); ?>" required>
                    </div>
                </div>
            </div>

            <button type="submit" name="register" class="btn-submit">
                Submit Application <i class="fa-solid fa-arrow-right"></i>
            </button>

            <div class="footer-text">
                Already registered? <a href="javascript:void(0)" onclick="backToLogin()">Sign in instead</a>
            </div>
        </form>
    </div>
</div>

<script>
    function backToLogin() {
        const card = document.getElementById('regCard');
        card.classList.add('exiting');
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
            icon.style.color = "#94a3b8";
        }
    }

    function handleLoading(form) {
        const btn = form.querySelector('.btn-submit');
        if (btn.disabled) return false;
        
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }, 15000);
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