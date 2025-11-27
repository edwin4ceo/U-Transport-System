<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Helper function
function getCount($conn, $table, $condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table $condition";
    $result = mysqli_query($conn, $sql);
    return ($result) ? mysqli_fetch_assoc($result)['count'] : 0;
}

$pending_drivers = getCount($conn, "users", "WHERE role='driver' AND verification_status='pending'");
$total_passengers = getCount($conn, "users", "WHERE role='passenger'");
$total_drivers = getCount($conn, "users", "WHERE role='driver'");
$feedback_count = getCount($conn, "contact_messages"); // Assuming you will have this table later
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | FMD Staff</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .admin-header { background-color: #2c3e50; color: white; padding: 15px 0; }
        .admin-nav ul { padding: 0; list-style: none; text-align: center; margin-top: 15px; }
        .admin-nav ul li { display: inline; margin: 0 10px; }
        .admin-nav a { color: #ecf0f1; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: 0.3s; }
        .admin-nav a:hover { color: #f1c40f; }
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 30px; }
        .card { padding: 25px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .badge { background-color: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; vertical-align: middle; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-building-user"></i> FMD Staff Dashboard</h1>
                <div>
                    <a href="admin_profile.php" style="color: white; margin-right: 15px;"><i class="fa-solid fa-user-pen"></i> Profile</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </div>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php">Home</a></li>
                    <li><a href="verify_drivers.php">Approve Drivers <?php if($pending_drivers>0) echo "<span class='badge'>$pending_drivers</span>"; ?></a></li>
                    <li><a href="view_drivers.php">View Drivers</a></li>
                    <li><a href="view_passengers.php">View Passengers</a></li>
                    <li><a href="view_feedback.php">Feedback</a></li>
                    <li><a href="reports.php">Reports</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
            
            <div class="dashboard-cards">
                <div class="card" style="border-top: 4px solid #e74c3c;">
                    <i class="fa-solid fa-user-check fa-3x" style="color: #e74c3c;"></i>
                    <h3><?php echo $pending_drivers; ?></h3>
                    <p>Pending Approvals</p>
                    <a href="verify_drivers.php">Review Applications &rarr;</a>
                </div>

                <div class="card" style="border-top: 4px solid #27ae60;">
                    <i class="fa-solid fa-car fa-3x" style="color: #27ae60;"></i>
                    <h3><?php echo $total_drivers; ?></h3>
                    <p>Total Drivers</p>
                    <a href="view_drivers.php">View List &rarr;</a>
                </div>

                <div class="card" style="border-top: 4px solid #3498db;">
                    <i class="fa-solid fa-users fa-3x" style="color: #3498db;"></i>
                    <h3><?php echo $total_passengers; ?></h3>
                    <p>Total Passengers</p>
                    <a href="view_passengers.php">View List &rarr;</a>
                </div>

                <div class="card" style="border-top: 4px solid #f39c12;">
                    <i class="fa-solid fa-envelope-open-text fa-3x" style="color: #f39c12;"></i>
                    <h3><?php echo $feedback_count; ?></h3>
                    <p>Feedback Messages</p>
                    <a href="view_feedback.php">Read Inbox &rarr;</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>