<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check if user is logged in
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

/**
 * Passenger "My Rides" Page
 * Fetches bookings for the current student.
 * Uses LEFT JOIN to display Driver and Vehicle details.
 */

$rides = [];

// Fetch Data
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.passengers,
        b.remark,
        b.status,
        b.vehicle_type,
        d.full_name AS driver_name,
        v.vehicle_model,
        v.plate_number
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.student_id = ?
    ORDER BY b.date_time ASC, b.id ASC
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rides[] = $row;
        }
    }
    $stmt->close();
}

// Separate into Upcoming and Past sections
$upcoming = [];
$past     = [];

foreach ($rides as $r) {
    $statusRaw = strtoupper(trim($r['status'] ?? ''));
    
    // Statuses considered as "Past"
    if (in_array($statusRaw, ['COMPLETED', 'CANCELLED', 'REJECTED', 'FAILED'])) {
        $past[] = $r;
    } else {
        // Statuses considered as "Upcoming" (Pending, Accepted, Ongoing)
        $upcoming[] = $r;
    }
}

include "header.php"; 
?>

<style>
.rides-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}
.rides-header-title h1 { margin: 0; font-size: 22px; font-weight: 700; color: #004b82; }
.rides-header-title p { margin: 0; font-size: 13px; color: #666; }
.rides-section-title { font-size: 15px; font-weight: 600; color: #2c3e50; margin: 20px 0 8px; }
.rides-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 8px 24px rgba(0,0,0,0.06); padding: 18px; }
.ride-item { border-bottom: 1px dashed #e0e0e0; padding: 15px 0; }
.ride-item:last-child { border-bottom: none; }
.ride-route { font-size: 15px; font-weight: 700; color: #004b82; margin-bottom: 5px; }
.ride-date { font-size: 12px; color: #888; white-space: nowrap; }
.ride-middle-row { display: flex; justify-content: space-between; font-size: 13px; color: #555; gap: 15px; flex-wrap: wrap; margin-top: 8px; }
.badge-status { padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.badge-pending { background: #fff8e6; color: #d35400; border: 1px solid #f8d49a; }
.badge-active { background: #e8f8ec; color: #27ae60; border: 1px solid #b7e2c4; } 
.badge-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.badge-cancelled { background: #fdecea; color: #e74c3c; border: 1px solid #f5b7b1; }
.info-pill { padding: 3px 9px; border-radius: 999px; background: #eef4ff; color: #2c3e50; font-size: 11px; font-weight: 600; }
.empty-state { text-align: center; padding: 30px; font-size: 14px; color: #777; }
.empty-state i { font-size: 32px; color: #cccccc; margin-bottom: 10px; display: block; }
</style>

<div class="rides-wrapper">
    <div class="rides-header">
        <div class="rides-header-title">
            <h1>My Rides</h1>
            <p>View your upcoming trips and ride status.</p>
        </div>
    </div>

    <h2 class="rides-section-title">Upcoming rides</h2>
    <div class="rides-card">
        <?php if (count($upcoming) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-clock"></i>
                <div>No upcoming rides.</div>
            </div>
        <?php else: ?>
            <?php foreach ($upcoming as $row): ?>
                <?php renderRideItem($row); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <h2 class="rides-section-title">Past rides</h2>
    <div class="rides-card">
        <?php if (count($past) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                <div>No past rides history.</div>
            </div>
        <?php else: ?>
            <?php foreach ($past as $row): ?>
                <?php renderRideItem($row); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to render a single row
function renderRideItem($row) {
    $pickup      = $row['pickup_point'];
    $destination = $row['destination'];
    $route       = "$pickup → $destination";
    $datetime    = date("d M Y, h:i A", strtotime($row['date_time']));
    
    $statusRaw   = strtoupper(trim($row['status'] ?? ''));
    $statusDisplay = $statusRaw ?: 'PENDING';

    // Status Colors
    $badgeClass = "badge-status badge-pending";
    if (in_array($statusRaw, ['ACCEPTED', 'ONGOING'])) $badgeClass = "badge-status badge-active";
    if (in_array($statusRaw, ['COMPLETED'])) $badgeClass = "badge-status badge-completed";
    if (in_array($statusRaw, ['CANCELLED', 'REJECTED'])) $badgeClass = "badge-status badge-cancelled";

    $driverName = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : "Finding Driver...";
    $carInfo    = $row['vehicle_model'] ? htmlspecialchars($row['vehicle_model'] . " (" . $row['plate_number'] . ")") : "-";
    ?>
    <div class="ride-item">
        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
            <div class="ride-route"><?php echo htmlspecialchars($route); ?></div>
            <div class="ride-date"><?php echo $datetime; ?></div>
        </div>

        <div class="ride-middle-row">
            <div>
                <i class="fa-solid fa-user-tie"></i> Driver: 
                <strong><?php echo $driverName; ?></strong>
            </div>
            <div>
                <i class="fa-solid fa-car"></i> 
                <?php echo $carInfo; ?>
            </div>
        </div>

        <?php if (!empty($row['remark'])): ?>
            <div class="ride-middle-row">
                <div style="color:#777; font-size:12px; font-style:italic;">
                    Remark: <?php echo htmlspecialchars($row['remark']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; margin-top:10px; align-items:center;">
            <span class="<?php echo $badgeClass; ?>"><?php echo $statusDisplay; ?></span>
            
            <div style="display:flex; gap:5px;">
                <span class="info-pill"><?php echo $row['passengers']; ?> Pax</span>
                <span class="info-pill">#<?php echo $row['booking_id']; ?></span>
            </div>
        </div>
    </div>
    <?php
}
include "footer.php"; 
?>