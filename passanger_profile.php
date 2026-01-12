<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login Session
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// --- [LOGIC] Handle Delete Favourite Driver ---
if(isset($_POST['delete_fav_id'])){
    $fav_id_to_delete = $_POST['delete_fav_id'];
    $del_stmt = $conn->prepare("DELETE FROM favourite_drivers WHERE id = ? AND student_id = ?");
    $del_stmt->bind_param("is", $fav_id_to_delete, $student_id);
    
    if($del_stmt->execute()){
        echo "<script>alert('Driver removed from favourites.'); window.location.href='passanger_profile.php';</script>";
        exit;
    }
}
// --------------------------------------------------

// 2. Retrieve Student Info
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// 3. Calculate Stats
$booking_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE student_id = '$student_id'")->fetch_assoc()['total'];
$review_count_query = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE passenger_id = '$student_id'");
$review_count = $review_count_query ? $review_count_query->fetch_assoc()['total'] : 0;

// 4. Retrieve History (Latest 3)
$history_sql = "SELECT * FROM bookings WHERE student_id = '$student_id' ORDER BY date_time DESC LIMIT 3";
$history_result = $conn->query($history_sql);

// 5. Retrieve Favourite Drivers
$fav_sql = "SELECT 
                f.id as fav_record_id, 
                f.*, 
                d.full_name as name, 
                v.vehicle_model as car_model 
            FROM favourite_drivers f 
            JOIN drivers d ON f.driver_id = d.driver_id 
            LEFT JOIN vehicles v ON d.driver_id = v.driver_id
            WHERE f.student_id = '$student_id'";

$fav_result = false; 
if($conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0) {
    $fav_result = $conn->query($fav_sql);
}

include "header.php"; 
?>

