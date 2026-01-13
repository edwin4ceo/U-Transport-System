<?php
session_start();

include "db_connect.php";
include "function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Default values
$full_name       = "Driver";
$email           = "";
$vehicle_model   = "";
$plate_number    = "";
$vehicle_type    = "";
$vehicle_color   = "";
$seat_count      = "";

// Retrieve driver info
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

// --- 1. Count Pending Bookings (FIXED: 'Pending' with capital P) ---
$pending_bookings_count = 0;
$notify_stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE driver_id = ? AND status = 'Pending'");
if ($notify_stmt) {
    $notify_stmt->bind_param("i", $driver_id);
    $notify_stmt->execute();
    $res = $notify_stmt->get_result();
    if ($row = $res->fetch_assoc()) $pending_bookings_count = $row['total'];
    $notify_stmt->close();
}

// --- 2. Count Unread Chat ---
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

// --- 3. Count Admin Messages ---
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

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #004b82;
        --bg-color: #f8f9fc;
        --card-bg: #ffffff;
        --text-main: #1a202c;
        --text-light: #718096;
    }

    body { background: var(--bg-color); font-family: 'Inter', sans-serif; }

    .dashboard-wrapper { max-width: 1200px; width: 95%; margin: 0 auto 40px; padding: 20px; box-sizing: border-box; }

    /* Header */
    .dashboard-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #edf2f7; }
    .dashboard-title h1 { margin: 0 0 5px 0; font-size: 26px; font-weight: 800; color: var(--text-main); }
    .dashboard-subtitle { font-size: 14px; color: var(--text-light); margin: 0; }
    .driver-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #ebf8ff; color: var(--primary); border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 8px; }
    
    .btn-edit-profile { background: white; color: var(--primary); border: 1px solid #cbd5e0; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-edit-profile:hover { border-color: var(--primary); background: #f8fafc; }

    /* Layout */
    .dashboard-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
    .modern-card { background: var(--card-bg); border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #eef2f6; height: fit-content; }
    
    /* Info List */
    .card-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f7fafc; }
    .card-title-text { font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
    .card-title-text i { color: var(--primary); }
    .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 14px; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-light); font-weight: 500; }
    .info-val { color: var(--text-main); font-weight: 600; text-align: right; }
    .update-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: #3182ce; font-weight: 600; text-decoration: none; }

    /* Tiles */
    .quick-actions-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .action-tile { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px 20px; display: flex; flex-direction: column; align-items: center; text-align: center; text-decoration: none; transition: all 0.2s ease; position: relative; }
    .action-tile:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 75, 130, 0.1); border-color: #3182ce; }
    .tile-icon { width: 50px; height: 50px; background: #f0f7ff; color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 15px; transition: background 0.2s; }
    .action-tile:hover .tile-icon { background: var(--primary); color: white; }
    .tile-title { font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 5px; }
    .tile-desc { font-size: 12px; color: var(--text-light); }

    /* --- [UPDATED] SOLID RED NOTIFICATION BADGE --- */
    .tile-badge {
        position: absolute; 
        top: -8px; right: -8px; /* Slightly outside the box */
        background: #e53e3e;    /* Solid Red Background */
        color: white;           /* White Text */
        border: 2px solid white; /* White border to separate from tile */
        font-size: 12px; font-weight: 700; 
        padding: 4px 10px; 
        border-radius: 20px;    /* Pill shape */
        box-shadow: 0 3px 8px rgba(229, 62, 62, 0.4); /* Red Glow */
        z-index: 10;
        animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
        from { transform: scale(0); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } .quick-actions-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .quick-actions-grid { grid-template-columns: 1fr; } }
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Hello, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">Here is what's happening with your transport account today.</p>
        </div>
        <div class="header-actions">
            <a href="javascript:void(0)" onclick="editProfilePopup()" class="btn-edit-profile">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="modern-card">
            <div class="card-title-row"><div class="card-title-text"><i class="fa-solid fa-id-card"></i> Profile Overview</div></div>
            <div class="info-list">
                <div class="info-row"><span class="info-label">Full Name</span><span class="info-val"><?php echo htmlspecialchars($full_name); ?></span></div>
                <div class="info-row"><span class="info-label">Email</span><span class="info-val"><?php echo htmlspecialchars($email); ?></span></div>
                <div class="info-row"><span class="info-label">Vehicle</span><span class="info-val"><?php echo $vehicle_model ? htmlspecialchars($vehicle_model) : '<span style="color:#aaa">Not set</span>'; ?></span></div>
                <div class="info-row"><span class="info-label">Plate No.</span><span class="info-val" style="font-family:monospace;"><?php echo $plate_number ? htmlspecialchars($plate_number) : '---'; ?></span></div>
                <div class="info-row"><span class="info-label">Capacity</span><span class="info-val"><?php echo $seat_count ? htmlspecialchars($seat_count).' Pax' : '---'; ?></span></div>
            </div>
            <a href="javascript:void(0)" onclick="editVehiclePopup()" class="update-link">Manage Vehicle Details →</a>
        </div>

        <div class="modern-card">
            <div class="card-title-row"><div class="card-title-text"><i class="fa-solid fa-grid-2"></i> Quick Actions</div></div>
            <div class="quick-actions-grid">
                
                <a href="driver_booking_requests.php" class="action-tile">
                    <?php if ($pending_bookings_count > 0): ?>
                        <div class="tile-badge"><?php echo $pending_bookings_count; ?> Pending</div>
                    <?php endif; ?>
                    <div class="tile-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="tile-title">Booking Requests</div>
                    <div class="tile-desc">Manage ride requests</div>
                </a>

                <a href="driver_today_trips.php" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-route"></i></div>
                    <div class="tile-title">Today's Trips</div>
                    <div class="tile-desc">View daily schedule</div>
                </a>

                <a href="driver_forum.php" class="action-tile">
                    <?php if ($chat_unread_count > 0): ?>
                        <div class="tile-badge"><?php echo $chat_unread_count; ?> New</div>
                    <?php endif; ?>
                    <div class="tile-icon"><i class="fa-solid fa-comments"></i></div>
                    <div class="tile-title">Student Chat</div>
                    <div class="tile-desc">Communicate with riders</div>
                </a>

                <a href="javascript:void(0)" onclick="editVehiclePopup()" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-car"></i></div>
                    <div class="tile-title">Vehicle Settings</div>
                    <div class="tile-desc">Update details</div>
                </a>

                <a href="driver_ratings.php" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-star"></i></div>
                    <div class="tile-title">My Ratings</div>
                    <div class="tile-desc">View feedback</div>
                </a>

                <a href="contact_us.php" class="action-tile">
                    <?php if ($admin_unread_count > 0): ?>
                        <div class="tile-badge">Reply</div>
                    <?php endif; ?>
                    <div class="tile-icon"><i class="fa-solid fa-headset"></i></div>
                    <div class="tile-title">Admin Support</div>
                    <div class="tile-desc">Get help</div>
                </a>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function editProfilePopup() {
    Swal.fire({
        title: 'Edit Profile', text: 'Update password and details.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#004b82', confirmButtonText: 'Proceed',
        showCloseButton: true
    }).then((result) => { if (result.isConfirmed) window.location.href = 'driver_profile.php'; });
}

function editVehiclePopup() {
    Swal.fire({
        title: 'Manage Vehicle', text: 'Update vehicle details.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#004b82', confirmButtonText: 'Go to Settings',
        showCloseButton: true
    }).then((result) => { if (result.isConfirmed) window.location.href = 'driver_vehicle.php'; });
}

// Logout Confirmation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); 
            let targetUrl = this.href;
            Swal.fire({
                title: 'Sign Out?', text: "Are you sure you want to end your session?", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, Sign Out'
            }).then((result) => { if (result.isConfirmed) window.location.href = targetUrl; });
        });
    });
});
</script>

<?php include "footer.php"; ?>