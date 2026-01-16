<?php
// FUNCTION: START SESSION
session_start();

// FUNCTION: SET TIMEZONE
// Essential for accurate date/time comparisons
date_default_timezone_set('Asia/Kuala_Lumpur');

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// FUNCTION: CHECK LOGIN STATUS
// Redirect to login page if no session is found
if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// =========================================================
// LOGIC: GET SEARCH FILTERS
// =========================================================
$search_state   = isset($_GET['state']) ? $_GET['state'] : '';
$search_region  = isset($_GET['region']) ? $_GET['region'] : '';
$search_date    = isset($_GET['date']) ? $_GET['date'] : ''; 
$search_vehicle = isset($_GET['vehicle']) ? $_GET['vehicle'] : ''; 

$current_time = date("Y-m-d H:i:s");

// =========================================================
// LOGIC: BUILD SQL QUERY
// Find all 'Approved' or 'Accepted' rides that are in the future
// =========================================================
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
        GROUP_CONCAT(b.student_id) as passenger_ids
    FROM bookings b
    JOIN drivers d ON b.driver_id = d.driver_id
    JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.status IN ('Approved', 'Accepted', 'APPROVED', 'ACCEPTED') 
      AND b.date_time > '$current_time' 
";

// APPLY FILTERS
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
if (!empty($search_vehicle)) {
    $safe_vehicle = $conn->real_escape_string($search_vehicle);
    $sql .= " AND b.vehicle_type = '$safe_vehicle' ";
}

// GROUP & ORDER
$sql .= " GROUP BY b.driver_id, b.date_time ORDER BY b.date_time ASC ";

$available_rides = [];
$result = $conn->query($sql);

if ($result) {
    // PREPARE STATEMENT: Check True Occupancy (Pending + Accepted)
    $stmt_real_count = $conn->prepare("SELECT SUM(passengers) as total FROM bookings WHERE driver_id = ? AND date_time = ? AND status IN ('Pending', 'Accepted', 'Approved', 'APPROVED', 'ACCEPTED')");

    while ($row = $result->fetch_assoc()) {
        $capacity = isset($row['seat_count']) ? (int)$row['seat_count'] : 4;
        
        $stmt_real_count->bind_param("ss", $row['driver_id'], $row['date_time']);
        $stmt_real_count->execute();
        $res_real = $stmt_real_count->get_result();
        $real_occupied = 0;
        if ($r = $res_real->fetch_assoc()) {
            $real_occupied = (int)$r['total'];
        }

        $remaining = $capacity - $real_occupied;
        $passengers_in_car = explode(",", $row['passenger_ids']);
        $am_i_joined = in_array($student_id, $passengers_in_car);

        if ($remaining <= 0 && !$am_i_joined) {
            continue; 
        }

        $row['remaining_seats'] = ($remaining < 0) ? 0 : $remaining; 
        $row['is_joined'] = $am_i_joined; 
        $available_rides[] = $row;
    }
    $stmt_real_count->close();
}

