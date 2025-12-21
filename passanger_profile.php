<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect user to login page if session is not set
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// --- [KEEPING FUNCTIONALITY] Handle Delete Favourite Driver Logic ---
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

// Retrieve current student information
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Calculate stats
$booking_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE student_id = '$student_id'")->fetch_assoc()['total'];
$review_count_query = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE passenger_id = '$student_id'");
$review_count = $review_count_query ? $review_count_query->fetch_assoc()['total'] : 0;

// Retrieve latest 3 bookings
$history_sql = "SELECT * FROM bookings WHERE student_id = '$student_id' ORDER BY date_time DESC LIMIT 3";
$history_result = $conn->query($history_sql);

// Retrieve favourite drivers
$fav_sql = "SELECT f.id as fav_record_id, f.*, d.name, d.car_model FROM favourite_drivers f 
            JOIN drivers d ON f.driver_id = d.id 
            WHERE f.student_id = '$student_id'";
$fav_result = false; 
if($conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0) {
    $fav_result = $conn->query($fav_sql);
}

include "header.php"; 
?>

<style>
    .profile-container {
        max-width: 850px; 
        margin: 0 auto;   
        padding-bottom: 30px; /* Footer padding kept small as requested */
    }

    /* --- [IMAGE MATCHED DESIGN] Profile Header --- */
    .profile-header {
        background: white;
        padding: 25px 30px;
        border-radius: 15px; 
        box-shadow: 0 2px 15px rgba(0,0,0,0.03); 
        margin-bottom: 30px;
        
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap; 
        gap: 20px;
        border: 1px solid #f9f9f9;
    }

    /* Left: Avatar & Info */
    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .avatar-circle {
        width: 70px; 
        height: 70px;
        background-color: #e0f2f1; 
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #009688; 
        font-size: 2rem; 
        flex-shrink: 0;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .profile-name {
        font-size: 1rem; 
        font-weight: 800;
        color: #222;
        margin: 0 0 5px 0;
        line-height: 1;
    }

    .profile-phone {
        color: #777;
        font-size: 1rem; 
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .phone-edit-icon {
        color: #999;
        font-size: 0.9rem; 
        cursor: pointer;
        transition: color 0.2s;
    }
    .phone-edit-icon:hover { color: #009688; }

    /* Right: Stats & Button */
    .header-right {
        display: flex;
        align-items: center;
        gap: 25px; 
    }

    .stats-row {
        display: flex;
        gap: 30px; 
        text-align: center;
    }

    .stat-item { display: flex; flex-direction: column; align-items: center; }
    .stat-num { font-weight: 800; font-size: 1.2rem; color: #222; }
    .stat-label { font-size: 0.75rem; color: #999; letter-spacing: 0.5px; margin-top: 2px; }

    /* Vertical Divider Line */
    .divider-line {
        width: 1px;
        height: 40px;
        background-color: #eee;
    }

    /* Edit Button (Oval Outline) */
    .btn-edit-pill {
        border: 2px solid #00bfa5; 
        color: #009688;
        background: transparent;
        padding: 8px 30px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 1rem;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-edit-pill:hover {
        background-color: #e0f2f1;
    }

    /* --- Section Headers --- */
    .section-header-blue {
        background-color: #005A9C; 
        color: white;
        padding: 8px 20px; 
        border-radius: 50px; 
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        /* Consistent Top Spacing for all headers */
        margin: 40px 0 15px 0; 
        
        font-weight: bold;
        font-size: 1rem;
        box-shadow: 0 3px 8px rgba(0, 90, 156, 0.2); 
        text-decoration: none; 
    }
    .section-header-blue:hover {
        background-color: #004a80; 
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    /* --- Scroll Container --- */
    .favorites-scroll {
        display: flex;
        overflow-x: auto;
        gap: 12px; 
        padding-bottom: 10px;
        scrollbar-width: none;
        margin-bottom: 10px;
    }
    .favorites-scroll::-webkit-scrollbar { display: none; }

    .fav-card {
        min-width: 120px; 
        background: white;
        border-radius: 12px;
        padding: 12px; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid #f0f0f0;
        position: relative;
        transition: transform 0.2s;
    }
    .fav-card:hover { transform: translateY(-3px); }

    .fav-card img {
        width: 45px !important; 
        height: 45px !important;
        margin-bottom: 8px !important;
    }

    .btn-delete-fav {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(254, 226, 226, 0.8);
        color: #ef4444;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.7rem;
    }

    /* --- [MODIFIED] History List Spacing Fix --- */
    .history-list {
        /* Changed from 10px to 30px to match the upper gap */
        margin-bottom: 30px;
    }

    .history-item {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border-left: 4px solid #eee;
    }
    .history-item:hover { border-left-color: #005A9C; }

    .status-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: bold;
    }
    .status-Pending { background: #fff3cd; color: #856404; }
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
                        <i class="fa-solid fa-phone" style="font-size: 0.8rem; margin-right: 5px;"></i>
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
                            <button type="submit" class="btn-delete-fav">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>

                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fav['name']); ?>&background=random" alt="Driver">
                        <div style="font-weight:bold; font-size:0.85rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo $fav['name']; ?></div>
                        <div style="color:#888; font-size:0.75rem;"><?php echo $fav['car_model']; ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="color: #999; font-style: italic; width: 100%; text-align: center; padding: 15px; font-size: 0.85rem;">
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
                            <div style="font-weight: bold; font-size: 0.95rem;"><?php echo $row['destination']; ?></div>
                            <div style="color: #888; font-size: 0.75rem; margin-top: 3px;">
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
                <p style="color: #999; text-align: center; padding: 15px; font-size: 0.85rem;">No ride history found.</p>
            <?php endif; ?>
        </div>
        
        <div class="section-header-blue">
            <span>My Reviews</span>
            <i class="fa-regular fa-star"></i>
        </div>
        
        <div style="text-align: center; color: #999; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
            <i class="fa-regular fa-comment-dots" style="font-size: 2rem; margin-bottom: 10px; color: #ccc;"></i>
            <p style="margin: 0; font-size: 0.85rem;">Your feedback helps the community!</p>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>