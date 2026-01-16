<?php
// Start the session to handle login states
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DETERMINE LOGO LINK based on who is logged in
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

    /* 2. GLOBAL FIX: Prevent Text Selection & Force Font */
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

    /* 3. SWEETALERT CUSTOMIZATION (Unified Style) */
    
    /* Popup Card */
    .swal2-popup {
        border-radius: 20px !important;
        padding: 30px !important;
    }
    
    /* Title */
    .swal2-title {
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 24px !important;
    }
    
    /* Content Text */
    .swal2-html-container {
        font-size: 15px !important;
        color: #666 !important;
    }
    
    /* Confirm Button (Blue) */
    .swal2-confirm {
        background-color: #005A9C !important; /* Theme Blue */
        border-radius: 10px !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        font-size: 15px !important;
        box-shadow: none !important;
    }
    
    /* Cancel Button (Red - for Logout) */
    .swal2-cancel {
        background-color: #e74c3c !important; /* Soft Red */
        border-radius: 10px !important;
        font-weight: 600 !important;
        padding: 12px 30px !important;
        font-size: 15px !important;
        box-shadow: none !important;
        margin-left: 15px !important; /* Gap between buttons */
    }

    /* Remove focus outlines */
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
                    // Passenger menu
                    ?>
                    <li><a href="search_transport.php">Search Rides</a></li>
                    <li><a href="passanger_request_transport.php">Request Ride</a></li>
                    <li><a href="passanger_rides.php">My Rides</a></li> 
                    <li><a href="qa_forum.php">Q&amp;A</a></li>
                    <li><a href="passanger_profile.php">Profile</a></li>
                    <li><a href="#" id="passengerLogoutLink" style="color: #ffcccb;">Logout</a></li>
                    <?php
                } elseif (isset($_SESSION['driver_id'])) {
                    // Driver menu
                    ?>
                    <li><a href="driver_dashboard.php">Dashboard</a></li>
                    <li><a href="driver_rides.php">My Rides</a></li>
                    <li><a href="driver_history.php">History</a></li>
                    <li><a href="driver_profile.php">Profile</a></li>
                    <li><a href="#" id="driverLogoutLink" style="color: #ffcccb;">Logout</a></li>
                    <?php
                } else {
                    // Guest menu
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

    // Helper Function for Consistent Logout Alert
    function showLogoutAlert(redirectUrl) {
        Swal.fire({
            title: "Confirm Logout?",
            text: "Are you sure you want to logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Logout",
            cancelButtonText: "Cancel",
            buttonsStyling: false, // Important: Disable default styles to use customClass
            customClass: {
                popup: 'swal2-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = redirectUrl;
            }
        });
    }

    // Passenger Logout Listener
    const passengerLogout = document.getElementById("passengerLogoutLink");
    if (passengerLogout) {
        passengerLogout.addEventListener("click", function (e) {
            e.preventDefault();
            showLogoutAlert("logout.php");
        });
    }

    // Driver Logout Listener
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