<?php
session_start();
require_once 'db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// 2. FETCH SUMMARY STATISTICS

// Count Total Drivers
$driver_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'driver'");
$total_drivers = mysqli_fetch_assoc($driver_query)['total'];

// Count Pending Approvals
$pending_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'driver' AND verification_status = 'pending'");
$total_pending = mysqli_fetch_assoc($pending_query)['total'];

// Count Total Passengers
$passenger_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'passenger'");
$total_passengers = mysqli_fetch_assoc($passenger_query)['total'];

// Count Total Bookings
$booking_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings");
$total_bookings = mysqli_fetch_assoc($booking_query)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | FMD Staff</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }

        /* --- Reuse the Header Style from your current files --- */
        .admin-header {
            background-color: #2c3e50;
            color: white;
            padding: 0;
            height: 70px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }
        .logo-section h1 { font-size: 1.5rem; margin: 0; }
        .admin-nav ul { list-style: none; display: flex; gap: 20px; padding: 0; margin: 0; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .admin-nav a:hover { color: white; }
        .nav-divider { width: 1px; background: rgba(255,255,255,0.2); height: 25px; margin: 0 10px; }
        
        /* --- Dashboard Specific Styles --- */
        .dashboard-container { margin-top: 30px; }
        .welcome-banner {
            background: white; padding: 25px; border-radius: 12px; margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #2c3e50;
        }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;
        }
        .stat-card {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-info h3 { font-size: 2rem; margin: 0; color: #2c3e50; }
        .stat-info p { margin: 5px 0 0; color: #7f8c8d; }
        .stat-icon {
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }
        /* Icon Colors */
        .bg-blue { background: #e3f2fd; color: #2196f3; }
        .bg-orange { background: #fff3e0; color: #ff9800; }
        .bg-green { background: #e8f5e9; color: #4caf50; }
        .bg-purple { background: #f3e5f5; color: #9c27b0; }

    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div class="logo-section">
                <h1><i class="fa-solid fa-building-user"></i> FMD Staff</h1>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php" style="color:white;">Home</a></li>
                    <li><a href="verify_drivers.php">Approve</a></li>
                    <li><a href="view_drivers.php">Drivers</a></li>
                    <li><a href="view_passengers.php">Passengers</a></li>
                    <li><a href="view_bookings.php">Bookings</a></li>
                    <li><a href="manage_reviews.php">Reviews</a></li>
                    <li><a href="view_feedback.php">Feedback</a></li>
                    
                    <li class="nav-divider"></li>
                    <li><a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                    <li><a href="admin_login.php" style="color:#e74c3c;"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container dashboard-container">
        
        <div class="welcome-banner">
            <h2 style="margin:0;">Welcome back, Admin!</h2>
            <p style="color:#666; margin-top:5px;">Here is the overview of the U-Transport system.</p>
        </div>

        <div class="stats-grid">
            
        <a href="view_drivers.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_drivers; ?></h3>
                    <p>Total Drivers</p>
                </div>
                <div class="stat-icon bg-blue"><i class="fa-solid fa-car"></i></div>
            </div>
        </a>

            <a href="verify_drivers.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $total_pending; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-user-clock"></i></div>
                </div>
            </a>

            <a href="view_passengers.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_passengers; ?></h3>
                    <p>Total Passengers</p>
                </div>
                <div class="stat-icon bg-purple"><i class="fa-solid fa-users"></i></div>
            </div>
            </a>

            <a href="view_bookings.php" style="text-decoration:none; color:inherit;">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
            </div>
            </a>

        </div>

        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;"><i class="fa-solid fa-bolt" style="color:#f1c40f;"></i> Quick Actions</h3>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 15px;">
                        <a href="admin_driver_chat.php" style="text-decoration:none; font-weight:bold; color:#2980b9;">
                            <i class="fa-solid fa-comments"></i> Open Driver Support Chat
                        </a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="view_bookings.php" style="text-decoration:none; font-weight:bold; color:#2980b9;">
                            <i class="fa-solid fa-list-check"></i> View Recent Bookings
                        </a>
                    </li>
                    
                    <li style="margin-bottom: 15px;">
                        <a href="admin_vehicle_requests.php" style="text-decoration:none; font-weight:bold; color:#2980b9;">
                            <i class="fa-solid fa-car-side"></i> Manage Vehicle Requests
                        </a>
                    </li>
                    <li>
                        <a href="verify_drivers.php" style="text-decoration:none; font-weight:bold; color:#2980b9;">
                            <i class="fa-solid fa-id-card"></i> Verify New Drivers
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </main>

</body>
</html>