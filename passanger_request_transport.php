<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check if user is logged in
if(!isset($_SESSION['student_id'])){
    // If not logged in, redirect to login page
    redirect("passanger_login.php");
}

// 2. Handle Form Submission
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    
    // Combine State, Region, and Address into one string
    $state        = $_POST['state'];
    $region       = $_POST['region'];
    $address      = $_POST['address'];
    $destination  = $state . ", " . $region . " - " . $address;
    
    $datetime     = $_POST['date_time'];
    $passengers   = $_POST['passengers'];
    $vehicle_type = $_POST['vehicle_type']; // Capture the vehicle type
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];

    // Basic Validation
    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers) || empty($vehicle_type)){
        echo "<script>alert('Please fill in all required fields.');</script>";
    } else {
        // 3. Insert into Database
        // Note: Ensure the 'vehicle_type' column exists in your 'bookings' table
        $stmt = $conn->prepare("INSERT INTO bookings (student_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssisss", $student_id, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark);

        if($stmt->execute()){
            echo "<script>alert('Transport request submitted successfully!'); window.location.href='passanger_booking_history.php';</script>"; 
        } else {
            echo "<script>alert('Error submitting request: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<h2>Request Transport</h2>

<div style="background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
    <strong><i class="fa-solid fa-circle-info"></i> Service Notice:</strong> <br>
    Our transport service is currently supported only for destinations within 
    <strong>Johor, Melaka, and Kuala Lumpur/Selangor</strong> regions.
</div>

<p>Fill in the details below to book a ride.</p>

<form action="" method="POST">
    
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
    <input type="text" name="address" required placeholder="e.g., No 123, Jalan Universiti, Taman Impian">

    <label>Date & Time</label>
    <input type="text" name="date_time" placeholder="Select Date & Time" 
           onfocus="(this.type='datetime-local')" 
           onblur="(this.type='text')" required>

    <label>Number of Passengers</label>
    <select name="passengers" id="passengerSelect" required style="color: #333;">
        <option value="" disabled selected hidden>Select Number of Passengers</option>
        <option value="1">1 Passenger</option>
        <option value="2">2 Passengers</option>
        <option value="3">3 Passengers</option>
        <option value="4">4 Passengers</option>
        <option value="5">5 Passengers</option>
        <option value="6">6 Passengers</option>
    </select>

    <label>Vehicle Category</label>
    <select name="vehicle_type" id="vehicleSelect" required style="color: #333;">
        <option value="" disabled selected hidden>Select Vehicle Type</option>
        <option value="Hatchback" class="small-car">Hatchback (Max 4 Pax)</option>
        <option value="Sedan" class="small-car">Sedan (Max 4 Pax)</option>
        <option value="SUV" class="small-car">SUV (Max 4 Pax)</option>
        <option value="MPV">MPV (Max 6 Pax)</option>
    </select>

    <label>Pick-up Point</label>
    <input type="text" name="pickup" required placeholder="e.g., MMU Main Gate, Library">

    <label>Remarks (Optional)</label>
    <input type="text" name="remark" placeholder="Any special requests?">

    <button type="submit" name="request">Submit Request</button>
</form>

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
                option.style.color = "#ccc"; // Visual cue
            });

            // If user had a small car selected, switch to MPV automatically
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