<?php
// =========================================
// BACKEND LOGIC
// =========================================
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

include "db_connect.php";
include "function.php";

// Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// --- LOGIC: Check if user is JOINING a ride ---
$pre_driver_id = isset($_GET['join_driver']) ? $_GET['join_driver'] : "";
$pre_date      = isset($_GET['join_date']) ? $_GET['join_date'] : "";
$pre_dest      = isset($_GET['join_dest']) ? $_GET['join_dest'] : ""; 

// 1. 获取 URL 传过来的州属 (join_state)
// 如果 URL 里没有传 join_state，尝试从 pre_dest (例如 "Johor, Kulai...") 里面截取第一个词
$pre_state = isset($_GET['join_state']) ? $_GET['join_state'] : "";

if(empty($pre_state) && !empty($pre_dest)) {
    // 简单的容错逻辑：假设地址格式是 "State, City..."
    $parts = explode(',', $pre_dest);
    $pre_state = trim($parts[0]); 
}

$is_join_mode  = !empty($pre_driver_id);

$swal_type = ""; 
$swal_message = "";
$swal_redirect = "";

// 2. Handle Form Submission
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    
    // 如果是 Join 模式，State 是 disabled 的，POST 拿不到值，所以要从 hidden input 拿
    $state        = isset($_POST['state']) ? $_POST['state'] : "";
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    
    $raw_date     = $_POST['date_time'];
    $datetime     = date("Y-m-d H:i:s", strtotime($raw_date));
    
    $passengers   = $_POST['passengers'];
    $vehicle_type = $_POST['vehicle_type']; 
    $pickup       = $_POST['pickup']; 
    $remark       = $_POST['remark'];
    
    $target_driver = isset($_POST['target_driver_id']) ? $_POST['target_driver_id'] : NULL;

    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers) || empty($vehicle_type)){
        $swal_type = "warning";
        $swal_message = "Please fill in all required fields.";
    } else {
        $status = 'Pending'; 

        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssss", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status);

        if($stmt->execute()){
            $swal_type = "success";
            $swal_message = "Request submitted! Please wait for driver confirmation.";
            $swal_redirect = "passanger_rides.php";
        } else {
            $swal_type = "error";
            $swal_message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

include "header.php"; 
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
.request-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 800px; margin: 0 auto; background: #f5f7fb; }
.request-header-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; }
.request-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px 30px; margin-top: 20px; }
label { display: block; margin-bottom: 8px; font-size: 15px; font-weight: 600; color: #333; margin-top: 18px; }
input[type="text"], select { width: 100%; padding: 12px 14px; font-size: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; box-sizing: border-box; }

/* 禁用状态的样式 */
select:disabled, input:read-only { background-color: #f2f2f2; cursor: not-allowed; color: #555; }

.btn-submit { width: 100%; padding: 14px; background-color: #004b82; color: white; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 30px; }
.join-box { background-color: #e8f5e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #2e7d32; font-size: 14px; line-height: 1.6; }
</style>

<div class="request-wrapper">
    <div class="request-header-title">
        <h1><?php echo $is_join_mode ? "Join This Ride" : "Request Your Ride"; ?></h1>
    </div>

    <div class="request-card">
        <?php if($is_join_mode): ?>
            <div class="join-box">
                <strong><i class="fa-solid fa-check-circle"></i> You are joining a ride to:</strong><br>
                <span style="font-size: 15px; font-weight: 600; color: #1b5e20;"><?php echo htmlspecialchars($pre_dest); ?></span><br>
                <span style="color: #555;">Date: <?php echo htmlspecialchars($pre_date); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="requestForm">
            <input type="hidden" name="request" value="1">
            
            <?php if($is_join_mode): ?>
                <input type="hidden" name="target_driver_id" value="<?php echo $pre_driver_id; ?>">
                <input type="hidden" name="state" value="<?php echo htmlspecialchars($pre_state); ?>">
            <?php endif; ?>

            <label>Date & Time</label>
            <input type="text" name="date_time" id="datetimepicker" value="<?php echo $pre_date; ?>" <?php echo $is_join_mode ? 'readonly' : 'required'; ?>>

            <label><i class="fa-solid fa-location-dot"></i> Pick-up Point (MMU Campus)</label>
            <select name="pickup" id="pickupPoint" required>
                <option value="" disabled selected hidden>Choose pick-up spot</option>
                <option value="MMU Main Gate">MMU Main Gate (Front)</option>
                <option value="MMU Back Gate">MMU Back Gate (Back)</option>
                <option value="MMU Library">MMU Library</option>
                <option value="MMU FOL Building">MMU FOL Building</option>
                <option value="MMU FOB Building">MMU FOB Building</option>
                <option value="MMU Female Hostel">MMU Female Hostel</option>
                <option value="MMU Male Hostel">MMU Male Hostel</option>
            </select>

            <hr style="margin: 30px 0; border: 0; border-top: 1px dashed #e2e8f0;">

            <label><i class="fa-solid fa-map-location-dot"></i> Destination State</label>
            <select name="state" id="stateSelect" required <?php echo $is_join_mode ? 'disabled' : ''; ?>>
                <option value="" disabled selected hidden>Select state</option>
                <option value="Johor" <?php echo ($pre_state == 'Johor') ? 'selected' : ''; ?>>Johor</option>
                <option value="Melaka" <?php echo ($pre_state == 'Melaka') ? 'selected' : ''; ?>>Melaka</option>
                <option value="Kuala Lumpur/Selangor" <?php echo ($pre_state == 'Kuala Lumpur/Selangor') ? 'selected' : ''; ?>>Kuala Lumpur / Selangor</option>
            </select>

            <label><i class="fa-solid fa-city"></i> City / Region</label>
            <select name="region" id="regionSelect" required>
                <option value="" disabled selected hidden>Select Region / City</option>
            </select>

            <label>Specific Destination Address</label>
            <input type="text" name="address" required placeholder="e.g., No 123, Jalan Universiti">

            <label>Number of Passengers</label>
            <select name="passengers" id="passengerSelect" required>
                <option value="" disabled selected hidden>Select Pax</option>
                <option value="1">1 Passenger</option>
                <option value="2">2 Passengers</option>
                <option value="3">3 Passengers</option>
                <option value="4">4 Passengers</option>
            </select>

            <label>Vehicle Category</label>
            <select name="vehicle_type" id="vehicleSelect" required>
                <option value="" disabled selected hidden>Select Vehicle Type</option>
                <option value="Hatchback" class="small-car">Hatchback (Max 4 Pax)</option>
                <option value="Sedan" class="small-car">Sedan (Max 4 Pax)</option>
                <option value="SUV" class="small-car">SUV (Max 4 Pax)</option>
                <option value="MPV">MPV (Max 6 Pax)</option>
            </select>

            <label>Remarks (Optional)</label>
            <input type="text" name="remark" placeholder="Any special requests?">

            <button type="submit" class="btn-submit">Confirm Booking</button>
        </form>
    </div>
</div>

<script>
    const stateSelect = document.getElementById('stateSelect');
    const regionSelect = document.getElementById('regionSelect');
    const regions = {
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"],
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"],
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Subang Jaya", "Cyberjaya", "Putrajaya", "Seremban", "Nilai"]
    };

    // 函数：根据 State 加载对应的 Region
    function updateRegions(stateValue) {
        // 先清空 Region 选项
        regionSelect.innerHTML = '<option value="" disabled selected hidden>Select Region / City</option>';
        
        if (stateValue && regions[stateValue]) {
            // 解锁 Region 选择框（如果有必要）
            regionSelect.disabled = false;
            
            // 循环添加城市
            regions[stateValue].forEach(city => {
                const opt = document.createElement('option');
                opt.value = city; 
                opt.textContent = city; 
                regionSelect.appendChild(opt);
            });
        } else {
            // 如果没有选 State，Region 保持禁用状态（非 Join 模式下）
            // 注意：Join 模式下我们希望 Region 始终可选，只要 State 有值
        }
    }

    // --- 关键逻辑：页面加载完成时执行 ---
    // 如果页面一加载，State 已经有值了（因为 Join 模式被 PHP 选中并 Lock 了），
    // 立即触发 updateRegions，把该州的城市加载出来。
    window.addEventListener('DOMContentLoaded', () => {
        const currentState = stateSelect.value;
        if (currentState) {
            updateRegions(currentState);
        }
    });

    // 监听 State 改变（针对非 Join 模式，或者 Join 模式出错没有 lock 住的情况）
    stateSelect.addEventListener('change', function() {
        updateRegions(this.value);
    });

    // 日期选择器
    flatpickr("#datetimepicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i", 
        minDate: "today",        
        time_24hr: false,
        altInput: true,          
        altFormat: "F j, Y at h:i K"
    });

    // 表单提交前确认
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 简单校验
        const dateRawValue = document.querySelector('input[name="date_time"]').value;
        if (!dateRawValue) {
            Swal.fire({ icon: 'warning', title: 'Required Field', text: 'Please select a Date & Time.' });
            return;
        }

        Swal.fire({
            title: 'Confirm Request?',
            text: 'Are you sure you want to submit this request?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            confirmButtonText: 'Yes, Submit'
        }).then((result) => {
            if (result.isConfirmed) { e.target.submit(); }
        });
    });

    <?php if ($swal_message != ""): ?>
        Swal.fire({
            title: "<?php echo ($swal_type == 'success') ? 'Success!' : 'Notice'; ?>",
            text: "<?php echo $swal_message; ?>",
            icon: "<?php echo $swal_type; ?>",
            confirmButtonColor: '#004b82'
        }).then((result) => {
            <?php if ($swal_redirect != ""): ?>
                window.location.href = "<?php echo $swal_redirect; ?>";
            <?php endif; ?>
        });
    <?php endif; ?>
</script>
<?php include "footer.php"; ?>