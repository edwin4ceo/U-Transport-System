<?php
// FUNCTION: START SESSION
session_start();

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// 1. CHECK LOGIN STATUS
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// ==========================================
// JOIN RIDE LOGIC & DATA PREPARATION
// ==========================================

$pre_driver_id = isset($_GET['join_driver']) ? $_GET['join_driver'] : "";
$pre_date      = isset($_GET['join_date']) ? $_GET['join_date'] : "";
$pre_dest      = isset($_GET['join_dest']) ? $_GET['join_dest'] : ""; 
$pre_state     = isset($_GET['join_state']) ? $_GET['join_state'] : "";
$pre_vehicle   = isset($_GET['join_vehicle']) ? $_GET['join_vehicle'] : "";

if(empty($pre_state) && !empty($pre_dest)) {
    $parts = explode(',', $pre_dest);
    $pre_state = trim($parts[0]); 
}

$is_join_mode = !empty($pre_driver_id);

// --- LOGIC: Calculate Available Seats & Lock Vehicle ---
$locked_vehicle = "";
$available_seats = 4; 

if ($is_join_mode) {
    $locked_vehicle = $pre_vehicle;

    if(empty($locked_vehicle)){
        $stmt_check = $conn->prepare("SELECT vehicle_type FROM vehicles WHERE driver_id = ? LIMIT 1");
        $stmt_check->bind_param("s", $pre_driver_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if($row_check = $res_check->fetch_assoc()){
            $locked_vehicle = $row_check['vehicle_type'];
        }
        $stmt_check->close();
    }
    
    if(empty($locked_vehicle)) $locked_vehicle = "Sedan";

    $max_capacity = 4; 
    if ($locked_vehicle == 'MPV') {
        $max_capacity = 6;
    }

    $stmt_b = $conn->prepare("SELECT SUM(passengers) as total_booked FROM bookings WHERE driver_id = ? AND date_time = ? AND status IN ('Pending', 'Accepted')");
    $stmt_b->bind_param("ss", $pre_driver_id, $pre_date);
    $stmt_b->execute();
    $res_b = $stmt_b->get_result();
    $booked_count = 0;
    if ($row_b = $res_b->fetch_assoc()) {
        $booked_count = (int)$row_b['total_booked'];
    }
    $stmt_b->close();

    $available_seats = $max_capacity - $booked_count;
    if ($available_seats < 0) $available_seats = 0; 
}

$swal_type = ""; 
$swal_message = "";
$swal_redirect = "";

// 2. HANDLE FORM SUBMISSION
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    $state        = isset($_POST['state']) ? $_POST['state'] : "";
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    $datetime     = $_POST['date_time'];
    $passengers   = (int)$_POST['passengers']; 
    $vehicle_type = $_POST['vehicle_type']; 
    $pickup       = $_POST['pickup']; 
    $remark       = $_POST['remark'];
    $fare         = isset($_POST['hidden_fare']) ? $_POST['hidden_fare'] : "0.00";
    $target_driver = isset($_POST['target_driver_id']) ? $_POST['target_driver_id'] : NULL;

    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers) || empty($vehicle_type)){
        $swal_type = "warning";
        $swal_message = "Please fill in all required fields.";
    }
    elseif ($is_join_mode && $passengers > $available_seats) {
        $swal_type = "error";
        $swal_message = "Sorry, seat availability has changed. Only $available_seats seat(s) left.";
    } 
    elseif ($vehicle_type != 'MPV' && $passengers > 4) {
        $swal_type = "error";
        $swal_message = "Limit Exceeded: " . $vehicle_type . " can only accept max 4 passengers. Please select MPV.";
    }
    else {
        $status = 'Pending'; 
        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status, fare) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssd", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status, $fare);

        if($stmt->execute()){
            $swal_type = "success";
            $swal_message = "Booking request submitted!";
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* ========================================================= */
    /* 1. CRITICAL FIX: REMOVE DEFAULT HEADER WRAPPER STYLES     */
    /* ========================================================= */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* 2. PAGE ENTRANCE ANIMATION */
    @keyframes fadeInUpPage {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* 3. LAYOUT WRAPPER (Grey Background) */
    .request-wrapper { 
        min-height: calc(100vh - 160px); 
        padding: 40px 20px; 
        max-width: 800px; 
        margin: 0 auto; 
        background: #f5f7fb; 
        font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 4. HEADER TEXT */
    .request-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; text-align: center; }
    .request-header-title p { margin: 8px 0 30px; font-size: 15px; color: #64748b; text-align: center; }

    /* 5. MAIN FORM CARD */
    .request-card { 
        background: #ffffff;          
        border-radius: 24px;          
        border: 1px solid #e2e8f0;    
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        padding: 40px;                
    }
    
    /* 6. FORM ELEMENTS */
    label { 
        display: block; 
        margin-bottom: 8px; 
        font-size: 14px; 
        font-weight: 600; 
        color: #333; 
        margin-top: 20px; 
    }
    label:first-child { margin-top: 0; }
    
    /* Input Fields */
    input[type="text"], select, .date-input-field { 
        width: 100%; 
        height: 52px; 
        padding: 0 15px; 
        font-size: 15px; 
        border: 1.5px solid #e2e8f0; 
        border-radius: 12px; 
        transition: all 0.2s; 
        box-sizing: border-box; 
        display: flex; 
        align-items: center;
        background-color: #fff;
        color: #333;
        font-family: 'Poppins', sans-serif;
    }
    
    input:focus, select:focus { 
        border-color: #004b82; 
        outline: none; 
    }
    
    /* Read-only styling */
    input:read-only, select:disabled, .readonly-select { 
        background-color: #f8fafc; 
        color: #94a3b8; 
        cursor: not-allowed; 
        border-color: #e2e8f0; 
        pointer-events: none; 
    }

    /* 7. DATE PICKER STYLE */
    .date-picker-container { position: relative; width: 100%; }
    .date-input-field { cursor: pointer; justify-content: space-between; }
    .date-input-field:hover { border-color: #004b82; }
    .date-input-field span { color: #333; }
    .date-input-field i { color: #004b82; font-size: 18px; }

    /* Calendar Popup */
    .calendar-popup {
        display: none; position: absolute; top: 110%; left: 0; 
        width: 100%; max-width: 350px;
        background: #fff; border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        z-index: 1000; 
        border: 1px solid #e2e8f0; overflow: hidden;
    }
    .calendar-popup.active { display: block; }
    
    .calendar-header { background-color: #004b82; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
    .calendar-nav { cursor: pointer; font-size: 18px; padding: 5px 10px; user-select: none; }
    .current-date { font-size: 16px; font-weight: 700; }
    .calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); padding: 15px 15px 5px; text-align: center; color: #94a3b8; font-weight: 600; font-size: 13px; }
    .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); padding: 0 15px; grid-auto-rows: 40px; }
    .calendar-days div { display: flex; justify-content: center; align-items: center; cursor: pointer; border-radius: 50%; font-size: 14px; color: #333; margin: 2px; }
    .calendar-days div:hover { background-color: #f1f5f9; }
    
    /* --- MODIFIED CALENDAR STYLES FOR DISTINCTION --- */
    
    /* Selected Date: Solid Blue Circle */
    .calendar-days div.selected { 
        background-color: #004b82 !important; 
        color: #fff !important; 
        font-weight: bold; 
    }
    
    /* Today's Date: Hollow Blue Ring (Empty Circle) */
    .calendar-days div.today { 
        background-color: transparent; /* No Fill */
        border: 2px solid #004b82;     /* Blue Border */
        color: #004b82;                /* Blue Text */
        font-weight: bold; 
    }
    
    /* Disabled Dates */
    .calendar-days div.disabled { color: #cbd5e1 !important; pointer-events: none; }

    /* Time Picker */
    .time-picker-simple {
        border-top: 1px solid #e2e8f0; 
        padding: 12px 15px 5px 15px; /* Reduced bottom padding */
        display: flex; justify-content: center; align-items: center; gap: 8px; background: #f8fafc;
    }
    .time-select-simple {
        padding: 0 10px; height: 40px; border: 1px solid #cbd5e0;
        border-radius: 8px; background: white; font-size: 15px;
        color: #2d3748; cursor: pointer; outline: none; width: auto;
    }
    
    /* Alignment Fix for Colon */
    .time-separator { 
        font-weight: 700; 
        color: #4a5568; 
        font-size: 20px; 
        height: 40px; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        transform: translateY(-5px); 
        padding-bottom: 2px;
    }

    /* DONE BUTTON */
    .btn-calendar-done {
        width: 100%;
        padding: 12px;
        background-color: #004b82;
        color: white;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 0; 
    }
    .btn-calendar-done:hover {
        background-color: #003660;
    }

    /* 8. JOIN INFO BOX */
    .join-box { 
        background-color: #f0fdf4; border: 1px solid #bbf7d0; 
        padding: 20px; border-radius: 12px; margin-bottom: 30px; 
        font-size: 14px; line-height: 1.6; 
    }

    /* 9. FARE ESTIMATOR CARD */
    .fare-container { 
        background: #f8fafc; 
        border: 1px solid #e2e8f0; 
        padding: 25px; border-radius: 16px; margin-top: 40px; 
        border-left: 6px solid #16a34a; 
    }
    .fare-flex { display: flex; justify-content: space-between; align-items: flex-end; }
    .fare-label { font-weight: 700; color: #166534; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .price-breakdown { font-size: 13px; color: #64748b; margin-top: 4px; display: none; }
    .original-strike { text-decoration: line-through; opacity: 0.7; }
    .surcharge-text { color: #e53e3e; font-weight: 700; margin-left: 5px; }
    .surcharge-badge { display: none; font-size: 11px; font-weight: 700; color: #c53030; background: #fff5f5; border: 1px solid #feb2b2; padding: 4px 10px; border-radius: 8px; margin-top: 8px; width: fit-content; }

    .fare-amount { font-size: 32px; font-weight: 800; color: #15803d; line-height: 1; }
    .rate-tag { display: inline-block; font-size: 13px; color: #065f46; font-weight: 600; background: #dcfce7; padding: 5px 12px; border-radius: 8px; margin-top: 10px; }

    /* 10. SUBMIT BUTTON */
    .btn-submit { 
        width: 100%; padding: 16px; 
        background: #004b82; color: white; 
        border: none; border-radius: 50px; 
        font-size: 16px; font-weight: 600; 
        cursor: pointer; margin-top: 30px; 
        transition: all 0.3s ease; 
        box-shadow: 0 4px 15px rgba(0, 75, 130, 0.2);
    }
    .btn-submit:hover { 
        background: #003660; 
        transform: translateY(-2px); 
        box-shadow: 0 6px 20px rgba(0, 75, 130, 0.3); 
    }
</style>

<div class="request-wrapper">
    
    <div class="request-header-title">
        <h1><?php echo $is_join_mode ? "Join This Ride" : "Request Your Ride"; ?></h1>
        <p>Door-to-door transport from MMU Melaka</p>
    </div>

    <div class="request-card">
        
        <?php if($is_join_mode): ?>
            <div class="join-box">
                <strong style="color: #166534; display:block; margin-bottom:5px;"><i class="fa-solid fa-check-circle"></i> You are joining a ride to:</strong>
                <span style="font-size: 15px; font-weight: 600; color: #333;"><?php echo htmlspecialchars($pre_dest); ?></span><br>
                <span style="color: #64748b;">Date: <?php echo htmlspecialchars($pre_date); ?></span><br>
                
                <?php if($available_seats > 0): ?>
                    <span style="color: #ea580c; font-weight:600; margin-top:5px; display:inline-block;">
                        <i class="fa-solid fa-chair"></i> Seats Remaining: <?php echo $available_seats; ?>
                    </span>
                <?php else: ?>
                    <span style="color: #dc2626; font-weight:600; margin-top:5px; display:inline-block;">
                        <i class="fa-solid fa-circle-xmark"></i> This ride is FULL
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="requestForm">
            <input type="hidden" name="request" value="1">
            <input type="hidden" name="hidden_fare" id="hiddenFare" value="0.00">
            
            <?php if($is_join_mode): ?>
                <input type="hidden" name="target_driver_id" value="<?php echo $pre_driver_id; ?>">
                <input type="hidden" name="state" value="<?php echo htmlspecialchars($pre_state); ?>">
                <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($locked_vehicle); ?>">
                
                <?php if($available_seats == 1): ?>
                    <input type="hidden" name="passengers" value="1">
                <?php endif; ?>
            <?php endif; ?>

            <label>Date & Time</label>
            <div class="date-picker-container">
                <div class="date-input-field <?php echo !empty($pre_date) ? 'has-value' : ''; ?>" id="customDateTrigger" onclick="toggleCalendar()">
                    <span id="selected-date-text">
                        <?php 
                            if(!empty($pre_date)) { echo date("d M Y, h:i A", strtotime($pre_date)); } 
                            else { echo "Select Date & Time"; }
                        ?>
                    </span>
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                
                <input type="hidden" name="date_time" id="real_datetime_input" value="<?php echo $pre_date; ?>">

                <div class="calendar-popup" id="calendar-popup">
                    <div class="calendar-header">
                        <span class="calendar-nav" onclick="changeMonth(-1)">&#10094;</span>
                        <div class="current-date">
                            <span id="month-display">Month</span> <span id="year-display">Year</span>
                        </div>
                        <span class="calendar-nav" onclick="changeMonth(1)">&#10095;</span>
                    </div>
                    
                    <div class="calendar-weekdays">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    <div class="calendar-days" id="calendar-days"></div>

                    <div class="time-picker-simple">
                        <select id="hourSelect" class="time-select-simple" onchange="updateDateTimeValue()">
                            <?php for($i=1; $i<=12; $i++) echo "<option value='$i'>".str_pad($i,2,"0",STR_PAD_LEFT)."</option>"; ?>
                        </select>
                        
                        <div class="time-separator">:</div>
                        
                        <select id="minuteSelect" class="time-select-simple" onchange="updateDateTimeValue()">
                            <?php 
                            for($m=0; $m<60; $m+=5) {
                                $minLabel = str_pad($m, 2, "0", STR_PAD_LEFT);
                                echo "<option value='$minLabel'>$minLabel</option>";
                            } 
                            ?>
                        </select>
                        
                        <select id="ampmSelect" class="time-select-simple" onchange="updateDateTimeValue()">
                            <option value="AM">AM</option>
                            <option value="PM" selected>PM</option>
                        </select>
                    </div>

                    <button type="button" class="btn-calendar-done" onclick="closeCalendar(event)">Done</button>
                </div>
            </div>

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
                    <?php if($is_join_mode): ?>
                        <select name="passengers" id="passengerSelect" required <?php echo ($available_seats == 1) ? 'class="readonly-select"' : ''; ?> <?php echo ($available_seats <= 0) ? 'disabled' : ''; ?>>
                            <?php 
                            if($available_seats > 0) {
                                for($i=1; $i<=$available_seats; $i++) {
                                    echo "<option value='$i'>$i Seat" . ($i>1?'s':'') . "</option>";
                                }
                            } else {
                                echo "<option disabled selected>Full</option>";
                            }
                            ?>
                        </select>
                    <?php else: ?>
                        <select name="passengers" id="passengerSelect" required>
                            <?php for($i=1; $i<=6; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> Seat<?php echo ($i>1)?'s':''; ?></option>
                            <?php endfor; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div>
                    <label><i class="fa-solid fa-car-side"></i> Vehicle Type</label>
                    <select name="vehicle_type" id="vehicleSelect" required <?php echo $is_join_mode ? 'disabled' : ''; ?>>
                        <?php if($is_join_mode): ?>
                            <option value="<?php echo $locked_vehicle; ?>" selected><?php echo $locked_vehicle; ?></option>
                        <?php else: ?>
                            <option value="Sedan" class="std-car">Sedan</option>
                            <option value="Hatchback" class="std-car">Hatchback</option>
                            <option value="SUV" class="std-car">SUV</option>
                            <option value="MPV">MPV (Premium)</option>
                        <?php endif; ?>
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

            <?php if(!$is_join_mode || $available_seats > 0): ?>
                <button type="submit" class="btn-submit">Confirm Booking</button>
            <?php else: ?>
                <button type="button" class="btn-submit" style="background-color:#ccc; cursor:not-allowed;">Ride Full</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    // --- CUSTOM CALENDAR SCRIPT ---
    const dateText = document.getElementById("selected-date-text");
    const dateInput = document.getElementById("real_datetime_input");
    const calendarPopup = document.getElementById("calendar-popup");
    const daysContainer = document.getElementById("calendar-days");
    const monthDisplay = document.getElementById("month-display");
    const yearDisplay = document.getElementById("year-display");
    
    let initialVal = dateInput.value;
    let currDate = new Date();
    let selectedDay = null;
    let selHour = "12";
    let selMin = "00";
    let selAmPm = "PM";

    if(initialVal) {
        let d = new Date(initialVal);
        if(!isNaN(d.getTime())) {
            currDate = d;
            selectedDay = d.getDate();
            let h = d.getHours();
            let m = d.getMinutes();
            selAmPm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12; 
            selHour = h;
            selMin = m < 10 ? '0'+m : m;
        }
    }
    
    document.getElementById('hourSelect').value = selHour;
    let minSelect = document.getElementById('minuteSelect');
    let found = false;
    for(let i=0; i<minSelect.options.length; i++){
        if(minSelect.options[i].value == selMin){
            minSelect.selectedIndex = i;
            found = true;
            break;
        }
    }
    if(!found) minSelect.value = "00";
    document.getElementById('ampmSelect').value = selAmPm;

    let currMonth = currDate.getMonth();
    let currYear = currDate.getFullYear();
    let todayDate = new Date(); todayDate.setHours(0,0,0,0);
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const isJoinMode = <?php echo $is_join_mode ? 'true' : 'false'; ?>;

    function renderCalendar() {
        let firstDay = new Date(currYear, currMonth, 1).getDay(); 
        let lastDate = new Date(currYear, currMonth + 1, 0).getDate();
        
        daysContainer.innerHTML = "";
        monthDisplay.innerText = months[currMonth];
        yearDisplay.innerText = currYear;

        for (let i = 0; i < firstDay; i++) {
            const div = document.createElement("div");
            daysContainer.appendChild(div);
        }

        for (let i = 1; i <= lastDate; i++) {
            const div = document.createElement("div");
            div.innerText = i;
            let checkDate = new Date(currYear, currMonth, i);
            checkDate.setHours(0,0,0,0); 

            if (checkDate < todayDate) {
                div.classList.add("disabled"); 
            } else {
                if (i === new Date().getDate() && currMonth === new Date().getMonth() && currYear === new Date().getFullYear()) div.classList.add("today");
                if (selectedDay && i === selectedDay && currMonth === currDate.getMonth() && currYear === currDate.getFullYear()) div.classList.add("selected");
                
                if(!isJoinMode) {
                    div.onclick = (e) => { e.stopPropagation(); selectDay(i); };
                }
            }
            daysContainer.appendChild(div);
        }
    }

    function selectDay(day) {
        selectedDay = day;
        currDate = new Date(currYear, currMonth, day);
        renderCalendar();
        updateDateTimeValue();
    }

    function changeMonth(dir) {
        event.stopPropagation();
        if(isJoinMode) return;
        currMonth += dir;
        if (currMonth < 0) { currMonth = 11; currYear--; } 
        else if (currMonth > 11) { currMonth = 0; currYear++; }
        renderCalendar();
    }

    function updateDateTimeValue() {
        if (!selectedDay) return;
        const h = document.getElementById('hourSelect').value;
        const m = document.getElementById('minuteSelect').value;
        const ap = document.getElementById('ampmSelect').value;
        const monthShort = months[currMonth].substring(0,3);
        const display = `${selectedDay} ${monthShort} ${currYear}, ${String(h).padStart(2,'0')}:${m} ${ap}`;
        dateText.innerText = display;
        dateText.style.color = "#333";
        document.getElementById("customDateTrigger").classList.add("has-value");

        let h24 = parseInt(h);
        if (ap === "PM" && h24 < 12) h24 += 12;
        if (ap === "AM" && h24 === 12) h24 = 0;
        let mm = String(currMonth + 1).padStart(2, '0');
        let dd = String(selectedDay).padStart(2, '0');
        let hh = String(h24).padStart(2, '0');
        dateInput.value = `${currYear}-${mm}-${dd} ${hh}:${m}`;
    }

    function toggleCalendar() {
        if(isJoinMode) return;
        event.stopPropagation();
        calendarPopup.classList.toggle("active");
        if(calendarPopup.classList.contains("active")) renderCalendar();
    }

    // NEW: Close Calendar Function for the Done Button
    function closeCalendar(e) {
        e.stopPropagation();
        calendarPopup.classList.remove("active");
    }

    document.addEventListener('click', function(e) {
        const wrap = document.querySelector('.date-picker-container');
        if (wrap && !wrap.contains(e.target)) calendarPopup.classList.remove('active');
    });

    window.onload = function() {
        renderCalendar();
        if(initialVal) updateDateTimeValue();
        if(stateSel.value) updateRegions(stateSel.value);
    };

    // --- FARE & REGION LOGIC ---
    const regions = {
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"],
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Bukit Beruang", "Klebang"],
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Cyberjaya", "Shah Alam", "Putrajaya", "Petaling Jaya"]
    };
    const stateSel = document.getElementById('stateSelect');
    const regionSel = document.getElementById('regionSelect');
    const paxSel = document.getElementById('passengerSelect');
    const vehSel = document.getElementById('vehicleSelect');
    const stdCars = document.querySelectorAll('.std-car');

    function updateRegions(val) {
        regionSel.innerHTML = '<option value="" disabled selected hidden>Select region</option>';
        if (val && regions[val]) {
            regionSel.disabled = false;
            regions[val].forEach(city => {
                const opt = document.createElement('option');
                opt.value = city; opt.innerText = city; regionSel.appendChild(opt);
            });
        }
        updatePrice();
    }

    stateSel.addEventListener('change', function() { updateRegions(this.value); });

    paxSel.addEventListener('change', function() {
        if (!vehSel.disabled) {
            if (parseInt(this.value) > 4) {
                stdCars.forEach(o => { o.disabled = true; o.style.color = '#ccc'; });
                vehSel.value = "MPV";
            } else {
                stdCars.forEach(o => { o.disabled = false; o.style.color = '#333'; });
            }
        }
        updatePrice();
    });

    vehSel.addEventListener('change', updatePrice);

    function updatePrice() {
        const state = stateSel.value;
        const pax = parseInt(paxSel.value) || 0;
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

    document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fare = document.getElementById('hiddenFare').value;
        const pax = paxSel.value;
        const state = stateSel.value;
        const region = regionSel.value;
        const address = document.getElementById('fullAddress').value; 
        const pickup = document.getElementById('pickupPoint').value;
        const vehicle = vehSel.value;
        
        if(!document.getElementById('real_datetime_input').value) {
            Swal.fire({ title: 'Date & Time Required', text: 'Please select when you want to leave.', icon: 'warning', confirmButtonColor: '#004b82' });
            return;
        }

        Swal.fire({
            title: 'Confirm Your Booking?',
            html: `
                <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 15px; font-size: 14px; border: 1px solid #e2e8f0; line-height: 1.6;">
                    <p style="margin-bottom:12px;"><i class="fa-solid fa-location-dot" style="color:#004b82; width:20px;"></i> <b>Pick-up:</b><br><span style="color: #64748b; margin-left: 24px;">${pickup} (MMU Melaka)</span></p>
                    <p style="margin-bottom:12px;"><i class="fa-solid fa-map-marker-alt" style="color:#e53e3e; width:20px;"></i> <b>Dest:</b><br><span style="color: #64748b; margin-left: 24px;">${address}, ${region}, ${state}</span></p>
                    <p style="margin-bottom:12px;"><i class="fa-solid fa-user-group" style="color:#004b82; width:20px;"></i> <b>Details:</b><br><span style="color: #64748b; margin-left: 24px;">${pax} Pax - ${vehicle}</span></p>
                    <hr style="margin:12px 0; border:0; border-top:1px solid #cbd5e1;">
                    <p style="font-size: 22px; color: #15803d; margin-top:12px;"><b>Total: RM ${fare}</b></p>
                </div>
            `,
            icon: 'info', showCancelButton: true, confirmButtonColor: '#004b82', confirmButtonText: 'Submit Request'
        }).then((res) => { if (res.isConfirmed) e.target.submit(); });
    });
</script>

<?php 
if($swal_type) {
    echo "<script>Swal.fire({ title: '".ucfirst($swal_type)."', text: '$swal_message', icon: '$swal_type', confirmButtonColor: '#004b82' }).then(() => { window.location.href='$swal_redirect'; });</script>";
}
include "footer.php"; 
?>