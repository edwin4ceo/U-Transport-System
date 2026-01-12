<?php
session_start();
// --- [FIX 1] Set Timezone to Malaysia ---
date_default_timezone_set('Asia/Kuala_Lumpur');

include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// --- Handle Search Filters ---
$search_state  = isset($_GET['state']) ? $_GET['state'] : '';
$search_region = isset($_GET['region']) ? $_GET['region'] : '';
$search_date   = isset($_GET['date']) ? $_GET['date'] : ''; 

// --- [FIX 2] Get current server time ---
$current_time = date("Y-m-d H:i:s");
$today_date   = date("Y-m-d"); 

// 2. Logic: Find Active Rides
$sql = "
    SELECT 
        b.driver_id,
        b.date_time,
        b.destination,
        b.vehicle_type,
        d.full_name AS driver_name,
        v.seat_count,
        v.plate_number,
        v.vehicle_model,
        SUM(b.passengers) as total_occupied
    FROM bookings b
    JOIN drivers d ON b.driver_id = d.driver_id
    JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.status = 'Accepted' 
      AND b.date_time > '$current_time' 
";

// [NEW] Filter by Date
if (!empty($search_date)) {
    $safe_date = $conn->real_escape_string($search_date);
    $sql .= " AND DATE(b.date_time) = '$safe_date' ";
}

// Filter by State
if (!empty($search_state)) {
    $safe_state = $conn->real_escape_string($search_state);
    $sql .= " AND b.destination LIKE '%$safe_state%' ";
}

// Filter by Region
if (!empty($search_region)) {
    $safe_region = $conn->real_escape_string($search_region);
    $sql .= " AND b.destination LIKE '%$safe_region%' ";
}

$sql .= "
    GROUP BY b.driver_id, b.date_time
    ORDER BY b.date_time ASC
";

$available_rides = [];
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $capacity = isset($row['seat_count']) ? (int)$row['seat_count'] : 4;
        $occupied = (int)$row['total_occupied'];
        $remaining = $capacity - $occupied;

        if ($remaining > 0) {
            $row['remaining_seats'] = $remaining;
            $available_rides[] = $row;
        }
    }
}

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
/* --- DESIGN RESTORED: WIDE CARD (800px) & TIGHT SPACING --- */

.request-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 800px; /* RESTORED WIDTH */
    margin: 0 auto;
    background: #f5f7fb;
}

.request-header-title h1 {
    margin: 0;
    font-size: 24px; 
    font-weight: 700;
    color: #004b82;
    text-align: left;
}

.request-header-title p {
    margin: 6px 0 0;
    font-size: 14px; 
    color: #666;
    text-align: left;
}

/* Card Container */
.request-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    padding: 25px 30px;
    margin-top: 20px;
    margin-bottom: 30px; 
}

/* Form Elements Styling - TIGHT VERTICAL GAPS */
label {
    display: block;
    margin-bottom: 4px; /* Tiny gap */
    font-size: 15px; 
    font-weight: 600;
    color: #333;
    margin-top: 8px; /* Tighter vertical gap */
}

label:first-of-type {
    margin-top: 0; 
}

