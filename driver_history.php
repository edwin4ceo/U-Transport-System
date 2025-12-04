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

/**
 * Driver ride history based on current tables:
 *
 * bookings (
 *   id INT,
 *   student_id VARCHAR(50),   -- FK to students.student_id (string)
 *   destination VARCHAR(255),
 *   date_time DATETIME,
 *   passengers INT,
 *   pickup_point VARCHAR(255),
 *   remark TEXT,
 *   status VARCHAR(50),
 *   created_at TIMESTAMP,
 *   driver_id INT NULL        -- you need to add this via ALTER TABLE
 * )
 *
 * students (
 *   id INT,
 *   name VARCHAR(100),
 *   student_id VARCHAR(50),
 *   email VARCHAR(100),
 *   password VARCHAR(255),
 *   phone VARCHAR(20)
 * )
 */

$history = [];

// Fetch bookings for this driver
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
    ORDER BY b.date_time DESC, b.id DESC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
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

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 10px;
}

.history-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.history-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.history-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.history-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

/* Single history row */
.history-item {
    border-bottom: 1px dashed #e0e0e0;
    padding: 10px 0;
}

.history-item:last-child {
    border-bottom: none;
}

.history-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.history-route {
    font-size: 14px;
    font-weight: 600;
    color: #004b82;
}

.history-date {
    font-size: 11px;
    color: #888;
}

.history-middle-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #555;
    gap: 10px;
    flex-wrap: wrap;
}

.history-bottom-row {
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

/* Simple pill */
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
    padding: 30px 10px;
    font-size: 13px;
    color: #777;
}

.empty-state i {
    font-size: 28px;
    color: #cccccc;
    margin-bottom: 8px;
}
</style>

<div class="history-wrapper">
    <div class="history-header">
        <div class="history-header-title">
            <h1>Ride History</h1>
            <p>View your past trips and completed bookings.</p>
        </div>
    </div>

    <div class="history-card">
        <?php if (count($history) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-clock"></i>
                <div>You do not have any trip history yet.</div>
            </div>
        <?php else: ?>
            <?php foreach ($history as $row): ?>
                <?php
                    $pickup      = $row['pickup_point'] ?? '';
                    $destination = $row['destination']   ?? '';
                    $route       = $pickup && $destination
                                   ? $pickup . " → " . $destination
                                   : "Trip #" . (int)$row['booking_id'];

                    $datetime = $row['date_time']
                        ? date("d M Y, h:i A", strtotime($row['date_time']))
                        : "-";

                    $statusRaw = trim($row['status'] ?? '');
                    $status    = $statusRaw !== '' ? strtoupper($statusRaw) : 'PENDING';

                    // Map status to badge class
                    $badgeClass = "badge-status badge-pending";
                    if (in_array(strtoupper($statusRaw), ['COMPLETED', 'FINISHED', 'DONE'])) {
                        $badgeClass = "badge-status badge-completed";
                    } elseif (in_array(strtoupper($statusRaw), ['CANCELLED', 'REJECTED', 'FAILED'])) {
                        $badgeClass = "badge-status badge-cancelled";
                    }

                    $passengers      = isset($row['passengers']) ? (int)$row['passengers'] : 1;
                    $remark          = $row['remark'] ?? '';
                    $passenger_name  = $row['passenger_name']  ?? 'Passenger';
                    $passenger_phone = $row['passenger_phone'] ?? '';
                ?>
                <div class="history-item">
                    <div class="history-top-row">
                        <div class="history-route">
                            <?php echo htmlspecialchars($route); ?>
                        </div>
                        <div class="history-date">
                            <?php echo htmlspecialchars($datetime); ?>
                        </div>
                    </div>

                    <div class="history-middle-row">
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
                        <div class="history-middle-row">
                            <div>
                                Remark: <span><?php echo htmlspecialchars($remark); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="history-bottom-row">
                        <span class="<?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                        <span class="info-pill">
                            Booking ID: #<?php echo (int)$row['booking_id']; ?>
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
