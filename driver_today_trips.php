<?php
session_start();
include "db_connect.php";
include "function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}
$driver_id = $_SESSION['driver_id'];

$trips = [];
$stmt = $conn->prepare("SELECT b.*, s.name AS passenger_name, s.phone AS passenger_phone FROM bookings b LEFT JOIN students s ON b.student_id = s.student_id WHERE b.driver_id = ? AND DATE(b.date_time) = CURDATE() ORDER BY b.date_time ASC");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $trips[] = $row; }

include "header.php";
?>

<style>
    .today-wrapper { padding: 30px 15px; max-width: 600px; margin: 0 auto; background: #f8fafc; }
    .trip-card { background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 15px; border-left: 8px solid #004b82; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .payment-notice { margin-top: 15px; background: #f0fdf4; border: 1px dashed #16a34a; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
    .status-badge { float: right; font-size: 11px; padding: 4px 10px; border-radius: 50px; font-weight: 700; background: #ebf8ff; color: #004b82; }
</style>

<div class="today-wrapper">
    <h1 style="color: #004b82; margin-bottom: 20px;">Today's Trips</h1>
    <?php if (empty($trips)): ?>
        <p style="text-align:center; color:#64748b;">No trips for today.</p>
    <?php else: ?>
        <?php foreach ($trips as $row): ?>
            <div class="trip-card">
                <span class="status-badge"><?php echo htmlspecialchars($row['status']); ?></span>
                <div style="font-weight: 800; font-size: 18px;"><i class="fa-regular fa-clock"></i> <?php echo date("h:i A", strtotime($row['date_time'])); ?></div>
                <div style="margin-top: 10px; font-size: 14px;">
                    <p><strong>Passenger:</strong> <?php echo htmlspecialchars($row['passenger_name']); ?> (<?php echo htmlspecialchars($row['passenger_phone']); ?>)</p>
                    <p><strong>Route:</strong> <?php echo htmlspecialchars($row['pickup_point']); ?> → <?php echo htmlspecialchars($row['destination']); ?></p>
                </div>
                <div class="payment-notice">
                    <span style="font-weight: 600; color: #166534;">Collect Fare:</span>
                    <span style="font-size: 22px; font-weight: 800; color: #15803d;">RM <?php echo number_format($row['fare'], 2); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>