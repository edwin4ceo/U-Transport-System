<?php
// Start the session to handle login states
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DETERMINE LOGO LINK based on who is logged in
$logo_link = "index.php"; // Default for guests

if(isset($_SESSION['student_id'])){
    // If Passenger is logged in
    $logo_link = "passenger_home.php";
} 
elseif(isset($_SESSION['driver_id'])){
    // If Driver is logged in
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
                <h1><a href="<?php echo $logo_link; ?>"><i class="fa-solid fa-car"></i> U-Transport</a></h1>
            </div>
            <nav>
                <ul>
                    <?php if(isset($_SESSION['student_id'])): ?>
                        <li><a href="search_transport.php">Search Rides</a></li>
                        <li><a href="passanger_request_transport.php">Request Ride</a></li>
                        <li><a href="passanger_booking_history.php">My History</a></li>
                        <li><a href="qa_forum.php">Q&A</a></li>
                        <li><a href="passanger_profile.php">Profile</a></li>
                        <li><a href="logout.php" style="color: #ffcccb;">Logout</a></li>

                    <?php elseif(isset($_SESSION['driver_id'])): ?>
                        <li><a href="driver_dashboard.php">Dashboard</a></li>
                        <li><a href="driver_rides.php">My Rides</a></li>
                        <li><a href="driver_history.php">History</a></li>
                        <li><a href="driver_profile.php">Profile</a></li>
                        <li><a href="driver_logout.php" style="color: #ffcccb;">Logout</a></li>

                    <?php else: ?>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="contact_us.php">Contact Us</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container content-area">