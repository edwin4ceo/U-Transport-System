<?php
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// Fetch ALL Reviews for this passenger
$reviews_sql = "
    SELECT 
        r.rating, 
        r.comment, 
        r.created_at, 
        d.full_name AS driver_name,
        v.vehicle_model,
        v.plate_number
    FROM reviews r
    LEFT JOIN drivers d ON r.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE r.passenger_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($reviews_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

include "header.php"; 
?>

<style>
    .reviews-wrapper {
        max-width: 850px; 
        margin: 0 auto;
        padding: 30px 20px;
        min-height: calc(100vh - 160px); 
        background: #f8f9fa;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .review-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .review-card {
        background: white;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
    }
    .review-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .driver-info {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .driver-avatar {
        width: 40px; height: 40px;
        background: #edf2f7;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #718096; font-size: 18px;
    }
    .driver-name {
        font-weight: 700; color: #2d3748; font-size: 15px;
    }
    .car-info {
        font-size: 12px; color: #718096; margin-top: 2px;
    }
    
    .review-date {
        font-size: 12px; color: #a0aec0;
    }

    .star-display {
        color: #f59e0b;
        font-size: 13px;
        margin: 8px 0;
    }
    
    .review-comment {
        font-size: 14px;
        color: #4a5568;
        line-height: 1.5;
        background: #f7fafc;
        padding: 10px;
        border-radius: 8px;
        margin-top: 5px;
        font-style: italic;
    }

    .empty-state {
        text-align: center; padding: 40px; 
        background: white; border-radius: 14px; border: 1px dashed #cbd5e0; 
        color: #a0aec0;
    }
</style>

<div class="reviews-wrapper">
    <div class="page-title">
        <a href="passanger_profile.php" style="color:#2d3748; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i></a>
        My Reviews History
    </div>

    <div class="review-list">
        <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
            <?php while($rv = $reviews_result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="driver-info">
                            <div class="driver-avatar"><i class="fa-solid fa-user-tie"></i></div>
                            <div>
                                <div class="driver-name"><?php echo htmlspecialchars($rv['driver_name']); ?></div>
                                <div class="car-info">
                                    <?php echo htmlspecialchars(($rv['vehicle_model'] ?? 'Car') . ' • ' . ($rv['plate_number'] ?? '')); ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">
                            <?php echo date("d M Y, h:i A", strtotime($rv['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="star-display">
                        <?php 
                        for($i=0; $i<$rv['rating']; $i++) echo '<i class="fa-solid fa-star"></i>';
                        for($i=$rv['rating']; $i<5; $i++) echo '<i class="fa-regular fa-star" style="color:#e2e8f0;"></i>';
                        ?>
                        <span style="color:#718096; font-size:12px; margin-left:5px;">(<?php echo $rv['rating']; ?>.0)</span>
                    </div>

                    <?php if(!empty($rv['comment'])): ?>
                        <div class="review-comment">"<?php echo htmlspecialchars($rv['comment']); ?>"</div>
                    <?php else: ?>
                        <div style="font-size:12px; color:#cbd5e0; margin-top:5px;">No comment provided.</div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-star" style="font-size: 30px; margin-bottom: 10px;"></i>
                <p>You haven't submitted any reviews yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "footer.php"; ?>