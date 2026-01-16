<?php
// FUNCTION: START SESSION
// Ensure session is started to handle login states
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LOGIC: DETERMINE LOGO LINK
// Clicking the logo should take the user to their respective dashboard
$logo_link = "index.php"; // Default for guests

if (isset($_SESSION['student_id'])) {
    // Passenger is logged in
    $logo_link = "passenger_home.php";
} elseif (isset($_SESSION['driver_id'])) {
    // Driver is logged in
    $logo_link = "driver_dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U-Transport System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<style>
    /* 1. IMPORT POPPINS FONT */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    /* 2. GLOBAL FIX: Force Font & Prevent Text Selection */
    body, h1, h2, h3, h4, h5, h6, p, span, label, a, li, button, .swal2-popup {
        font-family: 'Poppins', sans-serif !important; 
        user-select: none;           
        -webkit-user-select: none;   
        cursor: default;             
    }

    /* Exception: Allow typing in inputs */
    input, select, textarea { 
        user-select: text !important; -webkit-user-select: text !important; cursor: text !important; 
        font-family: 'Poppins', sans-serif !important;
    }
    
    /* Exception: Pointers for clickable items */
    a, button, .btn, .submit { 
        cursor: pointer !important; 
    }

    /* 3. SWEETALERT DESIGN (Unified Style) */
    
    /* Popup Card: Rounded Corners */
    .swal2-popup {
        border-radius: 20px !important;
        padding: 30px !important;
    }
    
    /* Title: Bold & Dark */
    .swal2-title {
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 24px !important;
    }
    
    /* Content Text: Standard Grey */
    .swal2-html-container {
        font-size: 15px !important;
        color: #666 !important;
    }
    
    /* Button Container: Add spacing */
    .swal2-actions {
        gap: 15px !important; 
    }

    /* Confirm Button (Blue - Theme Color) */
    /* NOTE: White text color forced */
    .swal2-confirm {
        background-color: #005A9C !important;
        color: #fff !important; 
        border-radius: 10px !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        font-size: 15px !important;
        box-shadow: 0 4px 6px rgba(0, 90, 156, 0.2) !important;
        border: none !important;
        outline: none !important;
    }
    
    /* Cancel Button (Red - Danger Color) */
    /* NOTE: White text color forced */
    .swal2-cancel {
        background-color: #e74c3c !important;
        color: #fff !important; 
        border-radius: 10px !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        font-size: 15px !important;
        box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2) !important;
        border: none !important;
        outline: none !important;
    }

    /* Hover Effects */
    .swal2-confirm:hover { background-color: #004a80 !important; }
    .swal2-cancel:hover { background-color: #c0392b !important; }

    /* Remove default focus outlines */
    .swal2-confirm:focus, .swal2-cancel:focus {
        box-shadow: none !important;
    }
</style>

<header>
    <div class="container">
        <div class="logo">
            <h1>
                <a href="<?php echo $logo_link; ?>">
                    <i class="fa-solid fa-car"></i> U-Transport
                </a>
            </h1>
        </div>
        <nav>
            <ul>
                <?php
                if (isset($_SESSION['student_id'])) {
                    // --- PASSENGER MENU ---
                    // Added "Home" and shortened names to fit cleanly
                    ?>
                    <li><a href="passenger_home.php"><i class="fa-solid fa-house"></i> Home</a></li>
                    
                    <li><a href="search_transport.php">Search</a></li>
                    
                    <li><a href="passanger_request_transport.php">Request</a></li>
                    
                    <li><a href="passanger_rides.php">My Rides</a></li> 
                    <li><a href="qa_forum.php">FAQ</a></li>
                    <li><a href="passanger_profile.php">Profile</a></li>
                    <li><a href="#" id="passengerLogoutLink" style="color: #ffcccb;">Logout</a></li>
                    <?php
                } elseif (isset($_SESSION['driver_id'])) {
                    // --- DRIVER MENU ---
                    // Added "Home" for driver as well
                    ?>
                    <li><a href="driver_dashboard.php"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="driver_rides.php">My Rides</a></li>
                    <li><a href="driver_history.php">History</a></li>
                    <li><a href="driver_profile.php">Profile</a></li>
                    <li><a href="#" id="driverLogoutLink" style="color: #ffcccb;">Logout</a></li>
                    <?php
                } else {
                    // --- GUEST MENU ---
                    ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="contact_us.php">Contact Us</a></li>
                    <?php
                }
                ?>
            </ul>
        </nav>
    </div>
</header>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // FUNCTION: Show Logout Confirmation -> Then Success -> Then Redirect
    function showLogoutAlert(redirectUrl) {
        // Step 1: Show Confirmation Alert
        Swal.fire({
            title: "Confirm Logout?",
            text: "Are you sure you want to logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Logout",
            cancelButtonText: "Cancel",
            buttonsStyling: false, // DISABLE default styling to use custom classes
            customClass: {
                popup: 'swal2-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel',
                actions: 'swal2-actions'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Step 2: Show "Logout Successful" Alert (Matches Login Style)
                Swal.fire({
                    title: 'Logout Successful!',
                    text: 'You have been logged out safely.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'swal2-popup',
                        title: 'swal2-title',
                        htmlContainer: 'swal2-html-container'
                    }
                }).then(() => {
                    // Step 3: Redirect to actual PHP logout script
                    window.location.href = redirectUrl;
                });
            }
        });
    }

    // LISTENER: Passenger Logout
    const passengerLogout = document.getElementById("passengerLogoutLink");
    if (passengerLogout) {
        passengerLogout.addEventListener("click", function (e) {
            e.preventDefault();
            showLogoutAlert("logout.php");
        });
    }

    // LISTENER: Driver Logout
    const driverLogout = document.getElementById("driverLogoutLink");
    if (driverLogout) {
        driverLogout.addEventListener("click", function (e) {
            e.preventDefault();
            showLogoutAlert("driver_logout.php");
        });
    }

});
</script>

<main>
    <div class="container content-area">