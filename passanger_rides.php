<?php
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// Fetch Rides
$rides = [];
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.passengers,
        b.remark,
        b.status,
        b.driver_id, /* Need this for chat */
        d.full_name AS driver_name,
        v.vehicle_model,
        v.plate_number
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.student_id = ?
    ORDER BY b.date_time ASC
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rides[] = $row;
    $stmt->close();
}

$upcoming = [];
$past = [];

foreach ($rides as $r) {
    $st = strtoupper(trim($r['status'] ?? ''));
    if (in_array($st, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
        $past[] = $r;
    } else {
        $upcoming[] = $r;
    }
}

include "header.php"; 
?>

<style>
/* CSS Styles */
.rides-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px; max-width: 1100px; margin: 0 auto; background: #f5f7fb; }
.section-title { font-size: 15px; font-weight: 600; color: #2c3e50; margin: 20px 0 8px; }
.ride-card { background: #fff; border-radius: 16px; border: 1px solid #e3e6ea; padding: 18px; margin-bottom: 15px; }
.ride-item { border-bottom: 1px dashed #e0e0e0; padding: 15px 0; }
.ride-item:last-child { border-bottom: none; }
.ride-route { font-size: 15px; font-weight: 700; color: #004b82; }
.status-badge { padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: bold; }
.st-pending { background: #fff8e6; color: #d35400; }
.st-active { background: #e8f8ec; color: #27ae60; }
.st-past { background: #f0f0f0; color: #777; }
.btn-chat { 
    background: #0084ff; color: white; border: none; padding: 5px 12px; 
    border-radius: 20px; font-size: 12px; cursor: pointer; text-decoration: none; 
    display: inline-flex; align-items: center; gap: 5px;
}
.btn-chat:hover { background: #006bcf; }
</style>

<div class="rides-wrapper">
    <h1>My Rides</h1>

    <h2 class="section-title">Upcoming rides</h2>
    <div class="ride-card">
        <?php if (empty($upcoming)): ?>
            <p style="text-align:center; color:#999;">No upcoming rides.</p>
        <?php else: ?>
            <?php foreach ($upcoming as $row): renderRide($row); endforeach; ?>
        <?php endif; ?>
    </div>

    <h2 class="section-title">Past rides</h2>
    <div class="ride-card">
        <?php if (empty($past)): ?>
            <p style="text-align:center; color:#999;">No past history.</p>
        <?php else: ?>
            <?php foreach ($past as $row): renderRide($row); endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
function renderRide($row) {
    $date = date("d M Y, h:i A", strtotime($row['date_time']));
    $status = strtoupper($row['status']);
    
    $badge = "status-badge st-pending";
    if ($status == 'ACCEPTED') $badge = "status-badge st-active";
    if (in_array($status, ['COMPLETED', 'CANCELLED'])) $badge = "status-badge st-past";

    $driver = $row['driver_name'] ?: "Pending Driver";
    
    // Create Unique Chat Key: DriverID_DateTime
    // Example: 3_2025-12-30 14:00:00
    // We encode it to pass in URL
    $chatKey = $row['driver_id'] . '_' . $row['date_time'];
    ?>
    <div class="ride-item">
        <div style="display:flex; justify-content:space-between;">
            <div class="ride-route"><?php echo htmlspecialchars($row['pickup_point'] . ' → ' . $row['destination']); ?></div>
            <div style="font-size:12px; color:#888;"><?php echo $date; ?></div>
        </div>
        
        <div style="font-size:13px; color:#555; margin-top:5px; display:flex; justify-content:space-between; align-items:center;">
            <div>Driver: <b><?php echo htmlspecialchars($driver); ?></b></div>
            
            <?php if($status == 'ACCEPTED'): ?>
                <a href="ride_chat.php?room=<?php echo urlencode($chatKey); ?>" class="btn-chat">
                    <i class="fa-regular fa-comments"></i> Group Chat
                </a>
            <?php endif; ?>
        </div>

        <div style="margin-top:8px;">
            <span class="<?php echo $badge; ?>"><?php echo $status; ?></span>
        </div>
    </div>
    <?php
}
include "footer.php"; 
?>