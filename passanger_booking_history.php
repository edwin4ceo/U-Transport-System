<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check if user is logged in
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// 2. Fetch bookings for the logged-in student, ordered by newest first
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
        d.full_name AS driver_name
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    WHERE b.student_id = ?
    ORDER BY b.date_time DESC, b.id DESC
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rides[] = $row;
    }
    $stmt->close();
}

include "header.php"; 
?>

<style>
.history-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}
.history-header-title h1 { margin: 0; font-size: 22px; font-weight: 700; color: #004b82; }
.history-header-title p { margin: 0; font-size: 13px; color: #666; }
.history-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 8px 24px rgba(0,0,0,0.06); padding: 18px 18px 16px; margin-top: 20px; }
.history-item { border-bottom: 1px dashed #e0e0e0; padding: 15px 0; }
.history-item:last-child { border-bottom: none; }
.history-route { font-size: 15px; font-weight: 700; color: #004b82; margin-bottom: 5px; }
.history-date { font-size: 12px; color: #888; }
.badge-status { padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.badge-pending { background: #fff8e6; color: #d35400; border: 1px solid #f8d49a; }
.badge-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.badge-cancelled { background: #fdecea; color: #e74c3c; border: 1px solid #f5b7b1; }
.info-pill { padding: 3px 9px; border-radius: 999px; background: #eef4ff; color: #2c3e50; font-size: 11px; font-weight: 600; }
.empty-state { text-align: center; padding: 30px; font-size: 13px; color: #777; }
</style>

<div class="history-wrapper">
    <div class="history-header">
        <div class="history-header-title">
            <h1>Ride History</h1>
            <p>View your full list of past transport requests.</p>
        </div>
    </div>

    <div class="history-card">
        <?php if (count($rides) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-clock" style="font-size:28px; margin-bottom:10px;"></i>
                <div>You do not have any trip history yet.</div>
            </div>
        <?php else: ?>
            <?php foreach ($rides as $row): ?>
                <?php
                    $pickup      = $row['pickup_point'];
                    $destination = $row['destination'];
                    $route       = "$pickup → $destination";
                    $datetime    = date("d M Y, h:i A", strtotime($row['date_time']));
                    $statusRaw   = strtoupper(trim($row['status'] ?? ''));
                    
                    // Status Badge Logic
                    $badgeClass = "badge-status badge-pending";
                    if ($statusRaw === 'COMPLETED') $badgeClass = "badge-status badge-completed";
                    if ($statusRaw === 'CANCELLED' || $statusRaw === 'REJECTED') $badgeClass = "badge-status badge-cancelled";
                    if ($statusRaw === 'ACCEPTED') $badgeClass = "badge-status badge-completed"; 

                    $driverName = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : "None";
                ?>
                <div class="history-item">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <div class="history-route"><?php echo htmlspecialchars($route); ?></div>
                        <div class="history-date"><?php echo $datetime; ?></div>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:8px;">
                        <div>
                            Driver: <strong><?php echo $driverName; ?></strong>
                        </div>
                        <div>
                            Passengers: <strong><?php echo $row['passengers']; ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($row['remark'])): ?>
                        <div style="font-size:12px; color:#777; margin-bottom:8px; font-style:italic;">
                            Note: <?php echo htmlspecialchars($row['remark']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="<?php echo $badgeClass; ?>"><?php echo $statusRaw ?: 'PENDING'; ?></span>
                        <span class="info-pill">ID: #<?php echo $row['booking_id']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include "footer.php"; ?>