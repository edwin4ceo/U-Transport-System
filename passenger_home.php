<?php 
session_start();
include "db_connect.php";
include "function.php";

// Check if user is logged in. If not, redirect to login.
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// Include the Header
include "header.php"; 
?>

<style>
    footer {
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }
</style>

<h2>Welcome back, <?php echo $_SESSION['student_name']; ?>!</h2>
<p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>

<h3>Quick Search</h3>
<form action="search_transport.php" method="GET">
    
    <label>Destination State</label>
    <select name="state" id="searchStateSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;">
        <option value="" selected>All States</option>
        <option value="Johor">Johor</option>
        <option value="Melaka">Melaka</option>
        <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
    </select>

    <label>Destination Region / City</label>
    <select name="region" id="searchRegionSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;" disabled>
        <option value="" selected>All Regions</option>
    </select>

    <label for="destination">Where to?</label>
    <input type="text" id="destination" name="destination" placeholder="e.g., University Library, Mall" style="width: 100%; padding: 10px; margin-bottom: 15px;">
    
    <label>Number of Passengers</label>
    <select name="passengers" id="searchPassengerSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;">
        <option value="" disabled selected hidden>Select Pax</option>
        <option value="1">1 Passenger</option>
        <option value="2">2 Passengers</option>
        <option value="3">3 Passengers</option>
        <option value="4">4 Passengers</option>
        <option value="5">5 Passengers</option>
        <option value="6">6 Passengers</option>
    </select>

    <label>Vehicle Category</label>
    <select name="vehicle_type" id="searchVehicleSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;">
        <option value="" selected>Any Type</option>
        <option value="Hatchback" class="small-car">Hatchback (Max 4)</option>
        <option value="Sedan" class="small-car">Sedan (Max 4)</option>
        <option value="SUV" class="small-car">SUV (Max 4)</option>
        <option value="MPV">MPV (Max 6)</option>
    </select>

    <button type="submit" style="width: 100%;">Search Rides</button>
</form>

<div style="background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-top: 25px; margin-bottom: 20px; border-radius: 4px;">
    <strong><i class="fa-solid fa-circle-info"></i> Service Notice:</strong> <br>
    Our transport service is currently supported only for destinations within 
    <strong>Johor, Melaka, and Kuala Lumpur/Selangor</strong> regions.
</div>

<script>
    // --- Logic 1: State & Region Dependency ---
    const stateSelect = document.getElementById('searchStateSelect');
    const regionSelect = document.getElementById('searchRegionSelect');

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
        regionSelect.innerHTML = '<option value="" selected>All Regions</option>';
        
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
        }
    });

    // --- Logic 2: Passenger & Vehicle Constraint ---
    const passengerSelect = document.getElementById('searchPassengerSelect');
    const vehicleSelect = document.getElementById('searchVehicleSelect');
    // Get all options that correspond to small cars (Hatchback, Sedan, SUV)
    const smallCarOptions = document.querySelectorAll('.small-car');

    passengerSelect.addEventListener('change', function() {
        const pax = parseInt(this.value);

        if (pax > 4) {
            // Logic: If more than 4 people, disable small cars
            smallCarOptions.forEach(option => {
                option.disabled = true; // Make unclickable
                option.style.color = "#ccc"; // Visual cue (greyed out)
            });

            // If user previously selected a small car, force reset to "Any" or "MPV"
            if (vehicleSelect.value === "Hatchback" || vehicleSelect.value === "Sedan" || vehicleSelect.value === "SUV") {
                vehicleSelect.value = "MPV"; 
            }
        } else {
            // Logic: If 4 or less, enable everything
            smallCarOptions.forEach(option => {
                option.disabled = false;
                option.style.color = "#333";
            });
        }
    });
</script>

<?php 
// Include the Footer
include "footer.php"; 
?>