input[type="text"], 
select {
    width: 100%;
    padding: 12px 14px; 
    font-size: 15px; 
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fff;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

input[type="text"]:focus, 
select:focus {
    border-color: #004b82;
    outline: none;
}

/* Submit/Filter Button */
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
    margin-top: 18px; /* Tighter gap */
    transition: background 0.2s;
}
.btn-submit:hover { background-color: #003660; }

/* Reset Link Style */
.reset-link {
    display: block;
    text-align: center;
    margin-top: 15px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
}
.reset-link:hover { text-decoration: underline; color: #004b82; }


/* --- FLATPICKR CUSTOMIZATION (NO EXTRA WHITE SPACE) --- */

.flatpickr-calendar { 
    width: 320px !important; 
    font-size: 13px !important; 
    border: none !important; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important; 
}

/* Force container to auto height so it shrinks when we remove rows */
.flatpickr-days, .dayContainer { 
    width: 320px !important; 
    min-width: 320px !important; 
    max-width: 320px !important;
    height: auto !important; /* Critical: Allows shrinking */
    
    /* [CRITICAL FIX] Force items to start from left, preventing spacing issues */
    justify-content: flex-start !important; 
}

/* Enforce fixed width for each day (100% / 7 = 14.28%) */
.flatpickr-day { 
    height: 38px !important; 
    line-height: 38px !important; 
    max-width: 14.2857% !important; /* Exact 1/7th */
    flex-basis: 14.2857% !important;
}

/* [CRITICAL FIX] Hide Logic */

/* 1. Prev Month: VISIBILITY HIDDEN (Must keep space for alignment) */
.flatpickr-day.prevMonthDay {
    visibility: hidden !important; 
    pointer-events: none !important;
}

/* 2. Next Month: DISPLAY NONE (Remove space to shrink height) */
/* Because we use flex-start above, removing these won't break the layout */
.flatpickr-day.nextMonthDay {
    display: none !important; 
    pointer-events: none !important;
}

/* Header Colors */
.flatpickr-months { background-color: #004b82 !important; color: #fff !important; fill: #fff !important; padding: 5px 0 !important; height: 50px !important; display: flex !important; align-items: center !important; justify-content: center !important; }

/* Layout: [ < Month > Year ] */
.flatpickr-current-month { width: 100% !important; left: 0 !important; position: static !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; height: 100% !important; }

/* Month Dropdown */
.flatpickr-current-month .flatpickr-monthDropdown-months { appearance: none; font-weight: 700 !important; color: #fff !important; margin: 0 !important; padding: 0 5px !important; background: transparent !important; border: none !important; }
.flatpickr-monthDropdown-months .flatpickr-monthDropdown-month { background-color: #fff !important; color: #000 !important; }

/* Navigation Arrows (White) */
.flatpickr-prev-month, .flatpickr-next-month { position: static !important; height: 30px !important; width: 30px !important; padding: 0 !important; margin: 0 2px !important; color: #fff !important; fill: #fff !important; display: flex !important; align-items: center !important; justify-content: center !important; }
.flatpickr-prev-month svg, .flatpickr-next-month svg { fill: #fff !important; width: 14px !important; height: 14px !important; }

/* Year Container & Arrows */
.flatpickr-current-month .numInputWrapper { width: 70px !important; height: 30px !important; display: inline-flex !important; flex-direction: column-reverse !important; position: relative !important; margin-left: 10px !important; vertical-align: middle !important; }
.flatpickr-current-month input.cur-year { color: #fff !important; font-weight: 700 !important; font-size: 16px !important; padding: 0 15px 0 0 !important; text-align: right !important; height: 100% !important; display: inline-block !important; margin: 0 !important; }

/* Hide default arrows */
input.numInput::-webkit-outer-spin-button, input.numInput::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

/* Custom Year Arrows (Reversed Logic) */
.numInputWrapper span.arrowUp { position: absolute !important; right: 0 !important; bottom: 0 !important; top: 50% !important; height: 50% !important; width: 14px !important; border: none !important; padding: 0 !important; display: flex !important; align-items: center; justify-content: center; cursor: pointer !important; z-index: 10 !important; }
.numInputWrapper span.arrowUp::after { content: ""; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 4px solid #fff; border-bottom: none; }
.numInputWrapper span.arrowDown { position: absolute !important; right: 0 !important; top: 0 !important; height: 50% !important; width: 14px !important; border: none !important; padding: 0 !important; display: flex !important; align-items: center; justify-content: center; cursor: pointer !important; z-index: 10 !important; }
.numInputWrapper span.arrowDown::after { content: ""; border-left: 4px solid transparent; border-right: 4px solid transparent; border-bottom: 4px solid #fff; border-top: none; }


/* --- SEARCH RESULTS STYLING --- */
.results-header { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 15px; margin-left: 5px; }
.ride-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 15px; transition: transform 0.2s; }
.ride-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
.route-text { font-size: 17px; font-weight: 700; color: #004b82; margin-bottom: 8px; }
.info-row { display: flex; gap: 15px; font-size: 15px; color: #555; margin-bottom: 12px; align-items: center; }
.seat-badge { background-color: #e8f5e9; color: #2e7d32; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; }
.btn-join { background-color: #009688; color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 15px; display: inline-block; }
.btn-join:hover { background-color: #00796b; }
.empty-state { text-align: center; padding: 40px; color: #777; font-size: 15px; }
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
            <input type="text" name="date" id="dateFilter" 
                   value="<?php echo htmlspecialchars($search_date); ?>" 
                   placeholder="Select Date">
            
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-magnifying-glass"></i> Search Rides
            </button>

            <?php if(!empty($search_state) || !empty($search_region) || !empty($search_date)): ?>
                <a href="search_transport.php" class="reset-link">Reset Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($available_rides)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-car-side" style="font-size: 48px; margin-bottom: 15px; color: #ccc;"></i>
            <p>No available carpool rides found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="results-header">Available Rides (<?php echo count($available_rides); ?>)</div>
        
        <?php foreach ($available_rides as $ride): ?>
            <div class="ride-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="route-text">
                            To: <?php echo htmlspecialchars($ride['destination']); ?>
                        </div>
                        <div class="info-row">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($ride['date_time'])); ?></span>
                            <span><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($ride['driver_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span style="background: #f5f5f5; padding: 5px 10px; border-radius: 6px;">
                                <i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($ride['vehicle_model']); ?> (<?php echo htmlspecialchars($ride['plate_number']); ?>)
                            </span>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <div style="margin-bottom: 15px;">
                            <span class="seat-badge">
                                <?php echo $ride['remaining_seats']; ?> Seats Left
                            </span>
                        </div>
                        
                        <a href="passanger_request_transport.php?join_driver=<?php echo $ride['driver_id']; ?>&join_date=<?php echo urlencode($ride['date_time']); ?>&join_dest=<?php echo urlencode($ride['destination']); ?>" class="btn-join">
                            Join Ride <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
    const stateSelect = document.getElementById('stateSelect');
    const regionSelect = document.getElementById('regionSelect');
    const filterForm = document.getElementById('filterForm'); 

    // --- Logic 0: Initialize Flatpickr (DATE ONLY, NO TIME) ---
    flatpickr("#dateFilter", {
        enableTime: false, // DISABLE TIME
        dateFormat: "Y-m-d", 
        minDate: "today",        
        altInput: true,          
        altFormat: "F j, Y", 
        
        // --- JS: Layout Fix to match Request Page Style ---
        onReady: function(selectedDates, dateStr, instance) {
            const currentMonthContainer = instance.monthNav.querySelector('.flatpickr-current-month');
            const yearWrapper = currentMonthContainer.querySelector('.numInputWrapper');
            const prevArrow = instance.prevMonthNav;
            const nextArrow = instance.nextMonthNav;
            
            // Layout Fix: [ < ] [ Month ] [ > ] [ Year ]
            currentMonthContainer.insertBefore(prevArrow, currentMonthContainer.firstChild);
            currentMonthContainer.insertBefore(nextArrow, yearWrapper);
        }
    });

    // --- Logic 1: State & Region Dependency ---
    const regions = {
        "Johor": [
            "Johor Bahru", "Skudai", "Muar", "Batu Pahat", 
            "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"
        ],
        "Melaka": [
            "Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"
        ],
        "Kuala Lumpur/Selangor": [
            "Kuala Lumpur", "Petaling Jaya", "Shah Alam", 
            "Subang Jaya", "Cyberjaya", "Putrajaya", "Seremban", "Nilai"
        ]
    };

    function updateRegions(selectedState, selectedRegion = "") {
        regionSelect.innerHTML = '<option value="">All Regions</option>'; 

        if (selectedState && regions[selectedState]) {
            regionSelect.disabled = false;
            regions[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                if (city === selectedRegion) {
                    option.selected = true; 
                }
                regionSelect.appendChild(option);
            });
        } else {
            regionSelect.disabled = true;
        }
    }

    stateSelect.addEventListener('change', function() {
        updateRegions(this.value);
    });

    // On Page Load: Restore previous selection
    const savedState = "<?php echo $search_state; ?>";
    const savedRegion = "<?php echo $search_region; ?>";

    if (savedState) {
        updateRegions(savedState, savedRegion);
    }
</script>

<?php include "footer.php"; ?>