<?php
session_start();
include "db_connect.php";
include "function.php";

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}
$driver_id = $_SESSION['driver_id'];

// --- HANDLE ACTIONS (ACCEPT / REJECT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $action     = $_POST['action'] ?? '';

    if ($booking_id > 0 && in_array($action, ['accept', 'reject'], true)) {
        
        if ($action === 'accept') {
            // 1. Prevent self-acceptance (Driver cannot accept their own passenger request)
            $stmt_d = $conn->prepare("SELECT email FROM drivers WHERE driver_id = ?");
            $stmt_d->bind_param("i", $driver_id);
            $stmt_d->execute();
            $res_d = $stmt_d->get_result()->fetch_assoc();
            $driver_email = $res_d['email'] ?? '';
            $stmt_d->close();

            $stmt_p = $conn->prepare("SELECT s.email FROM bookings b JOIN students s ON b.student_id = s.student_id WHERE b.id = ?");
            $stmt_p->bind_param("i", $booking_id);
            $stmt_p->execute();
            $res_p = $stmt_p->get_result()->fetch_assoc();
            $passenger_email = $res_p['email'] ?? '';
            $stmt_p->close();

            if (!empty($driver_email) && $driver_email === $passenger_email) {
                echo "<script>alert('Error: You cannot accept your own ride request!'); window.location.href='driver_booking_requests.php';</script>";
                exit;
            }

            // 2. Assign Driver & Update Status
            $stmt = $conn->prepare("UPDATE bookings SET driver_id = ?, status = 'Accepted' WHERE id = ? AND status = 'Pending'");
            $stmt->bind_param("ii", $driver_id, $booking_id);
            if ($stmt->execute()) {
                // Optional: You could set a success message here
            }
            $stmt->close();

        } elseif ($action === 'reject') {
            // [UPDATED LOGIC] 
            // Do NOT update bookings status to 'Rejected'. 
            // Instead, record that THIS driver doesn't want this booking.
            $stmt = $conn->prepare("INSERT IGNORE INTO declined_bookings (booking_id, driver_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $booking_id, $driver_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Refresh page to reflect changes
    header("Location: driver_booking_requests.php");
    exit;
}

// --- FETCH PENDING REQUESTS ---
// Logic: Select bookings that are Pending AND NOT in the 'declined_bookings' table for this specific driver.
$requests = [];

$sql = "
    SELECT b.*, s.name AS passenger_name 
    FROM bookings b 
    LEFT JOIN students s ON b.student_id = s.student_id 
    WHERE (b.driver_id IS NULL OR b.driver_id = 0) 
    AND b.status = 'Pending' 
    AND b.id NOT IN (
        SELECT booking_id FROM declined_bookings WHERE driver_id = ?
    )
    ORDER BY b.date_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) { 
    $requests[] = $row; 
}
$stmt->close();

include "header.php";
?>

<style>
    .requests-wrapper { padding: 30px 15px; max-width: 600px; margin: 0 auto; background: #f8fafc; }
    .request-card { background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #edf2f7; position: relative; }
    .income-tag { position: absolute; top: 0; right: 0; background: #dcfce7; color: #15803d; padding: 10px 20px; border-bottom-left-radius: 20px; font-weight: 800; font-size: 18px; }
    .route-box { margin: 20px 0; padding-left: 20px; border-left: 2px dashed #cbd5e1; position: relative; }
    .route-dot { position: absolute; left: -6px; width: 10px; height: 10px; border-radius: 50%; }
    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .btn-accept { flex: 1; background: #004b82; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .btn-reject { flex: 1; background: #f1f5f9; color: #64748b; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .btn-accept:hover { background: #003a66; }
    .btn-reject:hover { background: #e2e8f0; color: #334155; }
</style>

<div class="requests-wrapper">
    <h1 style="color: #004b82; margin-bottom: 20px;">Ride Requests</h1>
    <?php if (empty($requests)): ?>
        <p style="text-align:center; color:#64748b; margin-top:40px;">No pending requests available.</p>
    <?php else: ?>
        <?php foreach ($requests as $row): ?>
            <div class="request-card">
                <div class="income-tag">RM <?php echo number_format($row['fare'], 2); ?></div>
                <div style="font-weight: 700; font-size: 18px;"><?php echo htmlspecialchars($row['passenger_name']); ?></div>
                <div style="font-size: 13px; color: #64748b;"><i class="fa-regular fa-clock"></i> <?php echo date("d M, h:i A", strtotime($row['date_time'])); ?></div>
                
                <div class="route-box">
                    <div class="route-dot" style="top:0; background:#004b82;"></div>
                    <div class="route-dot" style="bottom:0; background:#e53e3e;"></div>
                    <div style="margin-bottom: 15px;"><strong>Pickup:</strong> <?php echo htmlspecialchars($row['pickup_point']); ?></div>
                    <div><strong>Drop-off:</strong> <?php echo htmlspecialchars($row['destination']); ?></div>
                </div>

                <div class="btn-group">
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-reject">Decline</button>
                    </form>
                    
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn-accept">Accept Ride</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>