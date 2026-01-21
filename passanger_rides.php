<?php
// ==========================================
// SECTION 1: CONFIGURATION & SESSION
// ==========================================
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: HANDLE RIDE CANCELLATION
// ==========================================
if(isset($_POST['cancel_ride'])){
    $cancel_id = $_POST['cancel_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND (status = 'PENDING' OR status = 'ACCEPTED' OR status = 'APPROVED')");
    $stmt->bind_param("is", $cancel_id, $student_id);
    if($stmt->execute()){
        // Set success session to trigger SweetAlert after redirect
        $_SESSION['cancel_success'] = true;
    }
    $stmt->close();
    header("Location: passanger_rides.php");
    exit();
}

// ==========================================
// SECTION 3: FETCH RIDES & DRIVER DETAILS
// ==========================================
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
    if(!empty($row['driver_name']) && $status == 'PENDING'){
        $status = 'ACCEPTED';
        $row['status'] = 'ACCEPTED'; 
    }
    $is_expired = ($ride_time < $current_timestamp);
    if(in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED', 'ONGOING', 'ARRIVED', 'IN PROGRESS']) && !$is_expired) {
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
    /* Global Page Fixes */
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    .rides-wrapper { max-width: 900px; margin: 0 auto; padding: 40px 20px; background: #f5f7fb; animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
    .rides-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; text-align: center; }
    .rides-header-title p { margin: 8px 0 25px; font-size: 15px; color: #64748b; text-align: center; }

    /* Search Bar */
    .search-container { position: relative; max-width: 500px; margin: 0 auto 25px; height: 50px; }
    .search-input { width: 100%; height: 100%; padding: 0 20px 0 55px !important; border-radius: 50px; border: 1px solid #e2e8f0; font-size: 15px; outline: none; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.03); box-sizing: border-box; }
    .search-input:focus { border-color: #004b82; box-shadow: 0 4px 15px rgba(0,75,130,0.15); }
    .search-icon { position: absolute; left: 20px !important; top: 0; bottom: 0; height: 100%; display: flex; align-items: center; color: #94a3b8; font-size: 18px; pointer-events: none; z-index: 10; }

    /* Tabs Layout */
    .tabs-wrapper { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
    .tab-btn { padding: 10px 30px; border-radius: 30px; border: none; background: #e2e8f0; color: #64748b; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 14px; }
    .tab-btn.active { background: #004b82; color: #fff; box-shadow: 0 4px 10px rgba(0,75,130,0.2); }

    /* Ride Card */
    .ride-card { background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9; overflow: hidden; padding: 25px; position: relative; }
    .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f8fafc; }
    .ride-date { font-weight: 700; color: #1e293b; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .ride-id { font-family: monospace; font-size: 14px; color: #64748b; letter-spacing: 0.5px; font-weight: 600; }
    
    .card-content-row { display: flex; justify-content: space-between; gap: 30px; }
    .timeline-container { flex: 1.5; position: relative; padding-left: 5px; padding-top: 5px; }
    .timeline-line { position: absolute; left: 11px; top: 10px; bottom: 30px; width: 2px; background: #e2e8f0; }
    .t-item { display: flex; gap: 20px; margin-bottom: 25px; position: relative; }
    .t-dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; flex-shrink: 0; position: relative; z-index: 1; margin-top: 2px; }
    .t-dot.pickup { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .t-dot.dropoff { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }
    .t-text h4 { margin: 0 0 4px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .t-text p { margin: 0; font-size: 15px; color: #334155; font-weight: 500; line-height: 1.4; }
    
    .info-right-col { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; min-width: 260px; }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .st-pending { background: #fff7ed; color: #f97316; }
    .st-accepted { background: #ecfdf5; color: #10b981; }
    .st-completed { background: #eff6ff; color: #3b82f6; }
    .st-cancelled { background: #fef2f2; color: #ef4444; }

    .driver-box-design { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 15px 20px; display: flex; align-items: center; gap: 15px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.03); cursor: pointer; transition: 0.2s; }
    .driver-img-wrap { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #e2e8f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .driver-img-wrap img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .d-name { font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; }
    .plate-badge-blue { background-color: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 6px; font-weight: 700; font-size: 12px; }

    /* Buttons */
    .card-footer { margin-top: 25px !important; padding-top: 15px !important; border-top: 1px dashed #e2e8f0 !important; display: flex !important; justify-content: center !important; align-items: center !important; gap: 12px !important; }
    .btn-pill-auto { display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 12px 35px !important; border-radius: 50px !important; font-size: 15px !important; font-weight: 600 !important; cursor: pointer !important; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important; transition: all 0.3s ease !important; border: none !important; text-decoration: none !important; white-space: nowrap !important; width: auto !important; gap: 8px !important; }
    .btn-pill-auto.blue { background-color: #004b82 !important; color: white !important; }
    .btn-pill-auto.blue:hover { background-color: #003660 !important; transform: translateY(-2px) !important; }
    .btn-pill-auto.green { background-color: #10b981 !important; color: white !important; margin-left: 10px !important; }
    .btn-pill-auto.green:hover { background-color: #059669 !important; transform: translateY(-2px) !important; }

    @media (max-width: 768px) {
        .card-footer { flex-direction: column !important; gap: 10px !important; }
        .btn-pill-auto { width: 100% !important; margin: 0 !important; }
    }

    /* Modals */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.show { display: flex !important; opacity: 1; }
    .modal-content { background: white; width: 90%; max-width: 550px; border-radius: 24px; padding: 30px; position: relative; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.3s ease; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 28px; color: #94a3b8; cursor: pointer; line-height: 1; }
    .m-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #e0f2fe; margin-bottom: 15px; }
    .m-name { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
    .m-detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 15px; }
    .btn-modal-action { display: block; width: 100%; padding: 14px; background: #004b82; color: white; border-radius: 50px; text-decoration: none; font-weight: 600; margin-top: 25px; cursor: pointer; border: none; }
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
        <?php if(empty($ongoing_rides)): ?>
            <div class="empty-state"><h3>No active rides</h3><p>You haven't booked any trips yet.</p></div>
        <?php else: ?>
            <?php foreach($ongoing_rides as $row): 
                $status = strtoupper($row['status']);
                $can_chat = in_array($status, ['ACCEPTED', 'APPROVED', 'ONGOING', 'ARRIVED', 'IN PROGRESS']);
                $img_url = (!empty($row['profile_image']) && file_exists("uploads/" . $row['profile_image'])) ? "uploads/" . $row['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['driver_name']);
            ?>
            <div class="ride-card">
                <div class="card-header-row">
                    <span class="ride-date"><i class="fa-regular fa-calendar-days"></i> <?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></span>
                    <span class="ride-id">#<?php echo $row['booking_id']; ?></span>
                </div>
                <div class="card-content-row">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <div class="t-item"><div class="t-dot pickup"></div><div class="t-text"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                        <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                    </div>
                    <div class="info-right-col">
                        <span class="status-pill <?php echo ($status == 'PENDING') ? 'st-pending' : 'st-accepted'; ?>"><?php echo ($status == 'APPROVED') ? 'ACCEPTED' : $status; ?></span>
                        <?php if(!empty($row['driver_name'])): ?>
                            <div class="driver-box-design" onclick="openDriverModal(this)" data-name="<?php echo htmlspecialchars($row['driver_name']); ?>" data-img="<?php echo $img_url; ?>" data-phone="<?php echo htmlspecialchars($row['driver_phone'] ?? 'N/A'); ?>" data-email="<?php echo htmlspecialchars($row['driver_email'] ?? 'N/A'); ?>" data-gender="<?php echo htmlspecialchars($row['driver_gender'] ?? 'Not Specified'); ?>" data-bio="<?php echo htmlspecialchars($row['driver_bio'] ?? 'No bio available.'); ?>" data-car="<?php echo htmlspecialchars($row['vehicle_model'] ?? 'N/A'); ?>" data-plate="<?php echo htmlspecialchars($row['plate_number'] ?? 'N/A'); ?>" data-color="<?php echo htmlspecialchars($row['vehicle_color'] ?? 'N/A'); ?>">
                                <div class="driver-img-wrap"><img src="<?php echo $img_url; ?>" alt="Driver"></div>
                                <div class="driver-info-block"><div class="d-name"><?php echo htmlspecialchars($row['driver_name']); ?></div><div class="d-car-row"><span><?php echo htmlspecialchars($row['vehicle_model']); ?></span><span class="plate-badge-blue"><?php echo htmlspecialchars($row['plate_number']); ?></span></div></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer">
                    <form method="POST" onsubmit="confirmCancel(event, this)" style="margin:0;">
                        <input type="hidden" name="cancel_id" value="<?php echo $row['booking_id']; ?>">
                        <input type="hidden" name="cancel_ride" value="1">
                        <button type="submit" class="btn-pill-auto blue"><i class="fa-solid fa-ban"></i> Cancel Ride</button>
                    </form>

                    <?php if($can_chat): ?>
                        <a href="ride_chat.php?room=<?php echo $row['booking_id']; ?>" class="btn-pill-auto green">
                            <i class="fa-solid fa-comments"></i> Chat
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="content-history" style="display:none;">
        <?php if(empty($history_rides)): ?>
            <div class="empty-state"><h3>No History</h3></div>
        <?php else: ?>
            <?php foreach($history_rides as $row): 
                $status = strtoupper($row['status']);
                $img_url = (!empty($row['profile_image']) && file_exists("uploads/" . $row['profile_image'])) ? "uploads/" . $row['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['driver_name']);
            ?>
            <div class="ride-card">
                <div class="card-header-row">
                    <span class="ride-date"><i class="fa-regular fa-calendar-days"></i> <?php echo date("d M Y", strtotime($row['date_time'])); ?></span>
                    <span class="ride-id">#<?php echo $row['booking_id']; ?></span>
                </div>
                <div class="card-content-row">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <div class="t-item"><div class="t-dot pickup"></div><div class="t-text"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                        <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                    </div>
                    <div class="info-right-col">
                        <span class="status-pill <?php echo ($status == 'COMPLETED') ? 'st-accepted' : 'st-cancelled'; ?>"><?php echo $status; ?></span>
                    </div>
                </div>
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
        <p id="m_bio" style="font-size:14px; color:#94a3b8; font-style:italic; margin-bottom:20px;"></p>
        <button class="btn-modal-action" onclick="closeDriverModal(event, true)">Close</button>
    </div>
</div>

<script>
    /**
     * Display success alert after page refresh if cancellation was successful
     */
    <?php if(isset($_SESSION['cancel_success'])): ?>
        Swal.fire({
            title: 'Cancel Successful!',
            text: "Your ride has been cancelled.",
            icon: 'success',
            confirmButtonColor: '#004b82',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['cancel_success']); ?>
    <?php endif; ?>

    function openDriverModal(element) {
        document.getElementById('m_img').src = element.getAttribute('data-img');
        document.getElementById('m_name').innerText = element.getAttribute('data-name');
        document.getElementById('m_bio').innerText = '"' + element.getAttribute('data-bio') + '"';
        document.getElementById('driverModal').classList.add('show'); 
    }
    function closeDriverModal(event, forceClose = false) { if (forceClose || event.target.id === 'driverModal') document.getElementById('driverModal').classList.remove('show'); }
    
    /**
     * Confirmation alert before submitting the cancellation form
     */
    function confirmCancel(event, form) { 
        event.preventDefault(); 
        Swal.fire({ 
            title: 'Cancel Ride?', 
            text: "Are you sure?", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#004b82', 
            cancelButtonColor: '#d33', 
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel'
        }).then((result) => { 
            if (result.isConfirmed) {
                form.submit(); 
            }
        }); 
    }
    
    function switchTab(tabName) {
        document.getElementById('content-ongoing').style.display = 'none'; document.getElementById('content-history').style.display = 'none';
        document.getElementById('btn-ongoing').classList.remove('active'); document.getElementById('btn-history').classList.remove('active');
        document.getElementById('content-' + tabName).style.display = 'block'; document.getElementById('btn-' + tabName).classList.add('active');
    }
    function filterRides() {
        let input = document.getElementById('searchInput').value.toUpperCase();
        let cards = document.getElementsByClassName('ride-card');
        for (let i = 0; i < cards.length; i++) cards[i].style.display = (cards[i].innerText.toUpperCase().indexOf(input) > -1) ? "" : "none";
    }
</script>

<?php include "footer.php"; ?>