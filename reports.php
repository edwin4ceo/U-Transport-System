<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

function getCount($conn, $table, $condition = "") {
    $r = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table $condition");
    return $r ? mysqli_fetch_assoc($r)['count'] : 0;
}

// LOGIC FIX: Count from correct tables (Removed Listings)
$total_passengers = getCount($conn, "students");
$total_drivers    = getCount($conn, "drivers");
$pending_drivers  = getCount($conn, "drivers", "WHERE verification_status='pending'");
// Removed $total_listings
$total_bookings   = getCount($conn, "bookings");
?>
<main class="dashboard-container">
    <div class="container">
        <h2>System Reports</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-top:20px;">
            <div style="background:white; padding:20px; border-radius:8px; text-align:center;">
                <h3><?php echo $total_passengers; ?></h3><p>Passengers</p>
            </div>
            <div style="background:white; padding:20px; border-radius:8px; text-align:center;">
                <h3><?php echo $total_drivers; ?></h3><p>Drivers</p>
            </div>
            <div style="background:white; padding:20px; border-radius:8px; text-align:center;">
                <h3><?php echo $total_bookings; ?></h3><p>Total Bookings</p>
            </div>
        </div>
    </div>
</main>
</body></html>