<style>
    /* --- Page Container --- */
    .profile-container {
        max-width: 850px; 
        margin: 0 auto;   
        padding-bottom: 30px; 
    }

    /* --- Header Section --- */
    .profile-header {
        background: white;
        padding: 25px 30px;
        border-radius: 16px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
        margin-bottom: 30px;
        
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap; 
        gap: 20px;
        border: 1px solid #f9f9f9;
    }

    .header-left { display: flex; align-items: center; gap: 20px; }

    .avatar-circle {
        width: 70px; height: 70px;
        background-color: #e0f2f1; 
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #009688; font-size: 2rem; flex-shrink: 0;
    }

    .user-info { display: flex; flex-direction: column; justify-content: center; }

    .profile-name {
        font-size: 2rem !important; 
        font-weight: 780 !important; 
        color: #222; margin: 0 0 3px 0; line-height: 1.2;
    }

    .profile-phone {
        color: #666; font-size: 14px !important; 
        display: flex; align-items: center; gap: 8px;
    }
    
    .profile-gender {
        color: #666; font-size: 14px !important; 
        display: flex; align-items: center; gap: 8px; margin-top: 4px;
    }

    .phone-edit-icon {
        color: #999; font-size: 12px; cursor: pointer; transition: color 0.2s;
    }
    .phone-edit-icon:hover { color: #009688; }

    .header-right { display: flex; align-items: center; gap: 25px; }

    .stats-row { display: flex; gap: 30px; text-align: center; }
    .stat-item { display: flex; flex-direction: column; align-items: center; }
    .stat-num { font-weight: 800; font-size: 20px !important; color: #222; } 
    .stat-label { font-size: 11px !important; color: #999; letter-spacing: 0.5px; margin-top: 2px; }

    .divider-line { width: 1px; height: 40px; background-color: #eee; }

    .btn-edit-pill {
        border: 2px solid #00bfa5; color: #009688; background: transparent;
        padding: 6px 25px; border-radius: 30px; font-weight: 700; font-size: 14px;
        text-decoration: none; transition: all 0.2s;
    }
    .btn-edit-pill:hover { background-color: #e0f2f1; }

    /* --- Section Headers --- */
    .section-header-blue {
        background-color: #005A9C; 
        color: white;
        padding: 12px 25px; 
        border-radius: 50px; 
        display: flex; justify-content: space-between; align-items: center;
        margin: 35px 0 15px 0; 
        font-weight: bold; font-size: 16px;
        box-shadow: 0 3px 8px rgba(0, 90, 156, 0.2); 
        text-decoration: none; 
        cursor: pointer;
    }
    .section-header-blue:hover {
        transform: scale(1.01);
        transition: all 0.2s ease;
        background-color: #004b82;
    }

    /* --- Scroll Container --- */
    .favorites-scroll {
        display: flex; overflow-x: auto; gap: 15px; 
        padding-bottom: 10px; margin-bottom: 10px;
    }
    .favorites-scroll::-webkit-scrollbar { display: none; }

    /* --- Favourite Card Style (Fixed) --- */
    .fav-card {
        min-width: 140px; 
        max-width: 140px;
        background: white;
        border-radius: 16px;
        padding: 25px 15px 15px 15px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid #f0f0f0;
        position: relative; 
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .fav-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    .fav-card img {
        width: 55px !important; 
        height: 55px !important;
        margin-bottom: 12px !important;
        border-radius: 50%;
        border: 2px solid #e0f2f1;
        object-fit: cover;
    }

    .fav-driver-name {
        font-weight: 700; 
        font-size: 14px; 
        color: #2d3748;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 4px;
    }

    .fav-car-model {
        color: #718096; 
        font-size: 11px;
        background: #f7fafc;
        padding: 2px 8px;
        border-radius: 10px;
    }

    /* --- FORCE FIX: Delete Button --- */
    /* Using specific ID-like specificity to override global button styles */
    .fav-card form {
        position: absolute !important;
        top: 8px !important;
        right: 8px !important;
        margin: 0 !important;
        padding: 0 !important;
        z-index: 10 !important;
        background: transparent !important;
        box-shadow: none !important;
        width: auto !important;
        height: auto !important;
    }

    .btn-del-floating {
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        background: #fff5f5 !important; /* Light Red Background */
        color: #e53e3e !important;       /* Red Icon */
        border: 1px solid #feb2b2 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        padding: 0 !important;
        font-size: 12px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        line-height: 1 !important;
    }
    
    .btn-del-floating:hover {
        background: #e53e3e !important;
        color: white !important;
        border-color: #e53e3e !important;
        transform: scale(1.1);
    }

    /* --- History List Styles --- */
    .history-list { margin-bottom: 30px; }
    .history-item {
        background: white; border-radius: 14px; padding: 18px;
        margin-bottom: 15px; display: flex; justify-content: space-between;
        align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 5px solid #eee;
    }
    .history-item:hover { border-left-color: #005A9C; }

    .status-badge {
        font-size: 13px; padding: 4px 10px; border-radius: 20px; font-weight: bold;
    }
    .status-Pending { background: #fff3cd; color: #856404; }
    .status-Accepted { background: #d4edda; color: #155724; }
    .status-Completed { background: #d4edda; color: #155724; }
    .status-Cancelled { background: #f8d7da; color: #721c24; }
</style>

<div class="profile-container">

    <div class="profile-header">
        
        <div class="header-left">
            <div class="avatar-circle">
                <i class="fa-regular fa-user"></i>
            </div>
            <div class="user-info">
                <h2 class="profile-name"><?php echo htmlspecialchars($student['name']); ?></h2>
                
                <div class="profile-phone">
                    <?php if (!empty($student['phone'])): ?>
                        <i class="fa-solid fa-phone" style="font-size: 12px !important; margin-right: 5px;"></i>
                        <span><?php echo htmlspecialchars($student['phone']); ?></span>
                        <a href="passanger_profile_edit.php" class="phone-edit-icon">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    <?php else: ?>
                        <a href="passanger_profile_edit.php" style="color:#009688; font-weight:bold; text-decoration:none;">
                            + Add Phone
                        </a>
                    <?php endif; ?>
                </div>

                <div class="profile-gender">
                    <?php 
                        $g_icon = ($student['gender'] == 'Female') ? 'fa-venus' : 'fa-mars';
                        $g_color = ($student['gender'] == 'Female') ? '#e91e63' : '#2196F3';
                    ?>
                    <i class="fa-solid <?php echo $g_icon; ?>" style="font-size: 14px; width: 14px; text-align: center; color: <?php echo $g_color; ?>;"></i>
                    <span><?php echo htmlspecialchars($student['gender'] ?? 'Not Specified'); ?></span>
                </div>

            </div>
        </div>

        <div class="header-right">
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-num"><?php echo $booking_count; ?></span>
                    <span class="stat-label">RIDES</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?php echo $review_count; ?></span>
                    <span class="stat-label">REVIEWS</span>
                </div>
            </div>
            
            <div class="divider-line"></div>
            
            <a href="passanger_profile_edit.php" class="btn-edit-pill">
                Edit
            </a>
        </div>
    </div>

    <div style="padding: 0 10px;">

        <div class="section-header-blue">
            <span>Favourite Drivers</span>
            <i class="fa-solid fa-heart"></i>
        </div>
        
        <div class="favorites-scroll">
            <?php if($fav_result && $fav_result->num_rows > 0): ?>
                <?php while($fav = $fav_result->fetch_assoc()): ?>
                    <div class="fav-card">
                        
                        <form method="POST" onsubmit="return confirm('Remove driver from favourites?');">
                            <input type="hidden" name="delete_fav_id" value="<?php echo $fav['fav_record_id']; ?>">
                            <button type="submit" class="btn-del-floating" title="Remove">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>

                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fav['name']); ?>&background=random" alt="Driver">
                        <div class="fav-driver-name"><?php echo htmlspecialchars($fav['name']); ?></div>
                        <div class="fav-car-model"><?php echo htmlspecialchars($fav['car_model']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="color: #999; font-style: italic; width: 100%; text-align: center; padding: 15px; font-size: 14px;">
                    No favourite drivers.
                </div>
            <?php endif; ?>
        </div>

        <a href="passanger_booking_history.php" class="section-header-blue">
            <span>Recent History</span>
            <i class="fa-solid fa-clock-rotate-left"></i>
        </a>

        <div class="history-list">
            <?php if($history_result->num_rows > 0): ?>
                <?php while($row = $history_result->fetch_assoc()): ?>
                    <div class="history-item">
                        <div>
                            <div style="font-weight: bold; font-size: 16px;"><?php echo $row['destination']; ?></div>
                            <div style="color: #888; font-size: 13px; margin-top: 4px;">
                                <i class="fa-regular fa-calendar"></i> <?php echo date("d M, h:i A", strtotime($row['date_time'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 15px; font-size: 14px;">No ride history found.</p>
            <?php endif; ?>
        </div>
        
        <a href="passanger_reviews.php" class="section-header-blue">
            <span>My Reviews</span>
            <i class="fa-solid fa-chevron-right" style="font-size:12px;"></i>
        </a>
        
        <p style="text-align:center; color:#999; font-size:13px; margin-top:5px;">
            Click above to view all your past ratings
        </p>

    </div>
</div>

<?php include "footer.php"; ?>