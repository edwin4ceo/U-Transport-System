<?php
session_start();

include "db_connect.php";
include "function.php";

// 1. Security Check: Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// --- [LOGIC] Handle Complete Ride Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_ride_action'])) {
    $booking_id_to_complete = intval($_POST['booking_id']);
    
    // Security: Ensure the ride belongs to this driver and is currently ACCEPTED
    $stmt = $conn->prepare("UPDATE bookings SET status = 'COMPLETED' WHERE id = ? AND driver_id = ? AND status = 'ACCEPTED'");
    $stmt->bind_param("ii", $booking_id_to_complete, $driver_id);
    
    if ($stmt->execute()) {
        // Success: Refresh page to show updated status
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Ride Completed!',
                    text: 'Great job! The ride has been marked as completed.',
                    icon: 'success',
                    confirmButtonColor: '#38a169',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href='driver_rides.php'; 
                });
            });
        </script>";
    } else {
        echo "<script>alert('System Error: Could not complete ride.');</script>";
    }
    $stmt->close();
}
// -------------------------------------------

// 2. Fetch all rides for this driver
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
        s.name  AS passenger_name,
        s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s 
        ON b.student_id = s.student_id
    WHERE b.driver_id = ?
    ORDER BY b.date_time ASC, b.id ASC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rides[] = $row;
        }
    }
    $stmt->close();
}

// 3. Separate into upcoming and past rides
$upcoming = [];
$past     = [];

foreach ($rides as $r) {
    $statusRaw = strtoupper(trim($r['status'] ?? ''));
    // Rides that are "Over" go to past
    if (in_array($statusRaw, ['COMPLETED', 'CANCELLED', 'REJECTED', 'FAILED'])) {
        $past[] = $r;
    } else {
        $upcoming[] = $r;
    }
}

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Base Layout */
.rides-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 800px; /* Made slightly narrower for better focus */
    margin: 0 auto;
    background: #f5f7fb;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.rides-header {
    margin-bottom: 20px;
}
.rides-header h1 {
    margin: 0; font-size: 24px; font-weight: 700; color: #004b82;
}
.rides-header p {
    margin: 5px 0 0; font-size: 14px; color: #666;
}

.rides-section-title {
    font-size: 16px; font-weight: 700; color: #2d3748;
    margin: 25px 0 10px; text-transform: uppercase; letter-spacing: 0.5px;
}

/* Card Container */
.rides-card-container {
    display: flex; flex-direction: column; gap: 15px;
}

/* Individual Ride Item */
.ride-item {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    padding: 20px;
    transition: transform 0.2s;
}

/* Top Row: Date & Status Badge */
.ride-meta-row {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 12px; padding-bottom: 12px;
    border-bottom: 1px dashed #edf2f7;
}
.ride-date {
    font-size: 13px; color: #718096; font-weight: 500;
    display: flex; align-items: center; gap: 6px;
}

