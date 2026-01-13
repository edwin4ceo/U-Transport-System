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

// --- [LOGIC] Handle Reply Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $review_id = intval($_POST['review_id']);
    $reply_text = trim($_POST['reply_text']);
    
    if (!empty($reply_text)) {
        // Update review with driver's reply
        $stmt = $conn->prepare("UPDATE reviews SET reply = ?, reply_at = NOW() WHERE review_id = ? AND driver_id = ?");
        $stmt->bind_param("sii", $reply_text, $review_id, $driver_id);
        
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('Replied!', 'Your response has been posted.', 'success');
                });
            </script>";
        } else {
            echo "<script>alert('Failed to post reply.');</script>";
        }
        $stmt->close();
    }
}
// ---------------------------------------

// 2. Fetch Data (Includes Reply field now)
$ratings = [];
$stmt = $conn->prepare("
    SELECT 
        r.review_id, 
        r.rating, 
        r.comment, 
        r.created_at,
        r.reply,
        r.reply_at,
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ... (Same Styles as before) ... */
.ratings-wrapper { min-height: calc(100vh - 160px); padding: 30px 10px 40px; max-width: 900px; margin: 0 auto; background: #f5f7fb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.ratings-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
.ratings-header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #004b82; }
.ratings-header p { margin: 5px 0 0; font-size: 14px; color: #718096; }
.avg-rating-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 50px; background: white; border: 1px solid #ecc94b; box-shadow: 0 2px 6px rgba(236, 201, 75, 0.15); }
.avg-score { font-size: 18px; font-weight: 800; color: #d69e2e; }
.avg-count { font-size: 13px; color: #718096; font-weight: 500; }
.ratings-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 0; overflow: hidden; }
.rating-item { padding: 25px; border-bottom: 1px solid #edf2f7; transition: background 0.2s; }
.rating-item:last-child { border-bottom: none; }
.ri-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.ri-user-info { display: flex; flex-direction: column; }
.ri-name { font-size: 15px; font-weight: 700; color: #2d3748; display: flex; align-items: center; gap: 8px; }
.ri-date { font-size: 12px; color: #a0aec0; margin-top: 3px; }
.ri-stars { color: #f6ad55; font-size: 14px; }
.ri-route { background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; display: inline-block; }
.route-text { font-size: 13px; color: #4a5568; display: flex; align-items: center; gap: 8px; font-weight: 500; }
.ri-comment { font-size: 14px; color: #4a5568; line-height: 1.6; background: #fff; font-style: italic; }
.empty-state { text-align: center; padding: 50px 20px; color: #a0aec0; }
.empty-state i { font-size: 40px; margin-bottom: 15px; color: #cbd5e0; display: block; }

/* --- [NEW STYLES FOR REPLY] --- */
.driver-reply-box {
    margin-top: 15px;
    padding: 12px 15px;
    background: #f0f9ff;
    border-left: 3px solid #3182ce;
    border-radius: 0 8px 8px 0;
    font-size: 13px;
    color: #2c5282;
}
.reply-label { font-weight: 700; font-size: 11px; text-transform: uppercase; color: #3182ce; margin-bottom: 4px; display:block;}
.btn-reply {
    margin-top: 10px;
    font-size: 12px;
    color: #3182ce;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex; align-items: center; gap: 5px;
}
.btn-reply:hover { text-decoration: underline; }

.reply-form { margin-top: 10px; display: none; }
.reply-textarea { 
    width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 13px; outline:none; 
    font-family: inherit; resize: vertical; min-height: 60px;
}
.reply-textarea:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }
.reply-actions { margin-top: 8px; text-align: right; }
.btn-submit-reply { background: #3182ce; color: white; border: none; padding: 6px 14px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-cancel-reply { background: transparent; color: #718096; border: none; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; margin-right: 5px; }
</style>

<div class="ratings-wrapper">
    <div class="ratings-header">
        <div>
            <h1>Feedback & Reviews</h1>
            <p>See what passengers are saying about your trips.</p>
        </div>

        <?php if (count($ratings) > 0): ?>
            <?php
            $total = 0; foreach ($ratings as $r) $total += (int)$r['rating'];
            $avg = $total / count($ratings);
            ?>
            <div class="avg-rating-badge">
                <i class="fa-solid fa-star" style="color:#f6ad55;"></i>
                <span class="avg-score"><?php echo number_format($avg, 1); ?></span>
                <span class="avg-count">/ 5.0 (<?php echo count($ratings); ?> reviews)</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="ratings-card">
        <?php if (count($ratings) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-star-half-stroke"></i>
                <h3>No ratings yet</h3>
                <p>Complete more rides to get feedback.</p>
            </div>
        <?php else: ?>
            <?php foreach ($ratings as $row): ?>
                <div class="rating-item">
                    
                    <div class="ri-top">
                        <div class="ri-user-info">
                            <div class="ri-name">
                                <i class="fa-solid fa-circle-user" style="color:#cbd5e0;"></i>
                                <?php echo htmlspecialchars($row['passenger_name'] ?? 'Passenger'); ?>
                            </div>
                            <div class="ri-date">
                                <?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($row['created_at']))); ?>
                            </div>
                        </div>
                        <div class="ri-stars">
                            <?php
                            $stars = (int)$row['rating'];
                            for ($i = 0; $i < $stars; $i++) echo '<i class="fa-solid fa-star"></i>';
                            for ($i = $stars; $i < 5; $i++) echo '<i class="fa-solid fa-star" style="color:#e2e8f0;"></i>';
                            ?>
                        </div>
                    </div>

                    <?php if (!empty($row['pickup_point'])): ?>
                        <div class="ri-route">
                            <div class="route-text">
                                <i class="fa-solid fa-route" style="color:#3182ce;"></i>
                                <span><?php echo htmlspecialchars($row['pickup_point']); ?></span>
                                <i class="fa-solid fa-arrow-right-long" style="color:#a0aec0; font-size:11px;"></i>
                                <span><?php echo htmlspecialchars($row['destination']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row['comment'])): ?>
                        <div class="ri-comment">
                            <i class="fa-solid fa-quote-left" style="color:#e2e8f0; margin-right:5px;"></i>
                            <?php echo nl2br(htmlspecialchars($row['comment'])); ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:13px; color:#cbd5e0; font-style:italic;">No written comment.</div>
                    <?php endif; ?>

                    <?php if (!empty($row['reply'])): ?>
                        <div class="driver-reply-box">
                            <span class="reply-label">Your Reply:</span>
                            <?php echo nl2br(htmlspecialchars($row['reply'])); ?>
                        </div>
                    <?php else: ?>
                        <button class="btn-reply" onclick="toggleReplyForm(<?php echo $row['review_id']; ?>)">
                            <i class="fa-solid fa-reply"></i> Reply to review
                        </button>

                        <form method="POST" class="reply-form" id="form-reply-<?php echo $row['review_id']; ?>">
                            <input type="hidden" name="review_id" value="<?php echo $row['review_id']; ?>">
                            <textarea name="reply_text" class="reply-textarea" placeholder="Write your response here..." required></textarea>
                            <div class="reply-actions">
                                <button type="button" class="btn-cancel-reply" onclick="toggleReplyForm(<?php echo $row['review_id']; ?>)">Cancel</button>
                                <button type="submit" name="submit_reply" class="btn-submit-reply">Post Reply</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleReplyForm(id) {
    var form = document.getElementById('form-reply-' + id);
    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
    }
}
</script>

<?php include "footer.php"; ?>