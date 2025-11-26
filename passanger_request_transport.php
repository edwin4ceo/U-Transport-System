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
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];

    // Basic Validation
    if(empty($state) || empty($region) || empty($address) || empty($datetime) || empty($pickup) || empty($passengers)){
        alert("Please fill in all required fields.");
    } else {
        // 3. Insert into Database
        $sql = "INSERT INTO bookings (student_id, destination, date_time, passengers, pickup_point, remark, status)
                VALUES ('$student_id', '$destination', '$datetime', '$passengers', '$pickup', '$remark', 'Pending')";

        if($conn->query($sql)){
            alert("Transport request submitted successfully!");
            redirect("passanger_booking_history.php"); 
        } else {
            alert("Error submitting request: " . $conn->error);
        }
    }
}
?>

<?php include "header.php"; ?>

<h2>Request Transport</h2>

<div style="background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
    <strong><i class="fa-solid fa-circle-info"></i> Service Notice:</strong> <br>
    Our transport service is currently supported only for destinations within 
    <strong>Johor, Melaka, and Kuala Lumpur/Selangor</strong> regions.
</div>

<p>Fill in the details below to book a ride.</p>

<form action="" method="POST">
    
    <label>State</label>
    <select name="state" id="stateSelect" required style="color: #333;">
        <option value="" disabled selected hidden>Select State</option>
        <option value="Johor">Johor</option>
        <option value="Melaka">Melaka</option>
        <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
    </select>

    <label>Region / City</label>
    <select name="region" id="regionSelect" required style="color: #333;" disabled>
        <option value="" disabled selected hidden>Please select a State first</option>
        </select>

    <label>Specific Address</label>
    <input type="text" name="address" required placeholder="e.g., No 123, Jalan Universiti, Taman Impian">

    <label>Date & Time</label>
    <input type="text" name="date_time" placeholder="Select Date & Time" 
           onfocus="(this.type='datetime-local')" 
           onblur="(this.type='text')" required>

    <label>Number of Passengers</label>
    <select name="passengers" required style="color: #333;">
        <option value="" disabled selected hidden>Select Number of Passengers</option>
        <option value="1">1 Passenger</option>
        <option value="2">2 Passengers</option>
        <option value="3">3 Passengers</option>
        <option value="4">4 Passengers</option>
        <option value="5">5 Passengers</option>
        <option value="6">6 Passengers</option>
    </select>

    <label>Pick-up Point</label>
    <input type="text" name="pickup" required placeholder="e.g., MMU Main Gate, Library">

    <label>Remarks (Optional)</label>
    <input type="text" name="remark" placeholder="Any special requests?">

    <button type="submit" name="request">Submit Request</button>
</form>

<script>
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
</script>

<?php include "footer.php"; ?>