<?php
// FUNCTION: START SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LOGIC: DETERMINE CURRENT PAGE (For Active Menu Highlight)
$current_page = basename($_SERVER['PHP_SELF']);

// LOGIC: DETERMINE LOGO LINK
$logo_link = "index.php"; 

if (isset($_SESSION['student_id'])) {
    $logo_link = "passenger_home.php";
} elseif (isset($_SESSION['driver_id'])) {
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

    /* 2. GLOBAL FIX */
    body {
        font-family: 'Poppins', sans-serif !important; 
        user-select: none;           
        -webkit-user-select: none;   
        cursor: default;
        
        /* [KEY CHANGE] Add padding to body so content isn't hidden behind fixed header */
        padding-top: 80px; 
        margin: 0;
    }
    
    h1, h2, h3, h4, h5, h6, p, span, label, a, li, button, .swal2-popup {
        font-family: 'Poppins', sans-serif !important; 
    }
    input, select, textarea { 
        user-select: text !important; -webkit-user-select: text !important; cursor: text !important; 
        font-family: 'Poppins', sans-serif !important;
    }
    a, button, .btn, .submit { cursor: pointer !important; }

    /* ========================================= */
    /* [NEW] FIXED HEADER STYLES                 */
    /* ========================================= */
    header {
        position: fixed;        /* Fix to top */
        top: 0;
        left: 0;
        width: 100%;            /* Full width */
        z-index: 1000;          /* Ensure it sits above content */
        background-color: #004b82; /* Ensure background is solid (Theme Blue) */
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Add shadow for depth */
        height: 80px;           /* Fixed height */
        display: flex;          /* Center content vertically */
        align-items: center;
    }

    /* Ensure container inside header behaves correctly */
    header .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* ========================================= */
    /* 3. MENU STYLES                            */
    /* ========================================= */
    nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 25px;
    }

    nav ul li a {
        position: relative;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
        padding-bottom: 5px;
        transition: color 0.3s ease;
        letter-spacing: 0.3px;
        font-size: 15px;
    }

    /* The "Silky Line" Animation */
    nav ul li a::after {
        content: '';
        position: absolute;
        width: 0%;
        height: 3px;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #ffffff;
        border-radius: 10px;
        transition: width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); 
    }

    nav ul li a:hover { color: #ffffff; opacity: 1; }
    nav ul li a:hover::after { width: 40%; background-color: rgba(255, 255, 255, 0.8); }

    nav ul li a.active { color: #ffffff; font-weight: 700; }
    nav ul li a.active::after { width: 100%; background-color: #ffffff; }

    /* LOGO STYLE */
    .logo h1 { margin: 0; }
    .logo a {
        text-decoration: none;
        color: white;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* 4. SWEETALERT DESIGN */
    .swal2-popup { border-radius: 20px !important; padding: 30px !important; }
    .swal2-title { font-weight: 600 !important; color: #333 !important; font-size: 24px !important; }
    .swal2-html-container { font-size: 15px !important; color: #666 !important; }
    .swal2-actions { gap: 15px !important; }
    .swal2-confirm { background-color: #005A9C !important; color: #fff !important; border-radius: 10px !important; font-weight: 600 !important; padding: 12px 30px !important; font-size: 15px !important; box-shadow: 0 4px 6px rgba(0, 90, 156, 0.2) !important; border: none !important; outline: none !important; }
    .swal2-cancel { background-color: #e74c3c !important; color: #fff !important; border-radius: 10px !important; font-weight: 600 !important; padding: 12px 30px !important; font-size: 15px !important; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2) !important; border: none !important; outline: none !important; }
    .swal2-confirm:hover { background-color: #004a80 !important; }
    .swal2-cancel:hover { background-color: #c0392b !important; }
    .swal2-confirm:focus, .swal2-cancel:focus { box-shadow: none !important; }
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
                    ?>
                    <li><a href="passenger_home.php" class="<?php echo ($current_page == 'passenger_home.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="search_transport.php" class="<?php echo ($current_page == 'search_transport.php') ? 'active' : ''; ?>">Search</a></li>
                    <li><a href="passanger_request_transport.php" class="<?php echo ($current_page == 'passanger_request_transport.php') ? 'active' : ''; ?>">Request</a></li>
                    <li><a href="passanger_rides.php" class="<?php echo ($current_page == 'passanger_rides.php') ? 'active' : ''; ?>">My Rides</a></li> 
                    <li><a href="FAQ.php" class="<?php echo ($current_page == 'FAQ.php') ? 'active' : ''; ?>">FAQ</a></li>
                    <li><a href="passanger_profile.php" class="<?php echo ($current_page == 'passanger_profile.php') ? 'active' : ''; ?>">Profile</a></li>
                    <li><a href="#" id="passengerLogoutLink" style="color: #ffcccb; opacity: 0.9;">Logout</a></li>
                    <?php
                } elseif (isset($_SESSION['driver_id'])) {
                    // --- DRIVER MENU ---
                    ?>
                    <li><a href="driver_dashboard.php" class="<?php echo ($current_page == 'driver_dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="driver_rides.php" class="<?php echo ($current_page == 'driver_rides.php') ? 'active' : ''; ?>">My Rides</a></li>
                    <li><a href="driver_history.php" class="<?php echo ($current_page == 'driver_history.php') ? 'active' : ''; ?>">History</a></li>
                    <li><a href="driver_profile.php" class="<?php echo ($current_page == 'driver_profile.php') ? 'active' : ''; ?>">Profile</a></li>
                    <li><a href="#" id="driverLogoutLink" style="color: #ffcccb; opacity: 0.9;">Logout</a></li>
                    <?php
                } else {
                    // --- GUEST MENU ---
                    ?>
                    <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="contact_us.php" class="<?php echo ($current_page == 'contact_us.php') ? 'active' : ''; ?>">Contact Us</a></li>
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
    function showLogoutAlert(redirectUrl) {
        Swal.fire({
            title: "Confirm Logout?",
            text: "Are you sure you want to logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Logout",
            cancelButtonText: "Cancel",
            buttonsStyling: false, 
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
                    window.location.href = redirectUrl;
                });
            }
        });
    }

    const passengerLogout = document.getElementById("passengerLogoutLink");
    if (passengerLogout) {
        passengerLogout.addEventListener("click", function (e) {
            e.preventDefault();
            showLogoutAlert("logout.php");
        });
    }

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