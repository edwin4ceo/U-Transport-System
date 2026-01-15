<?php
session_start();

include "db_connect.php";
include "function.php";

// 1. Security Check
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// --- Handle Complete Ride Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_ride_action'])) {
    $booking_id_to_complete = intval($_POST['booking_id']);
    
    // Update status to COMPLETED only if currently ACCEPTED
    $stmt = $conn->prepare("UPDATE bookings SET status = 'COMPLETED' WHERE id = ? AND driver_id = ? AND status = 'ACCEPTED'");
    $stmt->bind_param("ii", $booking_id_to_complete, $driver_id);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Ride Completed!',
                    text: 'Great job! The ride has been marked as completed.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => { window.location.href='driver_rides.php'; });
            });
        </script>";
    }
    $stmt->close();
}

// 2. Fetch all rides for this driver
$rides = [];
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.pickup_point, b.destination, b.date_time, b.passengers, b.remark, b.status,
           s.name AS passenger_name, s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    WHERE b.driver_id = ?
    ORDER BY b.date_time ASC, b.id ASC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) { while ($row = $result->fetch_assoc()) { $rides[] = $row; } }
    $stmt->close();
}

// 3. Separate Rides into Upcoming/Active and Past History
$upcoming = [];
$past     = [];

foreach ($rides as $r) {
    $statusRaw = strtoupper(trim($r['status'] ?? ''));
    if (in_array($statusRaw, ['COMPLETED', 'CANCELLED', 'REJECTED', 'FAILED'])) {
        $past[] = $r;
    } else {
        $upcoming[] = $r;
    }
}

