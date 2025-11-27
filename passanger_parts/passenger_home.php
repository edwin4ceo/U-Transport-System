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

<h2>Welcome back, <?php echo $_SESSION['student_name']; ?>!</h2>
<p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>

<h3>Quick Search</h3>
<form action="search_transport.php" method="GET">
    
    <label>State</label>
    <select name="state" id="searchStateSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;">
        <option value="" selected>All States</option>
        <option value="Johor">Johor</option>
        <option value="Melaka">Melaka</option>
        <option value="Kuala Lumpur/Selangor">Kuala Lumpur/Selangor</option>
    </select>

    <label>Region / City</label>
    <select name="region" id="searchRegionSelect" style="color: #333; width: 100%; padding: 10px; margin-bottom: 15px;" disabled>
        <option value="" selected>All Regions</option>
    </select>

    <label for="destination">Where to?</label>
    <input type="text" id="destination" name="destination" placeholder="e.g., University Library, Mall" style="width: 100%; padding: 10px; margin-bottom: 15px;">
    
    <button type="submit" style="width: 100%;">Search Rides</button>
</form>

<div style="background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-top: 25px; margin-bottom: 20px; border-radius: 4px;">
    <strong><i class="fa-solid fa-circle-info"></i> Service Notice:</strong> <br>
    Our transport service is currently supported only for destinations within 
    <strong>Johor, Melaka, and Kuala Lumpur/Selangor</strong> regions.
</div>

<script>
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
        
        // Reset region dropdown
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
            // If "All States" or invalid selection, disable region
            regionSelect.disabled = true;
        }
    });
</script>

<?php 
// Include the Footer
include "footer.php"; 
?>