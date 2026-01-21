<?php
// ==========================================
// SECTION 1: SETUP & AUTHENTICATION
// ==========================================

// Start session to access logged-in user data
session_start();

// Include database connection and helper functions
include "db_connect.php";
include "function.php";

// Check if user is logged in
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// SECTION 2: HANDLE ACTIONS (DELETE FAVOURITE)
// ==========================================
if(isset($_POST['delete_fav_id'])){
    $fav_id_to_delete = $_POST['delete_fav_id'];
    
    // Prepare DELETE statement
    $del_stmt = $conn->prepare("DELETE FROM favourite_drivers WHERE id = ? AND student_id = ?");
    $del_stmt->bind_param("is", $fav_id_to_delete, $student_id);
    
    if($del_stmt->execute()){
        $_SESSION['swal_success'] = "Driver removed from favourites.";
        header("Location: passanger_profile.php");
        exit;
    }
}

// ==========================================
// SECTION 3: FETCH DATA
// ==========================================

// Fetch Student Profile Data
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch Ride Count
$booking_count_query = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE student_id = '$student_id'");
$booking_count = $booking_count_query->fetch_assoc()['total'];

// Fetch Review Count
$review_count_query = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE passenger_id = '$student_id'");
$review_count = $review_count_query ? $review_count_query->fetch_assoc()['total'] : 0;

// [UPDATED] Fetch Recent History (Last 3 Rides)
// We need MORE details now for the popup (pickup, dropoff, remark, driver name, etc.)
$history_sql = "
    SELECT 
        b.id AS booking_id,
        b.pickup_point,
        b.destination,
        b.date_time,
        b.status,
        b.remark,
        b.vehicle_type,
        b.passengers,
        d.full_name AS driver_name
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    WHERE b.student_id = '$student_id' 
    ORDER BY b.date_time DESC 
    LIMIT 3
";
$history_result = $conn->query($history_sql);

// Fetch Favourite Drivers
$fav_sql = "SELECT 
                f.id as fav_record_id, 
                f.*, 
                d.full_name as name, 
                d.phone_number,
                d.email,
                d.gender,
                d.bio,
                d.profile_image,
                v.vehicle_model as car_model,
                v.plate_number,
                v.vehicle_color,
                v.vehicle_type
            FROM favourite_drivers f 
            JOIN drivers d ON f.driver_id = d.driver_id 
            LEFT JOIN vehicles v ON d.driver_id = v.driver_id
            WHERE f.student_id = '$student_id'
            GROUP BY f.driver_id"; 

$fav_result = false; 
if($conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0) {
    $fav_result = $conn->query($fav_sql);
}

