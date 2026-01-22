<?php
// ==========================================
// SECTION 1: CONFIGURATION & SESSION
// ==========================================
session_start();
include "db_connect.php";
include "function.php";

// Check if passenger is logged in
if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: HANDLE ACTIONS (AJAX & POST)
// ==========================================

// 1. Handle Rating & Review Submission
if(isset($_POST['submit_rating'])){
    $b_id = $_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    // Retrieve driver_id from booking to maintain database integrity
    $get_driver = $conn->query("SELECT driver_id FROM bookings WHERE id = '$b_id'");
    $driver_data = $get_driver->fetch_assoc();
    $driver_id = $driver_data['driver_id'];

    $check = $conn->query("SELECT id FROM reviews WHERE booking_id = '$b_id'");
    
    if($check->num_rows > 0){
        // Update existing review
        $sql = "UPDATE reviews SET rating = '$rating', comment = '$comment' WHERE booking_id = '$b_id'";
    } else {
        // Insert new review with all required relational IDs
        $sql = "INSERT INTO reviews (booking_id, student_id, driver_id, rating, comment, created_at) 
                VALUES ('$b_id', '$student_id', '$driver_id', '$rating', '$comment', NOW())";
    }
    
    if($conn->query($sql)){ 
        echo json_encode(['status' => 'success']); 
    } else { 
        echo json_encode(['status' => 'error', 'msg' => $conn->error]); 
    }
    exit();
}

