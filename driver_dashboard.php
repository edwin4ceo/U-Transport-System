<?php
session_start();

include "db_connect.php";
include "function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// --- 初始数据检索 ---
$full_name = "Driver";
$email = $vehicle_model = $plate_number = $vehicle_type = $vehicle_color = $seat_count = "";

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

// 获取初始计数用于 JS 对比
$pending_bookings_count = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE driver_id = $driver_id AND status = 'Pending'");
if ($row = $res->fetch_assoc()) $pending_bookings_count = $row['total'];

$chat_unread_count = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM ride_chat_messages r JOIN bookings b ON r.booking_ref = b.id WHERE b.driver_id = $driver_id AND r.sender_type = 'student' AND r.is_read = 0");
if ($row = $res->fetch_assoc()) $chat_unread_count = $row['total'];

$admin_unread_count = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM driver_support_messages WHERE driver_id = $driver_id AND sender_type = 'admin' AND is_read = 0");
if ($row = $res->fetch_assoc()) $admin_unread_count = $row['total'];

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* ... 这里保留你之前的所有 <style> 代码 ... */
    :root { --primary: #004b82; --bg-color: #f8f9fc; --card-bg: #ffffff; --text-main: #1a202c; --text-light: #718096; }
    body { background: var(--bg-color); font-family: 'Inter', sans-serif; }
    .dashboard-wrapper { max-width: 1200px; width: 95%; margin: 0 auto 40px; padding: 20px; box-sizing: border-box; }
    .dashboard-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #edf2f7; }
    .dashboard-title h1 { margin: 0 0 5px 0; font-size: 26px; font-weight: 800; color: var(--text-main); }
    .dashboard-subtitle { font-size: 14px; color: var(--text-light); margin: 0; }
    .btn-edit-profile { background: white; color: var(--primary); border: 1px solid #cbd5e0; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .dashboard-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
    .modern-card { background: var(--card-bg); border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #eef2f6; height: fit-content; }
    .card-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f7fafc; }
    .card-title-text { font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
    .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 14px; }
    .info-val { color: var(--text-main); font-weight: 600; text-align: right; }
    .quick-actions-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .action-tile { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px 20px; display: flex; flex-direction: column; align-items: center; text-align: center; text-decoration: none; transition: all 0.2s ease; position: relative; }
    .action-tile:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 75, 130, 0.1); border-color: #3182ce; }
    .tile-icon { width: 50px; height: 50px; background: #f0f7ff; color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 15px; }
    .tile-title { font-size: 14px; font-weight: 700; color: var(--text-main); }
    .tile-badge { position: absolute; top: -8px; right: -8px; background: #e53e3e; color: white; border: 2px solid white; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 3px 8px rgba(229, 62, 62, 0.4); z-index: 10; }
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Hello, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">Here is what's happening today.</p>
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
                <div class="info-row"><span class="info-label">Vehicle</span><span class="info-val"><?php echo $vehicle_model ? htmlspecialchars($vehicle_model) : 'Not set'; ?></span></div>
            </div>
            <a href="javascript:void(0)" onclick="editVehiclePopup()" style="display:block; text-align:center; margin-top:15px; font-size:13px; color:#004b82;">Manage Vehicle →</a>
        </div>

        <div class="modern-card">
            <div class="card-title-row"><div class="card-title-text"><i class="fa-solid fa-layer-group"></i> Quick Actions</div></div>
            <div class="quick-actions-grid">
                
                <a href="driver_booking_requests.php" class="action-tile">
                    <div id="badge-booking"><?php if ($pending_bookings_count > 0) echo "<div class='tile-badge'>$pending_bookings_count Pending</div>"; ?></div>
                    <div class="tile-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="tile-title">Booking Requests</div>
                </a>

                <a href="driver_forum.php" class="action-tile">
                    <div id="badge-chat"><?php if ($chat_unread_count > 0) echo "<div class='tile-badge'>$chat_unread_count New</div>"; ?></div>
                    <div class="tile-icon"><i class="fa-solid fa-comments"></i></div>
                    <div class="tile-title">Student Chat</div>
                </a>

                <a href="contact_us.php" class="action-tile">
                    <div id="badge-admin"><?php if ($admin_unread_count > 0) echo "<div class='tile-badge'>Reply</div>"; ?></div>
                    <div class="tile-icon"><i class="fa-solid fa-headset"></i></div>
                    <div class="tile-title">Admin Support</div>
                </a>

                <a href="driver_today_trips.php" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-route"></i></div>
                    <div class="tile-title">Today's Trips</div>
                </a>
                <a href="javascript:void(0)" onclick="editVehiclePopup()" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-car"></i></div>
                    <div class="tile-title">Vehicle Settings</div>
                </a>
                <a href="driver_ratings.php" class="action-tile">
                    <div class="tile-icon"><i class="fa-solid fa-star"></i></div>
                    <div class="tile-title">My Ratings</div>
                </a>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 1. 初始化当前数值
let currentStats = {
    bookings: <?php echo (int)$pending_bookings_count; ?>,
    chats: <?php echo (int)$chat_unread_count; ?>,
    admin: <?php echo (int)$admin_unread_count; ?>
};

// 2. 配置弹出通知样式
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true
});

// 3. 检查新通知的函数
function checkNotifications() {
    fetch('driver_check_notifications.php')
        .then(res => res.json())
        .then(data => {
            // 检查新订单
            if (data.pending_bookings > currentStats.bookings) {
                Toast.fire({ icon: 'info', title: 'New Booking Request!', text: 'You have a new ride request.' });
                document.getElementById('badge-booking').innerHTML = `<div class='tile-badge'>${data.pending_bookings} Pending</div>`;
            } else if (data.pending_bookings == 0) {
                document.getElementById('badge-booking').innerHTML = '';
            }

            // 检查新聊天
            if (data.chat_unread > currentStats.chats) {
                Toast.fire({ icon: 'success', title: 'New Message', text: 'A student sent you a message.' });
                document.getElementById('badge-chat').innerHTML = `<div class='tile-badge'>${data.chat_unread} New</div>`;
            } else if (data.chat_unread == 0) {
                document.getElementById('badge-chat').innerHTML = '';
            }

            // 检查管理员回复
            if (data.admin_unread > currentStats.admin) {
                Toast.fire({ icon: 'warning', title: 'Admin Support', text: 'You have a new reply from admin.' });
                document.getElementById('badge-admin').innerHTML = `<div class='tile-badge'>Reply</div>`;
            }

            // 更新当前状态
            currentStats = {
                bookings: data.pending_bookings,
                chats: data.chat_unread,
                admin: data.admin_unread
            };
        })
        .catch(err => console.log('Notification Check Error:', err));
}

// 4. 每 10 秒自动检查一次
setInterval(checkNotifications, 10000);

// 原有的弹窗函数
function editProfilePopup() {
    Swal.fire({ title: 'Edit Profile', text: 'Update password and details.', icon: 'info', showCancelButton: true, confirmButtonColor: '#004b82' })
    .then((result) => { if (result.isConfirmed) window.location.href = 'driver_profile.php'; });
}
function editVehiclePopup() {
    Swal.fire({ title: 'Manage Vehicle', text: 'Update vehicle details.', icon: 'info', showCancelButton: true, confirmButtonColor: '#004b82' })
    .then((result) => { if (result.isConfirmed) window.location.href = 'driver_vehicle.php'; });
}
</script>

<?php include "footer.php"; ?>