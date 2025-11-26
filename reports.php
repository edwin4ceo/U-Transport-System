<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// --- FUNCTIONS TO GET COUNTS ---
function getCount($conn, $table, $condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table $condition";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// 1. User Stats
$total_passengers = getCount($conn, "users", "WHERE role='passenger'");
$total_drivers    = getCount($conn, "users", "WHERE role='driver'");
$pending_drivers  = getCount($conn, "users", "WHERE role='driver' AND verification_status='pending'");

// 2. Listing Stats
$total_listings   = getCount($conn, "transportlistings");
$active_listings  = getCount($conn, "transportlistings", "WHERE status='active'");

// 3. Booking Stats
$total_bookings   = getCount($conn, "bookings");
$confirmed_bookings = getCount($conn, "bookings", "WHERE status='confirmed'");

// 4. Most Popular Destination (Advanced Query)
$pop_sql = "SELECT destination, COUNT(*) as count FROM transportlistings GROUP BY destination ORDER BY count DESC LIMIT 1";
$pop_res = mysqli_query($conn, $pop_sql);
$top_dest = (mysqli_num_rows($pop_res) > 0) ? mysqli_fetch_assoc($pop_res)['destination'] : "No data yet";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .report-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #2c3e50; }
        .report-card h4 { margin: 0; color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; }
        .report-card .number { font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        .highlight { border-left-color: #e74c3c; } /* Red for pending */
        .highlight-green { border-left-color: #27ae60; } /* Green for success */
        
        .print-btn { background-color: #2980b9; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-top: 20px; }
        @media print {
            header, .print-btn { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-chart-line"></i> System Reports</h1>
                <nav>
                    <a href="admin_dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Usage Statistics Overview</h3>
                <button onclick="window.print()" class="print-btn"><i class="fa-solid fa-print"></i> Print Report</button>
            </div>

            <h4>User Demographics</h4>
            <div class="report-grid">
                <div class="report-card">
                    <h4>Total Passengers</h4>
                    <div class="number"><?php echo $total_passengers; ?></div>
                </div>
                <div class="report-card highlight-green">
                    <h4>Total Drivers</h4>
                    <div class="number"><?php echo $total_drivers; ?></div>
                </div>
                <div class="report-card highlight">
                    <h4>Pending Verifications</h4>
                    <div class="number"><?php echo $pending_drivers; ?></div>
                </div>
            </div>

            <br><hr><br>

            <h4>Operations & Activity</h4>
            <div class="report-grid">
                <div class="report-card">
                    <h4>Total Listings Posted</h4>
                    <div class="number"><?php echo $total_listings; ?></div>
                </div>
                <div class="report-card">
                    <h4>Total Bookings Made</h4>
                    <div class="number"><?php echo $total_bookings; ?></div>
                </div>
                <div class="report-card highlight-green">
                    <h4>Top Destination</h4>
                    <div class="number" style="font-size: 1.5rem;"><?php echo htmlspecialchars($top_dest); ?></div>
                </div>
            </div>

        </div>
    </main>

</body>
</html>