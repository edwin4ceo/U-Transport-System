<?php
session_start();

include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Fetch all trips for this driver where date_time is today
$trips = [];

$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.passengers,
        b.remark,
        b.status,
        s.name  AS passenger_name,
        s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s 
        ON b.student_id = s.student_id
    WHERE b.driver_id = ?
      AND DATE(b.date_time) = CURDATE()
    ORDER BY b.date_time ASC, b.id ASC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $trips[] = $row;
        }
    }
    $stmt->close();
}

include "header.php";
?>

<style>
.today-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}

.today-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 10px;
}

.today-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.today-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.today-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.today-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

/* Single trip row */
.trip-item {
    border-bottom: 1px dashed #e0e0e0;
    padding: 10px 0;
}

.trip-item:last-child {
    border-bottom: none;
}

.trip-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
    gap: 8px;
}

.trip-route {
    font-size: 14px;
    font-weight: 600;
    color: #004b82;
}

.trip-time {
    font-size: 11px;
    color: #888;
    white-space: nowrap;
}

.trip-middle-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #555;
    gap: 10px;
    flex-wrap: wrap;
}

.trip-bottom-row {
    display: flex;
    justify-content: space-between;
    margin-top: 4px;
    font-size: 12px;
    gap: 10px;
    flex-wrap: wrap;
}

/* Status badge */
.badge-status {
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.badge-pending {
    background: #fff8e6;
    color: #d35400;
    border: 1px solid #f8d49a;
}

.badge-active {
    background: #e8f8ec;
    color: #27ae60;
    border: 1px solid #b7e2c4;
}

.badge-completed {
    background: #e8f8ec;
    color: #27ae60;
    border: 1px solid #b7e2c4;
}

.badge-cancelled {
    background: #fdecea;
    color: #e74c3c;
    border: 1px solid #f5b7b1;
}

/* Pill */
.info-pill {
    padding: 3px 9px;
    border-radius: 999px;
    background: #eef4ff;
    color: #2c3e50;
    font-size: 11px;
    font-weight: 600;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 24px 10px;
    font-size: 13px;
    color: #777;
}

.empty-state i {
    font-size: 26px;
    color: #cccccc;
    margin-bottom: 6px;
}
</style>

<div class="today-wrapper">
    <div class="today-header">
        <div class="today-header-title">
            <h1>Today's Trips</h1>
            <p>View all trips assigned to you for today.</p>
        </div>
    </div>

    <div class="today-card">
        <?php if (count($trips) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                <div>You do not have any trips scheduled for today.</div>
            </div>
        <?php else: ?>
            <?php foreach ($trips as $row): ?>
                <?php
                    $pickup      = $row['pickup_point'] ?? '';
                    $destination = $row['destination']   ?? '';
                    $route       = $pickup && $destination
                                   ? $pickup . " → " . $destination
                                   : "Trip #" . (int)$row['booking_id'];

                    // Only show time part for today
                    $timeLabel = $row['date_time']
                        ? date("h:i A", strtotime($row['date_time']))
                        : "-";

                    $statusRaw = strtoupper(trim($row['status'] ?? ''));
                    $status    = $statusRaw !== '' ? $statusRaw : 'PENDING';

                    $badgeClass = "badge-status badge-pending";
                    if (in_array($statusRaw, ['ACCEPTED', 'ONGOING', 'IN PROGRESS'])) {
                        $badgeClass = "badge-status badge-active";
                    } elseif (in_array($statusRaw, ['COMPLETED', 'FINISHED', 'DONE'])) {
                        $badgeClass = "badge-status badge-completed";
                    } elseif (in_array($statusRaw, ['CANCELLED', 'REJECTED', 'FAILED'])) {
                        $badgeClass = "badge-status badge-cancelled";
                    }

                    $passengers      = isset($row['passengers']) ? (int)$row['passengers'] : 1;
                    $remark          = $row['remark'] ?? '';
                    $passenger_name  = $row['passenger_name']  ?? 'Passenger';
                    $passenger_phone = $row['passenger_phone'] ?? '';
                ?>
                <div class="trip-item">
                    <div class="trip-top-row">
                        <div class="trip-route">
                            <?php echo htmlspecialchars($route); ?>
                        </div>
                        <div class="trip-time">
                            <?php echo htmlspecialchars($timeLabel); ?>
                        </div>
                    </div>

                    <div class="trip-middle-row">
                        <div>
                            Passenger:
                            <strong><?php echo htmlspecialchars($passenger_name); ?></strong>
                            <?php if ($passenger_phone): ?>
                                <span style="font-size:11px; color:#777; margin-left:4px;">
                                    (<?php echo htmlspecialchars($passenger_phone); ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            Passengers: <strong><?php echo $passengers; ?></strong>
                        </div>
                    </div>

                    <?php if ($remark !== ''): ?>
                        <div class="trip-middle-row">
                            <div>
                                Remark: <span><?php echo htmlspecialchars($remark); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="trip-bottom-row">
                        <span class="<?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                        <span class="info-pill">
                            Trip ID: #<?php echo (int)$row['booking_id']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include "footer.php";
?>
