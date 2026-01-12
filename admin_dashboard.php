<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// FETCH SUMMARY STATISTICS
$driver_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM drivers WHERE verification_status = 'verified'");
$total_drivers = mysqli_fetch_assoc($driver_query)['total'];

$pending_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM drivers WHERE verification_status = 'pending'");
$total_pending = mysqli_fetch_assoc($pending_query)['total'];

$passenger_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
$total_passengers = mysqli_fetch_assoc($passenger_query)['total'];

$booking_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings");
$total_bookings = mysqli_fetch_assoc($booking_query)['total'];

// Count Pending Vehicle Requests (For the badge/alert)
$veh_req_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM vehicle_change_requests WHERE status = 'pending'");
$total_veh_requests = mysqli_fetch_assoc($veh_req_query)['total'];
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
        .admin-header { background-color: #2c3e50; color: white; height: 70px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .admin-header .container { display: flex; justify-content: space-between; align-items: center; width: 95%; margin: 0 auto; }
        .admin-nav ul { list-style: none; display: flex; gap: 15px; padding: 0; margin: 0; align-items: center; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 0.9rem; }
        .admin-nav a:hover { color: white; }
        .nav-divider { width: 1px; background: rgba(255,255,255,0.2); height: 25px; margin: 0 5px; }
        
        .dashboard-container { margin-top: 30px; width: 90%; margin-left: auto; margin-right: auto; }
        .welcome-banner { background: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #2c3e50; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-info h3 { font-size: 2rem; margin: 0; color: #2c3e50; }
        .stat-info p { margin: 5px 0 0; color: #7f8c8d; }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        
        .bg-blue { background: #e3f2fd; color: #2196f3; }
        .bg-orange { background: #fff3e0; color: #ff9800; }
        .bg-green { background: #e8f5e9; color: #4caf50; }
        .bg-purple { background: #f3e5f5; color: #9c27b0; }
        .bg-red { background: #fadbd8; color: #e74c3c; }

        /* Action Section Split */
        .actions-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 40px; }
        .action-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .action-box h3 { margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .action-list { list-style: none; padding: 0; }
        .action-list li { margin-bottom: 15px; }
        .action-list a { text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 10px; transition: 0.2s; }
        .action-list a:hover { padding-left: 5px; }
    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <div class="welcome-banner">
            <h2 style="margin:0;">Welcome back, Admin!</h2>
            <p style="color:#666; margin-top:5px;">System Overview</p>
        </div>

        <div class="stats-grid">
            <a href="view_drivers.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_drivers; ?></h3><p>Total Drivers</p></div>
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-car"></i></div>
                </div>
            </a>
            
            <a href="verify_drivers.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_pending; ?></h3><p>New Drivers</p></div>
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-user-clock"></i></div>
                </div>
            </a>
            
            <a href="admin_vehicle_requests.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_veh_requests; ?></h3><p>Vehicle Requests</p></div>
                    <div class="stat-icon bg-red"><i class="fa-solid fa-car-side"></i></div>
                </div>
            </a>

            <a href="view_passengers.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_passengers; ?></h3><p>Passengers</p></div>
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-users"></i></div>
                </div>
            </a>
            
            <a href="view_bookings.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_bookings; ?></h3><p>Total Bookings</p></div>
                    <div class="stat-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
            </a>
        </div>

        <div class="actions-section">
            
            <div class="action-box">
                <h3 style="color:#2c3e50;"><i class="fa-solid fa-layer-group"></i> Management</h3>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <ul class="action-list">
                    <li>
                        <a href="admin_vehicle_requests.php" style="color:#c0392b;">
                            <i class="fa-solid fa-car-side"></i> Manage Vehicle Requests
                            <?php if($total_veh_requests > 0): ?> 
                                <span style="background:red; color:white; font-size:10px; padding:2px 6px; border-radius:10px;"><?php echo $total_veh_requests; ?> New</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="verify_drivers.php" style="color:#d35400;">
                            <i class="fa-solid fa-id-card"></i> Approve New Drivers
                        </a>
                    </li>
                    <li>
                        <a href="manage_reviews.php" style="color:#f39c12;">
                            <i class="fa-solid fa-star"></i> Manage Reviews & Ratings
                        </a>
                    </li>
                    <li>
                        <a href="view_bookings.php" style="color:#27ae60;">
                            <i class="fa-solid fa-list-check"></i> View All Bookings
                        </a>
                    </li>
                    <li>
                        <a href="view_passengers.php" style="color:#7f8c8d;">
                            <i class="fa-solid fa-users-viewfinder"></i> View Passenger List
                        </a>
                    </li>
                </ul>
            </div>

            <div class="action-box">
                <h3 style="color:#2c3e50;"><i class="fa-solid fa-headset"></i> Support Center</h3>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <ul class="action-list">
                    <li>
                        <a href="admin_driver_chat.php" style="color:#2980b9;">
                            <i class="fa-solid fa-comments"></i> Driver Support Chat
                        </a>
                    </li>
                    <li>
                        <a href="admin_student_chat.php" style="color:#8e44ad;">
                            <i class="fa-solid fa-user-graduate"></i> Student Support Chat
                        </a>
                    </li>
                </ul>
            </div>

        </div>

    </main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let lastUnreadCount = 0;
    
    // Initial Check
    fetch('admin_check_notifications.php').then(r => r.json()).then(d => lastUnreadCount = d.unread_count);

    // Poll every 5 seconds
    setInterval(() => {
        fetch('admin_check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.unread_count > lastUnreadCount) {
                    // Play Sound
                    const audio = new Audio('https://proxy.notificationsounds.com/notification-sounds/completed-577/download/file-sounds-1149-completed.mp3'); 
                    audio.play().catch(e => console.log("Audio blocked"));
                    
                    // Determine message source
                    let title = 'New Support Message';
                    let text = 'You have a new unread message.';
                    let link = 'admin_dashboard.php';

                    if (data.driver_unread > 0) {
                        title = 'Driver Message';
                        link = 'admin_driver_chat.php';
                    } else if (data.student_unread > 0) {
                        title = 'Student Message';
                        link = 'admin_student_chat.php';
                    }

                    Swal.fire({
                        position: 'top-end',
                        icon: 'info',
                        title: title,
                        text: text,
                        showConfirmButton: true,
                        confirmButtonText: 'View Chat',
                        toast: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = link;
                        }
                    });
                }
                lastUnreadCount = data.unread_count;
            });
    }, 5000);
</script>

</body>
</html>