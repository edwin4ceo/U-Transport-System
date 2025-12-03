<?php
session_start();
include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Fetch ratings for this driver
$ratings = [];
$stmt = $conn->prepare("
    SELECT rating_id, rating, review_text, created_at
    FROM driver_ratings
    WHERE driver_id = ?
    ORDER BY created_at DESC
");
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ratings[] = $row;
        }
    }
    $stmt->close();
}

include "header.php";
?>

<style>
.ratings-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}

.ratings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 10px;
}

.ratings-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ratings-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.ratings-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

/* Card container */
.ratings-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

/* Single rating item */
.rating-item {
    border-bottom: 1px dashed #e0e0e0;
    padding: 10px 0;
}

.rating-item:last-child {
    border-bottom: none;
}

.rating-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.rating-stars {
    font-size: 15px;
    color: #f1c40f;
    font-weight: 600;
}

.rating-date {
    font-size: 11px;
    color: #888;
}

.rating-text {
    font-size: 13px;
    color: #444;
    margin-top: 4px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 30px 10px;
    font-size: 13px;
    color: #777;
}

.empty-state i {
    font-size: 28px;
    color: #cccccc;
    margin-bottom: 8px;
}

/* Average rating badge */
.avg-rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #fff8e6;
    color: #d35400;
    font-size: 12px;
    font-weight: 600;
}

.avg-rating-badge i {
    color: #f1c40f;
}
</style>

<div class="ratings-wrapper">
    <div class="ratings-header">
        <div class="ratings-header-title">
            <h1>Ratings & Reviews</h1>
            <p>See feedback from passengers about your driving service.</p>
        </div>

        <?php if (count($ratings) > 0): ?>
            <?php
            // Calculate simple average rating
            $total = 0;
            foreach ($ratings as $r) {
                $total += (int)$r['rating'];
            }
            $avg = $total / count($ratings);
            ?>
            <div class="avg-rating-badge">
                <i class="fa-solid fa-star"></i>
                <span><?php echo number_format($avg, 1); ?> / 5.0</span>
                <span style="font-weight:400;">(<?php echo count($ratings); ?> reviews)</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="ratings-card">
        <?php if (count($ratings) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-face-smile"></i>
                <div>You do not have any ratings yet. Once passengers rate your trips, they will appear here.</div>
            </div>
        <?php else: ?>
            <?php foreach ($ratings as $row): ?>
                <div class="rating-item">
                    <div class="rating-header-row">
                        <div class="rating-stars">
                            <?php
                            $stars = (int)$row['rating'];
                            for ($i = 0; $i < $stars; $i++) {
                                echo "★";
                            }
                            for ($i = $stars; $i < 5; $i++) {
                                echo "☆";
                            }
                            ?>
                            <span style="font-size:12px;color:#555;margin-left:4px;">
                                <?php echo (int)$row['rating']; ?>/5
                            </span>
                        </div>
                        <div class="rating-date">
                            <?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($row['created_at']))); ?>
                        </div>
                    </div>
                    <?php if (!empty($row['review_text'])): ?>
                        <div class="rating-text">
                            <?php echo nl2br(htmlspecialchars($row['review_text'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="rating-text" style="color:#999;">
                            (No written review)
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include "footer.php";
?>