include "header.php"; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* 1. Global Layout */
    @keyframes fadeInUpPage { 
        0% { opacity: 0; transform: translateY(20px); } 
        100% { opacity: 1; transform: translateY(0); } 
    }
    
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    
    .profile-wrapper {
        max-width: 900px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 2. Header */
    .header-title { text-align: center; margin-bottom: 30px; }
    .header-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .header-title p { margin: 8px 0 0; font-size: 15px; color: #64748b; }

    /* 3. Main Card */
    .main-card {
        background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9; padding: 30px; display: flex; align-items: center;
        justify-content: space-between; margin-bottom: 40px; flex-wrap: wrap; gap: 20px;
    }
    
    .profile-left { display: flex; align-items: center; gap: 25px; }
    
    .avatar-box {
        width: 80px; height: 80px; border-radius: 50%; border: 2px solid #fff; 
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.15); overflow: hidden; 
        background: #e0f2fe; display: flex; align-items: center; justify-content: center;
    }
    .avatar-box i { font-size: 32px; color: #004b82; }
    .avatar-box img { width: 100%; height: 100%; object-fit: cover; }

    .info-box { display: flex; flex-direction: column; align-items: flex-start; }
    .info-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .info-box .phone-text { margin: 6px 0 6px 0; color: #4a5568; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }

    .gender-badge { font-size: 13px; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; margin-top: 5px; }
    .gender-male { background: #e3f2fd; color: #1976d2; }   
    .gender-female { background: #fce4ec; color: #d81b60; } 
    .gender-default { background: #f1f5f9; color: #64748b; }

    .stats-right { display: flex; align-items: center; gap: 30px; }
    .stat-item { text-align: center; }
    .stat-val { display: block; font-size: 20px; font-weight: 800; color: #004b82; }
    .stat-lbl { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

    .btn-edit {
        display: inline-block; background-color: #004b82 !important; color: white !important;
        padding: 10px 30px !important; border-radius: 50px !important; font-size: 14px !important;
        font-weight: 600 !important; text-decoration: none !important;
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2) !important; transition: all 0.3s ease !important; border: none !important;
    }
    .btn-edit:hover { background-color: #003660 !important; transform: translateY(-2px); }

    /* 4. Section Bars */
    .section-bar {
        background: #004b82; color: white; padding: 12px 25px; border-radius: 12px;
        display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px 0;
        text-decoration: none; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2);
        font-weight: 600; font-size: 16px; transition: 0.2s;
    }
    a.section-bar:hover { transform: translateY(-2px); background: #003660; box-shadow: 0 6px 15px rgba(0, 75, 130, 0.3); }
    .section-bar i { font-size: 14px; opacity: 0.8; }

    /* 5. Horizontal Scroll */
    .fav-scroll-container { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; margin-bottom: 10px; }
    .fav-scroll-container::-webkit-scrollbar { height: 6px; }
    .fav-scroll-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
    .fav-scroll-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .fav-card {
        min-width: 150px; background: #fff; border-radius: 16px;
        padding: 30px 10px 20px 10px; text-align: center; border: 1px solid #f1f5f9;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); position: relative;
        transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
    }
    .fav-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }

    .fav-card img { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 12px; object-fit: cover; border: 2px solid #e0f2f1; }
    .fav-driver-name { font-weight: 700; font-size: 14px; color: #2d3748; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 6px; }
    .fav-car-model { color: #718096; font-size: 11px; background: #f7fafc; padding: 2px 8px; border-radius: 10px; display: inline-block; }

    .btn-del-x {
        position: absolute; top: 8px; right: 8px; background: transparent;
        border: none; color: #94a3b8; font-size: 14px; cursor: pointer;
        padding: 5px; z-index: 10; transition: transform 0.2s, color 0.2s;
    }
    .btn-del-x:hover { transform: scale(1.2); color: #ef4444; }

    /* 6. History List Items */
    .history-card { 
        background: #fff; padding: 18px 25px; border-radius: 12px; 
        border: 1px solid #f1f5f9; margin-bottom: 15px; 
        display: flex; justify-content: space-between; align-items: center; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s; 
        border-left: 5px solid #eee; cursor: pointer; /* Clickable */
    }
    .history-card:hover { transform: translateX(5px); border-left-color: #004b82; background: #fdfdfd; }
    .h-info h4 { margin: 0 0 5px; font-size: 15px; color: #333; }
    .h-date { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px; }
    .status-badge { font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; }
    .st-Pending { background: #fff7ed; color: #f97316; }
    .st-Accepted, .st-Approved { background: #d4edda; color: #155724; }
    .st-Completed { background: #eff6ff; color: #3b82f6; }
    .st-Cancelled { background: #f8d7da; color: #721c24; }

    /* ================================================= */
    /* 7. GENERAL MODAL STYLES                           */
    /* ================================================= */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); 
        backdrop-filter: blur(4px); z-index: 2000;
        align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.show { display: flex !important; opacity: 1; }

    .modal-content {
        background: white; width: 90%; max-width: 550px;
        border-radius: 24px; padding: 30px; position: relative;
        text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        transform: translateY(20px); transition: transform 0.3s ease;
    }
    .modal-overlay.show .modal-content { transform: translateY(0); }

    .close-modal { position: absolute; top: 0px; right: 18px; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
    .close-modal:hover { color: #333; }

    /* 7a. Driver Modal Specifics */
    .m-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #e0f2fe; margin-bottom: 15px; }
    .m-name { font-size: 22px; font-weight: 700; color:     #1e293b; margin: 0; }
    .m-bio { font-size: 15px !important; color: #94a3b8 !important; margin: 4px auto 25px !important; font-weight: 400 !important; font-style: italic; line-height: 1.4; max-width: 85%; }
    .m-detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 15px; }
    .m-detail-row:last-child { border-bottom: none; }
    .m-label { color: #64748b; font-weight: 500; }
    .m-val { color: #333; font-weight: 600; }
    .btn-modal-action { display: block; width: 100%; padding: 14px; background: #004b82; color: white; border-radius: 50px; text-decoration: none; font-weight: 600; margin-top: 25px; box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2); }
    .btn-modal-action:hover { background: #003660; }

    /* ================================================= */
    /* 8. [NEW] RIDE DETAILS MODAL (STYLES LIKE HISTORY) */
    /* ================================================= */
    /* We re-use styles from booking_history.php but scoped to the modal */
    
    .ride-detail-card { text-align: left; /* Override center align of general modal */ }
    
    .card-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #e2e8f0; }
    .rd-date { font-weight: 600; color: #64748b; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .rd-id { font-family: monospace; font-size: 13px; color: #94a3b8; background: #f8fafc; padding: 4px 8px; border-radius: 6px; }

    .route-container { position: relative; padding-left: 20px; margin-bottom: 20px; }
    .route-line { position: absolute; left: 5px; top: 8px; bottom: 25px; width: 2px; background: #e2e8f0; }
    .route-item { position: relative; margin-bottom: 20px; }
    .route-item:last-child { margin-bottom: 0; }
    
    .route-dot { width: 10px; height: 10px; border-radius: 50%; position: absolute; left: -19px; top: 6px; border: 2px solid #fff; box-shadow: 0 0 0 2px #cbd5e1; z-index: 2; }
    .route-dot.pickup { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .route-dot.drop { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }

    .route-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 2px; letter-spacing: 0.5px; }
    .route-val { font-size: 15px; color: #1e293b; font-weight: 600; line-height: 1.4; }

    .info-grid { background: #f8fafc; border-radius: 12px; padding: 15px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; border: 1px solid #f1f5f9; margin-bottom: 20px; }
    .grid-box h5 { margin: 0 0 4px; font-size: 12px; color: #64748b; font-weight: 600; }
    .grid-box p { margin: 0; font-size: 14px; color: #334155; font-weight: 600; }
    .grid-box p i { color: #004b82; margin-right: 6px; width: 16px; text-align: center; }

    .rd-footer { display: flex; justify-content: space-between; align-items: center; }
    .rd-status-badge { padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .st-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .st-completed { background: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }
    .st-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    
    .btn-view-note-small {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff; color: #64748b; border: 1px solid #e2e8f0;
        padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .btn-view-note-small:hover { background: #f1f5f9; color: #004b82; border-color: #004b82; }
</style>

<div class="profile-wrapper">

    <div class="header-title">
        <h1>My Profile</h1>
        <p>Manage your account details.</p>
    </div>

    <div class="main-card">
        <div class="profile-left">
            <div class="avatar-box">
                <?php 
                    if(!empty($student['profile_image']) && file_exists("uploads/" . $student['profile_image'])){
                        $img_src = "uploads/" . $student['profile_image'] . "?v=" . time();
                        echo "<img src='$img_src' alt='Profile Picture'>";
                    } else {
                        echo "<i class='fa-regular fa-user'></i>";
                    }
                ?>
            </div>
            
            <div class="info-box">
                <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                <p class="phone-text">
                    <i class="fa-solid fa-phone"></i> 
                    <?php echo !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'No phone'; ?>
                </p>
                <?php 
                    $g = $student['gender'] ?? '';
                    $g_class = ($g == 'Male') ? 'gender-male' : (($g == 'Female') ? 'gender-female' : 'gender-default');
                    $g_icon = ($g == 'Male') ? 'fa-mars' : (($g == 'Female') ? 'fa-venus' : 'fa-venus-mars');
                ?>
                <span class="gender-badge <?php echo $g_class; ?>">
                    <i class="fa-solid <?php echo $g_icon; ?>"></i> <?php echo htmlspecialchars($g ?: 'Not Specified'); ?>
                </span>
            </div>
        </div>

        <div class="stats-right">
            <div class="stat-item"><span class="stat-val"><?php echo $booking_count; ?></span><span class="stat-lbl">Rides</span></div>
            <div class="stat-item"><span class="stat-val"><?php echo $review_count; ?></span><span class="stat-lbl">Reviews</span></div>
            <a href="passanger_profile_edit.php" class="btn-edit">Edit Profile</a>
        </div>
    </div>

    <div class="section-bar">
        <span><i class="fa-solid fa-heart" style="margin-right:10px;"></i> Favourite Drivers</span>
    </div>
    
    <div class="fav-scroll-container">
        <?php if($fav_result && $fav_result->num_rows > 0): ?>
            <?php while($fav = $fav_result->fetch_assoc()): 
                $db_img = $fav['profile_image'];
                if(!empty($db_img) && file_exists("uploads/" . $db_img)) {
                    $img_url = "uploads/" . $db_img;
                } else {
                    $img_url = "https://ui-avatars.com/api/?name=".urlencode($fav['name'])."&background=random&color=fff";
                }
            ?>
                <div class="fav-card" onclick="openDriverModal(this)"
                     data-name="<?php echo htmlspecialchars($fav['name']); ?>"
                     data-img="<?php echo $img_url; ?>"
                     data-phone="<?php echo htmlspecialchars($fav['phone_number'] ?? 'N/A'); ?>"
                     data-email="<?php echo htmlspecialchars($fav['email'] ?? 'N/A'); ?>"
                     data-gender="<?php echo htmlspecialchars($fav['gender'] ?? 'Not Specified'); ?>"
                     data-bio="<?php echo htmlspecialchars($fav['bio'] ?? 'No bio available.'); ?>"
                     data-car="<?php echo htmlspecialchars($fav['car_model'] ?? 'N/A'); ?>"
                     data-plate="<?php echo htmlspecialchars($fav['plate_number'] ?? 'N/A'); ?>"
                     data-color="<?php echo htmlspecialchars($fav['vehicle_color'] ?? 'N/A'); ?>"
                >
                    <form id="del-form-<?php echo $fav['fav_record_id']; ?>" method="POST" style="margin:0;">
                        <input type="hidden" name="delete_fav_id" value="<?php echo $fav['fav_record_id']; ?>">
                        <button type="button" class="btn-del-x" onclick="event.stopPropagation(); confirmDelete('<?php echo $fav['fav_record_id']; ?>')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>

                    <img src="<?php echo $img_url; ?>" alt="Driver">
                    <div class="fav-driver-name"><?php echo htmlspecialchars($fav['name']); ?></div>
                    <div class="fav-car-model"><?php echo htmlspecialchars($fav['car_model']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="width:100%; text-align:center; padding:20px; color:#94a3b8; background:#fff; border-radius:12px; border:1px dashed #cbd5e1;">
                No favourite drivers yet.
            </div>
        <?php endif; ?>
    </div>

    <a href="passanger_booking_history.php" class="section-bar">
        <span><i class="fa-solid fa-clock-rotate-left" style="margin-right:10px;"></i> Recent History</span>
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    
    <div class="history-list">
        <?php if($history_result->num_rows > 0): ?>
            <?php while($row = $history_result->fetch_assoc()): 
                $st = $row['status'];
                $st_class = ($st == 'Approved' || $st == 'APPROVED') ? 'st-Accepted' : 'st-'.$st;
                
                // Prepare Data for the Modal
                $date_display = date("d M Y, h:i A", strtotime($row['date_time']));
                $driver_display = $row['driver_name'] ? htmlspecialchars($row['driver_name']) : "Pending Assignment";
                $vehicle_display = $row['vehicle_type'] ? $row['vehicle_type'] : "Car";
                $pax_display = $row['passengers'] ? $row['passengers'] . " Pax" : "";
                
                // Logic for Remark Message
                $remark_msg = "No remark provided for this order.";
                if(in_array(strtoupper($st), ['CANCELLED', 'REJECTED'])) {
                    $remark_msg = "This order has been cancelled.";
                } elseif(!empty(trim($row['remark']))) {
                    $remark_msg = htmlspecialchars($row['remark']);
                }
            ?>
                <div class="history-card" onclick="openRideModal(this)"
                     data-id="<?php echo $row['booking_id']; ?>"
                     data-date="<?php echo $date_display; ?>"
                     data-pickup="<?php echo htmlspecialchars($row['pickup_point']); ?>"
                     data-drop="<?php echo htmlspecialchars($row['destination']); ?>"
                     data-driver="<?php echo $driver_display; ?>"
                     data-details="<?php echo $vehicle_display . ' (' . $pax_display . ')'; ?>"
                     data-status="<?php echo $st; ?>"
                     data-remark="<?php echo $remark_msg; ?>">
                     
                    <div class="h-info">
                        <h4><?php echo htmlspecialchars($row['destination']); ?></h4>
                        <div class="h-date"><i class="fa-regular fa-calendar"></i> <?php echo date("d M, h:i A", strtotime($row['date_time'])); ?></div>
                    </div>
                    <div class="h-status"><span class="status-badge <?php echo $st_class; ?>"><?php echo $st; ?></span></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:20px; color:#94a3b8; background:#fff; border-radius:12px; border:1px solid #f1f5f9;">No recent rides found.</div>
        <?php endif; ?>
    </div>

    <a href="passanger_reviews.php" class="section-bar">
        <span><i class="fa-solid fa-star" style="margin-right:10px;"></i> My Reviews</span>
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    
    <div style="text-align: center; margin-top: 30px; margin-bottom: 20px; color: #64748b; font-size: 16px; font-weight: 500; font-style: italic;">
        Please click 'My Reviews' above to view your past ratings and reviews.
    </div>

</div>

<div id="driverModal" class="modal-overlay" onclick="closeModal('driverModal')">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('driverModal', true)">&times;</span>
        
        <img id="m_img" class="m-avatar" src="" alt="Avatar">
        <h3 id="m_name" class="m-name">Driver Name</h3>
        <p id="m_bio" class="m-bio">Driver bio...</p>

        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-phone"></i> Phone</span><span class="m-val" id="m_phone">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-envelope"></i> Email</span><span class="m-val" id="m_email">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-venus-mars"></i> Gender</span><span class="m-val" id="m_gender">---</span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-car"></i> Vehicle</span><span class="m-val"><span id="m_color"></span> <span id="m_car"></span></span></div>
        <div class="m-detail-row"><span class="m-label"><i class="fa-solid fa-id-card"></i> Plate No</span><span class="m-val" id="m_plate" style="text-transform:uppercase; background:#f1f5f9; padding:2px 6px; border-radius:4px;">---</span></div>

        <button class="btn-modal-action" onclick="closeModal('driverModal', true)">Close</button>
    </div>
</div>

<div id="rideModal" class="modal-overlay" onclick="closeModal('rideModal')">
    <div class="modal-content ride-detail-card">
        <span class="close-modal" onclick="closeModal('rideModal', true)">&times;</span>
        
        <div class="card-top-row">
            <span class="rd-date"><i class="fa-regular fa-calendar"></i> <span id="r_date"></span></span>
            <span class="rd-id">#<span id="r_id"></span></span>
        </div>

        <div class="route-container">
            <div class="route-line"></div>
            <div class="route-item">
                <div class="route-dot pickup"></div>
                <div class="route-label">PICK-UP</div>
                <div class="route-val" id="r_pickup"></div>
            </div>
            <div class="route-item">
                <div class="route-dot drop"></div>
                <div class="route-label">DESTINATION</div>
                <div class="route-val" id="r_drop"></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="grid-box">
                <h5>Driver</h5>
                <p><i class="fa-solid fa-user-tie"></i> <span id="r_driver"></span></p>
            </div>
            <div class="grid-box">
                <h5>Details</h5>
                <p><i class="fa-solid fa-car"></i> <span id="r_details"></span></p>
            </div>
        </div>

        <div class="rd-footer">
            <span id="r_status_badge" class="rd-status-badge"></span>
            
            <button type="button" class="btn-view-note-small" onclick="showRemarkPopup()">
                <i class="fa-regular fa-comment-dots"></i> View Note
            </button>
        </div>
        
        <input type="hidden" id="r_current_remark">

    </div>
</div>

<script>
    // --- JS Logic: Delete Confirmation ---
    function confirmDelete(id) {
        Swal.fire({
            title: 'Remove Driver?',
            text: "Are you sure you want to remove this driver from your favourites?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('del-form-' + id).submit();
            }
        })
    }

    // --- JS Logic: Open Driver Modal ---
    function openDriverModal(element) {
        document.getElementById('m_img').src = element.getAttribute('data-img');
        document.getElementById('m_name').innerText = element.getAttribute('data-name');
        document.getElementById('m_bio').innerText = '"' + element.getAttribute('data-bio') + '"';
        document.getElementById('m_phone').innerText = element.getAttribute('data-phone');
        document.getElementById('m_email').innerText = element.getAttribute('data-email');
        document.getElementById('m_gender').innerText = element.getAttribute('data-gender'); 
        document.getElementById('m_car').innerText = element.getAttribute('data-car');
        document.getElementById('m_plate').innerText = element.getAttribute('data-plate');
        document.getElementById('m_color').innerText = element.getAttribute('data-color');

        const modal = document.getElementById('driverModal');
        modal.classList.add('show'); 
    }

    // --- JS Logic: Open Ride Modal (Recent History) ---
    function openRideModal(element) {
        // 1. Populate Data
        document.getElementById('r_id').innerText = element.getAttribute('data-id');
        document.getElementById('r_date').innerText = element.getAttribute('data-date');
        document.getElementById('r_pickup').innerText = element.getAttribute('data-pickup');
        document.getElementById('r_drop').innerText = element.getAttribute('data-drop');
        document.getElementById('r_driver').innerText = element.getAttribute('data-driver');
        document.getElementById('r_details').innerText = element.getAttribute('data-details');
        document.getElementById('r_current_remark').value = element.getAttribute('data-remark');

        // 2. Set Status Badge Logic
        const status = element.getAttribute('data-status');
        const badge = document.getElementById('r_status_badge');
        badge.innerText = status;
        
        // Reset classes
        badge.className = 'rd-status-badge'; 
        
        // Apply color based on status
        if(['PENDING'].includes(status)) badge.classList.add('st-pending');
        else if(['ACCEPTED', 'APPROVED', 'Approved'].includes(status)) badge.classList.add('st-completed'); // Green
        else if(['COMPLETED'].includes(status)) badge.classList.add('st-completed'); // Blue/Green
        else if(['CANCELLED', 'REJECTED'].includes(status)) badge.classList.add('st-cancelled'); // Red
        else badge.classList.add('st-pending'); // Default gray/orange

        // 3. Show Modal
        const modal = document.getElementById('rideModal');
        modal.classList.add('show');
    }

    // --- JS Logic: Show Remark (SweetAlert) ---
    function showRemarkPopup() {
        const remark = document.getElementById('r_current_remark').value;
        Swal.fire({
            title: 'Trip Note',
            text: remark,
            icon: 'info',
            confirmButtonColor: '#004b82',
            confirmButtonText: 'Close'
        });
    }

    // --- Generic Close Modal Function ---
    function closeModal(modalId, forceClose = false) {
        if (forceClose || event.target.id === modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
        }
    }
</script>

<?php 
// Display SweetAlert Success Message
if(isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        title: 'Success!',
        text: '<?php echo $_SESSION['swal_success']; ?>',
        icon: 'success',
        confirmButtonColor: '#004b82',
        timer: 1500,
        showConfirmButton: false
    });
</script>
<?php 
    unset($_SESSION['swal_success']);
endif; 
include "footer.php"; 
?>