<?php
// =========================================
// BACKEND LOGIC (UNCHANGED)
// =========================================
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

include "db_connect.php";
include "function.php";

// Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// Initialize Variables
$pre_driver_id = isset($_GET['join_driver']) ? $_GET['join_driver'] : "";
$pre_date      = isset($_GET['join_date']) ? $_GET['join_date'] : "";
$pre_dest      = isset($_GET['join_dest']) ? $_GET['join_dest'] : "";
$is_join_mode  = !empty($pre_driver_id);

$swal_type = ""; 
$swal_message = "";
$swal_redirect = "";

// Handle Form Submission
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    
    $state        = $_POST['state'];
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    
    // Process Date Format
    $raw_date     = $_POST['date_time'];
    $datetime     = date("Y-m-d H:i:s", strtotime($raw_date));
    
    $passengers   = $_POST['passengers'];
    $vehicle_type = $_POST['vehicle_type']; 
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];
    
    $target_driver = !empty($_POST['target_driver_id']) ? $_POST['target_driver_id'] : NULL;

    // Validation
    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers) || empty($vehicle_type)){
        $swal_type = "warning";
        $swal_message = "Please fill in all required fields.";
    } else {
        $can_proceed = true;
        if (!empty($target_driver)) {
            $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE student_id = ? AND driver_id = ? AND date_time = ? AND status != 'Cancelled'");
            $check_stmt->bind_param("sis", $student_id, $target_driver, $datetime);
            $check_stmt->execute();
            $check_stmt->store_result();
            if($check_stmt->num_rows > 0){
                $swal_type = "warning";
                $swal_message = "You have already joined this ride! Please check My Rides.";
                $swal_redirect = "passanger_rides.php"; 
                $can_proceed = false;
            }
            $check_stmt->close();
            $status = 'Approved'; 
        } else {
            $status = 'Pending';
        }

        if($can_proceed){
            $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssss", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status);
            if($stmt->execute()){
                $swal_type = "success";
                $swal_message = ($status == 'Approved') ? "Success! You have officially joined the ride." : "Request submitted! Waiting for driver confirmation.";
                $swal_redirect = "passanger_rides.php";
            } else {
                $swal_type = "error";
                $swal_message = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include "header.php"; 
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* --- Page Layout Styles --- */
.request-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 800px; margin: 0 auto; background: #f5f7fb; }
.request-header-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; text-align: left; }
.request-header-title p { margin: 6px 0 0; font-size: 14px; color: #666; text-align: left; }
.request-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px 30px; margin-top: 20px; }

/* --- FORCED UNIFIED SPACING --- */
/* This targets EVERY label in the form to have an 18px gap on top */
#requestForm label { 
    display: block !important; 
    margin-bottom: 8px !important; 
    font-size: 15px !important; 
    font-weight: 600 !important; 
    color: #333 !important; 
    margin-top: 18px !important; /* Forces 18px gap above every label */
}

/* Removes top margin for the very first element (either Date or Info box) */
#requestForm label:first-of-type,
.info-box, .join-box { 
    margin-top: 0 !important; 
}

/* Input Styles */
input[type="text"], select { width: 100%; padding: 12px 14px; font-size: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; transition: border-color 0.2s; box-sizing: border-box; }
input[type="text"]:focus, select:focus { border-color: #004b82; outline: none; }

/* Submit Button Spacing */
.btn-submit { 
    width: 100%; 
    padding: 14px; 
    background-color: #004b82; 
    color: white; 
    border: none; 
    border-radius: 50px; 
    font-size: 16px; 
    font-weight: 600; 
    cursor: pointer; 
    margin-top: 30px !important; /* Forced 30px gap for the button */
    transition: background 0.2s; 
}
.btn-submit:hover { background-color: #003660; }

/* Info boxes */
.info-box { background-color: #e7f3fe; border-left: 5px solid #2196F3; padding: 14px 18px; border-radius: 6px; font-size: 14px; color: #0d47a1; margin-bottom: 20px; line-height: 1.5; }
.join-box { background-color: #e8f5e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #2e7d32; font-size: 14px; }

/* --- DATE PICKER STYLES --- */
.date-picker-container { position: relative; width: 100%; }
.date-input-field { width: 100%; padding: 12px 14px; border: 1px solid #ddd; border-radius: 8px; background: #fff; cursor: pointer; font-size: 15px; display: flex; justify-content: space-between; align-items: center; }
.calendar-popup { display: none; position: absolute; top: 100%; left: 0; width: 320px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 1000; margin-top: 10px; overflow: hidden; height: auto; padding-bottom: 15px; }
.calendar-popup.active { display: block; }
.calendar-header { background-color: #004b82; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
.calendar-nav { cursor: pointer; font-size: 18px; padding: 5px 10px; user-select: none; }
.current-date { font-size: 16px; font-weight: 700; display: flex; align-items: center; }
.calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); padding: 10px 15px 5px; text-align: center; color: #888; font-weight: 600; font-size: 13px; }
.calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); padding: 0 15px; grid-auto-rows: 38px; }
.calendar-days div { display: flex; justify-content: center; align-items: center; cursor: pointer; border-radius: 50%; font-size: 14px; color: #333; margin: 2px; }
.calendar-days div:hover { background-color: #f0f0f0; }
.calendar-days div.selected, .calendar-days div.today { background-color: #004b82 !important; color: #fff !important; font-weight: bold; }
.calendar-days div.inactive { visibility: hidden; pointer-events: none; }
.calendar-days div.disabled { color: #d0d0d0 !important; pointer-events: none; }
.time-picker-container { border-top: 1px solid #eee; margin-top: 10px; padding: 15px 0; display: flex; justify-content: center; align-items: center; gap: 8px; }
.time-select { border: none; background: #f9f9f9; padding: 8px 0; border-radius: 6px; font-size: 16px; font-weight: 700; color: #333; cursor: pointer; text-align: center; width: 60px; }
.time-separator { font-weight: 800; color: #333; font-size: 18px; padding-bottom: 5px; display: inline-block; }
</style>

<div class="request-wrapper">
    <div class="request-header-title">
        <h1><?php echo $is_join_mode ? "Join an Existing Ride" : "Request Transport"; ?></h1>
        <p>Fill in the details below to book your ride.</p>
    </div>

    <div class="request-card">
        <?php if($is_join_mode): ?>
            <div class="join-box">
                <strong><i class="fa-solid fa-check-circle"></i> You are joining a ride!</strong><br>
                <div style="margin-top:5px;">
                    Destination: <b><?php echo htmlspecialchars($pre_dest); ?></b><br>
                    Time: <b><?php echo htmlspecialchars($pre_date); ?></b>
                </div>
            </div>
        <?php else: ?>
            <div class="info-box">
                <strong><i class="fa-solid fa-circle-info"></i> Service Notice:</strong><br>
                Service available in <strong>Johor, Melaka, and KL/Selangor</strong>.
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="requestForm">
            <input type="hidden" name="request" value="1">
            
            <?php if($is_join_mode): ?>
                <input type="hidden" name="target_driver_id" value="<?php echo htmlspecialchars($pre_driver_id); ?>">
                <label>Date & Time</label>
                <input type="text" name="date_time" value="<?php echo htmlspecialchars($pre_date); ?>" readonly style="background:#f9f9f9; color:#777; cursor:not-allowed;">
            <?php else: ?>
                <label>Date & Time</label>
                <div class="date-picker-container">
                    <div class="date-input-field" onclick="toggleCalendar()">
                        <span id="selected-date-text" style="color:#888;">Select Date & Time</span>
                        <i class="fa-solid fa-calendar-days" style="color:#004b82;"></i>
                    </div>
                    <input type="hidden" name="date_time" id="real_date_input">
                    <div class="calendar-popup" id="calendar-popup">
                        <div class="calendar-header">
                            <span class="calendar-nav" onclick="changeMonth(-1)">&#10094;</span>
                            <div class="current-date">
                                <span id="month-display">Month</span>
                                <span class="year-text" id="year-display" style="margin-left:10px;">Year</span>
                            </div>
                            <span class="calendar-nav" onclick="changeMonth(1)">&#10095;</span>
                        </div>
                        <div class="calendar-weekdays"><div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div></div>
                        <div class="calendar-days" id="calendar-days"></div>
                        <div class="time-picker-container">
                            <select id="hour-select" class="time-select" onchange="updateDateTime()">
                                <?php for($i=1; $i<=12; $i++) echo "<option value='$i'>".sprintf("%02d",$i)."</option>"; ?>
                            </select>
                            <span class="time-separator">:</span>
                            <select id="minute-select" class="time-select" onchange="updateDateTime()">
                                <?php for($i=0; $i<60; $i+=5) echo "<option value='".sprintf("%02d",$i)."'>".sprintf("%02d",$i)."</option>"; ?>
                            </select>
                            <select id="ampm-select" class="time-select" onchange="updateDateTime()" style="margin-left:5px;">
                                <option value="AM">AM</option>
                                <option value="PM" selected>PM</option>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <label>Destination State</label>
            <select name="state" id="stateSelect" required>
                <option value="" disabled selected hidden>Select State</option>
                <option value="Johor">Johor</option>
                <option value="Melaka">Melaka</option>
                <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
            </select>

            <label>Destination Region / City</label>
            <select name="region" id="regionSelect" required disabled>
                <option value="" disabled selected hidden>Please select a State first</option>
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
                <option value="Hatchback">Hatchback (Max 4 Pax)</option>
                <option value="Sedan">Sedan (Max 4 Pax)</option>
                <option value="SUV">SUV (Max 4 Pax)</option>
                <option value="MPV">MPV (Max 6 Pax)</option>
            </select>

            <label>Pick-up Point</label>
            <input type="text" name="pickup" required placeholder="e.g., MMU Main Gate">

            <label>Remarks (Optional)</label>
            <input type="text" name="remark" placeholder="Any luggage or special requests?">

            <button type="submit" class="btn-submit">Submit Request</button>
        </form>
    </div>
</div>

<script>
    // All JS Logic preserved
    const dateText = document.getElementById("selected-date-text");
    const dateInput = document.getElementById("real_date_input");
    const calendarPopup = document.getElementById("calendar-popup");
    const daysContainer = document.getElementById("calendar-days");
    const monthDisplay = document.getElementById("month-display");
    const yearDisplay = document.getElementById("year-display");
    const hourSelect = document.getElementById("hour-select");
    const minuteSelect = document.getElementById("minute-select");
    const ampmSelect = document.getElementById("ampm-select");

    let currDate = new Date();
    let currMonth = currDate.getMonth();
    let currYear = currDate.getFullYear();
    let selectedDay = currDate.getDate(); 
    let todayDate = new Date(); todayDate.setHours(0,0,0,0); 
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    function renderCalendar() {
        let firstDayOfMonth = new Date(currYear, currMonth, 1).getDay(); 
        let lastDateOfMonth = new Date(currYear, currMonth + 1, 0).getDate();
        daysContainer.innerHTML = ""; monthDisplay.innerText = months[currMonth]; yearDisplay.innerText = currYear;
        for (let i = 0; i < firstDayOfMonth; i++) { const emptyDiv = document.createElement("div"); emptyDiv.classList.add("inactive"); daysContainer.appendChild(emptyDiv); }
        for (let i = 1; i <= lastDateOfMonth; i++) {
            const dayDiv = document.createElement("div"); dayDiv.innerText = i;
            let checkDate = new Date(currYear, currMonth, i); checkDate.setHours(0,0,0,0); 
            if (checkDate < todayDate) { dayDiv.classList.add("disabled"); } else {
                if (i === new Date().getDate() && currMonth === new Date().getMonth() && currYear === new Date().getFullYear()) dayDiv.classList.add("today");
                if (i === selectedDay) dayDiv.classList.add("selected");
                dayDiv.onclick = () => { selectedDay = i; renderCalendar(); updateDateTime(); };
            }
            daysContainer.appendChild(dayDiv);
        }
    }
    function changeMonth(direction) { currMonth += direction; if (currMonth < 0) { currMonth = 11; currYear--; } else if (currMonth > 11) { currMonth = 0; currYear++; } selectedDay = null; renderCalendar(); }
    function updateDateTime() { if (!selectedDay) return; let h = parseInt(hourSelect.value); let m = minuteSelect.value; let ap = ampmSelect.value; dateText.innerText = `${selectedDay} ${months[currMonth]} ${currYear}, ${h}:${m} ${ap}`; dateText.style.color = "#333"; let h24 = h; if (ap === "PM" && h < 12) h24 += 12; if (ap === "AM" && h === 12) h24 = 0; dateInput.value = `${currYear}-${String(currMonth + 1).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')} ${String(h24).padStart(2, '0')}:${m}`; }
    function toggleCalendar() { calendarPopup.classList.toggle("active"); if(calendarPopup.classList.contains("active") && !selectedDay) { selectedDay = new Date().getDate(); renderCalendar(); updateDateTime(); } }
    document.addEventListener('click', (e) => { if (document.querySelector('.date-picker-container') && !document.querySelector('.date-picker-container').contains(e.target)) calendarPopup.classList.remove('active'); });

    const regions = { "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai"], "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"], "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Subang Jaya", "Cyberjaya", "Putrajaya"] };
    const stateSelect = document.getElementById('stateSelect'); const regionSelect = document.getElementById('regionSelect');
    stateSelect.addEventListener('change', function() { regionSelect.innerHTML = '<option value="" disabled selected hidden>Select Region / City</option>'; if (this.value && regions[this.value]) { regionSelect.disabled = false; regions[this.value].forEach(city => { const opt = document.createElement('option'); opt.value = city; opt.textContent = city; regionSelect.appendChild(opt); }); } });

    document.getElementById('requestForm').addEventListener('submit', function(e) { e.preventDefault(); Swal.fire({ title: 'Confirm Request?', text: "Are you sure you want to submit?", icon: 'info', showCancelButton: true, confirmButtonColor: '#004b82', confirmButtonText: 'Yes, Submit' }).then((result) => { if (result.isConfirmed) e.target.submit(); }); });
    <?php if ($swal_message != ""): ?> Swal.fire({ title: "Notice", text: "<?php echo $swal_message; ?>", icon: "<?php echo $swal_type; ?>", confirmButtonColor: '#004b82' }).then(() => { <?php if ($swal_redirect != "") echo "window.location.href = '$swal_redirect';"; ?> }); <?php endif; ?>
    renderCalendar(); updateDateTime();
</script>
<?php include "footer.php"; ?>