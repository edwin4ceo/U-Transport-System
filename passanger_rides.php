<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// --- [LOGIC] Handle Ride Cancellation ---
if(isset($_POST['cancel_ride'])){
    $cancel_id = $_POST['cancel_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND (status = 'PENDING' OR status = 'ACCEPTED' OR status = 'APPROVED')");
    $stmt->bind_param("is", $cancel_id, $student_id);
    if($stmt->execute()){
        // Success
    } else {
        echo "<script>alert('Failed to cancel ride.');</script>";
    }
    $stmt->close();
    header("Location: passanger_rides.php");
    exit();
}

// 2. Fetch Rides
$ongoing_rides = [];
$history_rides = [];
$current_timestamp = time();

$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,       
        b.status,
        b.remark,
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        v.vehicle_model,
        v.plate_number,
        r.rating,
        r.comment
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.student_id = ?
    ORDER BY b.date_time DESC
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $status = strtoupper($row['status']);
    $ride_time = strtotime($row['date_time']);
    
    if(!empty($row['driver_name']) && $status == 'PENDING'){
        $status = 'ACCEPTED';
        $row['status'] = 'ACCEPTED'; 
    }

    $is_expired = ($ride_time < $current_timestamp);

    if(in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED']) && !$is_expired) {
        $ongoing_rides[] = $row;
    } else {
        if($is_expired && in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED'])) {
            $row['status'] = 'EXPIRED';
        }
        $history_rides[] = $row;
    }
}
$stmt->close();
?>

