<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

function getCount($conn, $table, $condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table $condition";
    $result = mysqli_query($conn, $sql);
    return ($result) ? mysqli_fetch_assoc($result)['count'] : 0;
}

// 1. Fetch Existing Counts
$pending_drivers  = getCount($conn, "users", "WHERE role='driver' AND verification_status='pending'");
$total_passengers = getCount($conn, "users", "WHERE role='passenger'");
$total_drivers    = getCount($conn, "users", "WHERE role='driver'");
$feedback_count   = getCount($conn, "contact_messages");

// 2. Fetch NEW Counts (Bookings & Reviews)
$total_bookings   = getCount($conn, "bookings");
$total_reviews    = getCount($conn, "reviews");
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
        
        /* --- SINGLE ROW HEADER STYLES --- */
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

        .logo-section h1 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-nav ul {
            padding: 0;
            list-style: none;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-nav a {
            color: #bdc3c7;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .admin-nav a:hover { color: white; }

        .nav-divider {
            height: 25px;
            width: 1px;
            background-color: rgba(255,255,255,0.2);
            margin: 0 10px;
        }

        .user-action-link { display: flex; align-items: center; gap: 5px; }
        .logout-btn { color: #e74c3c !important; }
        .logout-btn:hover { color: #ff6b6b !important; }

        /* Dashboard Grid */
        .dashboard-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin-top: 30px; 
        }

        .card { 
            padding: 25px; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            text-align: center; 
        }

        .badge { 
            background-color: #e74c3c; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-size: 0.7rem; 
            vertical-align: middle; 
            position: relative; 
            top: -1px; 
        }
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
                    <li>
                        <a href="verify_drivers.php">
                            Approve 
                            <?php if($pending_drivers>0) echo "<span class='badge'>$pending_drivers</span>"; ?>
                        </a>
                    </li>
                    <li><a href="view_drivers.php">Drivers</a></li>
                    <li><a href="view_passengers.php">Passengers</a></li>
                    <li><a href="view_bookings.php">Bookings</a></li>
                    <li><a href="manage_reviews.php">Reviews</a></li>
                    <li><a href="view_feedback.php">Feedback</a></li>
                    <li><a href="reports.php">Reports</a></li>

                    <li class="nav-divider"></li>

                    <li><a href="admin_profile.php" class="user-action-link"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                    <li><a href="admin_login.php" class="user-action-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container" style="margin-top: 30px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Dashboard Overview</h2>
                <span style="color:#7f8c8d;">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                </span>
            </div>
            
            <div class="dashboard-cards">
                <div class="card" style="border-top: 4px solid #e74c3c;">
                    <i class="fa-solid fa-user-check fa-3x" style="color: #e74c3c;"></i>
                    <h3><?php echo $pending_drivers; ?></h3>
                    <p>Pending Approvals</p>
                    <a href="verify_drivers.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;">
                        Review Applications &rarr;
                    </a>
                </div>

                <div class="card" style="border-top: 4px solid #27ae60;">
                    <i class="fa-solid fa-car fa-3x" style="color: #27ae60;"></i>
                    <h3><?php echo $total_drivers; ?></h3>
                    <p>Total Drivers</p>
                    <a href="view_drivers.php" style="color: #27ae60; text-decoration: none; font-weight: bold;">
                        View List &rarr;
                    </a>
                </div>

                <div class="card" style="border-top: 4px solid #3498db;">
                    <i class="fa-solid fa-users fa-3x" style="color: #3498db;"></i>
                    <h3><?php echo $total_passengers; ?></h3>
                    <p>Total Passengers</p>
                    <a href="view_passengers.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
                        View List &rarr;
                    </a>
                </div>

                <div class="card" style="border-top: 4px solid #9b59b6;">
                    <i class="fa-solid fa-address-book fa-3x" style="color: #9b59b6;"></i>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                    <a href="view_bookings.php" style="color: #9b59b6; text-decoration: none; font-weight: bold;">
                        View Logs &rarr;
                    </a>
                </div>

                <div class="card" style="border-top: 4px solid #f1c40f;">
                    <i class="fa-solid fa-star-half-stroke fa-3x" style="color: #f1c40f;"></i>
                    <h3><?php echo $total_reviews; ?></h3>
                    <p>Reviews Posted</p>
                    <a href="manage_reviews.php" style="color: #f1c40f; text-decoration: none; font-weight: bold;">
                        Moderate &rarr;
                    </a>
                </div>

                <div class="card" style="border-top: 4px solid #e67e22;">
                    <i class="fa-solid fa-envelope-open-text fa-3x" style="color: #e67e22;"></i>
                    <h3><?php echo $feedback_count; ?></h3>
                    <p>Feedback Messages</p>
                    <a href="view_feedback.php" style="color: #e67e22; text-decoration: none; font-weight: bold;">
                        Read Inbox &rarr;
                    </a>
                </div>

                <!-- NEW: Driver Chat card -->
                <div class="card" style="border-top: 4px solid #1abc9c;">
                    <i class="fa-solid fa-comments fa-3x" style="color: #1abc9c;"></i>
                    <h3>Driver Chat</h3>
                    <p>View and reply to drivers</p>
                    <a href="admin_driver_chat.php" style="color: #1abc9c; text-decoration: none; font-weight: bold;">
                        Open Chat &rarr;
                    </a>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
