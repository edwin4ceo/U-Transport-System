<?php
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])) redirect("passanger_login.php");
$student_id = $_SESSION['student_id'];

// --- [LOGIC] Handle Add Favourite Driver ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fav_driver_id'])) {
    $fav_driver_id = $_POST['add_fav_driver_id'];

    // 1. Check if driver is already in favourites
    $check_stmt = $conn->prepare("SELECT id FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
    $check_stmt->bind_param("ss", $student_id, $fav_driver_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // 2. Insert into favourites if not exists
        $ins_stmt = $conn->prepare("INSERT INTO favourite_drivers (student_id, driver_id) VALUES (?, ?)");
        $ins_stmt->bind_param("ss", $student_id, $fav_driver_id);
        
        if ($ins_stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Driver added to your favourites.',
                        icon: 'success',
                        confirmButtonColor: '#3182ce'
                    });
                });
            </script>";
        } else {
            echo "<script>alert('Error adding driver.');</script>";
        }
    } else {
        // Optional: Alert if already added
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Info',
                    text: 'This driver is already in your favourites.',
                    icon: 'info',
                    confirmButtonColor: '#3182ce'
                });
            });
        </script>";
    }
}

// --- HANDLE REVIEW UPDATE (PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $new_rating = $_POST['rating'];
    $new_comment = trim($_POST['comment']);

    // Update query
    $update_sql = "UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ? AND passenger_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("isis", $new_rating, $new_comment, $review_id, $student_id);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Updated!',
                    text: 'Your review has been updated successfully.',
                    icon: 'success',
                    confirmButtonColor: '#3182ce'
                }).then(() => {
                    window.location.href = 'passanger_reviews.php'; 
                });
            });
        </script>";
    } else {
        echo "<script>alert('Error updating review.');</script>";
    }
}

