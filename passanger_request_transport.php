<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// --- LOGIC: Check if user is JOINING a ride ---
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
        // If joining a ride, set status to 'Pending' so driver can confirm
        $status = 'Pending'; 

        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssss", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status);

        if($stmt->execute()){
            echo "<script>alert('Request submitted! Please wait for driver confirmation.'); window.location.href='passanger_rides.php';</script>"; 
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<style>
/* --- UPDATED DESIGN: Slightly Larger Fonts --- */

.request-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 800px;
    margin: 0 auto;
    background: #f5f7fb;
}

/* Header Typography (Increased slightly) */
.request-header-title h1 {
    margin: 0;
    font-size: 24px; /* Was 22px, now 24px */
    font-weight: 700;
    color: #004b82;
}

.request-header-title p {
    margin: 6px 0 0;
    font-size: 14px; /* Was 13px, now 14px */
    color: #666;
}

/* Card Container */
.request-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    padding: 25px 30px;
    margin-top: 20px;
}

/* Form Elements Styling */
label {
    display: block;
    margin-bottom: 8px;
    font-size: 15px; /* Was 13px, now 15px for better readability */
    font-weight: 600;
    color: #333;
    margin-top: 18px;
}

input[type="text"], 
select {
    width: 100%;
    padding: 12px 14px; /* Increased padding for comfortable typing */
    font-size: 15px; /* Was 13px, now 15px */
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

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 14px;
    background-color: #004b82;
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 16px; /* Was 14px, now 16px */
    font-weight: 600;
    cursor: pointer;
    margin-top: 30px;
    transition: background 0.2s;
}

.btn-submit:hover {
    background-color: #003660;
}

/* Info Box */
.info-box {
    background-color: #e7f3fe;
    border-left: 5px solid #2196F3;
    padding: 14px 18px;
    border-radius: 6px;
    font-size: 14px; /* Increased to 14px */
    color: #0d47a1;
    margin-bottom: 20px;
    line-height: 1.5;
}

.join-box {
    background-color: #e8f5e9;
    border: 1px solid #c8e6c9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #2e7d32;
    font-size: 14px; /* Increased to 14px */
}
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
                Our transport service is currently supported only for destinations within 
                <strong>Johor, Melaka, and Kuala Lumpur/Selangor</strong> regions.
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            
            <?php if($is_join_mode): ?>
                <input type="hidden" name="target_driver_id" value="<?php echo $pre_driver_id; ?>">
                
                <label>Date & Time (Fixed for this ride)</label>
                <input type="text" name="date_time" value="<?php echo $pre_date; ?>" readonly style="background:#f9f9f9; color:#777; cursor:not-allowed;">
            <?php else: ?>
                <label>Date & Time</label>
                <input type="text" name="date_time" placeholder="Select Date & Time" 
                       onfocus="(this.type='datetime-local')" 
                       onblur="(this.type='text')" required>
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
            <input type="text" name="address" required placeholder="e.g., No 123, Jalan Universiti, Taman Impian">

            <label>Number of Passengers</label>
            <select name="passengers" id="passengerSelect" required>
                <option value="" disabled selected hidden>Select Pax</option>
                <option value="1">1 Passenger</option>
                <option value="2">2 Passengers</option>
                <option value="3">3 Passengers</option>
                <option value="4">4 Passengers</option>
                <option value="5">5 Passengers</option>
                <option value="6">6 Passengers</option>
            </select>

            <label>Vehicle Category</label>
            <select name="vehicle_type" id="vehicleSelect" required>
                <option value="" disabled selected hidden>Select Vehicle Type</option>
                <option value="Hatchback" class="small-car">Hatchback (Max 4 Pax)</option>
                <option value="Sedan" class="small-car">Sedan (Max 4 Pax)</option>
                <option value="SUV" class="small-car">SUV (Max 4 Pax)</option>
                <option value="MPV">MPV (Max 6 Pax)</option>
            </select>

            <label>Pick-up Point</label>
            <input type="text" name="pickup" required placeholder="e.g., MMU Main Gate, Library">

            <label>Remarks (Optional)</label>
            <input type="text" name="remark" placeholder="Any luggage or special requests?">

            <button type="submit" name="request" class="btn-submit">Submit Request</button>
        </form>
    </div>
</div>

<script>
    // --- Logic 1: State & Region Dependency ---
    const stateSelect = document.getElementById('stateSelect');
    const regionSelect = document.getElementById('regionSelect');

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

    stateSelect.addEventListener('change', function() {
        const selectedState = this.value;
        regionSelect.innerHTML = '<option value="" disabled selected hidden>Select Region / City</option>';
        
        if (selectedState && regions[selectedState]) {
            regionSelect.disabled = false;
            regions[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                regionSelect.appendChild(option);
            });
        } else {
            regionSelect.disabled = true;
            regionSelect.innerHTML = '<option value="" disabled selected hidden>Please select a State first</option>';
        }
    });

    // --- Logic 2: Passenger & Vehicle Constraint ---
    const passengerSelect = document.getElementById('passengerSelect');
    const vehicleSelect = document.getElementById('vehicleSelect');
    const smallCarOptions = document.querySelectorAll('.small-car');

    passengerSelect.addEventListener('change', function() {
        const pax = parseInt(this.value);

        if (pax > 4) {
            // If > 4 passengers, disable small cars
            smallCarOptions.forEach(option => {
                option.disabled = true;
                option.style.color = "#ccc"; 
            });

            if (['Hatchback', 'Sedan', 'SUV'].includes(vehicleSelect.value)) {
                vehicleSelect.value = "MPV";
            }
        } else {
            // If <= 4 passengers, enable all cars
            smallCarOptions.forEach(option => {
                option.disabled = false;
                option.style.color = "#333";
            });
        }
    });
</script>

<?php include "footer.php"; ?>