// INCLUDE HEADER (Navigation & Global Styles)
include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    /* 1. ANIMATION */
    @keyframes fadeInUpPage {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* 2. RESET HEADER CONTAINER */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* 3. MAIN LAYOUT WRAPPER */
    .search-wrapper { 
        min-height: calc(100vh - 100px); 
        padding: 40px 20px; 
        max-width: 900px;
        margin: 0 auto; 
        background: #f5f7fb; 
        font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 4. PAGE HEADER TEXT */
    .search-header-title { text-align: center; margin-bottom: 30px; }
    .search-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .search-header-title p { margin: 8px 0 0; font-size: 15px; color: #64748b; }

    /* 5. SEARCH FILTER CARD (Strict Alignment Fixes) */
    .filter-card { 
        background: #ffffff; 
        border-radius: 24px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        padding: 35px; 
        margin-bottom: 40px;
        border: 1px solid #e2e8f0;
    }

    /* --- ALIGNMENT FIX: GRID LAYOUT --- */
    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Exact 50/50 split */
        column-gap: 20px; /* Horizontal space */
        row-gap: 25px;    /* Vertical space */
        align-items: start; /* FIXED: Align tops to prevent uneven jumping */
    }

    /* --- ALIGNMENT FIX: LABELS --- */
    /* Ensure all labels have exact same spacing and height */
    .request-card form label, /* Fallback */
    .filter-card label { 
        display: block !important; 
        margin-bottom: 8px !important; 
        font-size: 14px !important; 
        font-weight: 600 !important; 
        color: #333 !important; 
        margin-top: 0 !important; /* Reset margin */
        line-height: 1.2 !important; /* Consistent text height */
    }

    /* --- ALIGNMENT FIX: INPUTS & SELECTS --- */
    /* Force exact same height and box model */
    .filter-card select, 
    .date-input-field { 
        width: 100% !important; 
        height: 52px !important; /* FIXED HEIGHT */
        padding: 0 15px !important; /* Exact padding */
        font-size: 15px !important; 
        border: 1.5px solid #e2e8f0 !important; 
        border-radius: 12px !important; 
        background-color: #fff !important; 
        box-sizing: border-box !important;
        transition: all 0.2s ease;
        color: #333 !important;
        font-family: 'Poppins', sans-serif !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .filter-card select:focus, 
    .date-input-field:hover {
        border-color: #004b82 !important;
        outline: none !important;
    }

    /* Fix Date Picker Inner Text Alignment */
    .date-input-field span {
        flex: 1; /* Push icon to right */
    }

    /* Search Button */
    .btn-search { 
        width: 100%; 
        padding: 16px; 
        background-color: #004b82; 
        color: white; 
        border: none; 
        border-radius: 50px; 
        font-size: 16px; 
        font-weight: 600; 
        cursor: pointer; 
        margin-top: 30px; 
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 75, 130, 0.2);
    }
    .btn-search:hover { 
        background-color: #003660; 
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 75, 130, 0.3);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr; /* Stack on mobile */
            gap: 20px;
        }
    }

    /* 6. RESULT RIDE CARD */
    .results-header {
        font-weight: 700; 
        font-size: 18px;
        margin-bottom: 20px; 
        color: #333;
        padding-left: 10px;
        border-left: 4px solid #004b82;
    }

    .ride-card { 
        background: white; 
        padding: 25px; 
        border-radius: 20px; 
        margin-bottom: 20px; 
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
    }
    .ride-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 90, 156, 0.1);
        border-color: #e0f2fe;
    }

    /* Route Text */
    .route-text { font-weight: 700; font-size: 17px; margin-bottom: 8px; color: #1e293b; }
    .info-row { font-size: 14px; color: #64748b; margin-bottom: 8px; display: flex; align-items: center; gap: 15px; }
    .info-row i { width: 18px; text-align: center; color: #004b82; }

    /* Badges */
    .vehicle-badge { background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 13px; color: #475569; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; margin-top: 5px; }
    .seat-badge { padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 15px; }
    .seat-badge.available { background: #dcfce7; color: #166534; }
    .seat-badge.full { background: #fee2e2; color: #991b1b; }

    /* Buttons */
    .btn-join { background-color: #009688; color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0, 150, 136, 0.2); }
    .btn-join:hover { background-color: #00796b; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 150, 136, 0.3); }
    .btn-joined { background-color: #cbd5e1; color: #475569; padding: 10px 24px; border-radius: 50px; font-weight: 600; cursor: default; }

    /* 7. DATE PICKER POPUP */
    .date-picker-container { position: relative; width: 100%; cursor: pointer; }
    .calendar-popup { display: none; position: absolute; top: 110%; left: 0; width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden; border: 1px solid #e2e8f0; padding-bottom: 15px; }
    .calendar-popup.active { display: block; }
    .calendar-header { background-color: #004b82; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
    .calendar-nav { cursor: pointer; font-size: 18px; padding: 5px 10px; user-select: none; }
    .current-date { font-size: 16px; font-weight: 700; }
    .calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); padding: 15px 15px 5px; text-align: center; color: #94a3b8; font-weight: 600; font-size: 13px; }
    .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); padding: 0 15px; grid-auto-rows: 40px; }
    .calendar-days div { display: flex; justify-content: center; align-items: center; cursor: pointer; border-radius: 50%; font-size: 14px; color: #333; margin: 2px; }
    .calendar-days div:hover { background-color: #f1f5f9; }
    .calendar-days div.selected, .calendar-days div.today { background-color: #004b82 !important; color: #fff !important; font-weight: bold; }
    .calendar-days div.inactive { visibility: hidden; pointer-events: none; }
    .calendar-days div.disabled { color: #cbd5e1 !important; pointer-events: none; }

    /* 8. EMPTY STATE */
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; background: #fff; border-radius: 20px; border: 1px dashed #cbd5e1; }
    .empty-state i { font-size: 48px; margin-bottom: 20px; color: #cbd5e1; }
    .empty-state p { font-size: 16px; }
</style>

<div class="search-wrapper">
    
    <div class="search-header-title">
        <h1>Find a Ride</h1>
        <p>Join existing trips and save costs by carpooling.</p>
    </div>

    <div class="filter-card">
        <form method="GET" action="search_transport.php" id="filterForm">
            
            <div class="filter-grid">
                
                <div class="filter-group">
                    <label>Destination State</label>
                    <select name="state" id="stateSelect">
                        <option value="">All States</option>
                        <option value="Johor" <?php if($search_state == 'Johor') echo 'selected'; ?>>Johor</option>
                        <option value="Melaka" <?php if($search_state == 'Melaka') echo 'selected'; ?>>Melaka</option>
                        <option value="Kuala Lumpur/Selangor" <?php if($search_state == 'Kuala Lumpur/Selangor') echo 'selected'; ?>>Kuala Lumpur / Selangor</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Destination Region / City</label>
                    <select name="region" id="regionSelect" disabled>
                        <option value="">All Regions</option>
                    </select>
                </div>

                <div class="filter-group">
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
                </div>

                <div class="filter-group">
                    <label>Vehicle Preference</label>
                    <select name="vehicle" id="vehicleSelect">
                        <option value="">All Vehicles</option>
                        <option value="Sedan" <?php if($search_vehicle == 'Sedan') echo 'selected'; ?>>Sedan</option>
                        <option value="Hatchback" <?php if($search_vehicle == 'Hatchback') echo 'selected'; ?>>Hatchback</option>
                        <option value="SUV" <?php if($search_vehicle == 'SUV') echo 'selected'; ?>>SUV</option>
                        <option value="MPV" <?php if($search_vehicle == 'MPV') echo 'selected'; ?>>MPV (6-Seater)</option>
                    </select>
                </div>

            </div>
            <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Search Rides</button>

            <?php if(!empty($search_state) || !empty($search_region) || !empty($search_date) || !empty($search_vehicle)): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="search_transport.php" style="color: #64748b; text-decoration: none; font-size: 14px; border-bottom: 1px dashed #64748b;">Clear Filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($available_rides)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-route"></i>
            <p>No available carpool rides found matching your criteria.</p>
            <span style="font-size: 14px; color: #94a3b8;">Try changing the date, location, or vehicle type.</span>
        </div>
    <?php else: ?>
        <div class="results-header">Available Rides (<?php echo count($available_rides); ?>)</div>
        
        <?php foreach ($available_rides as $ride): ?>
            <div class="ride-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div class="route-text">
                            <i class="fa-solid fa-location-dot" style="color:#e53e3e; margin-right:8px;"></i>
                            To: <?php echo htmlspecialchars($ride['destination']); ?>
                        </div>
                        <div class="info-row"><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($ride['date_time'])); ?></div>
                        <div class="info-row"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($ride['driver_name']); ?></div>
                        <div class="vehicle-badge"><i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($ride['vehicle_model']); ?> (<?php echo htmlspecialchars($ride['plate_number']); ?>)</div>
                    </div>
                    <div style="text-align: right; min-width: 120px;">
                        <div style="margin-bottom: 15px;">
                            <?php if ($ride['remaining_seats'] > 0): ?>
                                <span class="seat-badge available"><i class="fa-solid fa-chair"></i> <?php echo $ride['remaining_seats']; ?> Seats Left</span>
                            <?php else: ?>
                                <span class="seat-badge full">Full</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ride['is_joined']): ?>
                            <div class="btn-joined"><i class="fa-solid fa-check"></i> Joined</div>
                        <?php elseif ($ride['remaining_seats'] > 0): ?>
                            <a href="passanger_request_transport.php?join_driver=<?php echo $ride['driver_id']; ?>&join_date=<?php echo urlencode($ride['date_time']); ?>&join_dest=<?php echo urlencode($ride['destination']); ?>" class="btn-join">
                                Join Ride <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
    // --- 1. Form Validation (Prevent Empty Search if absolutely no filter) ---
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const state = document.getElementById('stateSelect').value;
        const region = document.getElementById('regionSelect').value;
        const date = document.getElementById('real_date_input').value;
        const vehicle = document.getElementById('vehicleSelect').value;
        
        if (state === "" && region === "" && date === "" && vehicle === "") {
            e.preventDefault();
            Swal.fire({ 
                icon: 'info', 
                title: 'No Filters Selected', 
                text: 'Showing all upcoming rides.', 
                confirmButtonColor: '#004b82',
                timer: 1500
            }).then(() => {
                e.target.submit();
            });
        }
    });

    // --- 2. Custom Date Picker Logic ---
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
        renderCalendar(); 
        updateDateValue(); 
        calendarPopup.classList.remove("active");
    }

    function changeMonth(direction) {
        event.stopPropagation();
        currMonth += direction;
        if (currMonth < 0) { currMonth = 11; currYear--; } 
        else if (currMonth > 11) { currMonth = 0; currYear++; }
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

    // --- 3. Dynamic Region Filter ---
    const regions = { 
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"], 
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"], 
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Subang Jaya", "Cyberjaya", "Putrajaya", "Seremban", "Nilai"] 
    };
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
        } else { 
            regionSelect.disabled = true; 
        }
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