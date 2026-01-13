<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// Logic for joining an existing ride if parameters are passed via GET
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
    $state        = $_POST['state'];
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    
    $datetime     = $_POST['date_time'];
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
        // Ensure 'fare' column exists in your 'bookings' table
        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status, fare) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssssd", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status, $fare);

        if($stmt->execute()){
            $swal_type = "success";
            $swal_message = "Booking request submitted! Total: RM " . $fare;
            $swal_redirect = "passanger_rides.php";
        } else {
            $swal_type = "error";
            $swal_message = "Database Error: " . $conn->error;
        }
        $stmt->close();
    }
}

include "header.php"; 
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .request-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 800px; margin: 0 auto; background: #f5f7fb; font-family: 'Inter', sans-serif; }
    .request-header-title h1 { margin: 0; font-size: 26px; font-weight: 800; color: #004b82; }
    .request-header-title p { margin: 6px 0 0; font-size: 14px; color: #718096; }
    .request-card { background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 35px; margin-top: 25px; }
    
    label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 700; color: #2d3748; margin-top: 20px; }
    input[type="text"], select { width: 100%; padding: 14px; font-size: 15px; border: 1.5px solid #e2e8f0; border-radius: 12px; transition: all 0.2s; box-sizing: border-box; }
    input:focus, select:focus { border-color: #004b82; outline: none; box-shadow: 0 0 0 3px rgba(0,75,130,0.1); }

    /* Fare Box Styling */
    .fare-container { background: #f0fdf4; border: 2px solid #bbf7d0; padding: 25px; border-radius: 16px; margin-top: 30px; border-left: 8px solid #16a34a; }
    .fare-flex { display: flex; justify-content: space-between; align-items: flex-end; }
    .fare-label { font-weight: 800; color: #166534; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .price-breakdown { font-size: 13px; color: #718096; margin-top: 4px; display: none; }
    .original-strike { text-decoration: line-through; opacity: 0.7; }
    .surcharge-text { color: #e53e3e; font-weight: 700; margin-left: 5px; }
    .surcharge-badge { display: none; font-size: 11px; font-weight: 700; color: #c53030; background: #fff5f5; border: 1px solid #feb2b2; padding: 4px 10px; border-radius: 8px; margin-top: 8px; width: fit-content; }

    .fare-amount { font-size: 36px; font-weight: 900; color: #15803d; line-height: 1; }
    .rate-tag { display: inline-block; font-size: 13px; color: #065f46; font-weight: 700; background: #dcfce7; padding: 5px 12px; border-radius: 8px; margin-top: 10px; }

    .btn-submit { width: 100%; padding: 18px; background: #004b82; color: white; border: none; border-radius: 50px; font-size: 18px; font-weight: 700; cursor: pointer; margin-top: 30px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,75,130,0.2); }
    .btn-submit:hover { background: #003660; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,75,130,0.3); }
</style>

<div class="request-wrapper">
    <div class="request-header-title">
        <h1><?php echo $is_join_mode ? "Join This Ride" : "Request Your Ride"; ?></h1>
    </div>

    <div class="request-card">
        <form action="" method="POST" id="requestForm">
            <input type="hidden" name="request" value="1">
            
            <?php if($is_join_mode): ?>
                <input type="hidden" name="target_driver_id" value="<?php echo $pre_driver_id; ?>">
                <label><i class="fa-regular fa-calendar-check"></i> Departure Time (Fixed)</label>
                <input type="text" name="date_time" value="<?php echo $pre_date; ?>" readonly style="background:#f8fafc; color:#64748b;">
            <?php else: ?>
                <label><i class="fa-regular fa-calendar"></i> Select Date & Time</label>
                <input type="text" name="date_time" id="datetimepicker" placeholder="When do you want to leave?" required>
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

            <label><i class="fa-solid fa-house-user"></i> Full Drop-off Address</label>
            <input type="text" name="address" id="fullAddress" required placeholder="House No, Street Name, Postal Code">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <label><i class="fa-solid fa-users"></i> Seats Needed</label>
                    <select name="passengers" id="passengerSelect" required>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Seat<?php echo ($i>1)?'s':''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label><i class="fa-solid fa-car-side"></i> Vehicle Type</label>
                    <select name="vehicle_type" id="vehicleSelect" required>
                        <option value="Sedan" class="std-car">Sedan</option>
                        <option value="Hatchback" class="std-car">Hatchback</option>
                        <option value="SUV" class="std-car">SUV</option>
                        <option value="MPV">MPV (Premium)</option>
                    </select>
                </div>
            </div>

            <label><i class="fa-regular fa-comment-dots"></i> Special Remarks</label>
            <input type="text" name="remark" placeholder="e.g. 2 large luggages, quiet ride">

            <div class="fare-container">
                <div class="fare-flex">
                    <div>
                        <span class="fare-label">Estimated Fare</span>
                        <div id="fareDetailWrap" class="price-breakdown">
                            Subtotal: <span class="original-strike">RM <span id="rawTotal">0.00</span></span>
                            <span class="surcharge-text">+ RM <span id="surchargeVal">0.00</span> (MPV)</span>
                        </div>
                        <span id="mpvBadge" class="surcharge-badge">Includes 25% MPV Surcharge</span>
                    </div>
                    <div style="text-align: right;">
                        <div class="fare-amount">RM <span id="displayFare">0.00</span></div>
                        <span class="rate-tag" id="rateInfo">Select destination</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Confirm Booking</button>
        </form>
    </div>
</div>

<script>
    // Initialize Calendar
    flatpickr("#datetimepicker", { enableTime: true, dateFormat: "Y-m-d H:i", minDate: "today" });

    const regions = {
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang"],
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Bukit Beruang", "Klebang"],
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Cyberjaya", "Shah Alam", "Putrajaya", "Petaling Jaya"]
    };

    const stateSel = document.getElementById('stateSelect');
    const regionSel = document.getElementById('regionSelect');
    const paxSel = document.getElementById('passengerSelect');
    const vehSel = document.getElementById('vehicleSelect');
    const stdCars = document.querySelectorAll('.std-car');

    // Handle State Selection
    stateSel.addEventListener('change', function() {
        regionSel.innerHTML = '<option value="" disabled selected hidden>Select region</option>';
        regionSel.disabled = false;
        regions[this.value].forEach(city => {
            const opt = document.createElement('option');
            opt.value = city; opt.innerText = city; regionSel.appendChild(opt);
        });
        updatePrice();
    });

    // Handle Passenger vs Vehicle Logic
    paxSel.addEventListener('change', function() {
        if (parseInt(this.value) > 4) {
            stdCars.forEach(o => { o.disabled = true; o.style.color = '#ccc'; });
            vehSel.value = "MPV";
        } else {
            stdCars.forEach(o => { o.disabled = false; o.style.color = '#333'; });
        }
        updatePrice();
    });

    vehSel.addEventListener('change', updatePrice);

    function updatePrice() {
        const state = stateSel.value;
        const pax = parseInt(paxSel.value);
        const vehicle = vehSel.value;
        
        let rate = 0;
        if (state === "Melaka") rate = 12.00;
        else if (state === "Kuala Lumpur/Selangor") rate = 60.00;
        else if (state === "Johor") rate = 85.00;

        let baseTotal = rate * pax;
        let finalTotal = baseTotal;
        let surcharge = 0;

        if (vehicle === "MPV") {
            finalTotal = baseTotal * 1.25;
            surcharge = finalTotal - baseTotal;
            document.getElementById('fareDetailWrap').style.display = "block";
            document.getElementById('mpvBadge').style.display = "block";
            document.getElementById('rawTotal').innerText = baseTotal.toFixed(2);
            document.getElementById('surchargeVal').innerText = surcharge.toFixed(2);
        } else {
            document.getElementById('fareDetailWrap').style.display = "none";
            document.getElementById('mpvBadge').style.display = "none";
        }

        document.getElementById('displayFare').innerText = finalTotal.toFixed(2);
        document.getElementById('hiddenFare').value = finalTotal.toFixed(2);
        if(state) document.getElementById('rateInfo').innerText = "Rate: RM " + rate.toFixed(2) + " / seat";
    }

    // FINAL CONFIRMATION POPUP WITH DETAILED ADDRESS
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fare = document.getElementById('hiddenFare').value;
        const pax = paxSel.value;
        const state = stateSel.value;
        const region = regionSel.value;
        const address = document.getElementById('fullAddress').value; // Get the specific address
        const pickup = document.getElementById('pickupPoint').value;
        const vehicle = vehSel.value;
        
        let rate = (state === "Melaka") ? 12.00 : (state === "Johor" ? 85.00 : 60.00);
        let base = (rate * pax).toFixed(2);
        let extra = (fare - base).toFixed(2);

        Swal.fire({
            title: 'Confirm Your Booking?',
            html: `
                <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 15px; font-size: 14px; border: 1px solid #e2e8f0; line-height: 1.6;">
                    <p style="margin-bottom:12px;">
                        <i class="fa-solid fa-location-dot" style="color:#004b82; width:20px;"></i> <b>Pick-up:</b><br>
                        <span style="color: #64748b; margin-left: 24px;">${pickup} (MMU Melaka)</span>
                    </p>
                    <p style="margin-bottom:12px;">
                        <i class="fa-solid fa-map-marker-alt" style="color:#e53e3e; width:20px;"></i> <b>Destination Address:</b><br>
                        <span style="color: #64748b; margin-left: 24px;">${address}, ${region}, ${state}</span>
                    </p>
                    <p style="margin-bottom:12px;">
                        <i class="fa-solid fa-user-group" style="color:#004b82; width:20px;"></i> <b>Booking Details:</b><br>
                        <span style="color: #64748b; margin-left: 24px;">${pax} Person(s) - ${vehicle}</span>
                    </p>
                    <hr style="margin:12px 0; border:0; border-top:1px solid #cbd5e1;">
                    <p><b>Standard Subtotal:</b> RM ${base}</p>
                    ${vehicle === "MPV" ? `<p style="color:#e53e3e;"><b>MPV Surcharge (25%):</b> + RM ${extra}</p>` : ''}
                    <p style="font-size: 22px; color: #15803d; margin-top:12px;"><b>Total Fare: RM ${fare}</b></p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            confirmButtonText: 'Submit Request',
            cancelButtonText: 'Cancel'
        }).then((res) => { if (res.isConfirmed) e.target.submit(); });
    });
</script>

<?php 
// Show post-submission alerts if any
if($swal_type) {
    echo "<script>Swal.fire({ title: '".ucfirst($swal_type)."', text: '$swal_message', icon: '$swal_type' }).then(() => { window.location.href='$swal_redirect'; });</script>";
}
include "footer.php"; 
?>
