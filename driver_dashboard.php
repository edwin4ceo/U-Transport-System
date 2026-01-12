<?php
session_start();

include "db_connect.php";
include "function.php";

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Default values for driver information
$full_name       = "Driver";
$email           = "";
$vehicle_model   = "";
$plate_number    = "";
$vehicle_type    = "";
$vehicle_color   = "";
$seat_count      = "";

// Retrieve driver information from database (READ-ONLY)
$stmt = $conn->prepare("
    SELECT d.full_name, d.email, v.vehicle_model, v.plate_number, v.vehicle_type, v.vehicle_color, v.seat_count
    FROM drivers d
    LEFT JOIN vehicles v ON v.driver_id = d.driver_id
    WHERE d.driver_id = ?
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $full_name     = $row['full_name'];
        $email         = $row['email'];
        $vehicle_model = $row['vehicle_model'] ?? "";
        $plate_number  = $row['plate_number'] ?? "";
        $vehicle_type  = $row['vehicle_type'] ?? "";
        $vehicle_color = $row['vehicle_color'] ?? "";
        $seat_count    = $row['seat_count'] ?? "";
    }
    $stmt->close();
}

// Count pending booking requests (READ-ONLY)
$pending_bookings_count = 0;
$notify_stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE driver_id = ? AND status = 'pending'");
if ($notify_stmt) {
    $notify_stmt->bind_param("i", $driver_id);
    $notify_stmt->execute();
    $res = $notify_stmt->get_result();
    if ($row = $res->fetch_assoc()) $pending_bookings_count = $row['total'];
    $notify_stmt->close();
}

// Count unread student messages in chat (READ-ONLY)
$chat_unread_count = 0;
$chat_stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM ride_chat_messages r
    JOIN bookings b ON r.booking_ref = b.id
    WHERE b.driver_id = ? AND r.sender_type = 'student' AND r.is_read = 0
");
if ($chat_stmt) {
    $chat_stmt->bind_param("i", $driver_id);
    if ($chat_stmt->execute()) {
        $res = $chat_stmt->get_result();
        if ($row = $res->fetch_assoc()) $chat_unread_count = $row['total'];
    }
    $chat_stmt->close();
}

// Count unread admin support messages (READ-ONLY)
$admin_unread_count = 0;
$admin_stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM driver_support_messages 
    WHERE driver_id = ? AND sender_type = 'admin' AND is_read = 0
");
if ($admin_stmt) {
    $admin_stmt->bind_param("i", $driver_id);
    if ($admin_stmt->execute()) {
        $res = $admin_stmt->get_result();
        if ($row = $res->fetch_assoc()) $admin_unread_count = $row['total'];
    }
    $admin_stmt->close();
}

$total_notifications = $pending_bookings_count + $chat_unread_count + $admin_unread_count;
include "header.php";
?>

