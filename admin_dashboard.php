<?php
session_start();
require_once 'db_connect.php';

// --- SECURITY CHECK ---
// If user is NOT logged in OR user is NOT an admin, redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}
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
        .admin-header { background-color: #2c3e50; color: white; padding: 15px 0; }
        .admin-nav ul { padding: 0; list-style: none; text-align: center; }
        .admin-nav ul li { display: inline; margin: 0 15px; }
        .admin-nav a { color: white; text-decoration: none; font-weight: bold; }
        .dashboard-cards { display: flex; justify-content: space-between; margin-top: 20px; }
        .card { flex: 1; padding: 20px; margin: 10px; background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .card h3 { font-size: 2rem; color: #2c3e50; margin: 10px 0; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</h1>
                <div>
                    <span>Welcome, <?php echo $_SESSION['full_name']; ?></span> | 
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </div>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php">Home</a></li>
                    <li><a href="verify_drivers.php">Verify Drivers</a></li>
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
                    <i class="fa-solid fa-users fa-2x"></i>
                    <h3>0</h3>
                    <p>Total Users</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-id-card fa-2x" style="color: orange;"></i>
                    <h3>0</h3>
                    <p>Pending Verifications</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-car fa-2x" style="color: green;"></i>
                    <h3>0</h3>
                    <p>Active Listings</p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>