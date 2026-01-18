<?php
// ==========================================
// SECTION 1: CONFIGURATION & SESSION
// ==========================================

// Start Session
session_start();

// Include Database & Helper Functions
include "db_connect.php";
include "function.php";

// Check Login Status
if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: HANDLE RIDE CANCELLATION
// ==========================================
if(isset($_POST['cancel_ride'])){
    $cancel_id = $_POST['cancel_id'];
    
    // Prepare statement to cancel only valid statuses
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND (status = 'PENDING' OR status = 'ACCEPTED' OR status = 'APPROVED')");
    $stmt->bind_param("is", $cancel_id, $student_id);
    
    if($stmt->execute()){
        // Cancellation successful
    } else {
        echo "<script>alert('Failed to cancel ride.');</script>";
    }
    $stmt->close();
    
    // Refresh page
    header("Location: passanger_rides.php");
    exit();
}

// ==========================================
// SECTION 3: FETCH RIDES & DRIVER DETAILS
// ==========================================
$ongoing_rides = [];
$history_rides = [];
$current_timestamp = time();

// [UPDATED SQL] Added: d.email, d.gender, d.bio, d.profile_image, v.vehicle_color
// We need these fields to display in the driver details modal
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
        d.email AS driver_email,
        d.gender AS driver_gender,
        d.bio AS driver_bio,
        d.profile_image,
        v.vehicle_model,
        v.plate_number,
        v.vehicle_color,
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
    
    // Logic: If driver is assigned but status is PENDING, treat as ACCEPTED
    if(!empty($row['driver_name']) && $status == 'PENDING'){
        $status = 'ACCEPTED';
        $row['status'] = 'ACCEPTED'; 
    }

    $is_expired = ($ride_time < $current_timestamp);

    // Categorize into Ongoing or History
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* Global Fixes */
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    .rides-wrapper {
        max-width: 900px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }
    .rides-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; text-align: center; }
    .rides-header-title p { margin: 8px 0 25px; font-size: 15px; color: #64748b; text-align: center; }

    /* Search Bar */
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

    /* Tabs */
    .tabs-wrapper { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
    .tab-btn {
        padding: 10px 30px; border-radius: 30px; border: none; background: #e2e8f0; color: #64748b;
        font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 14px;
    }
    .tab-btn.active { background: #004b82; color: #fff; box-shadow: 0 4px 10px rgba(0,75,130,0.2); }
    .tab-btn:hover:not(.active) { background: #cbd5e1; }

    /* Ride Card */
    .ride-card { background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9; overflow: hidden; padding: 25px; position: relative; }
    .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f8fafc; }
    .ride-date { font-weight: 700; color: #1e293b; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .ride-id { font-family: monospace; font-size: 14px; color: #64748b; letter-spacing: 0.5px; font-weight: 600; }
    .card-content-row { display: flex; justify-content: space-between; gap: 30px; }
    
    /* Timeline */
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
    
    /* Right Column (Status & Driver) */
    .info-right-col { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; min-width: 260px; }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; align-self: flex-end; }
    .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; }
    .st-pending { background: #fff7ed; color: #f97316; }
    .st-accepted { background: #ecfdf5; color: #10b981; }
    .st-completed { background: #eff6ff; color: #3b82f6; }
    .st-cancelled { background: #fef2f2; color: #ef4444; }
    .st-expired { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* [UPDATED] DRIVER BOX (Clickable Style) */
    .driver-box-design { 
        background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 15px 20px; 
        display: flex; align-items: center; gap: 15px; width: 100%; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); justify-content: flex-start;
        cursor: pointer; /* Indicates it's clickable */
        transition: all 0.2s ease;
    }
    .driver-box-design:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 75, 130, 0.1);
        border-color: #e0f2fe;
    }

    .driver-img-wrap { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #e2e8f0; padding: 2px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .driver-img-wrap img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .driver-avatar-circle { width: 100%; height: 100%; border-radius: 50%; background: #004b82; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .driver-info-block { display: flex; flex-direction: column; align-items: flex-start; overflow: hidden; }
    .d-name { font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
    .d-car-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; }
    .plate-badge-blue { background-color: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 6px; font-weight: 700; font-size: 12px; }
    
    .no-driver-box { background: #f8fafc; border: 1px dashed #cbd5e0; border-radius: 12px; padding: 15px; width: 100%; text-align: center; color: #94a3b8; font-size: 13px; font-style: italic; }
    
    .card-footer { margin-top: 25px; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: center; align-items: center; gap: 15px; }
    .btn-action { padding: 10px 25px; border-radius: 10px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; text-decoration: none; min-width: 120px; justify-content: center; }
    
    .btn-cancel { display: block !important; width: auto !important; min-width: 200px !important; margin: 0 auto !important; padding: 12px 40px !important; background-color: #004b82 !important; color: white !important; border: none !important; border-radius: 50px !important; font-size: 15px !important; font-weight: 600 !important; cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important; transition: all 0.3s ease !important; }
    .btn-cancel:hover { background-color: #003660 !important; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3) !important; }

    .btn-rate { background: #fef9c3; color: #ca8a04; border: 1px solid #fef08a; } .btn-rate:hover { background: #fde047; }
    .btn-rated { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; cursor: pointer; } .btn-rated:hover { background: #e2e8f0; }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; } .empty-state i { font-size: 48px; margin-bottom: 20px; opacity: 0.5; }
    .btn-book-now { margin-top: 15px; display: inline-block; background: #004b82; color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,75,130,0.2); } .btn-book-now:hover { background: #003660; transform: translateY(-2px); }
    @media (max-width: 768px) { .card-content-row { flex-direction: column; gap: 20px; } .info-right-col { width: 100%; align-items: flex-start; } .status-pill { align-self: flex-start; margin-bottom: 5px; } .driver-box-design, .no-driver-box { width: 100%; } .card-header-row { align-items: center; } .btn-cancel { width: 100% !important; } }

    /* [NEW] MODAL STYLES (Matching Search & Profile) */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.show { display: flex !important; opacity: 1; }
    .modal-content { background: white; width: 90%; max-width: 550px; border-radius: 24px; padding: 30px; position: relative; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.3s ease; }
    .modal-overlay.show .modal-content { transform: translateY(0); }
    .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
    .close-modal:hover { color: #333; }
    .m-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #e0f2fe; margin-bottom: 15px; }
    .m-name { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
    .m-bio { font-size: 15px !important; color: #94a3b8 !important; margin: 4px auto 25px !important; font-weight: 400 !important; font-style: italic; line-height: 1.4; max-width: 85%; }
    .m-detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 15px; }
    .m-detail-row:last-child { border-bottom: none; }
    .m-label { color: #64748b; font-weight: 500; }
    .m-val { color: #333; font-weight: 600; }
    .btn-modal-action { display: block; width: 100%; padding: 14px; background: #004b82; color: white; border-radius: 50px; text-decoration: none; font-weight: 600; margin-top: 25px; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2); cursor: pointer; border: none; }
    .btn-modal-action:hover { background: #003660; }
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
            <div class="empty-state">
                <i class="fa-solid fa-route"></i>
                <h3>No active rides</h3>
                <p>You haven't booked any trips yet.</p>
                <a href="search_transport.php" class="btn-book-now">Book a Ride</a>
            </div>
        <?php else: ?>
            <?php foreach($ongoing_rides as $row): 
                $status = strtoupper($row['status']);
                $st_class = ($status == 'PENDING') ? 'st-pending' : 'st-accepted';
                
                // Prepare Driver Data for Modal
                $driver_img = $row['profile_image'];
                if(!empty($driver_img) && file_exists("uploads/" . $driver_img)) {
                    $img_url = "uploads/" . $driver_img;
                } else {
                    $img_url = "https://ui-avatars.com/api/?name=".urlencode($row['driver_name'])."&background=random&color=fff";
                }
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
                            <div class="driver-box-design" onclick="openDriverModal(this)"
                                 data-name="<?php echo htmlspecialchars($row['driver_name']); ?>"
                                 data-img="<?php echo $img_url; ?>"
                                 data-phone="<?php echo htmlspecialchars($row['driver_phone'] ?? 'N/A'); ?>"
                                 data-email="<?php echo htmlspecialchars($row['driver_email'] ?? 'N/A'); ?>"
                                 data-gender="<?php echo htmlspecialchars($row['driver_gender'] ?? 'Not Specified'); ?>"
                                 data-bio="<?php echo htmlspecialchars($row['driver_bio'] ?? 'No bio available.'); ?>"
                                 data-car="<?php echo htmlspecialchars($row['vehicle_model'] ?? 'N/A'); ?>"
                                 data-plate="<?php echo htmlspecialchars($row['plate_number'] ?? 'N/A'); ?>"
                                 data-color="<?php echo htmlspecialchars($row['vehicle_color'] ?? 'N/A'); ?>">
                                
                                <div class="driver-img-wrap">
                                    <img src="<?php echo $img_url; ?>" alt="Driver">
                                </div>
                                <div class="driver-info-block">
                                    <div class="d-name"><?php echo htmlspecialchars($row['driver_name']); ?></div>
                                    <div class="d-car-row">
                                        <span><?php echo htmlspecialchars($row['vehicle_model']); ?></span>
                                        <span class="plate-badge-blue"><?php echo htmlspecialchars($row['plate_number']); ?></span>
                                    </div>
                                </div>
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

                // Prepare Driver Data for Modal
                $driver_img = $row['profile_image'];
                if(!empty($driver_img) && file_exists("uploads/" . $driver_img)) {
                    $img_url = "uploads/" . $driver_img;
                } else {
                    $img_url = "https://ui-avatars.com/api/?name=".urlencode($row['driver_name'])."&background=random&color=fff";
                }
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
                            <div class="driver-box-design" onclick="openDriverModal(this)"
                                 data-name="<?php echo htmlspecialchars($row['driver_name']); ?>"
                                 data-img="<?php echo $img_url; ?>"
                                 data-phone="<?php echo htmlspecialchars($row['driver_phone'] ?? 'N/A'); ?>"
                                 data-email="<?php echo htmlspecialchars($row['driver_email'] ?? 'N/A'); ?>"
                                 data-gender="<?php echo htmlspecialchars($row['driver_gender'] ?? 'Not Specified'); ?>"
                                 data-bio="<?php echo htmlspecialchars($row['driver_bio'] ?? 'No bio available.'); ?>"
                                 data-car="<?php echo htmlspecialchars($row['vehicle_model'] ?? 'N/A'); ?>"
                                 data-plate="<?php echo htmlspecialchars($row['plate_number'] ?? 'N/A'); ?>"
                                 data-color="<?php echo htmlspecialchars($row['vehicle_color'] ?? 'N/A'); ?>">
                                <div class="driver-img-wrap">
                                    <img src="<?php echo $img_url; ?>" alt="Driver">
                                </div>
                                <div class="driver-info-block">
                                    <div class="d-name"><?php echo htmlspecialchars($row['driver_name']); ?></div>
                                    <div class="d-car-row">
                                        <span><?php echo htmlspecialchars($row['vehicle_model']); ?></span>
                                        <span class="plate-badge-blue"><?php echo htmlspecialchars($row['plate_number']); ?></span>
                                    </div>
                                </div>
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

<div id="driverModal" class="modal-overlay" onclick="closeDriverModal(event)">
    <div class="modal-content">
        <span class="close-modal" onclick="closeDriverModal(event, true)">&times;</span>
        
        <img id="m_img" class="m-avatar" src="" alt="Avatar">
        <h3 id="m_name" class="m-name">Driver Name</h3>
        <p id="m_bio" class="m-bio">Driver bio...</p>

        <div class="m-detail-row">
            <span class="m-label"><i class="fa-solid fa-phone"></i> Phone</span>
            <span class="m-val" id="m_phone">---</span>
        </div>
        <div class="m-detail-row">
            <span class="m-label"><i class="fa-solid fa-envelope"></i> Email</span>
            <span class="m-val" id="m_email">---</span>
        </div>
        <div class="m-detail-row">
            <span class="m-label"><i class="fa-solid fa-venus-mars"></i> Gender</span>
            <span class="m-val" id="m_gender">---</span>
        </div>
        <div class="m-detail-row">
            <span class="m-label"><i class="fa-solid fa-car"></i> Vehicle</span>
            <span class="m-val"><span id="m_color"></span> <span id="m_car"></span></span>
        </div>
        <div class="m-detail-row">
            <span class="m-label"><i class="fa-solid fa-id-card"></i> Plate No</span>
            <span class="m-val" id="m_plate" style="text-transform:uppercase; background:#f1f5f9; padding:2px 6px; border-radius:4px;">---</span>
        </div>

        <button class="btn-modal-action" onclick="closeDriverModal(event, true)">Close</button>
    </div>
</div>

<script>
    // --- 1. Driver Modal Logic (New) ---
    function openDriverModal(element) {
        // Read data attributes from the clicked div
        document.getElementById('m_img').src = element.getAttribute('data-img');
        document.getElementById('m_name').innerText = element.getAttribute('data-name');
        document.getElementById('m_bio').innerText = '"' + element.getAttribute('data-bio') + '"';
        document.getElementById('m_phone').innerText = element.getAttribute('data-phone');
        document.getElementById('m_email').innerText = element.getAttribute('data-email');
        document.getElementById('m_gender').innerText = element.getAttribute('data-gender'); 
        document.getElementById('m_car').innerText = element.getAttribute('data-car');
        document.getElementById('m_plate').innerText = element.getAttribute('data-plate');
        document.getElementById('m_color').innerText = element.getAttribute('data-color');

        // Show Modal
        const modal = document.getElementById('driverModal');
        modal.classList.add('show'); 
    }

    function closeDriverModal(event, forceClose = false) {
        // Close if clicked background overlay or X button
        if (forceClose || event.target.id === 'driverModal') {
            const modal = document.getElementById('driverModal');
            modal.classList.remove('show');
        }
    }

    // --- 2. Existing Rating Alert ---
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

    // --- 3. Existing Cancellation Logic ---
    function confirmCancel(event, form) {
        event.preventDefault();
        Swal.fire({
            title: 'Cancel Ride?',
            text: "Are you sure you want to cancel this booking?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Cancel it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }

    // --- 4. Tabs Logic ---
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

    // --- 5. Search Logic ---
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

<?php include "footer.php"; ?>