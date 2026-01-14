<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER
require_once 'admin_header.php';

// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
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

$veh_req_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM vehicle_change_requests WHERE status = 'pending'");
$total_veh_requests = mysqli_fetch_assoc($veh_req_query)['total'];

// [NEW] Count Staff Members
$staff_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admins WHERE role = 'staff'");
$total_staff = mysqli_fetch_assoc($staff_query)['total'];
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
        .bg-gold { background: #fef9e7; color: #f1c40f; }

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
            <h2 style="margin:0;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
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

            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="manage_staff.php" style="text-decoration:none; color:inherit;">
                <div class="stat-card">
                    <div class="stat-info"><h3><?php echo $total_staff; ?></h3><p>Staff Team</p></div>
                    <div class="stat-icon bg-gold"><i class="fa-solid fa-users-gear"></i></div>
                </div>
            </a>
            <?php endif; ?>
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
                    <li><a href="verify_drivers.php" style="color:#d35400;"><i class="fa-solid fa-id-card"></i> Approve New Drivers</a></li>
                    <li><a href="manage_reviews.php" style="color:#f39c12;"><i class="fa-solid fa-star"></i>View Reviews & Ratings</a></li>
                    <li><a href="view_bookings.php" style="color:#27ae60;"><i class="fa-solid fa-list-check"></i> View All Bookings</a></li>
                    <li><a href="view_passengers.php" style="color:#7f8c8d;"><i class="fa-solid fa-users-viewfinder"></i> View Passenger List</a></li>
                    
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="manage_staff.php" style="color:#e67e22;"><i class="fa-solid fa-user-group"></i> Manage Staff Team</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="action-box">
                <h3 style="color:#2c3e50;"><i class="fa-solid fa-headset"></i> Support Center</h3>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <ul class="action-list">
                    <li><a href="admin_driver_chat.php" style="color:#2980b9;"><i class="fa-solid fa-comments"></i> Driver Support Chat</a></li>
                    <li><a href="admin_student_chat.php" style="color:#8e44ad;"><i class="fa-solid fa-user-graduate"></i> Student Support Chat</a></li>
                </ul>
            </div>
        </div>

    </main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let lastUnreadCount = 0;
    fetch('admin_check_notifications.php').then(r => r.json()).then(d => lastUnreadCount = d.unread_count);
    setInterval(() => {
        fetch('admin_check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.unread_count > lastUnreadCount) {
                    const audio = new Audio('https://proxy.notificationsounds.com/notification-sounds/completed-577/download/file-sounds-1149-completed.mp3'); 
                    audio.play().catch(e => console.log("Audio blocked"));
                    let title = 'New Support Message';
                    let link = 'admin_dashboard.php';
                    if (data.driver_unread > 0) { title = 'Driver Message'; link = 'admin_driver_chat.php'; } 
                    else if (data.student_unread > 0) { title = 'Student Message'; link = 'admin_student_chat.php'; }
                    Swal.fire({
                        position: 'top-end', icon: 'info', title: title, text: 'You have a new unread message.',
                        showConfirmButton: true, confirmButtonText: 'View Chat', toast: true
                    }).then((result) => { if (result.isConfirmed) window.location.href = link; });
                }
                lastUnreadCount = data.unread_count;
            });
    }, 5000);
</script>

</body>
</html>