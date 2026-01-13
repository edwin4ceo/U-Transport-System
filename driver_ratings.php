<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Security Check
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// 2. Fetch Data (Removed reply fields from query)
$ratings = [];
$stmt = $conn->prepare("
    SELECT 
        r.rating, 
        r.comment, 
        r.created_at,
        s.name AS passenger_name,
        b.pickup_point,
        b.destination
    FROM reviews r
    LEFT JOIN students s ON r.passenger_id = s.student_id
    LEFT JOIN bookings b ON r.booking_id = b.id
    WHERE r.driver_id = ?
    ORDER BY r.created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    $stmt->close();
}

include "header.php";
?>

<style>
/* Clean & Minimalist Styles */
.ratings-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 800px; margin: 0 auto; background: #f5f7fb; font-family: 'Inter', sans-serif; }
.ratings-header { margin-bottom: 25px; }
.ratings-header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; }
.ratings-header p { margin: 5px 0 0; font-size: 14px; color: #718096; }

.avg-rating-box { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 10px; margin-top: 15px; }
.avg-score { font-size: 20px; font-weight: 800; color: #d69e2e; }

.rating-item { background: white; border-radius: 16px; padding: 20px; margin-bottom: 15px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
.ri-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.ri-name { font-weight: 700; color: #2d3748; font-size: 15px; }
.ri-date { font-size: 12px; color: #a0aec0; }
.ri-stars { color: #f6ad55; font-size: 13px; }

.ri-route { font-size: 12px; color: #718096; background: #f8fafc; padding: 6px 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #edf2f7; }
.ri-comment { font-size: 14px; color: #4a5568; line-height: 1.6; font-style: italic; color: #2d3748; }
.empty-state { text-align: center; padding: 60px 20px; color: #cbd5e0; }
</style>

<div class="ratings-wrapper">
    <div class="ratings-header">
        <h1>Feedback & Reviews</h1>
        <p>Review history from your passengers.</p>

        <?php if (count($ratings) > 0): ?>
            <?php
            $total = 0; foreach ($ratings as $r) $total += (int)$r['rating'];
            $avg = $total / count($ratings);
            ?>
            <div class="avg-rating-box">
                <i class="fa-solid fa-star" style="color:#f6ad55;"></i>
                <span class="avg-score"><?php echo number_format($avg, 1); ?></span>
                <span style="color:#718096; font-size:13px;">Overall Rating (<?php echo count($ratings); ?> reviews)</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="ratings-list">
        <?php if (empty($ratings)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-face-meh" style="font-size: 48px; margin-bottom: 10px;"></i>
                <p>No ratings yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($ratings as $row): ?>
                <div class="rating-item">
                    <div class="ri-top">
                        <div class="ri-name">
                            <i class="fa-solid fa-user-circle" style="color:#cbd5e0;"></i> 
                            <?php echo htmlspecialchars($row['passenger_name'] ?? 'Anonymous Student'); ?>
                        </div>
                        <div class="ri-stars">
                            <?php
                            $stars = (int)$row['rating'];
                            for ($i = 0; $i < $stars; $i++) echo '<i class="fa-solid fa-star"></i>';
                            for ($i = $stars; $i < 5; $i++) echo '<i class="fa-solid fa-star" style="color:#e2e8f0;"></i>';
                            ?>
                        </div>
                    </div>

                    <div class="ri-route">
                        <i class="fa-solid fa-location-arrow" style="font-size: 10px;"></i>
                        Trip: <?php echo htmlspecialchars($row['pickup_point']); ?> → <?php echo htmlspecialchars($row['destination']); ?>
                        <div class="ri-date"><?php echo date("d M Y", strtotime($row['created_at'])); ?></div>
                    </div>

                    <div class="ri-comment">
                        <?php if (!empty($row['comment'])): ?>
                            "<?php echo nl2br(htmlspecialchars($row['comment'])); ?>"
                        <?php else: ?>
                            <span style="color: #cbd5e0;">(No written comment left)</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include "footer.php"; ?>