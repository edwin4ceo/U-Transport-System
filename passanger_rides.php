<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// --- [LOGIC] Handle Ride Cancellation ---
if(isset($_POST['cancel_ride'])){
    $cancel_id = $_POST['cancel_id'];
    
    // Update status to 'Cancelled'
    // NOTE: Added compatibility to cancel rides that are Pending, Accepted, or Approved
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND (status = 'PENDING' OR status = 'ACCEPTED' OR status = 'APPROVED')");
    $stmt->bind_param("is", $cancel_id, $student_id);
    
    if($stmt->execute()){
        echo "<script>window.location.href='passanger_rides.php';</script>";
    } else {
        echo "<script>alert('Failed to cancel ride.');</script>";
    }
    $stmt->close();
}
// ----------------------------------------

// 2. Fetch Rides (Join with Reviews to check 'Rated' status)
$rides = [];
// NOTE: Selecting booking data, driver info, vehicle info, and review status
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.passengers,
        b.remark,
        b.status,
        b.driver_id, 
        d.full_name AS driver_name,
        v.vehicle_model,
        v.plate_number,
        r.review_id AS has_rated  
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    LEFT JOIN reviews r ON b.id = r.booking_id 
    WHERE b.student_id = ?
    ORDER BY b.date_time ASC
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rides[] = $row;
    $stmt->close();
}

$upcoming = [];
$past = [];