// Fetch ALL Reviews for this passenger
// Added 'r.driver_id' to SELECT to allow adding to favourites
$reviews_sql = "
    SELECT 
        r.review_id, 
        r.rating, 
        r.comment, 
        r.created_at, 
        r.driver_id, 
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .reviews-wrapper {
        max-width: 850px; 
        margin: 0 auto;
        padding: 30px 20px;
        min-height: calc(100vh - 160px); 
        background: #f8f9fa;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
    
    /* Container for Date and Action Buttons (Right side) */
    .action-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px; /* Space between buttons */
    }

    .review-date {
        font-size: 12px; color: #a0aec0;
        margin-bottom: 2px;
        text-align: right;
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

    /* --- BUTTON STYLES --- */
    .btn-common {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 6px 14px;
        border-radius: 50px; font-size: 11px; font-weight: 600;
        cursor: pointer; text-decoration: none; transition: all 0.2s ease; line-height: 1;
        width: 110px; /* Fixed width for alignment */
    }

    /* Blue Edit Button Style */
    .btn-edit-blue {
        background-color: #ebf8ff; 
        color: #2b6cb0;           
        border: 1px solid #90cdf4; 
    }
    .btn-edit-blue:hover {
        background-color: #bee3f8;
        color: #2c5282;
        transform: translateY(-1px);
    }

    /* Pink/Green Favourite Button Style */
    .btn-fav-pink {
        background-color: #fff5f7; 
        color: #d53f8c; 
        border: 1px solid #fbb6ce;
    }
    .btn-fav-pink:hover {
        background-color: #fed7e2;
        color: #b83280;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(213, 63, 140, 0.15);
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        display: none; 
        justify-content: center; align-items: center;
        z-index: 1000;
    }
    .modal-content {
        background: white;
        padding: 25px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .modal-title { font-weight: 700; font-size: 18px; margin-bottom: 15px; color: #2d3748; }
    
    .edit-stars {
        display: flex; gap: 5px; margin-bottom: 15px; cursor: pointer;
    }
    .edit-stars i { font-size: 24px; color: #e2e8f0; transition: color 0.2s; }
    .edit-stars i.active { color: #f59e0b; }
    
    .edit-textarea {
        width: 100%; height: 100px;
        padding: 10px;
        border: 1px solid #cbd5e0; border-radius: 8px;
        resize: none; font-family: inherit;
        margin-bottom: 15px;
    }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
    
    .btn-cancel {
        padding: 8px 16px; border-radius: 6px; border: none; background: #e2e8f0; color: #4a5568; cursor: pointer;
    }
    .btn-save {
        padding: 8px 16px; border-radius: 6px; border: none; background: #3182ce; color: white; cursor: pointer;
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
                    <input type="hidden" id="data_rating_<?php echo $rv['review_id']; ?>" value="<?php echo $rv['rating']; ?>">
                    <input type="hidden" id="data_comment_<?php echo $rv['review_id']; ?>" value="<?php echo htmlspecialchars($rv['comment'] ?? ''); ?>">

                    <div class="review-header">
                        <div class="driver-info">
                            <div class="driver-avatar"><i class="fa-solid fa-user-tie"></i></div>
                            <div>
                                <div class="driver-name">
                                    <?php echo htmlspecialchars($rv['driver_name'] ?? 'Driver'); ?>
                                </div>
                                <div class="car-info">
                                    <?php echo htmlspecialchars(($rv['vehicle_model'] ?? 'Car') . ' • ' . ($rv['plate_number'] ?? '')); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-container">
                            <div class="review-date">
                                <?php echo date("d M Y, h:i A", strtotime($rv['created_at'])); ?>
                            </div>
                            
                            <button class="btn-common btn-edit-blue" onclick="openEditModal(<?php echo $rv['review_id']; ?>)">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>

                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="add_fav_driver_id" value="<?php echo $rv['driver_id']; ?>">
                                <button type="submit" class="btn-common btn-fav-pink" title="Add to Favourites">
                                    <i class="fa-solid fa-heart-circle-plus"></i> Add Fav
                                </button>
                            </form>
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

<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-title">Edit Review</div>
        <form id="editForm" method="POST">
            <input type="hidden" name="update_review" value="1">
            <input type="hidden" id="edit_review_id" name="review_id">
            <input type="hidden" id="edit_rating_input" name="rating">

            <label style="font-size:13px; color:#718096;">Rating:</label>
            <div class="edit-stars" id="starContainer">
                <i class="fa-solid fa-star" data-value="1"></i>
                <i class="fa-solid fa-star" data-value="2"></i>
                <i class="fa-solid fa-star" data-value="3"></i>
                <i class="fa-solid fa-star" data-value="4"></i>
                <i class="fa-solid fa-star" data-value="5"></i>
            </div>

            <label style="font-size:13px; color:#718096;">Comment:</label>
            <textarea id="edit_comment" name="comment" class="edit-textarea" placeholder="Write your review here..."></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save" onclick="confirmUpdate(event)">Update Review</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Star Rating Logic
    const stars = document.querySelectorAll('#starContainer i');
    const ratingInput = document.getElementById('edit_rating_input');

    stars.forEach(star => {
        star.addEventListener('click', () => {
            const value = star.getAttribute('data-value');
            ratingInput.value = value;
            updateStarVisuals(value);
        });
    });

    function updateStarVisuals(value) {
        stars.forEach(s => {
            if(s.getAttribute('data-value') <= value) {
                s.classList.add('active');
                s.style.color = '#f59e0b';
            } else {
                s.classList.remove('active');
                s.style.color = '#e2e8f0';
            }
        });
    }

    // Function to Open Modal and Pre-fill Data
    function openEditModal(reviewId) {
        document.getElementById('editModal').style.display = 'flex';
        
        var existingRating = document.getElementById('data_rating_' + reviewId).value;
        var existingComment = document.getElementById('data_comment_' + reviewId).value;

        document.getElementById('edit_review_id').value = reviewId;
        document.getElementById('edit_comment').value = existingComment;
        
        ratingInput.value = existingRating;
        updateStarVisuals(existingRating);
    }

    // Function to Close Modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // SweetAlert Confirmation
    function confirmUpdate(e) {
        e.preventDefault(); 
        
        Swal.fire({
            title: 'Are you sure?',
            text: "Your previous rating and comment will be replaced.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3182ce',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('editForm').submit(); 
            }
        });
    }
</script>

<?php include "footer.php"; ?>