<?php include "header.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showRatedAlert(rating, comment) {
        let stars = '';
        for(let i=0; i<5; i++) {
            stars += (i < rating) ? '<i class="fa-solid fa-star" style="color:#fbc02d; margin:0 2px;"></i>' : '<i class="fa-regular fa-star" style="color:#ddd; margin:0 2px;"></i>';
        }
        let commentText = comment ? comment : "<i>No comment provided.</i>";
        Swal.fire({
            title: 'Your Rating',
            html: `<div style="font-size: 24px; margin: 15px 0;">${stars}</div><div style="background:#f8fafc; padding:15px; border-radius:10px; color:#555; font-style:italic;">"${commentText}"</div>`,
            confirmButtonColor: '#005A9C',
            confirmButtonText: 'Close'
        });
    }

    function confirmCancel(event, form) {
        event.preventDefault();
        Swal.fire({
            title: 'Cancel Ride?',
            text: "Are you sure you want to cancel this booking?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004b82', // Changed to match button color
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Cancel it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }

    function switchTab(tabName) {
        document.getElementById('content-ongoing').style.display = 'none';
        document.getElementById('content-history').style.display = 'none';
        document.getElementById('btn-ongoing').classList.remove('active');
        document.getElementById('btn-history').classList.remove('active');

        document.getElementById('content-' + tabName).style.display = 'block';
        document.getElementById('btn-' + tabName).classList.add('active');
        
        document.getElementById('searchInput').value = '';
        filterRides();
    }

    function filterRides() {
        let input = document.getElementById('searchInput').value.toUpperCase();
        let activeId = document.getElementById('content-ongoing').style.display !== 'none' ? 'content-ongoing' : 'content-history';
        let container = document.getElementById(activeId);
        let cards = container.getElementsByClassName('ride-card');
        let visibleCount = 0;

        for (let i = 0; i < cards.length; i++) {
            let txt = cards[i].innerText;
            if (txt.toUpperCase().indexOf(input) > -1) {
                cards[i].style.display = "";
                visibleCount++;
            } else {
                cards[i].style.display = "none";
            }
        }

        let noMsg = container.querySelector('.no-match-msg');
        if(noMsg) {
            if(visibleCount === 0 && cards.length > 0) {
                noMsg.style.display = 'block';
            } else {
                noMsg.style.display = 'none';
            }
        }
    }
</script>

<style>
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    .rides-wrapper {
        max-width: 900px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }
    .rides-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; text-align: center; }
    .rides-header-title p { margin: 8px 0 25px; font-size: 15px; color: #64748b; text-align: center; }

    /* SEARCH BAR (Fixed Icon Centering) */
    .search-container { position: relative; max-width: 500px; margin: 0 auto 25px; height: 50px; }
    .search-input {
        width: 100%; height: 100%; padding: 0 20px 0 55px !important; border-radius: 50px;
        border: 1px solid #e2e8f0; font-size: 15px; outline: none; transition: 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); box-sizing: border-box;
    }
    .search-input:focus { border-color: #004b82; box-shadow: 0 4px 15px rgba(0,75,130,0.15); }
    .search-icon {
        position: absolute; left: 20px !important; top: 0; bottom: 0; height: 100%;
        display: flex; align-items: center; color: #94a3b8; font-size: 18px;
        pointer-events: none; z-index: 10;
    }

    /* TABS */
    .tabs-wrapper { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
    .tab-btn {
        padding: 10px 30px; border-radius: 30px; border: none; background: #e2e8f0; color: #64748b;
        font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 14px;
    }
    .tab-btn.active { background: #004b82; color: #fff; box-shadow: 0 4px 10px rgba(0,75,130,0.2); }
    .tab-btn:hover:not(.active) { background: #cbd5e1; }

    /* CARD STYLES */
    .ride-card { background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9; overflow: hidden; padding: 25px; position: relative; }
    .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f8fafc; }
    .ride-date { font-weight: 700; color: #1e293b; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .ride-id { font-family: monospace; font-size: 14px; color: #64748b; letter-spacing: 0.5px; font-weight: 600; }
    .card-content-row { display: flex; justify-content: space-between; gap: 30px; }
    .timeline-container { flex: 1.5; position: relative; padding-left: 5px; padding-top: 5px; }
    .timeline-line { position: absolute; left: 11px; top: 10px; bottom: 30px; width: 2px; background: #e2e8f0; }
    .t-item { display: flex; gap: 20px; margin-bottom: 25px; position: relative; }
    .t-item:last-child { margin-bottom: 0; }
    .t-dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; flex-shrink: 0; position: relative; z-index: 1; margin-top: 2px; }
    .t-dot.pickup { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .t-dot.dropoff { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }
    .t-text { text-align: left; }
    .t-text h4 { margin: 0 0 4px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .t-text p { margin: 0; font-size: 15px; color: #334155; font-weight: 500; line-height: 1.4; }
    .info-right-col { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; min-width: 260px; }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; align-self: flex-end; }
    .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; }
    .st-pending { background: #fff7ed; color: #f97316; }
    .st-accepted { background: #ecfdf5; color: #10b981; }
    .st-completed { background: #eff6ff; color: #3b82f6; }
    .st-cancelled { background: #fef2f2; color: #ef4444; }
    .st-expired { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .driver-box-design { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 15px 20px; display: flex; align-items: center; gap: 15px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.03); justify-content: flex-start; }
    .driver-img-wrap { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #e2e8f0; padding: 2px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .driver-avatar-circle { width: 100%; height: 100%; border-radius: 50%; background: #004b82; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .driver-info-block { display: flex; flex-direction: column; align-items: flex-start; overflow: hidden; }
    .d-name { font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
    .d-car-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; }
    .plate-badge-blue { background-color: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 6px; font-weight: 700; font-size: 12px; }
    .no-driver-box { background: #f8fafc; border: 1px dashed #cbd5e0; border-radius: 12px; padding: 15px; width: 100%; text-align: center; color: #94a3b8; font-size: 13px; font-style: italic; }
    
    .card-footer { margin-top: 25px; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: center; align-items: center; gap: 15px; }
    
    .btn-action { 
        padding: 10px 25px; border-radius: 10px; font-size: 13px; font-weight: 600; 
        border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; 
        transition: 0.2s; text-decoration: none; min-width: 120px; justify-content: center; 
    }

    /* === [UPDATED] CANCEL BUTTON (EXACTLY MATCHING OTHER BUTTONS) === */
    .btn-cancel { 
        display: block !important;          
        width: auto !important;             
        min-width: 200px !important;        /* Same min-width as Search/Confirm */
        margin: 0 auto !important;          /* Center in Flex container */
        
        padding: 12px 40px !important;      /* Standard Pill Padding */
        background-color: #004b82 !important; 
        color: white !important; 
        border: none !important; 
        border-radius: 50px !important;     /* Fully Round */
        font-size: 15px !important;         
        font-weight: 600 !important;
        cursor: pointer !important; 
        text-align: center !important;
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    .btn-cancel:hover { 
        background-color: #003660 !important; 
        transform: translateY(-2px); 
        box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3) !important; 
    }

    .btn-rate { background: #fef9c3; color: #ca8a04; border: 1px solid #fef08a; } .btn-rate:hover { background: #fde047; }
    .btn-rated { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; cursor: pointer; } .btn-rated:hover { background: #e2e8f0; }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; } .empty-state i { font-size: 48px; margin-bottom: 20px; opacity: 0.5; }
    .btn-book-now { margin-top: 15px; display: inline-block; background: #004b82; color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,75,130,0.2); } .btn-book-now:hover { background: #003660; transform: translateY(-2px); }
    @media (max-width: 768px) {
        .card-content-row { flex-direction: column; gap: 20px; }
        .info-right-col { width: 100%; align-items: flex-start; }
        .status-pill { align-self: flex-start; margin-bottom: 5px; }
        .driver-box-design, .no-driver-box { width: 100%; }
        .card-header-row { align-items: center; } 
        .btn-cancel { width: 100% !important; } /* Full width on mobile */
    }
</style>

<div class="rides-wrapper">
    
    <div class="rides-header-title">
        <h1>My Journeys</h1>
        <p>Track your current trips and view history.</p>
    </div>

    <div class="search-container">
        <div class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
        <input type="text" id="searchInput" class="search-input" placeholder="Search ID, Location, Status..." onkeyup="filterRides()">
    </div>

    <div class="tabs-wrapper">
        <button id="btn-ongoing" class="tab-btn active" onclick="switchTab('ongoing')">Ongoing Rides</button>
        <button id="btn-history" class="tab-btn" onclick="switchTab('history')">History</button>
    </div>

    <div id="content-ongoing">
        <div class="no-match-msg" style="display:none; text-align:center; padding:30px; color:#94a3b8;">
            <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px; display:block;"></i>
            No matching ongoing rides found.
        </div>

        <?php if(empty($ongoing_rides)): ?>
            <div class="empty-state"><i class="fa-solid fa-route"></i><h3>No active rides</h3><p>You haven't booked any trips yet.</p><a href="search_transport.php" class="btn-book-now">Book a Ride</a></div>
        <?php else: ?>
            <?php foreach($ongoing_rides as $row): 
                $status = strtoupper($row['status']);
                $st_class = ($status == 'PENDING') ? 'st-pending' : 'st-accepted';
            ?>
            <div class="ride-card">
                <div class="card-header-row">
                    <span class="ride-date"><i class="fa-regular fa-calendar-days" style="color:#004b82;"></i> <?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></span>
                    <span class="ride-id">Track ID: #<?php echo $row['booking_id']; ?></span>
                </div>
                <div class="card-content-row">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <div class="t-item"><div class="t-dot pickup"></div><div class="t-text"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                        <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                    </div>
                    <div class="info-right-col">
                        <span class="status-pill <?php echo $st_class; ?>"><?php echo ($status == 'APPROVED') ? 'ACCEPTED' : $status; ?></span>
                        <?php if(!empty($row['driver_name'])): ?>
                            <div class="driver-box-design">
                                <div class="driver-img-wrap"><div class="driver-avatar-circle"><i class="fa-solid fa-user"></i></div></div>
                                <div class="driver-info-block"><div class="d-name"><?php echo htmlspecialchars($row['driver_name']); ?></div><div class="d-car-row"><span><?php echo htmlspecialchars($row['vehicle_model']); ?></span><span class="plate-badge-blue"><?php echo htmlspecialchars($row['plate_number']); ?></span></div></div>
                            </div>
                        <?php else: ?>
                            <div class="no-driver-box"><i class="fa-solid fa-spinner fa-spin"></i> Waiting for driver assignment...</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" onsubmit="confirmCancel(event, this)">
                        <input type="hidden" name="cancel_id" value="<?php echo $row['booking_id']; ?>">
                        <input type="hidden" name="cancel_ride" value="1">
                        <button type="submit" class="btn-cancel"><i class="fa-solid fa-ban"></i> Cancel Ride</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="content-history" style="display:none;">
        <div class="no-match-msg" style="display:none; text-align:center; padding:30px; color:#94a3b8;">
            <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px; display:block;"></i>
            No matching history records found.
        </div>

        <?php if(empty($history_rides)): ?>
            <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><h3>No History</h3><p>Your past trips will appear here.</p></div>
        <?php else: ?>
            <?php foreach($history_rides as $row): 
                $status = strtoupper($row['status']);
                $st_class = ($status == 'COMPLETED') ? 'st-completed' : 'st-cancelled';
                if($status == 'EXPIRED') $st_class = 'st-expired';
                
                $is_completed = ($status == 'COMPLETED');
                $has_rated = !empty($row['rating']);
            ?>
            <div class="ride-card">
                <div class="card-header-row">
                    <span class="ride-date"><i class="fa-regular fa-calendar-days" style="color:#004b82;"></i> <?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></span>
                    <span class="ride-id">Track ID: #<?php echo $row['booking_id']; ?></span>
                </div>
                <div class="card-content-row">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <div class="t-item"><div class="t-dot pickup"></div><div class="t-text"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                        <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                    </div>
                    <div class="info-right-col">
                        <span class="status-pill <?php echo $st_class; ?>"><?php echo $status; ?></span>
                        <?php if(!empty($row['driver_name'])): ?>
                            <div class="driver-box-design">
                                <div class="driver-img-wrap"><div class="driver-avatar-circle"><i class="fa-solid fa-user"></i></div></div>
                                <div class="driver-info-block"><div class="d-name"><?php echo htmlspecialchars($row['driver_name']); ?></div><div class="d-car-row"><span><?php echo htmlspecialchars($row['vehicle_model']); ?></span><span class="plate-badge-blue"><?php echo htmlspecialchars($row['plate_number']); ?></span></div></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if($is_completed): ?>
                <div class="card-footer">
                    <?php if(!$has_rated): ?>
                        <a href="passanger_rate.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-action btn-rate"><i class="fa-solid fa-star"></i> Rate Trip</a>
                    <?php else: ?>
                        <button onclick="showRatedAlert(<?php echo $row['rating']; ?>, '<?php echo htmlspecialchars(addslashes($row['comment'] ?? ''), ENT_QUOTES); ?>')" class="btn-action btn-rated"><i class="fa-solid fa-check"></i> Rated (View)</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include "footer.php"; ?>