<style>
    body { background: #f5f7fb; }
    .dashboard-wrapper { min-height: calc(100vh - 140px); padding: 30px 40px 40px; max-width: 100%; margin: 0; box-sizing: border-box; }
    .dashboard-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 22px; }
    .dashboard-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; }
    .dashboard-subtitle { font-size: 13px; color: #666; }
    .driver-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: #e6f4ff; color: #005a9c; font-size: 12px; font-weight: 500; }
    .driver-chip span.icon { width: 20px; height: 20px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: #005a9c; color: white; font-size: 11px; }
    .dashboard-actions-top { display: flex; gap: 12px; align-items: center; }
    .btn-outline { border-radius: 999px; border: 1px solid #005a9c; background: #fff; color: #005a9c; padding: 8px 14px; font-size: 13px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .btn-outline:hover { background: #005a9c; color: #fff; }
    
    /* Notification styling */
    .notification-bell { position: relative; width: 38px; height: 38px; border-radius: 50%; background: #fff; border: 1px solid #005a9c; display: flex; align-items: center; justify-content: center; color: #005a9c; font-size: 16px; text-decoration: none; transition: 0.2s; }
    .notification-bell:hover { background: #e6f4ff; }
    .bell-badge { position: absolute; top: -4px; right: -4px; background: #e74c3c; color: white; font-size: 10px; font-weight: bold; min-width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; }
    .alert-badge { margin-top: 6px; font-size: 11px; color: #e74c3c; font-weight: 700; background: #fff2f0; padding: 2px 8px; border-radius: 4px; display: inline-block; border: 1px solid #ffccc7; }
    .debug-info { font-size: 10px; color: #888; background: #f0f0f0; padding: 2px 5px; border-radius: 3px; margin-left: 5px; }

    /* Grid layout */
    .dashboard-grid { display: grid; grid-template-columns: 1.6fr 2.4fr; gap: 36px; margin-top: 14px; }
    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e3e6ea; padding: 18px 18px 16px; }
    .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .card-title { font-size: 15px; font-weight: 600; color: #004b82; margin: 0; }
    .card-tag { font-size: 11px; padding: 3px 8px; border-radius: 999px; background: #eaf7ff; color: #0077c2; font-weight: 500; }
    .card-body { font-size: 13px; color: #555; }
    .profile-row { display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; }
    .profile-label { font-size: 12px; color: #888; }
    .profile-value { font-size: 13px; font-weight: 500; color: #333; }
    .card-footer { margin-top: 10px; display: flex; justify-content: flex-end; }
    .card-link { font-size: 12px; color: #005a9c; text-decoration: none; font-weight: 500; }
    .card-link:hover { text-decoration: underline; }

    /* Quick Actions styling */
    .quick-actions-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .quick-card { border-radius: 18px; border: 1px solid #d3d8dd; background: #ffffff; height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 18px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); transition: transform 0.2s ease, box-shadow 0.2s ease; position: relative; text-decoration: none; cursor: pointer; }
    .quick-card:hover { transform: translateY(-4px); box-shadow: 0 14px 28px rgba(0,0,0,0.15); }
    .quick-icon { font-size: 28px; color: #005a9c; margin-bottom: 8px; }
    .quick-title { font-size: 14px; font-weight: 700; color: #004b82; text-align: center; }
    .quick-link { margin-top: 8px; font-size: 11px; color: #005a9c; text-decoration: none; font-weight: 600; }
    .quick-link:hover { text-decoration: underline; }
    
    @media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } .quick-actions-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .quick-actions-grid { grid-template-columns: 1fr; } }
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">Manage your transport services, bookings, and profile.</p>
            <div class="driver-chip"><span class="icon"><i class="fa-solid fa-car-side"></i></span><span>Registered MMU Driver</span></div>
        </div>
        <div class="dashboard-actions-top">
            <a href="driver_booking_requests.php" class="notification-bell">
                <i class="fa-regular fa-bell"></i>
                <?php if ($total_notifications > 0): ?><span class="bell-badge"><?php echo $total_notifications; ?></span><?php endif; ?>
            </a>
            <a href="javascript:void(0)" onclick="editProfilePopup()" class="btn-outline"><i class="fa-regular fa-user"></i> Edit profile</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Driver & Vehicle</h3><span class="card-tag">Overview</span></div>
            <div class="card-body">
                <div class="profile-row"><span class="profile-label">Name</span><span class="profile-value"><?php echo htmlspecialchars($full_name); ?></span></div>
                <div class="profile-row"><span class="profile-label">Email</span><span class="profile-value"><?php echo htmlspecialchars($email); ?></span></div>
                <div class="profile-row"><span class="profile-label">Vehicle Model</span><span class="profile-value"><?php echo $vehicle_model ? htmlspecialchars($vehicle_model) : 'Not set yet'; ?></span></div>
                <div class="profile-row"><span class="profile-label">Plate Number</span><span class="profile-value"><?php echo $plate_number ? htmlspecialchars($plate_number) : 'Not set yet'; ?></span></div>
                <div class="profile-row"><span class="profile-label">Seat Count</span><span class="profile-value"><?php echo $seat_count ? htmlspecialchars($seat_count) : 'Not set yet'; ?></span></div>
            </div>
            <div class="card-footer"><a href="javascript:void(0)" onclick="editVehiclePopup()" class="card-link">Update details</a></div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick Actions</h3><span class="card-tag">Start here</span></div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <div class="quick-card" onclick="editVehiclePopup()">
                        <div class="quick-icon"><i class="fa-solid fa-car"></i></div>
                        <div class="quick-title">Add / Edit Transport</div>
                        <div class="quick-link">Open →</div>
                    </div>
                    <div class="quick-card" onclick="window.location.href='driver_booking_requests.php'">
                        <div class="quick-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="quick-title">Booking Requests</div>
                        <?php if ($pending_bookings_count > 0): ?><div class="alert-badge"><?php echo $pending_bookings_count; ?> Pending</div><?php endif; ?>
                        <div class="quick-link">View →</div>
                    </div>
                    <div class="quick-card" onclick="window.location.href='driver_today_trips.php'">
                        <div class="quick-icon"><i class="fa-solid fa-calendar-day"></i></div>
                        <div class="quick-title">Today's Trips</div>
                        <div class="quick-link">Open →</div>
                    </div>
                    <div class="quick-card" onclick="window.location.href='driver_ratings.php'">
                        <div class="quick-icon"><i class="fa-solid fa-star"></i></div>
                        <div class="quick-title">Ratings & Reviews</div>
                        <div class="quick-link">View →</div>
                    </div>
                    <div class="quick-card" onclick="window.location.href='driver_forum.php'">
                        <div class="quick-icon"><i class="fa-solid fa-comments"></i></div>
                        <div class="quick-title">Chat</div>
                        <?php if ($chat_unread_count > 0): ?><div class="alert-badge"><?php echo $chat_unread_count; ?> New Msg</div><?php endif; ?>
                        <div class="quick-link">Go →</div>
                    </div>
                    
                    <div class="quick-card" onclick="window.location.href='contact_us.php'">
                        <div class="quick-icon"><i class="fa-solid fa-headset"></i></div>
                        <div class="quick-title">Contact Admin</div>
                        
                        <?php if ($admin_unread_count > 0): ?>
                            <div class="alert-badge"><?php echo $admin_unread_count; ?> New Reply</div>
                        <?php endif; ?>

                        <div class="quick-link">Contact →</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- NOTIFICATION LOGIC ---
    // Get unread count from PHP
    var unreadAdmin = <?php echo (int)$admin_unread_count; ?>;

    // Check if we have unread messages AND if we haven't shown the popup in this session yet
    if (unreadAdmin > 0 && !sessionStorage.getItem('adminSupportNotified')) {
        
        Swal.fire({
            title: 'New Support Message!',
            text: 'You have ' + unreadAdmin + ' new reply from the Support Team.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Read & Reply',
            cancelButtonText: 'Later'
        }).then((result) => {
            if (result.isConfirmed) {
                // Only redirect if they click Confirm.
                // NOTE: This does NOT mark the message as read. 
                // That happens only when the next page loads.
                window.location.href = 'contact_us.php';
            }
            // Record that we have notified the user, so we don't annoy them on refresh
            sessionStorage.setItem('adminSupportNotified', 'true');
        });
    }

    // --- LOGOUT LOGIC ---
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); 
            let targetUrl = this.href;
            Swal.fire({
                title: 'Sign Out?', 
                text: "Are you sure you want to end your session?", 
                icon: 'warning',
                showCancelButton: true, 
                confirmButtonColor: '#d33', 
                confirmButtonText: 'Yes, Sign Out'
            }).then((result) => { 
                if (result.isConfirmed) {
                    // Clear session storage on logout so notifications reset for next login
                    sessionStorage.removeItem('adminSupportNotified');
                    window.location.href = targetUrl; 
                }
            });
        });
    });
});

function editProfilePopup() {
    Swal.fire({
        title: 'Edit Profile', text: 'Update password and details.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#005a9c', confirmButtonText: 'Proceed',
        showCloseButton: true
    }).then((result) => { if (result.isConfirmed) window.location.href = 'driver_profile.php'; });
}

function editVehiclePopup() {
    Swal.fire({
        title: 'Manage Vehicle', text: 'Update vehicle details.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#005a9c', confirmButtonText: 'Go to Settings',
        showCloseButton: true
    }).then((result) => { if (result.isConfirmed) window.location.href = 'driver_vehicle.php'; });
}
</script>

<?php include "footer.php"; ?>