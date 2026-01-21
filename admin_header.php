<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Global Security Check
// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | FMD Staff</title>
    <link rel="icon" href="uploads/logo.jpg" type="image/jpeg">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: sans-serif; margin: 0; }
        
        .admin-header { background-color: #2c3e50; color: white; height: 70px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .admin-header .container { display: flex; justify-content: space-between; align-items: center; width: 95%; margin: 0 auto; }
        
        .logo-section h1 { font-size: 1.5rem; margin: 0; }
        .logo-section i { margin-right: 10px; }
        
        .admin-nav ul { list-style: none; display: flex; gap: 15px; padding: 0; margin: 0; align-items: center; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 0.9rem; padding: 5px 10px; border-radius: 4px; }
        .admin-nav a:hover, .admin-nav a.active { color: white; background-color: rgba(255,255,255,0.1); }
        
        .nav-divider { width: 1px; background: rgba(255,255,255,0.2); height: 25px; margin: 0 5px; }
        .dashboard-container { margin-top: 30px; width: 90%; margin-left: auto; margin-right: auto; }
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
                <li><a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="verify_drivers.php" class="<?= $current_page == 'verify_drivers.php' ? 'active' : '' ?>">Approve</a></li>
                <li><a href="view_bookings.php" class="<?= $current_page == 'view_bookings.php' ? 'active' : '' ?>">Bookings</a></li>
                <li><a href="manage_reviews.php" class="<?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>">Reviews</a></li>
                <li><a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">Reports</a></li>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin_register.php" class="<?= $current_page == 'admin_register.php' ? 'active' : '' ?>" style="color:#f1c40f;">Add Staff</a></li>
                    <li><a href="manage_staff.php" class="<?= $current_page == 'manage_staff.php' ? 'active' : '' ?>" style="color:#e67e22;">Manage Staff</a></li>
                <?php endif; ?>

                <li class="nav-divider"></li>
                
                <li><a href="admin_driver_chat.php" class="<?= $current_page == 'admin_driver_chat.php' ? 'active' : '' ?>"><i class="fa-solid fa-headset"></i></a></li>
                <li><a href="admin_student_chat.php" class="<?= $current_page == 'admin_student_chat.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-graduate"></i></a></li>
                <li><a href="admin_profile.php" class="<?= $current_page == 'admin_profile.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                <li><a href="admin_login.php" style="color:#e74c3c;"><i class="fa-solid fa-right-from-bracket"></i></a></li>
            </ul>
        </nav>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let lastUnreadCount = 0;
        
        // Initial Check
        fetch('admin_check_notifications.php')
            .then(r => r.json())
            .then(d => lastUnreadCount = d.unread_count)
            .catch(e => console.log('Notification check failed', e));

        // Poll every 8 seconds
        setInterval(() => {
            fetch('admin_check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread_count > lastUnreadCount) {
                        const audio = new Audio('https://proxy.notificationsounds.com/notification-sounds/completed-577/download/file-sounds-1149-completed.mp3'); 
                        audio.play().catch(e => console.log("Audio blocked"));

                        let title = 'New Message';
                        let link = 'admin_dashboard.php';

                        if (data.driver_unread > 0) {
                            title = 'New Driver Message';
                            link = 'admin_driver_chat.php';
                        } else if (data.student_unread > 0) {
                            title = 'New Student Message';
                            link = 'admin_student_chat.php';
                        }

                        Swal.fire({
                            position: 'top-end',
                            icon: 'info',
                            title: title,
                            text: 'You have a new support message.',
                            showConfirmButton: true,
                            confirmButtonText: 'View Chat',
                            toast: true,
                            timer: 5000
                        }).then((result) => {
                            if (result.isConfirmed) window.location.href = link;
                        });
                    }
                    lastUnreadCount = data.unread_count;
                });
        }, 8000);
    });
</script>