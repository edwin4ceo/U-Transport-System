<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE HEADER
require_once 'admin_header.php';

// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// FETCH STATISTICS
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

$staff_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admins WHERE role = 'staff'");
$total_staff = mysqli_fetch_assoc($staff_query)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | FMD Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; color: #1f2937; }
        
        /* Welcome Banner */
        .welcome-section {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.2);
            position: relative;
            overflow: hidden;
        }
        .welcome-section::after {
            content: ''; position: absolute; top: 0; right: 0; bottom: 0; left: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.1;
        }
        .welcome-text h2 { margin: 0; font-size: 2rem; font-weight: 700; }
        .welcome-text p { margin: 5px 0 0; opacity: 0.9; font-size: 1.1rem; }
        .date-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px; border-radius: 20px; font-size: 0.9rem;
            display: inline-block; margin-top: 15px; backdrop-filter: blur(5px);
        }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        
        .stat-card {
            background: white; padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            display: flex; justify-content: space-between; align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #f0f0f0;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: transparent; }
        
        .stat-info h3 { margin: 0; font-size: 2.2rem; font-weight: 700; color: #111827; }
        .stat-info p { margin: 5px 0 0; color: #6b7280; font-size: 0.95rem; font-weight: 500; }
        
        .icon-box {
            width: 60px; height: 60px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-orange { background: #fff7ed; color: #f97316; }
        .icon-red { background: #fef2f2; color: #ef4444; }
        .icon-purple { background: #f3e8ff; color: #a855f7; }
        .icon-green { background: #f0fdf4; color: #22c55e; }
        .icon-gold { background: #fefce8; color: #eab308; }

        /* Actions Section */
        .section-title { font-size: 1.25rem; font-weight: 700; color: #374151; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; }
        
        .action-card {
            background: white; border-radius: 16px; padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .action-list { list-style: none; padding: 0; margin: 0; }
        .action-list li { margin-bottom: 15px; }
        .action-list li:last-child { margin-bottom: 0; }
        
        .action-link {
            display: flex; align-items: center; padding: 15px;
            background: #f9fafb; border-radius: 10px;
            text-decoration: none; color: #4b5563; font-weight: 600;
            transition: 0.2s; border: 1px solid transparent;
        }
        .action-link:hover { background: #fff; border-color: #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.05); color: #2c3e50; }
        .action-link i { margin-right: 15px; font-size: 1.2rem; width: 30px; text-align: center; }
        
        .notification-badge {
            background: #ef4444; color: white; font-size: 0.75rem; 
            padding: 2px 8px; border-radius: 10px; margin-left: auto;
        }

    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p>Here is what's happening in your system today.</p>
                <div class="date-badge"><i class="fa-regular fa-calendar"></i> <?php echo date("l, d F Y"); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <a href="view_drivers.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_drivers; ?></h3><p>Active Drivers</p></div>
                <div class="icon-box icon-blue"><i class="fa-solid fa-car"></i></div>
            </a>
            
            <a href="verify_drivers.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_pending; ?></h3><p>Pending Approvals</p></div>
                <div class="icon-box icon-orange"><i class="fa-solid fa-user-clock"></i></div>
            </a>
            
            <a href="admin_vehicle_requests.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_veh_requests; ?></h3><p>Vehicle Requests</p></div>
                <div class="icon-box icon-red"><i class="fa-solid fa-car-side"></i></div>
            </a>

            <a href="view_passengers.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_passengers; ?></h3><p>Total Passengers</p></div>
                <div class="icon-box icon-purple"><i class="fa-solid fa-users"></i></div>
            </a>
            
            <a href="view_bookings.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_bookings; ?></h3><p>Total Bookings</p></div>
                <div class="icon-box icon-green"><i class="fa-solid fa-calendar-check"></i></div>
            </a>

            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="manage_staff.php" class="stat-card">
                <div class="stat-info"><h3><?php echo $total_staff; ?></h3><p>Staff Team</p></div>
                <div class="icon-box icon-gold"><i class="fa-solid fa-users-gear"></i></div>
            </a>
            <?php endif; ?>
        </div>

        <div class="actions-grid">
            <div class="action-card">
                <div class="section-title"><i class="fa-solid fa-layer-group" style="color:#3498db;"></i> Quick Management</div>
                <ul class="action-list">
                    <li>
                        <a href="admin_vehicle_requests.php" class="action-link">
                            <i class="fa-solid fa-car-side" style="color:#e74c3c;"></i> Vehicle Requests
                            <?php if($total_veh_requests > 0): ?> 
                                <span class="notification-badge"><?php echo $total_veh_requests; ?> New</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="verify_drivers.php" class="action-link"><i class="fa-solid fa-id-card" style="color:#e67e22;"></i> Verify New Drivers</a></li>
                    <li><a href="manage_reviews.php" class="action-link"><i class="fa-solid fa-star" style="color:#f1c40f;"></i> Reviews & Ratings</a></li>
                    <li><a href="view_bookings.php" class="action-link"><i class="fa-solid fa-list-check" style="color:#27ae60;"></i> View All Bookings</a></li>
                    
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="manage_staff.php" class="action-link"><i class="fa-solid fa-user-group" style="color:#8e44ad;"></i> Manage Staff Team</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="action-card">
                <div class="section-title"><i class="fa-solid fa-headset" style="color:#27ae60;"></i> Support Center</div>
                <ul class="action-list">
                    <li><a href="admin_driver_chat.php" class="action-link"><i class="fa-solid fa-comments" style="color:#2980b9;"></i> Driver Support Chat</a></li>
                    <li><a href="admin_student_chat.php" class="action-link"><i class="fa-solid fa-user-graduate" style="color:#9b59b6;"></i> Student Support Chat</a></li>
                    <li><a href="reports.php" class="action-link"><i class="fa-solid fa-chart-pie" style="color:#34495e;"></i> System Reports</a></li>
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
                        showConfirmButton: true, confirmButtonText: 'View Chat', toast: true, timer: 5000
                    }).then((result) => { if (result.isConfirmed) window.location.href = link; });
                }
                lastUnreadCount = data.unread_count;
            });
    }, 5000);
</script>

</body>
</html>