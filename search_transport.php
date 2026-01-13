<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
include "db_connect.php";
include "function.php";

// Check Login
if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// Get Filters
$search_state  = isset($_GET['state']) ? $_GET['state'] : '';
$search_region = isset($_GET['region']) ? $_GET['region'] : '';
$search_date   = isset($_GET['date']) ? $_GET['date'] : ''; 

$current_time = date("Y-m-d H:i:s");

// Search Logic: Find all Approved/Accepted rides
$sql = "
    SELECT 
        b.driver_id,
        b.date_time,
        b.destination,
        b.vehicle_type,
        b.status,
        d.full_name AS driver_name,
        v.seat_count,
        v.plate_number,
        v.vehicle_model,
        SUM(b.passengers) as total_occupied,
        GROUP_CONCAT(b.student_id) as passenger_ids
    FROM bookings b
    JOIN drivers d ON b.driver_id = d.driver_id
    JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.status IN ('Approved', 'Accepted', 'APPROVED', 'ACCEPTED') 
      AND b.date_time > '$current_time' 
";

// Apply Filters
if (!empty($search_date)) {
    $safe_date = $conn->real_escape_string($search_date);
    $sql .= " AND DATE(b.date_time) = '$safe_date' ";
}
if (!empty($search_state)) {
    $safe_state = $conn->real_escape_string($search_state);
    $sql .= " AND b.destination LIKE '%$safe_state%' ";
}
if (!empty($search_region)) {
    $safe_region = $conn->real_escape_string($search_region);
    $sql .= " AND b.destination LIKE '%$safe_region%' ";
}

$sql .= " GROUP BY b.driver_id, b.date_time ORDER BY b.date_time ASC ";

$available_rides = [];
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $capacity = isset($row['seat_count']) ? (int)$row['seat_count'] : 4;
        $occupied = (int)$row['total_occupied'];
        $remaining = $capacity - $occupied;

        // Check if current user is already in this ride
        $passengers_in_car = explode(",", $row['passenger_ids']);
        $am_i_joined = in_array($student_id, $passengers_in_car);

        if ($remaining > 0 || $am_i_joined) {
            $row['remaining_seats'] = ($remaining < 0) ? 0 : $remaining; 
            $row['is_joined'] = $am_i_joined; 
            $available_rides[] = $row;
        }
    }
}

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* --- Page Layout --- */
.request-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 800px; margin: 0 auto; background: #f5f7fb; width: 100%; }
.request-header-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; text-align: center; }
.request-header-title p { margin: 6px 0 0; font-size: 14px; color: #666; text-align: center; }
.request-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px 30px; margin-top: 20px; margin-bottom: 30px; }

/* --- FORCED UNIFIED SPACING --- */
/* Reduced margin-top from 18px to 8px and margin-bottom to 5px */
.request-card form label { 
    display: block !important; 
    margin-bottom: 5px !important; 
    font-size: 15px !important; 
    font-weight: 600 !important; 
    color: #333 !important; 
    margin-top: 1px !important; 
}
.request-card form label:first-of-type { 
    margin-top: 0 !important; 
}

