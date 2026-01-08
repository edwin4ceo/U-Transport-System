<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// --- Handle Search Filter ---
$search_state  = isset($_GET['state']) ? $_GET['state'] : '';
$search_region = isset($_GET['region']) ? $_GET['region'] : '';

// 2. Logic: Find Active Rides with Available Seats
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
      AND b.date_time > NOW() 
";

// Filter by State
if (!empty($search_state)) {
    $safe_state = $conn->real_escape_string($search_state);
    $sql .= " AND b.destination LIKE '%$safe_state%' ";
}

// Filter by Region (City)
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

<style>
/* --- Page Structure --- */
.search-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}

.header-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; }
.header-title p { margin: 6px 0 0; font-size: 14px; color: #666; }

/* --- Search Bar Container --- */
.search-bar-container {
    background: white;
    padding: 15px 20px;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    margin-bottom: 25px;
    display: flex;
    gap: 12px;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    flex-wrap: wrap; /* Allow wrapping on small screens */
}

.search-select {
    flex: 1;
    min-width: 150px; /* Minimum width for inputs */
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 15px;
    color: #333;
    outline: none;
    background-color: #f9f9f9;
    transition: border-color 0.2s;
}
.search-select:focus { border-color: #004b82; background-color: #fff; }
.search-select:disabled { background-color: #f0f0f0; color: #aaa; cursor: not-allowed; }

/* --- Buttons --- */
.btn-search {
    background: linear-gradient(135deg, #004b82, #0060a3);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2);
    transition: all 0.2s ease;
}
.btn-search:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3);
    background: linear-gradient(135deg, #003660, #004b82);
}

.btn-reset {
    background-color: transparent;
    color: #666;
    border: 2px solid #e0e0e0;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}
.btn-reset:hover { background-color: #f5f5f5; color: #333; border-color: #ccc; }

/* --- Ride Card --- */
.ride-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 15px; transition: transform 0.2s; }
.ride-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
.route-text { font-size: 17px; font-weight: 700; color: #004b82; margin-bottom: 8px; }
.info-row { display: flex; gap: 15px; font-size: 15px; color: #555; margin-bottom: 12px; align-items: center; }
.seat-badge { background-color: #e8f5e9; color: #2e7d32; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; }
.btn-join { background-color: #009688; color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 15px; display: inline-block; }
.btn-join:hover { background-color: #00796b; }
.empty-state { text-align: center; padding: 40px; color: #777; font-size: 15px; }
</style>

<div class="search-wrapper">
    <div style="margin-bottom: 25px;">
        <div class="header-title">
            <h1>Find a Ride</h1>
            <p>Join existing trips and save costs by carpooling.</p>
        </div>
    </div>

    <form method="GET" action="search_transport.php" class="search-bar-container">
        
        <select name="state" id="stateSelect" class="search-select">
            <option value="">All States</option>
            <option value="Johor" <?php if($search_state == 'Johor') echo 'selected'; ?>>Johor</option>
            <option value="Melaka" <?php if($search_state == 'Melaka') echo 'selected'; ?>>Melaka</option>
            <option value="Kuala Lumpur/Selangor" <?php if($search_state == 'Kuala Lumpur/Selangor') echo 'selected'; ?>>Kuala Lumpur / Selangor</option>
        </select>

        <select name="region" id="regionSelect" class="search-select" disabled>
            <option value="">All Regions</option>
            </select>
        
        <button type="submit" class="btn-search">
            <i class="fa-solid fa-filter"></i> Filter
        </button>

        <?php if(!empty($search_state) || !empty($search_region)): ?>
            <a href="search_transport.php" class="btn-reset">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </form>
    <?php if (empty($available_rides)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-car-side" style="font-size: 48px; margin-bottom: 15px; color: #ccc;"></i>
            <p>No available carpool rides found matching your criteria.</p>
            <p><a href="passanger_request_transport.php" style="color: #2196F3; font-weight: bold;">Click here to Request a new ride</a></p>
        </div>
    <?php else: ?>
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

    // Define Region Data (Same as Request Page)
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

    // Function to populate regions
    function updateRegions(selectedState, selectedRegion = "") {
        regionSelect.innerHTML = '<option value="">All Regions</option>'; // Default

        if (selectedState && regions[selectedState]) {
            regionSelect.disabled = false;
            regions[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                if (city === selectedRegion) {
                    option.selected = true; // Restore selection
                }
                regionSelect.appendChild(option);
            });
        } else {
            regionSelect.disabled = true;
        }
    }

    // Event Listener for Change
    stateSelect.addEventListener('change', function() {
        updateRegions(this.value);
    });

    // On Page Load: Check if we need to restore previous selection (from PHP)
    // Pass PHP variable to JS
    const savedState = "<?php echo $search_state; ?>";
    const savedRegion = "<?php echo $search_region; ?>";

    if (savedState) {
        updateRegions(savedState, savedRegion);
    }
</script>

<?php include "footer.php"; ?>