<?php
session_start();
include "db_connect.php";
include "function.php";

// Check login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// 1. Fetch Student Details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// 2. Fetch Stats (Total Bookings & Reviews)
$booking_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE student_id = '$student_id'")->fetch_assoc()['total'];
// Assuming reviews table has passenger_id matching student_id (or user_id logic)
// For now, we count reviews based on passenger_id (you might need to adjust based on your table structure)
$review_count = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE passenger_id = '$student_id'")->fetch_assoc()['total'];

// 3. Fetch Recent History (Limit 3)
$history_sql = "SELECT * FROM bookings WHERE student_id = '$student_id' ORDER BY date_time DESC LIMIT 3";
$history_result = $conn->query($history_sql);

// 4. Fetch Favorites (Empty for now, logic ready)
$fav_sql = "SELECT f.*, d.name, d.car_model FROM favourite_drivers f 
            JOIN drivers d ON f.driver_id = d.id 
            WHERE f.student_id = '$student_id'";
// Note: If 'drivers' table doesn't exist yet, this query will fail. 
// I will wrap it in a try-catch or simple check to prevent crashing if drivers table is missing.
$fav_result = false; 
if($conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0) {
    $fav_result = $conn->query($fav_sql);
}

include "header.php"; 
?>

<style>
    .profile-header {
        text-align: center;
        margin-bottom: 20px;
        background: white;
        padding: 20px;
        border-radius: 0 0 15px 15px; /* Rounded bottom corners */
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        background-color: #e0f2f1;
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #009688; /* Teal color */
        font-size: 2.5rem;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .profile-name {
        font-size: 1.4rem;
        font-weight: bold;
        color: #333;
        margin: 0;
    }

    .profile-phone {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    .stats-row {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-num {
        font-weight: bold;
        font-size: 1.1rem;
        display: block;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #888;
    }

    .btn-edit-profile {
        background-color: #00b140; /* Grab Green */
        color: white;
        border: none;
        padding: 10px 40px;
        border-radius: 20px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }

    .btn-edit-profile:hover {
        background-color: #008c33;
    }

    .section-title {
        font-weight: bold;
        margin-top: 30px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title a {
        font-size: 0.9rem;
        color: #005A9C;
        text-decoration: none;
    }

    /* Horizontal Scroll for Favorites */
    .favorites-scroll {
        display: flex;
        overflow-x: auto;
        gap: 15px;
        padding-bottom: 10px;
    }

    .fav-card {
        min-width: 140px;
        background: white;
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        text-align: center;
    }

    .fav-card img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-bottom: 10px;
        object-fit: cover;
    }

    .history-item {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
    }
    .status-Pending { background: #fff3cd; color: #856404; }
    .status-Completed { background: #d4edda; color: #155724; }
    .status-Cancelled { background: #f8d7da; color: #721c24; }
</style>

<div class="profile-header">
    <div class="avatar-circle">
        <i class="fa-regular fa-user"></i>
    </div>
    
    <h2 class="profile-name"><?php echo htmlspecialchars($student['name']); ?></h2>
    
    <p class="profile-phone">
        <?php echo $student['phone'] ? htmlspecialchars($student['phone']) : 'No phone number added'; ?>
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

    <a href="profile_edit.php" class="btn-edit-profile">Edit profile</a>
</div>

<div class="container" style="padding-bottom: 100px;"> <div class="section-title">
        <span>Favourite Drivers</span>
        </div>
    
    <div class="favorites-scroll">
        <?php if($fav_result && $fav_result->num_rows > 0): ?>
            <?php while($fav = $fav_result->fetch_assoc()): ?>
                <div class="fav-card">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fav['name']); ?>&background=random" alt="Driver">
                    <div style="font-weight:bold; font-size:0.9rem;"><?php echo $fav['name']; ?></div>
                    <div style="color:#888; font-size:0.8rem;"><?php echo $fav['car_model']; ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="color: #999; font-style: italic; width: 100%; text-align: center;">
                No favourite drivers yet.
            </div>
        <?php endif; ?>
    </div>

    <div class="section-title">
        <span>Recent History</span>
        <a href="passanger_booking_history.php"><i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <div class="history-list">
        <?php if($history_result->num_rows > 0): ?>
            <?php while($row = $history_result->fetch_assoc()): ?>
                <div class="history-item">
                    <div>
                        <div style="font-weight: bold;"><?php echo $row['destination']; ?></div>
                        <div style="color: #888; font-size: 0.8rem;">
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
            <p style="color: #999; text-align: center;">No ride history found.</p>
        <?php endif; ?>
    </div>
    
    <div class="section-title">
        <span>My Reviews</span>
    </div>
    <div style="text-align: center; color: #999; padding: 20px; background: white; border-radius: 10px;">
        <i class="fa-regular fa-comment-dots" style="font-size: 2rem; margin-bottom: 10px;"></i>
        <p>Your feedback helps the community!</p>
    </div>

</div>

<?php include "footer.php"; ?>