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
    .search-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    label {
        font-weight: 600;
        display: block;
        margin-bottom: 5px;
        margin-top: 15px;
    }
    select, input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-sizing: border-box;
    }
    .btn-search {
        width: 100%;
        padding: 14px;
        background-color: #004b82;
        color: white;
        border: none;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 25px;
    }
</style>

<h2>Welcome back, <?php echo $_SESSION['student_name']; ?>!</h2>
<p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>

<div class="search-card">
    <h3>Quick Search</h3>
    <form action="search_transport.php" method="GET">
        
        <label><i class="fa-solid fa-location-dot"></i> Pick-up Point (MMU Campus)</label>
        <select name="pickup" id="searchPickupPoint">
            <option value="" selected>All Pick-up Points</option>
            <option value="MMU Main Gate">MMU Main Gate (Front)</option>
            <option value="MMU Back Gate">MMU Back Gate (Back)</option>
            <option value="MMU Library">MMU Library</option>
            <option value="MMU FOL Building">MMU FOL Building</option>
            <option value="MMU FOB Building">MMU FOB Building</option>
            <option value="MMU Female Hostel">MMU Female Hostel</option>
            <option value="MMU Male Hostel">MMU Male Hostel</option>
        </select>

        <label>Destination State</label>
        <select name="state" id="searchStateSelect">
            <option value="" selected>All States</option>
            <option value="Johor">Johor</option>
            <option value="Melaka">Melaka</option>
            <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
        </select>

        <label>Destination Region / City</label>
        <select name="region" id="searchRegionSelect" disabled>
            <option value="" selected>Select State First</option>
        </select>

        <label>Passengers</label>
        <select name="passengers" id="searchPassengerSelect">
            <option value="1" selected>1 Passenger</option>
            <option value="2">2 Passengers</option>
            <option value="3">3 Passengers</option>
            <option value="4">4 Passengers</option>
            <option value="5">5 Passengers</option>
            <option value="6">6 Passengers</option>
        </select>

        <label>Vehicle Preference</label>
        <select name="vehicle_type" id="searchVehicleSelect">
            <option value="" selected>Any Vehicle</option>
            <option value="Hatchback" class="small-car">Hatchback (Max 4)</option>
            <option value="Sedan" class="small-car">Sedan (Max 4)</option>
            <option value="SUV" class="small-car">SUV (Max 4)</option>
            <option value="MPV">MPV (Max 6)</option>
        </select>

        <button type="submit" class="btn-search">Search Rides</button>
    </form>
</div>

<script>
    // --- Logic 1: State & Region Dynamic Dropdown ---
    const stateSelect = document.getElementById('searchStateSelect');
    const regionSelect = document.getElementById('searchRegionSelect');

    const regions = {
        "Johor": ["Johor Bahru", "Skudai", "Muar", "Batu Pahat", "Kluang", "Segamat", "Kulai", "Tangkak", "Pagoh"],
        "Melaka": ["Melaka City", "Ayer Keroh", "Alor Gajah", "Jasin"],
        "Kuala Lumpur/Selangor": ["Kuala Lumpur", "Petaling Jaya", "Shah Alam", "Subang Jaya", "Cyberjaya", "Putrajaya", "Seremban", "Nilai"]
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
    const smallCarOptions = document.querySelectorAll('.small-car');

    passengerSelect.addEventListener('change', function() {
        const pax = parseInt(this.value);

        if (pax > 4) {
            smallCarOptions.forEach(option => {
                option.disabled = true;
                option.style.color = "#ccc";
            });

            if (vehicleSelect.value === "Hatchback" || vehicleSelect.value === "Sedan" || vehicleSelect.value === "SUV") {
                vehicleSelect.value = "MPV"; 
            }
        } else {
            smallCarOptions.forEach(option => {
                option.disabled = false;
                option.style.color = "#333";
            });
        }
    });
</script>

<?php include "footer.php"; ?>