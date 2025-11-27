<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect user to login page if session is not set
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// Retrieve current student information from database
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Calculate total number of bookings made by this student
$booking_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE student_id = '$student_id'")->fetch_assoc()['total'];

// Calculate total reviews written by this student
$review_count_query = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE passenger_id = '$student_id'");
$review_count = $review_count_query ? $review_count_query->fetch_assoc()['total'] : 0;

// Retrieve the latest 3 booking records for history display
$history_sql = "SELECT * FROM bookings WHERE student_id = '$student_id' ORDER BY date_time DESC LIMIT 3";
$history_result = $conn->query($history_sql);

// Retrieve list of favourite drivers associated with this student
$fav_sql = "SELECT f.*, d.name, d.car_model FROM favourite_drivers f 
            JOIN drivers d ON f.driver_id = d.id 
            WHERE f.student_id = '$student_id'";
$fav_result = false; 
// Check if the drivers table exists before querying to prevent errors
if($conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0) {
    $fav_result = $conn->query($fav_sql);
}

include "header.php"; 
?>

<style>
    /* Sets the maximum width of the page content to prevent it from being too wide on desktop */
    .profile-container {
        max-width: 800px; 
        margin: 0 auto;   
        padding-bottom: 100px;
    }

    /* Styling for the top white card containing user info */
    .profile-header {
        text-align: center;
        margin-bottom: 20px;
        background: white;
        padding: 30px 20px;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }

    /* Styling for the circular profile picture container */
    .avatar-circle {
        width: 90px; 
        height: 90px;
        background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #009688;
        font-size: 3rem; 
        border: 4px solid white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Styling for the user's display name */
    .profile-name {
        font-size: 1.5rem; 
        font-weight: 800;
        color: #333;
        margin: 0 0 5px 0;
    }

    /* Styling for the phone number display area */
    .profile-phone {
        color: #666;
        font-size: 1rem; 
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    /* Styling for the 'Add' button next to the phone number */
    .link-add-phone {
        font-size: 0.8rem;
        color: #005A9C;
        background: #e7f3fe;
        padding: 5px 12px;
        border-radius: 15px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .link-add-phone:hover {
        background: #005A9C;
        color: white;
    }

    /* Layout container for the Rides and Reviews counts */
    .stats-row {
        display: flex;
        justify-content: center;
        gap: 50px; 
        margin-bottom: 25px;
    }

    .stat-item { text-align: center; }
    
    /* Styling for the statistic numbers */
    .stat-num { font-weight: 800; font-size: 1.3rem; display: block; color: #333; }
    
    /* Styling for the statistic labels (Rides/Reviews) */
    .stat-label { font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }

    /* Styling for the main Edit Profile button */
    .btn-edit-profile {
        background: linear-gradient(to right, #00b09b, #96c93d); 
        color: white;
        border: none;
        padding: 12px 45px; 
        font-size: 1rem; 
        border-radius: 50px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(0, 176, 155, 0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-edit-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 176, 155, 0.5);
    }

    /* Styling for the section headers (Favourite, History, etc.) */
    .section-header-blue {
        background-color: #005A9C; 
        color: white;
        padding: 10px 25px;
        border-radius: 50px; 
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 30px 0 20px 0;
        font-weight: bold;
        font-size: 1.05rem;
        box-shadow: 0 4px 10px rgba(0, 90, 156, 0.2); 
        text-decoration: none; 
    }
    .section-header-blue:hover {
        background-color: #004a80; 
        transform: scale(1.01);
        transition: all 0.2s ease;
    }
    .section-header-blue i {
        font-size: 1.3rem;
        color: rgba(255,255,255,0.9);
    }

    /* Container for horizontal scrolling of favourite drivers */
    .favorites-scroll {
        display: flex;
        overflow-x: auto;
        gap: 15px;
        padding-bottom: 10px;
        scrollbar-width: none;
    }
    .favorites-scroll::-webkit-scrollbar { display: none; }

    /* Styling for individual driver cards */
    .fav-card {
        min-width: 130px;
        background: white;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid #f0f0f0;
    }

    /* Styling for individual history items */
    .history-item {
        background: white;
        border-radius: 15px;
        padding: 18px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        border-left: 5px solid #eee;
    }
    .history-item:hover { border-left-color: #005A9C; }

    /* Styling for status badges */
    .status-badge {
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: bold;
    }
    .status-Pending { background: #fff3cd; color: #856404; }
    .status-Completed { background: #d4edda; color: #155724; }
    .status-Cancelled { background: #f8d7da; color: #721c24; }
</style>

<div class="profile-container">

    <div class="profile-header">
        <div class="avatar-circle">
            <i class="fa-regular fa-user"></i>
        </div>
        
        <h2 class="profile-name"><?php echo htmlspecialchars($student['name']); ?></h2>
        
        <p class="profile-phone">
            <?php if (!empty($student['phone'])): ?>
                <span><?php echo htmlspecialchars($student['phone']); ?></span>
                <a href="passanger_profile_edit.php" style="color: #999; font-size: 0.8rem; margin-left: 5px;"><i class="fa-solid fa-pen"></i></a>
            <?php else: ?>
                <span>No phone number added</span>
                <a href="passanger_profile_edit.php" class="link-add-phone"><i class="fa-solid fa-plus"></i> Add</a>
            <?php endif; ?>
        </p>

        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-num"><?php echo $booking_count; ?></span>
                <span class="stat-label">Rides</span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?php echo $review_count; ?></span>
                <span class="stat-label">Reviews</span>
            </div>
        </div>

        <a href="passanger_profile_edit.php" class="btn-edit-profile">Edit Profile</a>
    </div>

    <div style="padding: 0 15px;">

        <div class="section-header-blue">
            <span>Favourite Drivers</span>
            <i class="fa-solid fa-circle-chevron-right"></i>
        </div>
        
        <div class="favorites-scroll">
            <?php if($fav_result && $fav_result->num_rows > 0): ?>
                <?php while($fav = $fav_result->fetch_assoc()): ?>
                    <div class="fav-card">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fav['name']); ?>&background=random" alt="Driver" style="width:55px; height:55px; border-radius:50%; margin-bottom:10px;">
                        <div style="font-weight:bold; font-size:0.9rem;"><?php echo $fav['name']; ?></div>
                        <div style="color:#888; font-size:0.8rem;"><?php echo $fav['car_model']; ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="color: #999; font-style: italic; width: 100%; text-align: center; padding: 20px; font-size: 0.9rem;">
                    No favourite drivers yet.
                </div>
            <?php endif; ?>
        </div>

        <a href="passanger_booking_history.php" class="section-header-blue">
            <span>Recent History</span>
            <i class="fa-solid fa-circle-chevron-right"></i>
        </a>

        <div class="history-list">
            <?php if($history_result->num_rows > 0): ?>
                <?php while($row = $history_result->fetch_assoc()): ?>
                    <div class="history-item">
                        <div>
                            <div style="font-weight: bold; font-size: 1rem;"><?php echo $row['destination']; ?></div>
                            <div style="color: #888; font-size: 0.8rem; margin-top: 4px;">
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
                <p style="color: #999; text-align: center; padding: 20px; font-size: 0.9rem;">No ride history found.</p>
            <?php endif; ?>
        </div>
        
        <div class="section-header-blue">
            <span>My Reviews</span>
            <i class="fa-solid fa-circle-chevron-right"></i>
        </div>
        
        <div style="text-align: center; color: #999; padding: 25px; background: white; border-radius: 15px; box-shadow: 0 3px 8px rgba(0,0,0,0.05);">
            <i class="fa-regular fa-comment-dots" style="font-size: 2.2rem; margin-bottom: 12px; color: #ccc;"></i>
            <p style="margin: 0; font-size: 0.9rem;">Your feedback helps the community!<br><small>Reviews you write will appear here.</small></p>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>