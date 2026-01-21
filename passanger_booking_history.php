<?php
// ==========================================
// SECTION 1: SETUP & AUTHENTICATION
// ==========================================

// Start session
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// Check if user is logged in
if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: FETCH HISTORY & DRIVER DETAILS
// ==========================================
$rides = [];

// [UPDATED SQL] Added driver details: phone, email, gender, bio, profile_image, vehicle_color
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.passengers,
        b.remark,
        b.status,
        b.vehicle_type,
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        d.email AS driver_email,
        d.gender AS driver_gender,
        d.bio AS driver_bio,
        d.profile_image,
        v.plate_number,
        v.vehicle_model,
        v.vehicle_color
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.student_id = ?
    ORDER BY b.date_time DESC, b.id DESC
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rides[] = $row;
    $stmt->close();
}

include "header.php"; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* 1. Global Layout & Animation */
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }

    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }

    .history-wrapper {
        max-width: 850px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        position: relative;
    }

    /* 2. Buttons */
    .btn-back-floating {
        position: absolute; left: 20px; top: 40px;
        background: white; color: #64748b; padding: 10px 20px;
        border-radius: 50px; font-size: 14px; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
        z-index: 10; transition: all 0.3s ease;
    }
    .btn-back-floating:hover {
        transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 75, 130, 0.1);
        color: #004b82; border-color: #e0f2fe;
    }

    /* 3. Header */
    .page-header { text-align: center; margin-bottom: 30px; padding-top: 15px; }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .page-header p { margin: 5px 0 0; color: #64748b; font-size: 15px; }

    /* 4. Search Bar */
    .search-container {
        position: relative !important;
        max-width: 500px !important;
        margin: 0 auto 40px !important;
        height: 50px !important;
    }
    .search-input {
        width: 100% !important; height: 100% !important;
        padding-left: 55px !important; padding-right: 20px !important;
        border-radius: 50px !important; border: 1px solid #e2e8f0 !important;
        background: #fff !important; font-size: 15px !important; color: #333 !important;
        font-family: 'Poppins', sans-serif !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important;
        outline: none !important; box-sizing: border-box !important;
    }
    .search-input:focus { border-color: #004b82 !important; box-shadow: 0 8px 25px rgba(0, 75, 130, 0.1) !important; }
    .search-icon {
        position: absolute !important; left: 20px !important; top: 50% !important;
        transform: translateY(-50%) !important; color: #94a3b8 !important; font-size: 18px !important;
        pointer-events: none !important; z-index: 10 !important; transition: 0.3s !important;
    }
    .search-input:focus + .search-icon { color: #004b82 !important; }

    /* 5. History Card */
    .history-card { 
        background: #ffffff; border-radius: 20px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.03); 
        padding: 30px; margin-bottom: 25px; 
        border: 1px solid #f1f5f9; transition: transform 0.3s ease;
        position: relative; overflow: hidden;
    }
    .history-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0, 75, 130, 0.08); }

    .card-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #e2e8f0; }
    .h-date { font-weight: 600; color: #64748b; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .h-id { font-family: monospace; font-size: 13px; color: #94a3b8; background: #f8fafc; padding: 4px 8px; border-radius: 6px; }

    .route-container { position: relative; padding-left: 20px; margin-bottom: 20px; }
    .route-line { position: absolute; left: 5px; top: 8px; bottom: 25px; width: 2px; background: #e2e8f0; }
    .route-item { position: relative; margin-bottom: 20px; }
    .route-item:last-child { margin-bottom: 0; }
    
    .route-dot { 
        width: 10px; height: 10px; border-radius: 50%; position: absolute; left: -19px; top: 6px; 
        border: 2px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; z-index: 2;
    }
    .route-dot.pickup { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .route-dot.drop { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }

    .route-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 2px; letter-spacing: 0.5px; }
    .route-val { font-size: 15px; color: #1e293b; font-weight: 600; line-height: 1.4; }

    .info-grid { 
        background: #f8fafc; border-radius: 12px; padding: 15px 20px; 
        display: grid; grid-template-columns: 1fr 1fr; gap: 15px; 
        border: 1px solid #f1f5f9; margin-bottom: 20px;
    }
    .info-box h5 { margin: 0 0 4px; font-size: 12px; color: #64748b; font-weight: 600; }
    .info-box p { margin: 0; font-size: 14px; color: #334155; font-weight: 600; }
    .info-box p i { color: #004b82; margin-right: 6px; width: 16px; text-align: center; }

    .card-footer { display: flex; justify-content: space-between; align-items: center; }
    .status-badge { 
        padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; 
        text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;
    }
    .st-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .st-completed { background: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }
    .st-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    .st-expired { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* VIEW NOTE BUTTON */
    .btn-view-note {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff; color: #64748b; border: 1px solid #e2e8f0;
        padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .btn-view-note:hover { background: #f1f5f9; color: #004b82; border-color: #004b82; transform: translateY(-1px); }

    /* 6. MODAL OVERLAY (Shared for both Modals) */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
        z-index: 3000; display: none;
        justify-content: center; align-items: center;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.show { display: flex !important; opacity: 1; }

    .remark-card, .driver-modal-content {
        background: #fff; width: 90%; max-width: 400px;
        border-radius: 24px; padding: 35px 30px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        transform: scale(0.9); transition: transform 0.3s ease;
        text-align: center;
    }
    .modal-overlay.show .remark-card, 
    .modal-overlay.show .driver-modal-content { transform: scale(1); }

    /* Remark Modal Specifics */
    .remark-title { font-size: 24px; font-weight: 700; color: #004b82; margin-top: 0; margin-bottom: 20px; }
    .remark-content-box {
        background: #f8fafc; border-radius: 12px; padding: 20px;
        color: #475569; font-size: 15px; line-height: 1.6;
        font-style: italic; margin-bottom: 25px; border: 1px dashed #cbd5e1;
    }

    /* Driver Modal Specifics (Larger width) */
    .driver-modal-content { max-width: 500px; }
    .m-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #e0f2fe; margin-bottom: 15px; }
    .m-name { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
    .m-bio { font-size: 15px !important; color: #94a3b8 !important; margin: 4px auto 25px !important; font-weight: 400 !important; font-style: italic; line-height: 1.4; max-width: 85%; }
    .m-detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 15px; text-align: left; }
    .m-detail-row:last-child { border-bottom: none; }
    .m-label { color: #64748b; font-weight: 500; }
    .m-val { color: #333; font-weight: 600; }

    .btn-close-modal {
        background: #004b82; color: white; border: none;
        padding: 12px 40px; border-radius: 10px; font-size: 15px; font-weight: 600;
        cursor: pointer; transition: 0.2s; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2);
    }
    .btn-close-modal:hover { background: #003660; transform: translateY(-2px); }

    /* Clickable Driver Name */
    .clickable-driver { cursor: pointer; transition: 0.2s; }
    .clickable-driver:hover { color: #004b82; text-decoration: underline; }

    #noResults { display: none; text-align: center; padding: 40px; color: #94a3b8; }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .empty-state i { font-size: 48px; margin-bottom: 20px; color: #cbd5e0; }

    @media (max-width: 768px) {
        .btn-back-floating { top: 20px; left: 20px; }
        .page-header { margin-bottom: 30px; padding-top: 60px; }
        .info-grid { grid-template-columns: 1fr; }
        .card-footer { flex-direction: row; align-items: center; } 
    }
</style>

<div class="history-wrapper">
    
    <a href="passanger_profile.php" class="btn-back-floating">
        <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>

    <div class="page-header">
        <h1>Ride History</h1>
        <p>A record of your past journeys and requests.</p>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by ID, Location, Driver or Date..." onkeyup="filterHistory()">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
    </div>

    <div id="historyList">
        <?php if (count($rides) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-clock"></i>
                <p>You haven't taken any trips yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rides as $row): ?>
                <?php
                    $rawStatus = strtoupper($row['status']);
                    $st_class = 'st-pending';
                    if(in_array($rawStatus, ['COMPLETED', 'ACCEPTED', 'APPROVED'])) $st_class = 'st-completed';
                    if(in_array($rawStatus, ['CANCELLED', 'REJECTED'])) $st_class = 'st-cancelled';
                    if($rawStatus == 'EXPIRED') $st_class = 'st-expired';

                    $driverDisplay = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : "Pending Assignment";
                    $dateDisplay = date("d M Y, h:i A", strtotime($row['date_time']));
                    
                    // Logic for Remark Message
                    $finalMessage = "No remark provided for this order.";
                    if (in_array($rawStatus, ['CANCELLED', 'REJECTED'])) {
                        $finalMessage = "This order has been cancelled.";
                    } elseif (!empty(trim($row['remark']))) {
                        $finalMessage = htmlspecialchars($row['remark'], ENT_QUOTES, 'UTF-8');
                    }

                    // Prepare Driver Data for Modal (if driver exists)
                    $hasDriver = !empty($row['driver_name']);
                    $driver_img_src = "https://ui-avatars.com/api/?name=".urlencode($driverDisplay)."&background=random&color=fff";
                    if($hasDriver && !empty($row['profile_image']) && file_exists("uploads/" . $row['profile_image'])) {
                        $driver_img_src = "uploads/" . $row['profile_image'];
                    }
                ?>
                <div class="history-card">
                    <span style="display:none;"><?php echo $dateDisplay . ' ' . $driverDisplay . ' ' . $row['booking_id'] . ' ' . $row['pickup_point'] . ' ' . $row['destination']; ?></span>

                    <div class="card-top-row">
                        <span class="h-date"><i class="fa-regular fa-calendar"></i> <?php echo $dateDisplay; ?></span>
                        <span class="h-id">#<?php echo $row['booking_id']; ?></span>
                    </div>

                    <div class="route-container">
                        <div class="route-line"></div>
                        <div class="route-item">
                            <div class="route-dot pickup"></div>
                            <div class="route-label">Pick-up</div>
                            <div class="route-val"><?php echo htmlspecialchars($row['pickup_point']); ?></div>
                        </div>
                        <div class="route-item">
                            <div class="route-dot drop"></div>
                            <div class="route-label">Destination</div>
                            <div class="route-val"><?php echo htmlspecialchars($row['destination']); ?></div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-box">
                            <h5>Driver</h5>
                            <p>
                                <i class="fa-solid fa-user-tie"></i> 
                                <?php if($hasDriver): ?>
                                    <span class="clickable-driver" onclick="openDriverModal(
                                        '<?php echo $driver_img_src; ?>',
                                        '<?php echo htmlspecialchars($row['driver_name']); ?>',
                                        '<?php echo htmlspecialchars($row['driver_phone'] ?? 'N/A'); ?>',
                                        '<?php echo htmlspecialchars($row['driver_email'] ?? 'N/A'); ?>',
                                        '<?php echo htmlspecialchars($row['driver_gender'] ?? 'Not Specified'); ?>',
                                        '<?php echo htmlspecialchars($row['driver_bio'] ?? 'No bio available.'); ?>',
                                        '<?php echo htmlspecialchars($row['vehicle_model'] ?? 'N/A'); ?>',
                                        '<?php echo htmlspecialchars($row['plate_number'] ?? 'N/A'); ?>',
                                        '<?php echo htmlspecialchars($row['vehicle_color'] ?? 'N/A'); ?>'
                                    )">
                                        <?php echo $driverDisplay; ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo $driverDisplay; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="info-box">
                            <h5>Details</h5>
                            <p><i class="fa-solid fa-car"></i> <?php echo $row['vehicle_type']; ?> (<?php echo $row['passengers']; ?> Pax)</p>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span class="status-badge <?php echo $st_class; ?>"><?php echo $rawStatus; ?></span>
                        
                        <button type="button" class="btn-view-note" onclick="openRemarkModal('<?php echo $finalMessage; ?>')">
                            <i class="fa-regular fa-comment-dots"></i> View Note
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="noResults">
        <i class="fa-solid fa-ghost" style="font-size: 32px; margin-bottom: 10px; display:block;"></i>
        No matching records found.
    </div>

</div>

<div id="remarkModal" class="modal-overlay" onclick="closeModal('remarkModal')">
    <div class="remark-card" onclick="event.stopPropagation()">
        <h3 class="remark-title">Trip Note</h3>
        <div class="remark-content-box">"<span id="modalRemarkText"></span>"</div>
        <button class="btn-close-modal" onclick="closeModal('remarkModal', true)">Close</button>
    </div>
</div>

<div id="driverModal" class="modal-overlay" onclick="closeModal('driverModal')">
    <div class="driver-modal-content" onclick="event.stopPropagation()">
        <span class="close-modal" onclick="closeModal('driverModal', true)" style="position:absolute; top:15px; right:20px; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</span>
        
        <img id="m_img" class="m-avatar" src="" alt="Avatar">
        <h3 id="m_name" class="m-name">Driver Name</h3>
        <p id="m_bio" class="m-bio">Driver bio...</p>

        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-phone"></i> Phone</span><span class="m-val" id="m_phone">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-envelope"></i> Email</span><span class="m-val" id="m_email">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-venus-mars"></i> Gender</span><span class="m-val" id="m_gender">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-car"></i> Vehicle</span><span class="m-val"><span id="m_color"></span> <span id="m_car"></span></span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-id-card"></i> Plate No</span><span class="m-val" id="m_plate" style="text-transform:uppercase; background:#f1f5f9; padding:2px 6px; border-radius:4px;">---</span></div>

        <button class="btn-close-modal" onclick="closeModal('driverModal', true)" style="margin-top:25px;">Close</button>
    </div>
</div>

<script>
    // --- 1. Search Filter Logic ---
    function filterHistory() {
        var input, filter, list, cards, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        list = document.getElementById("historyList");
        cards = list.getElementsByClassName("history-card");
        var visibleCount = 0;

        for (i = 0; i < cards.length; i++) {
            txtValue = cards[i].textContent || cards[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                cards[i].style.display = "";
                visibleCount++;
            } else {
                cards[i].style.display = "none";
            }
        }

        var noRes = document.getElementById("noResults");
        if (visibleCount === 0 && cards.length > 0) {
            noRes.style.display = "block";
        } else {
            noRes.style.display = "none";
        }
    }

    // --- 2. Open Remark Modal ---
    function openRemarkModal(text) {
        document.getElementById('modalRemarkText').innerText = text;
        const modal = document.getElementById('remarkModal');
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('show'); }, 10);
    }

    // --- 3. Open Driver Modal ---
    function openDriverModal(img, name, phone, email, gender, bio, car, plate, color) {
        document.getElementById('m_img').src = img;
        document.getElementById('m_name').innerText = name;
        document.getElementById('m_phone').innerText = phone;
        document.getElementById('m_email').innerText = email;
        document.getElementById('m_gender').innerText = gender;
        document.getElementById('m_bio').innerText = '"' + bio + '"';
        document.getElementById('m_car').innerText = car;
        document.getElementById('m_plate').innerText = plate;
        document.getElementById('m_color').innerText = color;

        const modal = document.getElementById('driverModal');
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('show'); }, 10);
    }

    // --- 4. Generic Close Function ---
    function closeModal(modalId, force = false) {
        if (force || event.target.id === modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        }
    }
</script>

<?php include "footer.php"; ?>