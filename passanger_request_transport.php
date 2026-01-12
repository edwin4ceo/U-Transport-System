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

// Initialize SweetAlert variables
$swal_type = ""; 
$swal_message = "";
$swal_redirect = "";

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
        // Set warning alert
        $swal_type = "warning";
        $swal_message = "Please fill in all required fields.";
    } else {
        // If joining a ride, set status to 'Pending' so driver can confirm
        $status = 'Pending'; 

        $stmt = $conn->prepare("INSERT INTO bookings (student_id, driver_id, destination, date_time, passengers, vehicle_type, pickup_point, remark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssss", $student_id, $target_driver, $destination, $datetime, $passengers, $vehicle_type, $pickup, $remark, $status);

        if($stmt->execute()){
            // Set success alert with redirect
            $swal_type = "success";
            $swal_message = "Request submitted! Please wait for driver confirmation.";
            $swal_redirect = "passanger_rides.php";
        } else {
            // Set error alert
            $swal_type = "error";
            $swal_message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
/* --- UPDATED DESIGN: Layout & Typography --- */

.request-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 800px;
    margin: 0 auto;
    background: #f5f7fb;
}

/* Header Typography */
.request-header-title h1 {
    margin: 0;
    font-size: 24px; 
    font-weight: 700;
    color: #004b82;
}

.request-header-title p {
    margin: 6px 0 0;
    font-size: 14px; 
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
    font-size: 15px; 
    font-weight: 600;
    color: #333;
    margin-top: 18px;
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

/* --- FLATPICKR STABILIZED --- */

/* 1. Container Size */
.flatpickr-calendar {
    width: 320px !important; 
    font-size: 13px !important;
    border: none !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}
.flatpickr-days { width: 320px !important; }
.dayContainer { width: 320px !important; min-width: 320px !important; max-width: 320px !important; }
.flatpickr-day { height: 38px !important; line-height: 38px !important; max-width: 38px !important; }

/* 2. Header (Blue Background) */
.flatpickr-months {
    background-color: #004b82 !important;
    color: #fff !important;
    fill: #fff !important;
    padding: 5px 0 !important;
    height: 50px !important; 
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* 3. The Row Container for [ < Month > Year ] */
.flatpickr-current-month {
    width: 100% !important;
    left: 0 !important;
    position: static !important; 
    display: flex !important;    
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    height: 100% !important;
}

/* 4. Month Dropdown */
.flatpickr-current-month .flatpickr-monthDropdown-months {
    appearance: none;
    font-weight: 700 !important;
    color: #fff !important; 
    margin: 0 !important;
    padding: 0 5px !important;
    background: transparent !important;
    border: none !important;
}
.flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
    background-color: #fff !important;
    color: #000 !important; 
}

/* 5. Navigation Arrows (White, Inline) */
.flatpickr-prev-month, 
.flatpickr-next-month {
    position: static !important; 
    height: 30px !important;
    width: 30px !important;
    padding: 0 !important;
    margin: 0 2px !important;
    color: #fff !important;
    fill: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.flatpickr-prev-month svg, 
.flatpickr-next-month svg {
    fill: #fff !important;
    width: 14px !important;
    height: 14px !important;
}

/* 6. Year Container & Arrows */
.flatpickr-current-month .numInputWrapper {
    width: 70px !important;
    height: 30px !important; 
    display: inline-flex !important;
    flex-direction: column-reverse !important; /* Visual Down=Increase, Visual Up=Decrease */
    position: relative !important;
    margin-left: 10px !important; 
    vertical-align: middle !important;
}

/* Year Text Input */
.flatpickr-current-month input.cur-year {
    color: #fff !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    padding: 0 15px 0 0 !important; 
    text-align: right !important;
    height: 100% !important;
    display: inline-block !important;
    margin: 0 !important;
}

/* Hide native arrows */
input.numInput::-webkit-outer-spin-button,
input.numInput::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* CUSTOM ARROWS */

/* The 'arrowUp' (Increase) -> Move to Bottom -> Look like Down Arrow */
.numInputWrapper span.arrowUp {
    position: absolute !important;
    right: 0 !important;
    bottom: 0 !important;
    top: 50% !important; 
    height: 50% !important;
    width: 14px !important;
    border: none !important; padding: 0 !important;
    display: flex !important; align-items: center; justify-content: center;
    cursor: pointer !important;
    z-index: 10 !important;
}
.numInputWrapper span.arrowUp::after {
    content: "";
    border-left: 4px solid transparent; border-right: 4px solid transparent;
    border-top: 4px solid #fff; /* White Down Triangle */
    border-bottom: none;
}

/* The 'arrowDown' (Decrease) -> Move to Top -> Look like Up Arrow */
.numInputWrapper span.arrowDown {
    position: absolute !important;
    right: 0 !important;
    top: 0 !important;
    height: 50% !important;
    width: 14px !important;
    border: none !important; padding: 0 !important;
    display: flex !important; align-items: center; justify-content: center;
    cursor: pointer !important;
    z-index: 10 !important;
}
.numInputWrapper span.arrowDown::after {
    content: "";
    border-left: 4px solid transparent; border-right: 4px solid transparent;
    border-bottom: 4px solid #fff; /* White Up Triangle */
    border-top: none;
}

/* Time Picker Styling & No Highlight */
.flatpickr-time input, .flatpickr-time .flatpickr-am-pm {
    font-weight: 700 !important; 
    color: #333 !important;
}
/* FORCE REMOVE FOCUS/HIGHLIGHT */
.flatpickr-time input:focus, 
.flatpickr-time .flatpickr-am-pm:focus,
.flatpickr-time input:hover,
.flatpickr-time .flatpickr-am-pm:hover {
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.flatpickr-time .numInputWrapper span.arrowUp::after { border-top-color: #333 !important; }
.flatpickr-time .numInputWrapper span.arrowDown::after { border-bottom-color: #333 !important; }

/* Submit Button */
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
    font-size: 14px; 
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
    font-size: 14px; 
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
                <input type="text" name="date_time" id="datetimepicker" placeholder="Select Date & Time" required>
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
    // --- Logic 0: Initialize Flatpickr ---
    flatpickr("#datetimepicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i", 
        minDate: "today",        
        time_24hr: false, 
        minuteIncrement: 5,
        altInput: true,          
        altFormat: "F j, Y at h:i K", 
        
        // --- JS: Layout Fix & Anti-Highlight ---
        onReady: function(selectedDates, dateStr, instance) {
            const currentMonthContainer = instance.monthNav.querySelector('.flatpickr-current-month');
            const yearWrapper = currentMonthContainer.querySelector('.numInputWrapper');
            const prevArrow = instance.prevMonthNav;
            const nextArrow = instance.nextMonthNav;
            
            // 1. Move arrows to create [ < ] [ Month ] [ > ] [ Year ]
            currentMonthContainer.insertBefore(prevArrow, currentMonthContainer.firstChild);
            currentMonthContainer.insertBefore(nextArrow, yearWrapper);
        },

        // --- NEW: Fix Auto-Highlight Issue ---
        onChange: function(selectedDates, dateStr, instance) {
            // Force blur the time inputs so they don't get highlighted blue
            setTimeout(() => {
                const timeInputs = instance.calendarContainer.querySelectorAll(".flatpickr-time input");
                timeInputs.forEach(input => input.blur());
            }, 1); 
        }
    });

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

    // --- Logic 3: SweetAlert Trigger (PHP-Driven) ---
    <?php if ($swal_message != ""): ?>
        Swal.fire({
            title: "<?php echo ($swal_type == 'success') ? 'Success!' : 'Notice'; ?>",
            text: "<?php echo $swal_message; ?>",
            icon: "<?php echo $swal_type; ?>",
            confirmButtonColor: '#004b82',
            confirmButtonText: 'OK'
        }).then((result) => {
            <?php if ($swal_redirect != ""): ?>
                if (result.isConfirmed || result.isDismissed) {
                    window.location.href = "<?php echo $swal_redirect; ?>";
                }
            <?php endif; ?>
        });
    <?php endif; ?>

</script>

<?php include "footer.php"; ?>