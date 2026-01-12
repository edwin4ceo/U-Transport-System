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
        b.driver_id, 
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
/* --- RE-ADJUSTED SIZES (Balanced Mode) --- */
.rides-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px; max-width: 1100px; margin: 0 auto; background: #f5f7fb; }

/* 1. Main Page Title - Made slightly bigger to dominate */
.rides-header-title h1 { margin: 0; font-size: 26px; font-weight: 700; color: #004b82; }
.rides-header-title p { margin: 4px 0 0; font-size: 13px; color: #666; }

/* 2. Section Titles - Slightly smaller than before */
.section-title { font-size: 16px; font-weight: 600; color: #2c3e50; margin: 25px 0 10px; }

/* 3. Card Container - Reduced padding to fix "too big" feel */
.ride-card { background: #fff; border-radius: 16px; border: 1px solid #e3e6ea; padding: 15px 18px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }

/* Ride Item Rows */
.ride-item { border-bottom: 1px dashed #e0e0e0; padding: 15px 0; }
.ride-item:last-child { border-bottom: none; }

/* 4. Route Text - Balanced size (15px) */
.ride-route { font-size: 15px; font-weight: 700; color: #004b82; }
.ride-date { font-size: 12px; color: #888; }

/* 5. Middle Info - Balanced size (13.5px) */
.ride-middle-row { display: flex; justify-content: space-between; font-size: 13.5px; color: #555; margin-top: 6px; gap: 10px; flex-wrap: wrap; }

/* Badges & Pills - Kept compact */
.status-badge { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: bold; }
.st-pending { background: #fff8e6; color: #d35400; }
.st-active { background: #e8f8ec; color: #27ae60; }
.st-past { background: #f0f0f0; color: #777; }

.info-pill { padding: 3px 8px; border-radius: 999px; background: #eef4ff; color: #2c3e50; font-size: 11px; font-weight: 600; }

.btn-chat { 
    background: #0084ff; color: white; border: none; padding: 5px 14px; 
    border-radius: 20px; font-size: 12px; cursor: pointer; text-decoration: none; 
    display: inline-flex; align-items: center; gap: 5px;
}
.btn-chat:hover { background: #006bcf; }
</style>

<div class="rides-wrapper">
    <div class="rides-header-title">
        <h1>My Rides</h1>
        <p>View your upcoming trips and ride status.</p>
    </div>

    <h2 class="section-title">Upcoming rides</h2>
    <div class="ride-card">
        <?php if (empty($upcoming)): ?>
            <p style="text-align:center; color:#999; font-size:14px; padding:15px;">No upcoming rides.</p>
        <?php else: ?>
            <?php foreach ($upcoming as $row): renderRide($row); endforeach; ?>
        <?php endif; ?>
    </div>

    <h2 class="section-title">Past rides</h2>
    <div class="ride-card">
        <?php if (empty($past)): ?>
            <p style="text-align:center; color:#999; font-size:14px; padding:15px;">No past history.</p>
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
    
    // [FIX] Removed $chatKey logic, using booking_id directly
    ?>
    <div class="ride-item">
        <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
            <div class="ride-route"><?php echo htmlspecialchars($row['pickup_point'] . ' → ' . $row['destination']); ?></div>
            <div class="ride-date"><?php echo $date; ?></div>
        </div>
        
        <div class="ride-middle-row">
            <div style="display:flex; align-items:center; gap:5px;">
                <i class="fa-solid fa-user-tie"></i> Driver: <b><?php echo htmlspecialchars($driver); ?></b>
            </div>
            
            <?php if($status == 'ACCEPTED'): ?>
                <a href="ride_chat.php?room=<?php echo $row['booking_id']; ?>" class="btn-chat">
                    <i class="fa-regular fa-comments"></i> Group Chat
                </a>
            <?php endif; ?>
        </div>

        <?php if(!empty($row['remark'])): ?>
            <div class="ride-middle-row" style="color:#777; font-style:italic; font-size:12.5px;">
                Remark: <?php echo htmlspecialchars($row['remark']); ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
            <span class="<?php echo $badge; ?>"><?php echo $status; ?></span>
            <div style="display:flex; gap:8px;">
                <span class="info-pill"><?php echo $row['passengers']; ?> Pax</span>
                <span class="info-pill">#<?php echo $row['booking_id']; ?></span>
            </div>
        </div>
    </div>
    <?php
}
include "footer.php"; 
?>