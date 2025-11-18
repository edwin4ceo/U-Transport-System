<?php
session_start();
require_once 'db_connect.php';

// --- 1. SECURITY CHECK ---
// If user is NOT logged in OR user is NOT an admin, redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// --- 2. FETCH REAL-TIME STATISTICS ---
// Function to helper fetch counts easily
function getCount($conn, $table, $condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table $condition";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    return 0;
}

// Get the numbers
$total_users = getCount($connection, "users");
$pending_drivers = getCount($connection, "users", "WHERE role='driver' AND verification_status='pending'");
$active_listings = getCount($connection, "transportlistings", "WHERE status='active'");
$completed_trips = getCount($connection, "transportlistings", "WHERE status='completed'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | U-Transport</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Admin specific layout tweaks */
        body { background-color: #f4f6f9; }
        .admin-header { background-color: #2c3e50; color: white; padding: 15px 0; }
        .admin-nav ul { padding: 0; list-style: none; text-align: center; margin-top: 10px; }
        .admin-nav ul li { display: inline; margin: 0 15px; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .admin-nav a:hover { color: white; }
        
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; }
        .card { padding: 25px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { font-size: 2.5rem; color: #2c3e50; margin: 10px 0; }
        .card p { color: #7f8c8d; font-weight: bold; text-transform: uppercase; font-size: 0.9rem; }
        
        /* Badge for pending items */
        .badge { background-color: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; vertical-align: middle; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</h1>
                <div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span> | 
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </div>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php" style="color: white;">Home</a></li>
                    <li>
                        <a href="verify_drivers.php">Verify Drivers 
                        <?php if($pending_drivers > 0): ?>
                            <span class="badge"><?php echo $pending_drivers; ?></span>
                        <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="manage_listings.php">Listings</a></li>
                    <li><a href="reports.php">Reports</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2>System Overview</h2>
            
            <div class="dashboard-cards">
                
                <div class="card">
                    <i class="fa-solid fa-users fa-3x" style="color: #3498db;"></i>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Registered Users</p>
                    <a href="manage_users.php" style="font-size:0.8rem; color: #3498db;">View Details &rarr;</a>
                </div>

                <div class="card" style="border-bottom: 5px solid <?php echo ($pending_drivers > 0) ? '#e74c3c' : '#27ae60'; ?>;">
                    <i class="fa-solid fa-id-card fa-3x" style="color: <?php echo ($pending_drivers > 0) ? '#e74c3c' : '#27ae60'; ?>;"></i>
                    <h3><?php echo $pending_drivers; ?></h3>
                    <p>Pending Driver Approvals</p>
                    <a href="verify_drivers.php" style="font-size:0.8rem; color: #e74c3c;">Action Required &rarr;</a>
                </div>

                <div class="card">
                    <i class="fa-solid fa-car fa-3x" style="color: #f1c40f;"></i>
                    <h3><?php echo $active_listings; ?></h3>
                    <p>Active Rides Available</p>
                    <a href="manage_listings.php" style="font-size:0.8rem; color: #f1c40f;">Monitor Listings &rarr;</a>
                </div>

                <div class="card">
                    <i class="fa-solid fa-flag-checkered fa-3x" style="color: #2c3e50;"></i>
                    <h3><?php echo $completed_trips; ?></h3>
                    <p>Total Completed Trips</p>
                    <a href="reports.php" style="font-size:0.8rem; color: #2c3e50;">View Reports &rarr;</a>
                </div>

            </div>
        </div>
    </main>

</body>
</html>