/* Ensure fillboxes themselves match Request page */
.request-card form input[type="text"], 
.request-card form select,
.request-card form .date-input-field { 
    width: 100% !important; 
    padding: 12px 14px !important; 
    font-size: 15px !important; 
    border: 1px solid #ddd !important; 
    border-radius: 8px !important; 
    background-color: #fff !important; 
    box-sizing: border-box !important;
    height: auto !important;
}

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
    margin-top: 20px !important; /* Slightly reduced button top margin */
    transition: background 0.2s; 
}
.btn-submit:hover { background-color: #003660; }

/* --- Date Picker Popup Sync --- */
.date-picker-container { position: relative; width: 100%; }
.calendar-popup {
    display: none; position: absolute;
    top: 100%; left: 0; width: 320px;
    background: #fff; border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    z-index: 1000; margin-top: 10px;
    overflow: hidden;
    height: auto; 
    padding-bottom: 15px;
}
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
</style>

<div class="request-wrapper">
    <div class="request-header-title">
        <h1>Find a Ride</h1>
        <p>Join existing trips and save costs by carpooling.</p>
    </div>

    <div class="request-card">
        <form method="GET" action="search_transport.php" id="filterForm">
            <label>Destination State</label>
            <select name="state" id="stateSelect">
                <option value="">All States</option>
                <option value="Johor" <?php if($search_state == 'Johor') echo 'selected'; ?>>Johor</option>
                <option value="Melaka" <?php if($search_state == 'Melaka') echo 'selected'; ?>>Melaka</option>
                <option value="Kuala Lumpur/Selangor" <?php if($search_state == 'Kuala Lumpur/Selangor') echo 'selected'; ?>>Kuala Lumpur / Selangor</option>
            </select>

            <label>Destination Region / City</label>
            <select name="region" id="regionSelect" disabled>
                <option value="">All Regions</option>
            </select>

            <label>Date (Optional)</label>
            <div class="date-picker-container">
                <div class="date-input-field" onclick="toggleCalendar()">
                    <span id="selected-date-text" style="<?php echo empty($search_date) ? 'color:#888;' : 'color:#333;'; ?>">
                        <?php 
                            if(!empty($search_date)) { echo date("d F Y", strtotime($search_date)); } 
                            else { echo "Select Date"; }
                        ?>
                    </span>
                    <i class="fa-solid fa-calendar-days" style="color:#004b82;"></i>
                </div>
                
                <input type="hidden" name="date" id="real_date_input" value="<?php echo htmlspecialchars($search_date); ?>">

                <div class="calendar-popup" id="calendar-popup">
                    <div class="calendar-header">
                        <span class="calendar-nav" onclick="changeMonth(-1)">&#10094;</span>
                        <div class="current-date">
                            <span id="month-display">Month</span>
                            <span class="year-text" id="year-display" style="margin-left: 10px;">Year</span>
                        </div>
                        <span class="calendar-nav" onclick="changeMonth(1)">&#10095;</span>
                    </div>
                    <div class="calendar-weekdays">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    <div class="calendar-days" id="calendar-days"></div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit"><i class="fa-solid fa-magnifying-glass"></i> Search Rides</button>

            <?php if(!empty($search_state) || !empty($search_region) || !empty($search_date)): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="search_transport.php" class="reset-link" style="color: #666; text-decoration: none; font-size: 14px;">Reset Filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($available_rides)): ?>
        <div class="empty-state" style="text-align: center; padding: 40px; color: #777;">
            <i class="fa-solid fa-car-side" style="font-size: 48px; margin-bottom: 15px; color: #ccc;"></i>
            <p>No available carpool rides found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="results-header" style="font-weight: 700; margin-bottom: 15px; color: #333;">Available Rides (<?php echo count($available_rides); ?>)</div>
        <?php foreach ($available_rides as $ride): ?>
            <div class="ride-card" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="route-text" style="font-weight: 700; font-size: 16px; margin-bottom: 8px;">To: <?php echo htmlspecialchars($ride['destination']); ?></div>
                        <div class="info-row" style="font-size: 14px; color: #666; margin-bottom: 5px;">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($ride['date_time'])); ?></span>
                            <span style="margin-left: 15px;"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($ride['driver_name']); ?></span>
                        </div>
                        <div class="info-row" style="font-size: 14px; color: #666;">
                            <span style="background: #f5f5f5; padding: 5px 10px; border-radius: 6px;">
                                <i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($ride['vehicle_model']); ?> (<?php echo htmlspecialchars($ride['plate_number']); ?>)
                            </span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="margin-bottom: 15px;">
                            <?php if ($ride['remaining_seats'] > 0): ?>
                                <span class="seat-badge" style="background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"><?php echo $ride['remaining_seats']; ?> Seats Left</span>
                            <?php else: ?>
                                <span class="seat-badge" style="background:#ffebee; color:#c62828; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Full</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ride['is_joined']): ?>
                            <div class="btn-joined" style="background-color: #cfd8dc; color: #546e7a; padding: 10px 24px; border-radius: 50px; font-weight: 600;"><i class="fa-solid fa-check"></i> Joined</div>
                        <?php elseif ($ride['remaining_seats'] > 0): ?>
                            <a href="passanger_request_transport.php?join_driver=<?php echo $ride['driver_id']; ?>&join_date=<?php echo urlencode($ride['date_time']); ?>&join_dest=<?php echo urlencode($ride['destination']); ?>" style="background-color: #009688; color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-block;">
                                Join Ride <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // Region and Calendar Logic (Unchanged to keep functionality)
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const state = document.getElementById('stateSelect').value;
        const region = document.getElementById('regionSelect').value;
        const date = document.getElementById('real_date_input').value;
        if (state === "" && region === "" && date === "") {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Empty Search', text: 'Please select at least one filter.', confirmButtonColor: '#004b82' });
        }
    });

    const dateText = document.getElementById("selected-date-text");
    const dateInput = document.getElementById("real_date_input");
    const calendarPopup = document.getElementById("calendar-popup");
    const daysContainer = document.getElementById("calendar-days");
    const monthDisplay = document.getElementById("month-display");
    const yearDisplay = document.getElementById("year-display");
    
    let initialDateStr = dateInput.value;
    let currDate = initialDateStr ? new Date(initialDateStr) : new Date();
    let currMonth = currDate.getMonth();
    let currYear = currDate.getFullYear();
    let selectedDay = initialDateStr ? currDate.getDate() : null; 
    let todayDate = new Date(); todayDate.setHours(0,0,0,0); 
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    function renderCalendar() {
        let firstDayOfMonth = new Date(currYear, currMonth, 1).getDay(); 
        let lastDateOfMonth = new Date(currYear, currMonth + 1, 0).getDate();
        daysContainer.innerHTML = "";
        monthDisplay.innerText = months[currMonth];
        yearDisplay.innerText = currYear;
        for (let i = 0; i < firstDayOfMonth; i++) {
            const emptyDiv = document.createElement("div");
            emptyDiv.classList.add("inactive");
            daysContainer.appendChild(emptyDiv);
        }
        for (let i = 1; i <= lastDateOfMonth; i++) {
            const dayDiv = document.createElement("div");
            dayDiv.innerText = i;
            let checkDate = new Date(currYear, currMonth, i);
            checkDate.setHours(0,0,0,0); 
            if (checkDate < todayDate) {
                dayDiv.classList.add("disabled"); 
            } else {
                if (i === new Date().getDate() && currMonth === new Date().getMonth() && currYear === new Date().getFullYear()) {
                    dayDiv.classList.add("today");
                }
                if (selectedDay && i === selectedDay && currMonth === currDate.getMonth() && currYear === currDate.getFullYear()) {
                    dayDiv.classList.add("selected");
                }
                dayDiv.onclick = (e) => { e.stopPropagation(); selectDay(i); };
            }
            daysContainer.appendChild(dayDiv);
        }
    }

    function selectDay(day) {
        selectedDay = day;
        currDate = new Date(currYear, currMonth, day);
        renderCalendar(); updateDateValue(); calendarPopup.classList.remove("active");
    }

    function changeMonth(direction) {
        event.stopPropagation();
        currMonth += direction;
        if (currMonth < 0) { currMonth = 11; currYear--; } else if (currMonth > 11) { currMonth = 0; currYear++; }
        renderCalendar();
    }

    function updateDateValue() {
        if (!selectedDay) return;
        let monthStr = String(currMonth + 1).padStart(2, '0');
        let dayStr = String(selectedDay).padStart(2, '0');
        dateText.innerText = `${selectedDay} ${months[currMonth]} ${currYear}`;
        dateText.style.color = "#333";
        dateInput.value = `${currYear}-${monthStr}-${dayStr}`;
    }

    function toggleCalendar() {
        event.stopPropagation();
        calendarPopup.classList.toggle("active");
        if(calendarPopup.classList.contains("active")) renderCalendar();
    }

    document.addEventListener('click', function(event) {
        const wrapper = document.querySelector('.date-picker-container');
        if (wrapper && !wrapper.contains(event.target)) {
            calendarPopup.classList.remove('active');
        }
    });

    const regions = { "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"], "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"], "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Subang Jaya", "Cyberjaya", "Putrajaya", "Seremban", "Nilai"] };
    const stateSelect = document.getElementById('stateSelect');
    const regionSelect = document.getElementById('regionSelect');

    function updateRegions(selectedState, selectedRegion = "") {
        regionSelect.innerHTML = '<option value="">All Regions</option>'; 
        if (selectedState && regions[selectedState]) {
            regionSelect.disabled = false;
            regions[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city; option.textContent = city;
                if (city === selectedRegion) option.selected = true; 
                regionSelect.appendChild(option);
            });
        } else { regionSelect.disabled = true; }
    }
    stateSelect.addEventListener('change', function() { updateRegions(this.value); });
    window.onload = function() {
        const savedState = "<?php echo $search_state; ?>";
        const savedRegion = "<?php echo $search_region; ?>";
        if (savedState) updateRegions(savedState, savedRegion);
        renderCalendar();
    };
</script>

<?php include "footer.php"; ?>