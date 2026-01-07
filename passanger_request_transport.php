<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// --- LOGIC TO HANDLE "JOIN RIDE" PRE-FILL ---
$pre_driver_id = isset($_GET['join_driver']) ? $_GET['join_driver'] : "";
$pre_date      = isset($_GET['join_date']) ? $_GET['join_date'] : "";
$pre_dest      = isset($_GET['join_dest']) ? $_GET['join_dest'] : "";
$is_join_mode  = !empty($pre_driver_id);

// 2. Handle Form Submission
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    
    // Combine Address
    $state        = $_POST['state'];
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    
    $datetime     = $_POST['date_time'];
    $passengers   = $_POST['passengers'];
    $vehicle_type = $_POST['vehicle_type']; 
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];
    
    // Hidden Driver ID if joining
    $target_driver = isset($_POST['target_driver_id']) ? $_POST['target_driver_id'] : NULL;

    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers) || empty($vehicle_type)){
        echo "<script>alert('Please fill in all required fields.');</script>";
    } else {
        // Insert with Specific Driver ID if joining, else Driver ID is NULL (Pending)
        $status = ($target_driver) ? 'Accepted' : 'Pending'; // Auto-accept if joining? Or keep Pending? Let's keep Pending for driver to confirm.
        $status = 'Pending'; // Safer to let driver confirm the extra passengers

        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssss", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status);

        if($stmt->execute()){
            echo "<script>alert('Request submitted! Driver will confirm shortly.'); window.location.href='passanger_rides.php';</script>"; 
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<h2><?php echo $is_join_mode ? "Join an Existing Ride" : "Request Transport"; ?></h2>

<?php if($is_join_mode): ?>
    <div style="background-color: #e8f5e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #2e7d32;">
        <strong><i class="fa-solid fa-check-circle"></i> You are joining a ride!</strong><br>
        Destination: <b><?php echo htmlspecialchars($pre_dest); ?></b><br>
        Time: <b><?php echo htmlspecialchars($pre_date); ?></b>
    </div>
<?php endif; ?>

<div style="background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
    <strong><i class="fa-solid fa-circle-info"></i> Notice:</strong> Service available for Johor, Melaka, and KL/Selangor.
</div>

<form action="" method="POST">
    
    <?php if($is_join_mode): ?>
        <input type="hidden" name="target_driver_id" value="<?php echo $pre_driver_id; ?>">
        <label>Date & Time (Fixed for this ride)</label>
        <input type="text" name="date_time" value="<?php echo $pre_date; ?>" readonly style="background:#f0f0f0;">
    <?php else: ?>
        <label>Date & Time</label>
        <input type="text" name="date_time" placeholder="Select Date & Time" onfocus="(this.type='datetime-local')" onblur="(this.type='text')" required>
    <?php endif; ?>

    <label>Destination State</label>
    <select name="state" id="stateSelect" required style="color: #333;">
        <option value="" disabled selected hidden>Select State</option>
        <option value="Johor">Johor</option>
        <option value="Melaka">Melaka</option>
        <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
    </select>

    <label>Destination Region / City</label>
    <select name="region" id="regionSelect" required style="color: #333;" disabled>
        <option value="" disabled selected hidden>Please select a State first</option>
    </select>

    <label>Specific Destination Address</label>
    <input type="text" name="address" required placeholder="e.g., No 123, Jalan Universiti">

    <label>Number of Passengers</label>
    <select name="passengers" id="passengerSelect" required style="color: #333;">
        <option value="1">1 Passenger</option>
        <option value="2">2 Passengers</option>
        <option value="3">3 Passengers</option>
        <option value="4">4 Passengers</option>
    </select>

    <label>Vehicle Category</label>
    <select name="vehicle_type" id="vehicleSelect" required style="color: #333;">
        <option value="Hatchback" class="small-car">Hatchback</option>
        <option value="Sedan" class="small-car">Sedan</option>
        <option value="SUV" class="small-car">SUV</option>
        <option value="MPV">MPV</option>
    </select>

    <label>Pick-up Point</label>
    <input type="text" name="pickup" required placeholder="e.g., MMU Main Gate">

    <label>Remarks</label>
    <input type="text" name="remark" placeholder="Luggage? Special request?">

    <button type="submit" name="request">Submit Request</button>
</form>

<script>
    // Region Logic (Same as before)
    const stateSelect = document.getElementById('stateSelect');
    const regionSelect = document.getElementById('regionSelect');
    const regions = {
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai"],
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"],
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Cyberjaya", "Putrajaya"]
    };

    stateSelect.addEventListener('change', function() {
        const selectedState = this.value;
        regionSelect.innerHTML = '<option value="" disabled selected hidden>Select Region</option>';
        if (selectedState && regions[selectedState]) {
            regionSelect.disabled = false;
            regions[selectedState].forEach(function(city) {
                let opt = document.createElement('option');
                opt.value = city; opt.textContent = city;
                regionSelect.appendChild(opt);
            });
        }
    });
</script>

<?php include "footer.php"; ?>