foreach ($rides as $r) {
    $st = strtoupper(trim($r['status'] ?? ''));
    // NOTE: 'APPROVED' is treated as an active ride (Upcoming), same as 'ACCEPTED'
    if (in_array($st, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
        $past[] = $r;
    } else {
        $upcoming[] = $r;
    }
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* --- STYLES (Kept exactly as provided) --- */

.rides-wrapper { 
    min-height: calc(100vh - 160px); 
    padding: 30px 20px; 
    max-width: 880px;   
    margin: 0 auto; 
    background: #f8f9fa; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.rides-header-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #1a202c; }
.rides-header-title p { margin: 6px 0 0; font-size: 14px; color: #718096; }

.section-title { 
    font-size: 15px; font-weight: 700; color: #4a5568; 
    margin: 28px 0 14px; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.section-title i { color: #004b82; font-size: 14px; }

.ride-card-container { display: flex; flex-direction: column; gap: 16px; }

/* Card Style */
.ride-item-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 20px;       
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease;
    position: relative;
}
.ride-item-card:hover { 
    box-shadow: 0 6px 12px rgba(0,0,0,0.06); 
    border-color: #cbd5e0;
    transform: translateY(-2px);
}

/* Address Grid */
.card-top-grid {
    display: grid;
    grid-template-columns: 1fr 24px 1fr; 
    gap: 10px;
    align-items: stretch; 
    margin-bottom: 16px;
}

/* Address Box */
.address-box {
    background-color: #eff3f6; 
    border: 1px solid #dce2e8; 
    border-radius: 9px;
    padding: 10px 14px; 
    font-size: 14px;    
    font-weight: 600;
    color: #2d3748;
    line-height: 1.4;
    
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center; 
    text-align: center;  
}

.box-label {
    font-size: 10.5px; 
    color: #718096; 
    font-weight: 700; 
    text-transform: uppercase; 
    margin-bottom: 4px;
}

.address-text {
    display: flex; 
    align-items: center; 
    justify-content: center; 
    gap: 6px;
    width: 100%;
}
.address-text i { font-size: 13px; flex-shrink: 0; } 

.route-arrow {
    color: #cbd5e0;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Date Row */
.date-row {
    display: flex; 
    justify-content: flex-end; 
    margin-bottom: 10px;
}
.date-badge {
    font-size: 12.5px; 
    font-weight: 600; 
    color: #004b82;            
    background-color: #e6f0ff; 
    border: 1px solid #cce0ff; 
    display: flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
}

/* Middle Row */
.card-middle {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 14px; border-bottom: 1px solid #edf2f7; margin-bottom: 14px;
}

/* Driver Info Left Aligned */
.driver-info {
    display: flex; 
    align-items: center; 
    gap: 12px; 
    font-size: 14px; 
    color: #4a5568;
    text-align: left; 
}

.driver-text-block {
    display: flex; 
    flex-direction: column;
    align-items: flex-start; 
    justify-content: center;
}

.driver-avatar {
    width: 40px; height: 40px; 
    background: #edf2f7; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; 
    color: #718096; 
    font-size: 18px;
    flex-shrink: 0; 
}

/* Buttons */
.action-buttons { display: flex; gap: 9px; align-items: center; }

.btn-common {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 7px 16px; 
    border-radius: 50px; font-size: 13px; font-weight: 600;
    cursor: pointer; text-decoration: none; transition: all 0.2s ease; line-height: 1; border: none;
}

.btn-chat { 
    background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%); color: white; 
    box-shadow: 0 2px 4px rgba(49, 130, 206, 0.2);
}
.btn-chat:hover { box-shadow: 0 4px 8px rgba(49, 130, 206, 0.3); transform: translateY(-1px); }

.btn-cancel {
    background-color: #fff5f5; color: #c53030; border: 1px solid #feb2b2;
}
.btn-cancel:hover { background-color: #fee2e2; }

/* Rate Button (Gold) */
.btn-rate {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
    color: white; 
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}
.btn-rate:hover { 
    box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3); 
    transform: translateY(-1px); 
}

/* Rated Button (Gray/Subtle) */
.btn-rated {
    background: #edf2f7;
    color: #718096;
    border: 1px solid #cbd5e0;
    cursor: default;
}
.btn-rated:hover {
    background: #e2e8f0;
    color: #4a5568;
}

/* Bottom Row */
.card-bottom { display: flex; justify-content: space-between; align-items: center; }

.status-pill {
    padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.st-pending { background: #fffaf0; color: #dd6b20; border: 1px solid #fbd38d; }
.st-accepted { background: #f0fff4; color: #38a169; border: 1px solid #9ae6b4; }
.st-completed { background: #ebf8ff; color: #3182ce; border: 1px solid #90cdf4; }
.st-cancelled { background: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2; }
.st-rejected { background: #edf2f7; color: #718096; border: 1px solid #cbd5e0; }

.empty-state {
    text-align: center; padding: 35px; background: white;
    border-radius: 14px; border: 1px dashed #cbd5e0; color: #a0aec0; font-size: 14px;
}
.empty-state i { font-size: 26px; margin-bottom: 10px; display: block; }

.remark-text {
    font-size: 12.5px; color: #a0aec0; font-style: italic; 
    margin-right: auto; margin-left: 15px;
    max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
</style>

<div class="rides-wrapper">
    <div class="rides-header-title">
        <h1>My Rides</h1>
        <p>Manage your upcoming trips and view history.</p>
    </div>

    <div class="section-title">
        <i class="fa-solid fa-calendar-days"></i> Upcoming Rides
    </div>
    
    <div class="ride-card-container">
        <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-road"></i>
                No upcoming rides found.
            </div>
        <?php else: ?>
            <?php foreach ($upcoming as $row): renderRideCard($row); endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-title">
        <i class="fa-solid fa-clock-rotate-left"></i> Past History
    </div>

    <div class="ride-card-container">
        <?php if (empty($past)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                No ride history available.
            </div>
        <?php else: ?>
            <?php foreach ($past as $row): renderRideCard($row); endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmCancel(bookingId) {
        Swal.fire({
            title: 'Cancel Ride?',
            text: "Are you sure you want to cancel this booking? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e53e3e',
            cancelButtonColor: '#718096',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it',
            reverseButtons: true 
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-cancel-' + bookingId).submit();
            }
        })
    }

    // UPDATED FUNCTION: Accepts bookingId and shows an Edit button
    function showRatedAlert(bookingId) {
        Swal.fire({
            title: 'Review Submitted',
            text: 'You have already rated this driver. Would you like to edit your review?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3182ce', // Blue color for the Edit button
            cancelButtonColor: '#718096',  // Grey color for the Close button
            confirmButtonText: '<i class="fa-solid fa-pen-to-square"></i> Edit Review',
            cancelButtonText: 'Close',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to rating page with the booking ID so it can load the existing review
                window.location.href = 'passanger_rate.php?booking_id=' + bookingId;
            }
        });
    }
</script>

<?php
function renderRideCard($row) {
    $date = date("d M Y, h:i A", strtotime($row['date_time']));
    $status = strtoupper($row['status']);
    
    // Status Styles Mapping
    // NOTE: Treating 'APPROVED' exactly like 'ACCEPTED' for display purposes
    $st_class = "st-rejected"; 
    if ($status == 'PENDING') $st_class = "st-pending";
    if ($status == 'ACCEPTED' || $status == 'APPROVED') $st_class = "st-accepted";
    if ($status == 'COMPLETED') $st_class = "st-completed";
    if ($status == 'CANCELLED') $st_class = "st-cancelled";

    $driver = $row['driver_name'] ?: "Waiting for Driver";
    
    // --- [LOGIC] Define action permissions ---
    // NOTE: User can cancel if status is PENDING, ACCEPTED, or APPROVED
    $can_cancel = in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED']);
    
    // NOTE: Chat is enabled if the driver has accepted (ACCEPTED or APPROVED)
    $can_chat = in_array($status, ['ACCEPTED', 'APPROVED']);
    
    $is_completed = ($status == 'COMPLETED');
    $has_rated = !empty($row['has_rated']); 
    // -----------------------------------------

    ?>
    
    <div class="ride-item-card">
        
        <div class="date-row">
            <div class="date-badge">
                <i class="fa-regular fa-calendar"></i> <?php echo $date; ?>
            </div>
        </div>

        <div class="card-top-grid">
            <div class="address-box">
                <div class="box-label">From</div>
                <div class="address-text">
                    <i class="fa-solid fa-location-dot" style="color:#e53e3e;"></i>
                    <?php echo htmlspecialchars($row['pickup_point']); ?>
                </div>
            </div>
            <div class="route-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>
            <div class="address-box">
                <div class="box-label">To</div>
                <div class="address-text">
                    <i class="fa-solid fa-flag-checkered" style="color:#2b6cb0;"></i>
                    <?php echo htmlspecialchars($row['destination']); ?>
                </div>
            </div>
        </div>

        <div class="card-middle">
            <div class="driver-info">
                <div class="driver-avatar">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="driver-text-block">
                    <span style="font-size:10px; color:#a0aec0; font-weight:700; text-transform:uppercase; line-height:1.2; letter-spacing:0.5px;">Driver</span>
                    <span style="font-weight:600; font-size:14px; color:#2d3748;"><?php echo htmlspecialchars($driver); ?></span>
                    <span style="font-size:11px; color:#718096; margin-top:2px;"><?php echo $row['passengers']; ?> Passenger(s)</span>
                </div>
            </div>

            <div class="action-buttons">
                <?php if($can_chat): ?>
                    <a href="ride_chat.php?room=<?php echo $row['booking_id']; ?>" class="btn-common btn-chat">
                        <i class="fa-regular fa-comments"></i> Chat
                    </a>
                <?php endif; ?>

                <?php if($can_cancel): ?>
                    <form id="form-cancel-<?php echo $row['booking_id']; ?>" method="POST" style="margin:0;">
                        <input type="hidden" name="cancel_id" value="<?php echo $row['booking_id']; ?>">
                        <input type="hidden" name="cancel_ride" value="1">
                        
                        <button type="button" onclick="confirmCancel(<?php echo $row['booking_id']; ?>)" class="btn-common btn-cancel">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                    </form>
                <?php endif; ?>

                <?php if($is_completed): ?>
                    <?php if(!$has_rated): ?>
                        <a href="passanger_rate.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-common btn-rate">
                            <i class="fa-solid fa-star"></i> Rate
                        </a>
                    <?php else: ?>
                        <button onclick="showRatedAlert(<?php echo $row['booking_id']; ?>)" class="btn-common btn-rated">
                            <i class="fa-solid fa-check"></i> Rated
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>

        <div class="card-bottom">
            <span class="status-pill <?php echo $st_class; ?>">
                <?php 
                    // Display 'ACCEPTED' even if DB says 'APPROVED'
                    echo ($status == 'APPROVED') ? 'ACCEPTED' : $status; 
                ?>
            </span>
            
            <?php if(!empty($row['remark'])): ?>
                <span class="remark-text">"<?php echo htmlspecialchars($row['remark']); ?>"</span>
            <?php endif; ?>

            <span style="font-size:11px; color:#cbd5e0;">#<?php echo $row['booking_id']; ?></span>
        </div>

    </div>
    <?php
}
include "footer.php"; 
?>