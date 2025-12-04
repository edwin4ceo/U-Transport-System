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
 * IMPORTANT:
 * Adjust this query based on your actual bookings table.
 *
 * Assumed table structure:
 *   bookings (
 *       booking_id INT,
 *       driver_id INT,
 *       student_id INT,      -- FK to students.id
 *       pickup_point VARCHAR,
 *       dropoff_point VARCHAR,
 *       travel_datetime DATETIME,
 *       status VARCHAR,      -- e.g. COMPLETED / CANCELLED / PENDING
 *       fare_amount DECIMAL
 *   )
 *
 *   students (
 *       id INT,              -- PK
 *       name VARCHAR(100),
 *       student_id VARCHAR(50),
 *       email VARCHAR(100),
 *       password VARCHAR(255),
 *       phone VARCHAR(20)
 *   )
 */

$history = [];

// JOIN students.id according to your SQL structure
$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.pickup_point,
        b.dropoff_point,
        b.travel_datetime,
        b.status,
        b.fare_amount,
        s.name AS passenger_name
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.id
    WHERE b.driver_id = ?
    ORDER BY b.travel_datetime DESC, b.booking_id DESC
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
}

.history-bottom-row {
    display: flex;
    justify-content: space-between;
    margin-top: 4px;
    font-size: 12px;
}

/* Status badge */
.badge-status {
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
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

.badge-pending {
    background: #fff8e6;
    color: #d35400;
    border: 1px solid #f8d49a;
}

/* Fare pill */
.fare-pill {
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
                    $pickup  = $row['pickup_point']  ?? '';
                    $dropoff = $row['dropoff_point'] ?? '';
                    $route   = $pickup && $dropoff 
                               ? $pickup . " → " . $dropoff 
                               : "Trip #" . (int)$row['booking_id'];

                    $datetime = $row['travel_datetime'] 
                        ? date("d M Y, h:i A", strtotime($row['travel_datetime']))
                        : "-";

                    $status = strtoupper($row['status'] ?? "");
                    $badgeClass = "badge-status";
                    if ($status === "COMPLETED") {
                        $badgeClass .= " badge-completed";
                    } elseif ($status === "CANCELLED") {
                        $badgeClass .= " badge-cancelled";
                    } else {
                        $badgeClass .= " badge-pending";
                    }

                    $fare = isset($row['fare_amount']) ? (float)$row['fare_amount'] : 0;
                    $passenger_name = $row['passenger_name'] ?? "Passenger";
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
                            Passenger: <strong><?php echo htmlspecialchars($passenger_name); ?></strong>
                        </div>
                        <div>
                            Booking ID: #<?php echo (int)$row['booking_id']; ?>
                        </div>
                    </div>

                    <div class="history-bottom-row">
                        <span class="<?php echo $badgeClass; ?>">
                            <?php echo $status ? htmlspecialchars($status) : "UNKNOWN"; ?>
                        </span>
                        <span class="fare-pill">
                            RM <?php echo number_format($fare, 2); ?>
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