// 2. Handle Ride Cancellation
if(isset($_POST['cancel_ride'])){
    $cancel_id = $_POST['cancel_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND (status = 'PENDING' OR status = 'ACCEPTED' OR status = 'APPROVED')");
    $stmt->bind_param("is", $cancel_id, $student_id);
    if($stmt->execute()){
        $_SESSION['cancel_success'] = true;
    }
    $stmt->close();
    header("Location: passanger_rides.php");
    exit();
}

// 3. Handle Driver Favourite Toggle
if(isset($_POST['toggle_fav'])){
    $fav_driver_id = $_POST['driver_id'];
    $check = $conn->prepare("SELECT id FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
    $check->bind_param("ss", $student_id, $fav_driver_id);
    $check->execute();
    if($check->get_result()->num_rows > 0){
        $stmt = $conn->prepare("DELETE FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
        $stmt->bind_param("ss", $student_id, $fav_driver_id);
        $stmt->execute();
        echo json_encode(['status' => 'removed']);
    } else {
        $stmt = $conn->prepare("INSERT INTO favourite_drivers (student_id, driver_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $student_id, $fav_driver_id);
        $stmt->execute();
        echo json_encode(['status' => 'added']);
    }
    exit();
}

// 4. Check Favourite Status
if(isset($_POST['check_fav'])){
    $d_id = $_POST['driver_id'];
    $check = $conn->prepare("SELECT id FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
    $check->bind_param("ss", $student_id, $d_id);
    $check->execute();
    echo json_encode(['is_fav' => ($check->get_result()->num_rows > 0)]);
    exit();
}

// ==========================================
// SECTION 3: FETCH RIDE DATA
// ==========================================
$ongoing_rides = [];
$history_rides = [];
$current_timestamp = time();

$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id, b.pickup_point, b.destination, b.date_time, b.status, b.remark,
        d.driver_id, d.full_name AS driver_name, d.phone_number AS driver_phone, d.email AS driver_email,
        d.gender AS driver_gender, d.bio AS driver_bio, d.profile_image,
        v.vehicle_model, v.plate_number, v.vehicle_color,
        r.rating, r.comment
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
    if(!empty($row['driver_name']) && $status == 'PENDING'){ $status = 'ACCEPTED'; $row['status'] = 'ACCEPTED'; }
    $is_expired = ($ride_time < $current_timestamp);
    if(in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED', 'ONGOING', 'ARRIVED', 'IN PROGRESS']) && !$is_expired) {
        $ongoing_rides[] = $row;
    } else {
        if($is_expired && in_array($status, ['PENDING', 'ACCEPTED', 'APPROVED'])) { $row['status'] = 'EXPIRED'; }
        $history_rides[] = $row;
    }
}
$stmt->close();
?>

<?php include "header.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
    .rides-wrapper { max-width: 900px; margin: 0 auto; padding: 40px 20px; background: #f5f7fb; animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
    .rides-header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; text-align: center; }
    .rides-header-title p { margin: 8px 0 25px; font-size: 15px; color: #64748b; text-align: center; }
    .tabs-wrapper { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
    .tab-btn { padding: 10px 30px; border-radius: 30px; border: none; background: #e2e8f0; color: #64748b; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 14px; }
    .tab-btn.active { background: #004b82; color: #fff; box-shadow: 0 4px 10px rgba(0,75,130,0.2); }
    .ride-card { background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #f1f5f9; overflow: hidden; padding: 25px; position: relative; }
    .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f8fafc; }
    .card-content-row { display: flex; justify-content: space-between; gap: 30px; }
    .timeline-container { flex: 1.5; position: relative; padding-left: 5px; padding-top: 5px; }
    .timeline-line { position: absolute; left: 11px; top: 10px; bottom: 30px; width: 2px; background: #e2e8f0; }
    .t-item { display: flex; gap: 20px; margin-bottom: 25px; position: relative; }
    .t-dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; flex-shrink: 0; position: relative; z-index: 1; margin-top: 2px; }
    .t-dot.pickup { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .t-dot.dropoff { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }
    .t-text h4 { margin: 0 0 4px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .t-text p { margin: 0; font-size: 14px; color: #334155; font-weight: 600; line-height: 1.4; }
    .info-right-col { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; min-width: 250px; }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .st-pending { background: #fff7ed; color: #f97316; }
    .st-accepted { background: #ecfdf5; color: #10b981; }
    .st-completed { background: #eff6ff; color: #3b82f6; }
    .st-cancelled { background: #fef2f2; color: #ef4444; }
    .driver-box-design { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 12px 15px; display: flex; align-items: center; gap: 12px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.03); cursor: pointer; transition: 0.2s; }
    .driver-box-design:hover { border-color: #004b82; transform: translateY(-2px); }
    .driver-img-wrap { width: 45px; height: 45px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; flex-shrink: 0; }
    .driver-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .plate-badge-premium { background-color: #004b82; color: white; padding: 3px 10px; border-radius: 8px; font-weight: 700; font-size: 11px; letter-spacing: 1px; display: inline-block; margin-top: 4px; }
    .card-footer { margin-top: 25px !important; padding-top: 15px !important; border-top: 1px dashed #e2e8f0 !important; display: grid !important; grid-template-columns: 1fr auto 1fr !important; align-items: center !important; position: relative !important; width: 100% !important; }
    .btn-pill-auto { display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 10px 25px !important; border-radius: 50px !important; font-size: 14px !important; font-weight: 600 !important; cursor: pointer !important; transition: all 0.3s ease !important; border: none !important; text-decoration: none !important; width: fit-content !important; min-width: auto !important; white-space: nowrap !important; }
    .btn-pill-auto.blue { background-color: #004b82 !important; color: white !important; }
    .btn-pill-auto.green { background-color: #10b981 !important; color: white !important; }
    .btn-pill-auto.orange { background-color: #f97316 !important; color: white !important; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9999 !important; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex !important; }
    .modal-content { background: white; width: 90%; max-width: 450px; border-radius: 24px; padding: 30px; position: relative; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
    .m-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #e0f2fe; margin-bottom: 15px; }
    .m-detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 15px; }
    .style-saved-modal { background-color: #ffe4e6 !important; color: #e11d48 !important; border: 1px solid #e11d48 !important; }
    .style-save-add-modal { background-color: #e0f2fe !important; color: #004b82 !important; border: 1px solid #004b82 !important; }
</style>

<div class="rides-wrapper">
    <div class="rides-header-title">
        <h1>My Journeys</h1>
        <p>Track your current trips and view history.</p>
    </div>

    <div class="tabs-wrapper">
        <button id="btn-ongoing" class="tab-btn active" onclick="switchTab('ongoing')">Ongoing Rides</button>
        <button id="btn-history" class="tab-btn" onclick="switchTab('history')">History</button>
    </div>

    <div id="content-ongoing">
        <?php foreach($ongoing_rides as $row): 
            $status = strtoupper($row['status']);
            $img_url = (!empty($row['profile_image'])) ? "uploads/" . $row['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['driver_name']);
        ?>
        <div class="ride-card">
            <div class="card-header-row">
                <span style="font-weight:700;"><i class="fa-regular fa-calendar-days"></i> <?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></span>
                <span style="color:#64748b; font-weight:700;">#<?php echo $row['booking_id']; ?></span>
            </div>
            <div class="card-content-row">
                <div class="timeline-container">
                    <div class="timeline-line"></div>
                    <div class="t-item"><div class="t-dot pickup"></div><div class="t-text" style="text-align:left;"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                    <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text" style="text-align:left;"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                </div>
                <div class="info-right-col">
                    <span class="status-pill <?php echo ($status == 'PENDING') ? 'st-pending' : 'st-accepted'; ?>"><?php echo ($status == 'APPROVED') ? 'ACCEPTED' : $status; ?></span>
                    <?php if(!empty($row['driver_name'])): ?>
                        <div class="driver-box-design" onclick="openDriverModal(this)" data-did="<?php echo $row['driver_id']; ?>" data-name="<?php echo htmlspecialchars($row['driver_name']); ?>" data-img="<?php echo $img_url; ?>" data-phone="<?php echo htmlspecialchars($row['driver_phone']); ?>" data-email="<?php echo htmlspecialchars($row['driver_email']); ?>" data-gender="<?php echo htmlspecialchars($row['driver_gender']); ?>" data-car="<?php echo htmlspecialchars($row['vehicle_model']); ?>" data-plate="<?php echo htmlspecialchars($row['plate_number']); ?>" data-bio="<?php echo htmlspecialchars($row['driver_bio']); ?>">
                            <div class="driver-img-wrap"><img src="<?php echo $img_url; ?>"></div>
                            <div style="flex:1; text-align:left;"><div style="font-size:13px; font-weight:800; color:#1e293b;"><?php echo htmlspecialchars($row['driver_name']); ?></div><div class="plate-badge-premium"><?php echo htmlspecialchars($row['plate_number']); ?></div></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <div></div> 
                <form method="POST" onsubmit="confirmCancel(event, this)" style="margin:0;"><input type="hidden" name="cancel_id" value="<?php echo $row['booking_id']; ?>"><button type="submit" class="btn-pill-auto blue" style="border-radius: 50px !important;"><i class="fa-solid fa-ban" style="margin-right: 10px !important;"></i> Cancel Ride</button></form>
                <div style="display:flex; justify-content:flex-end;"><a href="ride_chat.php?room=<?php echo $row['booking_id']; ?>" class="btn-pill-auto green" style="border-radius: 50px !important;"><i class="fa-solid fa-comments" style="margin-right: 10px !important;"></i> Chat</a></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div id="content-history" style="display:none;">
        <?php foreach($history_rides as $row): 
            $status = strtoupper($row['status']);
            $st_class = ($status == 'COMPLETED') ? 'st-completed' : 'st-cancelled';
            $img_url = (!empty($row['profile_image'])) ? "uploads/" . $row['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['driver_name'] ?? 'U');
        ?>
        <div class="ride-card">
            <div class="card-header-row">
                <span style="font-weight:700;"><i class="fa-regular fa-calendar-days"></i> <?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></span>
                <span style="color:#64748b; font-weight:700;">#<?php echo $row['booking_id']; ?></span>
            </div>
            <div class="card-content-row">
                <div class="timeline-container">
                    <div class="timeline-line"></div>
                    <div class="t-item"><div class="t-dot pickup"></div><div class="t-text" style="text-align:left;"><h4>Pickup</h4><p><?php echo htmlspecialchars($row['pickup_point']); ?></p></div></div>
                    <div class="t-item"><div class="t-dot dropoff"></div><div class="t-text" style="text-align:left;"><h4>Destination</h4><p><?php echo htmlspecialchars($row['destination']); ?></p></div></div>
                </div>
                <div class="info-right-col">
                    <span class="status-pill <?php echo $st_class; ?>"><?php echo $status; ?></span>
                    <?php if(!empty($row['driver_name'])): ?>
                        <div class="driver-box-design" onclick="openDriverModal(this)" data-did="<?php echo $row['driver_id']; ?>" data-name="<?php echo htmlspecialchars($row['driver_name']); ?>" data-img="<?php echo $img_url; ?>" data-phone="<?php echo htmlspecialchars($row['driver_phone']); ?>" data-email="<?php echo htmlspecialchars($row['driver_email']); ?>" data-gender="<?php echo htmlspecialchars($row['driver_gender']); ?>" data-car="<?php echo htmlspecialchars($row['vehicle_model']); ?>" data-plate="<?php echo htmlspecialchars($row['plate_number']); ?>" data-bio="<?php echo htmlspecialchars($row['driver_bio']); ?>">
                            <div class="driver-img-wrap"><img src="<?php echo $img_url; ?>"></div>
                            <div style="flex:1; text-align:left;"><div style="font-size:13px; font-weight:800; color:#1e293b;"><?php echo htmlspecialchars($row['driver_name']); ?></div><div class="plate-badge-premium"><?php echo htmlspecialchars($row['plate_number']); ?></div></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <div></div> 
                <div style="display:flex; justify-content:center;">
                    <?php if($status == 'COMPLETED'): ?>
                        <button onclick="handleRating('<?php echo $row['booking_id']; ?>', '<?php echo $row['rating']; ?>', '<?php echo htmlspecialchars($row['comment'] ?? ''); ?>')" class="btn-pill-auto orange" style="border-radius: 50px !important;"><i class="fa-solid fa-star" style="margin-right: 10px !important;"></i> <?php echo empty($row['rating']) ? 'Rate & Review' : 'My Reviews'; ?></button>
                    <?php endif; ?>
                </div>
                <div style="display:flex; justify-content:flex-end;"><a href="ride_chat.php?room=<?php echo $row['booking_id']; ?>" class="btn-pill-auto green" style="border-radius: 50px !important;"><i class="fa-solid fa-comments" style="margin-right: 10px !important;"></i> Chat History</a></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="driverModal" class="modal-overlay" onclick="closeDriverModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="close-modal" onclick="closeDriverModal(event, true)" style="position: absolute; top: 15px; right: 20px; font-size: 25px; cursor: pointer;">&times;</span>
        <img id="m_img" class="m-avatar" src="" alt="Avatar">
        <h3 id="m_name_header" style="margin-bottom: 5px; color:#004b82;">Driver Details</h3>
        <p id="m_bio" style="font-size:14px; color:#64748b; font-style:italic; margin-bottom:20px; border-bottom: 1px solid #eee; padding-bottom: 15px;"></p>
        <div class="m-detail-row"><span>Phone:</span><span id="m_phone" style="font-weight:700;"></span></div>
        <div class="m-detail-row"><span>Email:</span><span id="m_email" style="font-weight:700;"></span></div>
        <div class="m-detail-row"><span>Gender:</span><span id="m_gender" style="font-weight:700;"></span></div>
        <div class="m-detail-row"><span>Vehicle:</span><span id="m_car" style="font-weight:700;"></span></div>
        <div class="m-detail-row"><span>Plate:</span><span id="m_plate" style="font-weight:700;"></span></div>
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button id="m_fav_btn" class="btn-pill-auto" style="flex: 1; border-radius: 50px !important;" onclick="toggleFavModal()"><i id="m_fav_icon" class="fa-solid fa-heart" style="margin-right: 8px !important;"></i> <span id="m_fav_text">Save</span></button>
            <button class="btn-pill-auto blue" style="flex: 1; border-radius: 50px !important;" onclick="closeDriverModal(event, true)">Close</button>
        </div>
    </div>
</div>

<script>
    <?php if(isset($_SESSION['cancel_success'])): ?>
        Swal.fire({ title: 'Success!', text: 'Your ride booking has been cancelled.', icon: 'success', confirmButtonColor: '#004b82' });
        <?php unset($_SESSION['cancel_success']); ?>
    <?php endif; ?>

    let currentDriverIdModal = null;
    let currentDriverNameModal = null;

    function openDriverModal(el) {
        currentDriverIdModal = el.getAttribute('data-did');
        currentDriverNameModal = el.getAttribute('data-name');
        document.getElementById('m_img').src = el.getAttribute('data-img'); 
        document.getElementById('m_phone').innerText = el.getAttribute('data-phone');
        document.getElementById('m_email').innerText = el.getAttribute('data-email');
        document.getElementById('m_gender').innerText = el.getAttribute('data-gender');
        document.getElementById('m_car').innerText = el.getAttribute('data-car');
        document.getElementById('m_plate').innerText = el.getAttribute('data-plate');
        document.getElementById('m_bio').innerText = el.getAttribute('data-bio') ? '"' + el.getAttribute('data-bio') + '"' : "No bio available.";
        const fd = new FormData(); fd.append('check_fav', '1'); fd.append('driver_id', currentDriverIdModal);
        fetch('passanger_rides.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            updateFavBtnUI(data.is_fav);
            document.getElementById('driverModal').classList.add('show'); 
        });
    }

    function updateFavBtnUI(isFav) {
        const btn = document.getElementById('m_fav_btn');
        const text = document.getElementById('m_fav_text');
        if(isFav) { btn.className = "btn-pill-auto style-saved-modal"; text.innerText = "Saved"; } 
        else { btn.className = "btn-pill-auto style-save-add-modal"; text.innerText = "Save"; }
    }

    function toggleFavModal() {
        const btn = document.getElementById('m_fav_btn');
        const isSaving = btn.classList.contains('style-save-add-modal');
        Swal.fire({ target: document.getElementById('driverModal'), title: isSaving ? 'Save Driver?' : 'Unsave Driver?', text: isSaving ? `Save ${currentDriverNameModal}?` : `Remove ${currentDriverNameModal}?`, icon: 'question', showCancelButton: true, confirmButtonColor: '#004b82', cancelButtonColor: '#64748b', confirmButtonText: 'Yes', reverseButtons: true }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData(); fd.append('toggle_fav', '1'); fd.append('driver_id', currentDriverIdModal);
                fetch('passanger_rides.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                    updateFavBtnUI(data.status === 'added');
                    Swal.fire({ target: document.getElementById('driverModal'), title: 'Success!', text: data.status === 'added' ? 'Driver saved!' : 'Removed!', icon: 'success', timer: 1000, showConfirmButton: false });
                });
            }
        });
    }

    function closeDriverModal(e, force = false) { if (force || e.target.id === 'driverModal') document.getElementById('driverModal').classList.remove('show'); }
    function confirmCancel(event, form) { event.preventDefault(); Swal.fire({ title: 'Cancel Ride?', text: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#004b82', cancelButtonColor: '#d33', confirmButtonText: 'Yes' }).then((result) => { if (result.isConfirmed) form.submit(); }); }
    
    function handleRating(bookingId, currentRating, currentComment) {
        if(currentRating && currentRating !== "") {
            let starsHtml = '';
            for(let i=1; i<=5; i++) { starsHtml += '<i class="fa-star ' + (i <= currentRating ? 'fa-solid' : 'fa-regular') + '" style="color:#f97316; font-size: 24px; margin: 0 3px;"></i>'; }
            Swal.fire({ title: 'My Review', html: '<div style="margin-bottom: 15px;">' + starsHtml + '</div><p style="background: #f8fafc; padding: 15px; border-radius: 12px; font-style: italic; color: #475569; border: 1px solid #e2e8f0;">"' + (currentComment || 'No comment provided.') + '"</p>', showCancelButton: true, confirmButtonText: 'Edit Review', cancelButtonText: 'Close', confirmButtonColor: '#f97316', cancelButtonColor: '#64748b' }).then((result) => { if (result.isConfirmed) showRatePopup(bookingId, currentRating, currentComment, true); });
        } else { showRatePopup(bookingId, 0, "", false); }
    }

    function showRatePopup(bookingId, r, c, isEdit) {
        Swal.fire({ title: isEdit ? 'My Review' : 'Rate & Review', html: '<div style="font-size: 28px; margin-bottom: 20px;">' + 
                  [1,2,3,4,5].map(n => '<i class="fa-star ' + (n <= r ? 'fa-solid' : 'fa-regular') + ' star-btn" data-val="' + n + '" style="cursor:pointer; color:#f97316; margin: 0 5px;"></i>').join('') + 
                  '</div><input type="hidden" id="star_val" value="' + (r || 0) + '"><textarea id="swal_cmt" class="swal2-textarea" placeholder="Describe your experience..." style="width: 85%; height: 100px; font-size: 14px;">' + (c || '') + '</textarea>',
            didOpen: () => {
                const stars = Swal.getHtmlContainer().querySelectorAll('.star-btn');
                stars.forEach(s => s.onclick = () => {
                    const v = s.getAttribute('data-val'); document.getElementById('star_val').value = v;
                    stars.forEach(st => { st.classList.toggle('fa-solid', st.getAttribute('data-val') <= v); st.classList.toggle('fa-regular', st.getAttribute('data-val') > v); });
                });
            }, showCancelButton: true, confirmButtonText: 'Submit', confirmButtonColor: '#004b82', preConfirm: () => {
                const rating = document.getElementById('star_val').value; const comment = document.getElementById('swal_cmt').value;
                if (rating == 0) return Swal.showValidationMessage('Please select a rating!');
                return { rating, comment };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (isEdit) { Swal.fire({ title: 'Are you sure?', text: "Update review?", icon: 'warning', showCancelButton: true, confirmButtonColor: '#004b82', confirmButtonText: 'Yes' }).then((cr) => { if (cr.isConfirmed) submitReviewAjax(bookingId, result.value.rating, result.value.comment); }); } 
                else { submitReviewAjax(bookingId, result.value.rating, result.value.comment); }
            }
        });
    }

    function submitReviewAjax(bookingId, rating, comment) {
        const fd = new FormData(); fd.append('submit_rating', '1'); fd.append('booking_id', bookingId); fd.append('rating', rating); fd.append('comment', comment);
        fetch('passanger_rides.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                Swal.fire({ title: 'Success!', text: 'Rate & Review completed successfully!', icon: 'success', confirmButtonColor: '#004b82' }).then(() => { location.reload(); });
            } else { Swal.fire('Error', 'Could not save review: ' + data.msg, 'error'); }
        });
    }

    function switchTab(t) {
        document.getElementById('content-ongoing').style.display = (t === 'ongoing') ? 'block' : 'none';
        document.getElementById('content-history').style.display = (t === 'history') ? 'block' : 'none';
        document.getElementById('btn-ongoing').classList.toggle('active', t === 'ongoing');
        document.getElementById('btn-history').classList.toggle('active', t === 'history');
    }
</script>

<?php include "footer.php"; ?>