/* Route Info */
.ride-route-row {
    margin-bottom: 15px;
}
.route-point {
    display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px;
}
.route-point i { margin-top: 4px; font-size: 14px; width: 16px; text-align: center; }
.route-text { font-size: 15px; font-weight: 600; color: #2d3748; line-height: 1.4; }
.route-label { font-size: 11px; color: #a0aec0; font-weight: 700; text-transform: uppercase; display: block; }

/* Passenger Info */
.passenger-info {
    background: #f7fafc; border-radius: 8px; padding: 10px 12px;
    font-size: 13px; color: #4a5568; margin-bottom: 15px;
}
.passenger-row { display: flex; justify-content: space-between; align-items: center; }

/* Action Buttons Area */
.driver-actions {
    display: flex; gap: 10px; margin-top: 15px; padding-top: 15px;
    border-top: 1px solid #edf2f7;
}

.btn-action {
    flex: 1;
    display: inline-flex; justify-content: center; align-items: center; gap: 8px;
    padding: 10px; border-radius: 8px;
    font-size: 14px; font-weight: 600; cursor: pointer; border: none;
    text-decoration: none; transition: all 0.2s;
}

/* Button Colors */
.btn-chat {
    background: #ebf8ff; color: #3182ce;
}
.btn-chat:hover { background: #bee3f8; }

.btn-complete {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    box-shadow: 0 4px 6px rgba(56, 161, 105, 0.2);
}
.btn-complete:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px rgba(56, 161, 105, 0.3);
}

/* Status Badges */
.badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-pending { background: #fffaf0; color: #dd6b20; border: 1px solid #fbd38d; }
.badge-active { background: #f0fff4; color: #38a169; border: 1px solid #9ae6b4; }
.badge-completed { background: #edf2f7; color: #718096; border: 1px solid #cbd5e0; }
.badge-cancelled { background: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2; }

/* Empty State */
.empty-state {
    text-align: center; padding: 40px 20px; background: white;
    border-radius: 12px; border: 1px dashed #cbd5e0; color: #a0aec0;
}
.empty-state i { font-size: 32px; margin-bottom: 10px; display: block; color: #cbd5e0; }
</style>

<div class="rides-wrapper">
    <div class="rides-header">
        <h1>My Rides</h1>
        <p>Manage your active trips and view history.</p>
    </div>

    <div class="rides-section-title">
        <i class="fa-solid fa-car-side"></i> Active & Upcoming
    </div>

    <div class="rides-card-container">
        <?php if (count($upcoming) === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-road"></i>
                No active rides at the moment.
            </div>
        <?php else: ?>
            <?php foreach ($upcoming as $row): ?>
                <?php
                    // Data Preparation
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M Y, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? ''));
                    $status = $statusRaw ?: 'PENDING';

                    // Badge Logic
                    $badgeClass = "badge-pending";
                    if ($statusRaw === 'ACCEPTED') $badgeClass = "badge-active";
                    
                    // Logic: Can we complete this ride? Only if it's ACCEPTED.
                    $can_complete = ($statusRaw === 'ACCEPTED');
                    $can_chat = ($statusRaw === 'ACCEPTED');
                ?>
                
                <div class="ride-item">
                    <div class="ride-meta-row">
                        <div class="ride-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo $datetime; ?>
                        </div>
                        <span class="badge-status <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>

                    <div class="ride-route-row">
                        <div class="route-point">
                            <i class="fa-solid fa-circle-dot" style="color: #3182ce;"></i>
                            <div>
                                <span class="route-label">Pick Up</span>
                                <div class="route-text"><?php echo htmlspecialchars($row['pickup_point']); ?></div>
                            </div>
                        </div>
                        <div class="route-point">
                            <i class="fa-solid fa-location-dot" style="color: #e53e3e;"></i>
                            <div>
                                <span class="route-label">Drop Off</span>
                                <div class="route-text"><?php echo htmlspecialchars($row['destination']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="passenger-info">
                        <div class="passenger-row">
                            <span><i class="fa-solid fa-user"></i> <strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong></span>
                            <?php if ($row['passenger_phone']): ?>
                                <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($row['passenger_phone']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:5px; font-size:12px; color:#718096;">
                            Passengers: <strong><?php echo $row['passengers']; ?></strong>
                            <?php if (!empty($row['remark'])): ?>
                                <span style="margin-left:8px; font-style:italic;">(Note: <?php echo htmlspecialchars($row['remark']); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($can_complete): ?>
                        <div class="driver-actions">
                            <?php if($can_chat): ?>
                                <a href="ride_chat.php?room=<?php echo $id; ?>" class="btn-action btn-chat">
                                    <i class="fa-regular fa-comments"></i> Chat
                                </a>
                            <?php endif; ?>

                            <form id="form-complete-<?php echo $id; ?>" method="POST" style="flex:1; display:flex;">
                                <input type="hidden" name="booking_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="complete_ride_action" value="1">
                                
                                <button type="button" onclick="confirmComplete(<?php echo $id; ?>)" class="btn-action btn-complete">
                                    <i class="fa-solid fa-check-circle"></i> Complete Ride
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="rides-section-title">
        <i class="fa-solid fa-clock-rotate-left"></i> Past History
    </div>

    <div class="rides-card-container">
        <?php if (count($past) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                No history available.
            </div>
        <?php else: ?>
            <?php foreach ($past as $row): ?>
                <?php
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M Y, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? ''));
                    
                    $badgeClass = "badge-completed";
                    if (in_array($statusRaw, ['CANCELLED', 'REJECTED', 'FAILED'])) {
                        $badgeClass = "badge-cancelled";
                    }
                ?>
                <div class="ride-item" style="opacity: 0.8;">
                    <div class="ride-meta-row">
                        <div class="ride-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo $datetime; ?>
                        </div>
                        <span class="badge-status <?php echo $badgeClass; ?>">
                            <?php echo $statusRaw; ?>
                        </span>
                    </div>
                    <div class="ride-route-row" style="margin-bottom:0;">
                        <div style="font-size:14px; font-weight:600; color:#4a5568;">
                            <?php echo htmlspecialchars($row['pickup_point']); ?> 
                            <i class="fa-solid fa-arrow-right" style="font-size:12px; color:#cbd5e0; margin:0 5px;"></i>
                            <?php echo htmlspecialchars($row['destination']); ?>
                        </div>
                        <div style="font-size:12px; color:#a0aec0; margin-top:4px;">
                            Ride #<?php echo $id; ?> • <?php echo htmlspecialchars($row['passenger_name']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmComplete(bookingId) {
        Swal.fire({
            title: 'Arrived at Destination?',
            text: "Are you sure you want to mark this ride as completed?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#38a169',
            cancelButtonColor: '#718096',
            confirmButtonText: 'Yes, Complete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-complete-' + bookingId).submit();
            }
        })
    }
</script>

<?php
include "footer.php";
?>