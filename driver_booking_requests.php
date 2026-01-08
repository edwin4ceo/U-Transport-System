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

/* -----------------------------------------
   1. Handle Accept / Reject Actions
----------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $action     = $_POST['action'] ?? '';

    if ($booking_id > 0 && in_array($action, ['accept', 'reject'], true)) {

        if ($action === 'accept') {
            
            // =================================================================
            // [NEW LOGIC START] Prevent Driver from accepting their own request
            // =================================================================
            
            // 1. Get current Driver's Email
            $stmt_d = $conn->prepare("SELECT email FROM drivers WHERE driver_id = ?");
            $stmt_d->bind_param("i", $driver_id);
            $stmt_d->execute();
            $res_d = $stmt_d->get_result();
            $driver_email = ($res_d->num_rows > 0) ? $res_d->fetch_assoc()['email'] : '';
            $stmt_d->close();

            // 2. Get the Passenger's Email for this specific booking
            // We join bookings table with students table
            $stmt_p = $conn->prepare("
                SELECT s.email 
                FROM bookings b 
                JOIN students s ON b.student_id = s.student_id 
                WHERE b.id = ?
            ");
            $stmt_p->bind_param("i", $booking_id);
            $stmt_p->execute();
            $res_p = $stmt_p->get_result();
            $passenger_email = ($res_p->num_rows > 0) ? $res_p->fetch_assoc()['email'] : '';
            $stmt_p->close();

            // 3. Compare Emails
            // If both exist and are identical, block the action.
            if (!empty($driver_email) && !empty($passenger_email) && $driver_email === $passenger_email) {
                echo "<script>
                        alert('Error: You cannot accept your own ride request!');
                        window.location.href='driver_booking_requests.php';
                      </script>";
                exit; // Stop executing the rest of the code
            }
            // =================================================================
            // [NEW LOGIC END]
            // =================================================================


            // --- [EXISTING LOGIC] Auto-send System Message & Assign Driver ---
            
            // 1. Get the ride date_time BEFORE assigning, to generate Chat Key
            $get_date = $conn->prepare("SELECT date_time FROM bookings WHERE id = ?");
            $get_date->bind_param("i", $booking_id);
            $get_date->execute();
            $res_date = $get_date->get_result();
            $row_date = $res_date->fetch_assoc();
            $ride_datetime = $row_date['date_time'] ?? '';
            $get_date->close();

            // 2. Assign Driver
            $stmt = $conn->prepare("
                UPDATE bookings
                SET driver_id = ?, status = 'Accepted'
                WHERE id = ?
                AND (driver_id IS NULL OR driver_id = 0)
                AND (status = 'Pending' OR status = '' OR status IS NULL)
            ");
            
            if ($stmt) {
                $stmt->bind_param("ii", $driver_id, $booking_id);
                $stmt->execute();
                
                // 3. If assignment successful, insert System Message
                if ($stmt->affected_rows > 0 && !empty($ride_datetime)) {
                    $chat_ref = $driver_id . '_' . $ride_datetime;
                    $sys_msg  = "Please be ready 10-15 minutes before the departure time to avoid unnecessary delays.";
                    
                    // Check duplicate to prevent spamming if re-accepted
                    $chk = $conn->prepare("SELECT id FROM ride_chat_messages WHERE booking_ref = ? AND sender_type = 'system'");
                    $chk->bind_param("s", $chat_ref);
                    $chk->execute();
                    
                    if ($chk->get_result()->num_rows == 0) {
                        $ins = $conn->prepare("INSERT INTO ride_chat_messages (booking_ref, sender_type, sender_id, sender_name, message) VALUES (?, 'system', '0', 'System', ?)");
                        $ins->bind_param("ss", $chat_ref, $sys_msg);
                        $ins->execute();
                        $ins->close();
                    }
                    $chk->close();
                }
                
                $stmt->close();
            }
            // --- [EXISTING LOGIC END] ---

        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("
                UPDATE bookings
                SET status = 'Rejected'
                WHERE id = ?
                AND (status = 'Pending' OR status = '' OR status IS NULL)
            ");
            if ($stmt) {
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: driver_booking_requests.php");
    exit;
}

/* -----------------------------------------
   2. Fetch All Pending Requests
----------------------------------------- */

$requests = [];

$result = $conn->query("
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
    WHERE (b.driver_id IS NULL OR b.driver_id = 0)
      AND (b.status = 'Pending' OR b.status = '' OR b.status IS NULL)
    ORDER BY b.date_time ASC, b.id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

include "header.php";
?>

<style>
.requests-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}

.requests-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.requests-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.requests-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

.request-item {
    border-bottom: 1px dashed #e0e0e0;
    padding: 10px 0;
}

.request-item:last-child {
    border-bottom: none;
}

.request-route {
    font-size: 14px;
    font-weight: 600;
    color: #004b82;
}

.request-date {
    font-size: 11px;
    color: #888;
    white-space: nowrap;
}

.request-middle-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #555;
    gap: 10px;
    flex-wrap: wrap;
}

.badge-status {
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    background: #fff8e6;
    color: #d35400;
    border: 1px solid #f8d49a;
}

.request-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn-pill {
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn-accept {
    background: #27ae60;
    color: #ffffff;
}

.btn-reject {
    background: #e74c3c;
    color: #ffffff;
}

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

<div class="requests-wrapper">
    <div class="requests-header">
        <div class="requests-header-title">
            <h1>Booking Requests</h1>
            <p>View and manage new booking requests from passengers.</p>
        </div>
    </div>

    <div class="requests-card">
        <?php if (count($requests) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-inbox"></i>
                <div>No pending booking requests at the moment.</div>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $row): ?>
                <?php
                    $pickup      = $row['pickup_point'];
                    $destination = $row['destination'];
                    $route       = "$pickup → $destination";

                    $datetime = date("d M Y, h:i A", strtotime($row['date_time']));
                    $passengers = (int)$row['passengers'];
                    $remark     = $row['remark'];
                    $passenger_name  = $row['passenger_name'] ?? "Passenger";
                    $passenger_phone = $row['passenger_phone'] ?? "";
                ?>
                <div class="request-item">

                    <div class="request-top-row">
                        <div class="request-route"><?php echo htmlspecialchars($route); ?></div>
                        <div class="request-date"><?php echo htmlspecialchars($datetime); ?></div>
                    </div>

                    <div class="request-middle-row">
                        <div>
                            Passenger:
                            <strong><?php echo htmlspecialchars($passenger_name); ?></strong>
                            <?php if ($passenger_phone): ?>
                                <span style="font-size:11px; color:#777;">(<?php echo htmlspecialchars($passenger_phone); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div>Passengers: <strong><?php echo $passengers; ?></strong></div>
                    </div>

                    <?php if ($remark): ?>
                        <div class="request-middle-row">
                            Remark: <span><?php echo htmlspecialchars($remark); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="request-bottom-row" style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="badge-status">Pending</span>

                        <div class="request-actions">
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn-pill btn-accept">Accept</button>
                            </form>

                            <form method="post" style="margin:0;">
                                <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-pill btn-reject">Reject</button>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include "footer.php";
?>