include "header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- 1. Global Styles --- */
    body { background-color: #f4f7fe; font-family: 'Poppins', sans-serif; color: #2b3674; }
    
    .rides-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px 20px 80px;
    }

    /* --- 2. Header & Search Bar --- */
    .page-header { margin-bottom: 25px; text-align: center; }
    .page-header h1 { font-size: 26px; font-weight: 700; color: #004b82; margin: 0; }
    .page-header p { color: #a3aed0; font-size: 14px; margin-top: 5px; }

    .search-box-wrapper {
        position: relative;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(112, 144, 176, 0.08);
        border-radius: 30px;
        background: white;
    }
    
    .search-input {
        width: 100%;
        /* Adjusted padding: removed the left space for the icon */
        padding: 16px 25px; 
        border: none;
        border-radius: 30px;
        font-size: 15px;
        color: #2b3674;
        background: transparent;
        outline: none;
        transition: all 0.2s;
        text-align: center; /* Optional: Center the placeholder text since no icon */
    }
    .search-input:focus { box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1); }

    /* --- 3. Core Card Styles --- */
    .ride-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid transparent;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .ride-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-color: #eef2f6;
    }

    /* Status Strip on the Left */
    .status-strip { position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
    
    /* Color Definitions */
    .strip-active { background: #4318ff; }    /* Blue - Active */
    .strip-pending { background: #ffce20; }   /* Yellow - Pending */
    .strip-completed { background: #05cd99; } /* Green - Completed */
    .strip-cancelled { background: #ee5d50; } /* Red - Cancelled */

    /* Card Internal Layout */
    .card-top { display: flex; justify-content: space-between; margin-bottom: 12px; }
    .trip-date { font-size: 13px; color: #a3aed0; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    
    .route-display { margin-bottom: 15px; padding-left: 10px; border-left: 2px solid #eef2f6; }
    .route-text { font-size: 15px; font-weight: 600; line-height: 1.4; color: #1b2559; }

    .card-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 12px; border-top: 1px dashed #eef2f6;
    }
    .passenger-info { font-size: 13px; font-weight: 500; color: #707eae; display: flex; align-items: center; gap: 6px; }

    /* Badges */
    .status-badge { padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-active { background: #f4f7fe; color: #4318ff; }
    .badge-pending { background: #fffbf0; color: #ffce20; }
    .badge-completed { background: #e6fdf6; color: #05cd99; }
    .badge-cancelled { background: #fff5f5; color: #ee5d50; }

    /* --- 4. Action Buttons Area --- */
    .action-buttons {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eef2f6;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .btn-action {
        border: none; padding: 10px; border-radius: 10px; cursor: pointer;
        font-size: 13px; font-weight: 600; text-align: center; text-decoration: none;
        display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s;
    }
    .btn-chat { background: #f4f7fe; color: #4318ff; }
    .btn-chat:hover { background: #e0e5f2; }
    
    .btn-complete { background: linear-gradient(90deg, #4318ff 0%, #2b3674 100%); color: white; }
    .btn-complete:hover { box-shadow: 0 4px 10px rgba(67, 24, 255, 0.3); transform: translateY(-1px); }

    /* Section Labels */
    .section-label {
        font-size: 12px; font-weight: 700; color: #a3aed0; 
        text-transform: uppercase; letter-spacing: 1px;
        margin: 30px 0 15px; display: block;
    }
    .empty-state { text-align: center; padding: 40px; color: #a3aed0; font-size: 14px; }
</style>

<div class="rides-wrapper">
    <div class="page-header">
        <h1>My Rides</h1>
        <p>Manage current trips & history</p>
    </div>

    <div class="search-box-wrapper">
        <input type="text" id="ridesSearchInput" class="search-input" placeholder="Search passenger, location or ID...">
    </div>

    <span class="section-label"><i class="fa-solid fa-car-side"></i> Active & Upcoming</span>
    
    <div id="activeList">
        <?php if (count($upcoming) === 0): ?>
            <div class="empty-state">No active rides at the moment.</div>
        <?php else: ?>
            <?php foreach ($upcoming as $row): ?>
                <?php
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? 'PENDING'));
                    
                    if ($statusRaw === 'ACCEPTED') {
                        $stripClass = "strip-active"; $badgeClass = "badge-active";
                    } else {
                        $stripClass = "strip-pending"; $badgeClass = "badge-pending";
                    }
                    
                    $routeText = htmlspecialchars($row['pickup_point']) . ' <i class="fa-solid fa-arrow-right-long" style="color:#a3aed0; font-size:12px; margin:0 5px;"></i> ' . htmlspecialchars($row['destination']);
                ?>
                <div class="ride-card item-ride">
                    <div class="status-strip <?php echo $stripClass; ?>"></div>
                    
                    <div class="card-top">
                        <div class="trip-date"><i class="fa-regular fa-calendar-alt"></i> <?php echo $datetime; ?></div>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusRaw; ?></span>
                    </div>

                    <div class="route-display">
                        <div class="route-text"><?php echo $routeText; ?></div>
                    </div>

                    <div class="card-footer">
                        <div class="passenger-info">
                            <i class="fa-solid fa-user-circle"></i>
                            <?php echo htmlspecialchars($row['passenger_name']); ?>
                            <span style="font-size:11px; color:#a3aed0; margin-left:5px;">(<?php echo $row['passengers']; ?> Pax)</span>
                        </div>
                        <div style="font-size:11px; color:#a3aed0; font-weight:600;">#<?php echo $id; ?></div>
                    </div>

                    <?php if ($statusRaw === 'ACCEPTED'): ?>
                        <div class="action-buttons">
                            <a href="ride_chat.php?room=<?php echo $id; ?>" class="btn-action btn-chat">
                                <i class="fa-regular fa-comments"></i> Chat
                            </a>
                            <form id="form-complete-<?php echo $id; ?>" method="POST" style="margin:0;">
                                <input type="hidden" name="booking_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="complete_ride_action" value="1">
                                <button type="button" onclick="confirmComplete(<?php echo $id; ?>)" class="btn-action btn-complete">
                                    Complete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <span class="section-label"><i class="fa-solid fa-clock-rotate-left"></i> Recent History</span>
    
    <div id="pastList">
        <?php if (count($past) === 0): ?>
            <div class="empty-state">No history found.</div>
        <?php else: ?>
            <?php foreach ($past as $row): ?>
                <?php
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status']));

                    if (in_array($statusRaw, ['CANCELLED', 'REJECTED'])) {
                        $stripClass = "strip-cancelled"; $badgeClass = "badge-cancelled";
                    } else {
                        $stripClass = "strip-completed"; $badgeClass = "badge-completed";
                    }
                    
                    $routeText = htmlspecialchars($row['pickup_point']) . ' <i class="fa-solid fa-arrow-right-long" style="color:#a3aed0; font-size:12px; margin:0 5px;"></i> ' . htmlspecialchars($row['destination']);
                ?>
                <div class="ride-card item-ride" style="opacity: 0.85;">
                    <div class="status-strip <?php echo $stripClass; ?>"></div>
                    
                    <div class="card-top">
                        <div class="trip-date"><i class="fa-regular fa-calendar-check"></i> <?php echo $datetime; ?></div>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusRaw; ?></span>
                    </div>

                    <div class="route-display">
                        <div class="route-text" style="font-size:14px;"><?php echo $routeText; ?></div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="passenger-info">
                             <?php echo htmlspecialchars($row['passenger_name']); ?>
                        </div>
                        <div style="font-size:11px; color:#a3aed0;">#<?php echo $id; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Search Logic (Client-side)
document.getElementById('ridesSearchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.item-ride').forEach(card => {
        let text = card.innerText.toLowerCase();
        card.style.display = text.includes(filter) ? "" : "none";
    });
});

// Confirm Completion Logic
function confirmComplete(bookingId) {
    Swal.fire({
        title: 'Complete Ride?',
        text: "Confirm that you have arrived at the destination.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4318ff',
        cancelButtonColor: '#a3aed0',
        confirmButtonText: 'Yes, Complete',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-complete-' + bookingId).submit();
        }
    });
}
</script>

<?php include "footer.php"; ?>