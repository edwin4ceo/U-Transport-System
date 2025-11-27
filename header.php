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
                    <li><a href="passanger_booking_history.php">My History</a></li>
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

<!-- SweetAlert2 for logout confirmation -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const passengerLogout = document.getElementById("passengerLogoutLink");
    if (passengerLogout) {
        passengerLogout.addEventListener("click", function (e) {
            e.preventDefault();
            Swal.fire({
                title: "Confirm Logout?",
                text: "Are you sure you want to logout?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e74c3c",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "logout.php"; // passenger logout
                }
            });
        });
    }

    const driverLogout = document.getElementById("driverLogoutLink");
    if (driverLogout) {
        driverLogout.addEventListener("click", function (e) {
            e.preventDefault();
            Swal.fire({
                title: "Confirm Logout?",
                text: "Are you sure you want to logout?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e74c3c",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "driver_logout.php"; // driver logout
                }
            });
        });
    }

});
</script>

<main>
    <div class="container content-area">
