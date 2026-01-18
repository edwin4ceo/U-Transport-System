<?php
// FUNCTION: START SESSION
session_start();
include "db_connect.php";
include "function.php";

// 1. CHECK LOGIN
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// ==========================================
// LOGIC: ADD FAVOURITE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fav_driver_id'])) {
    $fav_driver_id = $_POST['add_fav_driver_id'];
    $check_stmt = $conn->prepare("SELECT id FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
    $check_stmt->bind_param("ss", $student_id, $fav_driver_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows == 0) {
        $ins_stmt = $conn->prepare("INSERT INTO favourite_drivers (student_id, driver_id) VALUES (?, ?)");
        $ins_stmt->bind_param("ss", $student_id, $fav_driver_id);
        if ($ins_stmt->execute()) {
            $_SESSION['swal_success'] = "Driver saved successfully!";
        }
    }
    header("Location: passanger_reviews.php");
    exit();
}

// ==========================================
// LOGIC: REMOVE FAVOURITE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_fav_driver_id'])) {
    $rem_driver_id = $_POST['remove_fav_driver_id'];
    $del_stmt = $conn->prepare("DELETE FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
    $del_stmt->bind_param("ss", $student_id, $rem_driver_id);
    if ($del_stmt->execute()) {
        $_SESSION['swal_success'] = "Driver removed from saved list.";
    }
    header("Location: passanger_reviews.php");
    exit();
}

// ==========================================
// LOGIC: UPDATE REVIEW
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $new_rating = $_POST['rating'];
    $new_comment = trim($_POST['comment']);

    $update_sql = "UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ? AND passenger_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("isis", $new_rating, $new_comment, $review_id, $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['swal_success'] = "Review updated successfully.";
        header("Location: passanger_reviews.php");
        exit();
    }
}

// ==========================================
// LOGIC: FETCH REVIEWS + FAV STATUS
// ==========================================
$reviews_sql = "
    SELECT 
        r.review_id, 
        r.rating, 
        r.comment, 
        r.created_at, 
        r.driver_id, 
        d.full_name AS driver_name,
        d.profile_image, 
        v.vehicle_model,
        v.plate_number,
        (SELECT COUNT(*) FROM favourite_drivers f WHERE f.driver_id = r.driver_id AND f.student_id = ?) as is_fav
    FROM reviews r
    LEFT JOIN drivers d ON r.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE r.passenger_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($reviews_sql);
$stmt->bind_param("ss", $student_id, $student_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* 1. LAYOUT & ANIMATION */
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }

    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }

    .reviews-wrapper {
        max-width: 850px; margin: 0 auto; padding: 40px 20px;
        background: #f5f7fb; font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        position: relative;
    }

    /* 2. FLOATING BACK BUTTON */
    .btn-back-floating {
        position: absolute; left: 20px; top: 40px;
        background: white; color: #64748b; padding: 10px 20px;
        border-radius: 50px; font-size: 14px; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
        z-index: 10; transition: all 0.3s ease;
    }
    .btn-back-floating:hover {
        transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 75, 130, 0.1);
        color: #004b82; border-color: #e0f2fe;
    }

    /* 3. HEADER */
    .page-header { text-align: center; margin-bottom: 50px; padding-top: 15px; }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; }
    .page-header p { margin: 5px 0 0; color: #64748b; font-size: 15px; }

    /* 4. REVIEW CARDS */
    .review-card { 
        background: #ffffff; border-radius: 20px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.03); 
        padding: 30px; margin-bottom: 25px; 
        border: 1px solid #f1f5f9; transition: transform 0.3s ease;
    }
    .review-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0, 75, 130, 0.08); }

    .review-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    
    .driver-info-group { display: flex; gap: 15px; align-items: center; }
    .driver-avatar-img { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #e0f2fe; padding: 2px; }
    .driver-text h4 { margin: 0; font-size: 16px; color: #1e293b; font-weight: 700; }
    .driver-text span { font-size: 13px; color: #64748b; display: block; margin-top: 2px; }

    .meta-group { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
    .review-date-badge { font-size: 12px; color: #94a3b8; font-weight: 500; background: #f8fafc; padding: 4px 10px; border-radius: 8px; }

    /* 5. SHARED BUTTON STYLES (Fixed Pill Shape) */
    .btn-pill-base {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 8px 20px !important;
        border-radius: 50px !important; 
        font-size: 13px !important;
        font-weight: 700 !important;
        min-width: 90px !important;     
        height: 36px !important;        
        cursor: pointer !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05) !important;
    }

    /* Style: Edit (Grey/White) */
    .style-edit {
        background-color: #fff !important;
        color: #004b82 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .style-edit:hover {
        background-color: #f8fafc !important;
        border-color: #004b82 !important;
    }

    /* Style: SAVED (Red Tint) */
    .style-saved {
        background-color: #ffe4e6 !important;
        color: #e11d48 !important;            
        border: 1px solid #e11d48 !important; 
    }
    .style-saved:hover {
        background-color: #e11d48 !important; 
        color: white !important;
    }

    /* Style: SAVE (Blue Tint - Matches Saved Logic) */
    .style-save-add {
        background-color: #e0f2fe !important; 
        color: #004b82 !important;            
        border: 1px solid #004b82 !important; 
    }
    .style-save-add:hover {
        background-color: #004b82 !important; 
        color: white !important;
    }

    /* 6. CONTENT */
    .star-row { margin-bottom: 12px; font-size: 14px; }
    .star-row i { margin-right: 2px; }
    .star-filled { color: #f59e0b; }
    .star-empty { color: #e2e8f0; }
    
    .comment-bubble {
        background: #f8fafc; border-radius: 16px; padding: 15px 20px;
        font-size: 14px; color: #334155; line-height: 1.6; border: 1px dashed #cbd5e1;
    }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; background: #fff; border-radius: 20px; border: 1px dashed #cbd5e1; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; color: #cbd5e0; }

    /* 7. MODAL STYLES */
    .modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
        z-index: 9999; display: none;
        justify-content: center; align-items: center; 
        opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.show { display: flex !important; opacity: 1; }

    .modal-card {
        background: #fff; width: 90%; max-width: 450px;
        border-radius: 24px; padding: 35px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        transform: translateY(20px); transition: transform 0.3s ease;
        margin: auto;
    }
    .modal-overlay.show .modal-card { transform: translateY(0); }

    .modal-title { font-size: 22px; font-weight: 700; color: #004b82; margin-bottom: 25px; text-align: center; }
    .edit-stars { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; cursor: pointer; }
    .edit-stars i { font-size: 32px; color: #e2e8f0; transition: transform 0.2s; }
    .edit-stars i.active { color: #f59e0b; }
    .edit-stars i:hover { transform: scale(1.1); }

    .modal-textarea {
        width: 100%; height: 120px; padding: 15px;
        border: 1.5px solid #e2e8f0; border-radius: 16px;
        font-family: 'Poppins', sans-serif; font-size: 14px; color: #333;
        resize: none; outline: none; transition: 0.3s;
        box-sizing: border-box; background: #f8fafc;
    }
    .modal-textarea:focus { border-color: #004b82; background: #fff; box-shadow: 0 4px 15px rgba(0,75,130,0.05); }

    .modal-actions { display: flex; gap: 15px; margin-top: 30px; }
    .modal-btn {
        flex: 1; padding: 12px; border-radius: 50px; border: none;
        font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s;
    }
    .btn-cancel { background: #f1f5f9; color: #64748b; }
    .btn-cancel:hover { background: #e2e8f0; color: #333; }
    .btn-save { background: #004b82; color: white; box-shadow: 0 4px 10px rgba(0,75,130,0.2); }
    .btn-save:hover { background: #003660; transform: translateY(-2px); }

    @media (max-width: 768px) {
        .btn-back-floating { top: 20px; left: 20px; }
        .page-header { margin-bottom: 40px; padding-top: 60px; }
    }
</style>

<div class="reviews-wrapper">
    
    <a href="passanger_profile.php" class="btn-back-floating">
        <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>

    <div class="page-header">
        <h1>My Reviews</h1>
        <p>Manage your feedback and favourite drivers.</p>
    </div>

    <div class="reviews-list">
        <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
            <?php while($rv = $reviews_result->fetch_assoc()): 
                // Avatar
                $d_img = $rv['profile_image'];
                if(!empty($d_img) && file_exists("uploads/" . $d_img)) {
                    $img_src = "uploads/" . $d_img;
                } else {
                    $img_src = "https://ui-avatars.com/api/?name=".urlencode($rv['driver_name'])."&background=e0f2fe&color=004b82";
                }

                $is_fav = ($rv['is_fav'] > 0);
            ?>
                <div class="review-card">
                    <input type="hidden" id="data_rating_<?php echo $rv['review_id']; ?>" value="<?php echo $rv['rating']; ?>">
                    <input type="hidden" id="data_comment_<?php echo $rv['review_id']; ?>" value="<?php echo htmlspecialchars($rv['comment'] ?? ''); ?>">

                    <div class="review-header-row">
                        <div class="driver-info-group">
                            <img src="<?php echo $img_src; ?>" class="driver-avatar-img" alt="Driver">
                            <div class="driver-text">
                                <h4><?php echo htmlspecialchars($rv['driver_name'] ?? 'Unknown Driver'); ?></h4>
                                <span><?php echo htmlspecialchars(($rv['vehicle_model'] ?? 'Car') . ' • ' . ($rv['plate_number'] ?? '')); ?></span>
                            </div>
                        </div>
                        
                        <div class="meta-group">
                            <span class="review-date-badge"><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y", strtotime($rv['created_at'])); ?></span>
                            
                            <div style="display:flex;">
                                <button class="btn-pill-base style-edit" onclick="openEditModal(<?php echo $rv['review_id']; ?>)">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>

                                <?php if ($is_fav): ?>
                                    <form id="removeFavForm_<?php echo $rv['driver_id']; ?>" method="POST" style="margin:0;">
                                        <input type="hidden" name="remove_fav_driver_id" value="<?php echo $rv['driver_id']; ?>">
                                        <button type="button" class="btn-pill-base style-saved" style="margin-left:8px;" onclick="confirmRemoveFav('<?php echo $rv['driver_id']; ?>', '<?php echo htmlspecialchars($rv['driver_name']); ?>')">
                                            <i class="fa-solid fa-heart"></i> Saved
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form id="addFavForm_<?php echo $rv['driver_id']; ?>" method="POST" style="margin:0;">
                                        <input type="hidden" name="add_fav_driver_id" value="<?php echo $rv['driver_id']; ?>">
                                        <button type="button" class="btn-pill-base style-save-add" style="margin-left:8px;" onclick="confirmAddFav('<?php echo $rv['driver_id']; ?>', '<?php echo htmlspecialchars($rv['driver_name']); ?>')">
                                            Save
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="star-row">
                        <?php 
                        for($i=0; $i<$rv['rating']; $i++) echo '<i class="fa-solid fa-star star-filled"></i>';
                        for($i=$rv['rating']; $i<5; $i++) echo '<i class="fa-solid fa-star star-empty"></i>';
                        ?>
                        <span style="color:#94a3b8; font-weight:600; margin-left:5px; font-size:12px;">(<?php echo $rv['rating']; ?>.0)</span>
                    </div>

                    <div class="comment-bubble">
                        <?php if(!empty($rv['comment'])): ?>
                            <i class="fa-solid fa-quote-left"></i> <?php echo htmlspecialchars($rv['comment']); ?>
                        <?php else: ?>
                            <span style="font-style:italic; color:#94a3b8;">No written comment provided.</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-comments"></i>
                <p>You haven't submitted any reviews yet.</p>
                <span style="font-size:14px; color:#cbd5e1;">Complete a ride to rate your driver!</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal-overlay" onclick="closeEditModal(event)">
    <div class="modal-card" onclick="event.stopPropagation()"> 
        <div class="modal-title">Edit Review</div>
        
        <form id="editForm" method="POST">
            <input type="hidden" name="update_review" value="1">
            <input type="hidden" id="edit_review_id" name="review_id">
            <input type="hidden" id="edit_rating_input" name="rating">

            <div class="edit-stars" id="starContainer">
                <i class="fa-solid fa-star" data-value="1"></i>
                <i class="fa-solid fa-star" data-value="2"></i>
                <i class="fa-solid fa-star" data-value="3"></i>
                <i class="fa-solid fa-star" data-value="4"></i>
                <i class="fa-solid fa-star" data-value="5"></i>
            </div>

            <textarea id="edit_comment" name="comment" class="modal-textarea" placeholder="Share your experience..."></textarea>

            <div class="modal-actions">
                <button type="button" class="modal-btn btn-cancel" onclick="document.getElementById('editModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="modal-btn btn-save" onclick="confirmUpdate(event)">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. Confirm ADD Favourite (New)
    function confirmAddFav(driverId, driverName) {
        Swal.fire({
            title: 'Save Driver?',
            text: `Do you want to save ${driverName} to your favourites?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, save',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('addFavForm_' + driverId).submit();
            }
        });
    }

    // 2. Confirm REMOVE Favourite
    function confirmRemoveFav(driverId, driverName) {
        Swal.fire({
            title: 'Unsave Driver?',
            text: `Remove ${driverName} from your saved drivers?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('removeFavForm_' + driverId).submit();
            }
        });
    }

    // 3. Open Modal
    function openEditModal(reviewId) {
        const modal = document.getElementById('editModal');
        modal.style.display = 'flex'; 
        setTimeout(() => { modal.classList.add('show'); }, 10);
        
        var existingRating = document.getElementById('data_rating_' + reviewId).value;
        var existingComment = document.getElementById('data_comment_' + reviewId).value;

        document.getElementById('edit_review_id').value = reviewId;
        document.getElementById('edit_comment').value = existingComment;
        
        document.getElementById('edit_rating_input').value = existingRating;
        updateStarVisuals(existingRating);
    }

    // 4. Close Modal
    function closeEditModal(e) {
        if (!e || e.target.id === 'editModal') {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display = 'none'; }, 300); 
        }
    }

    // 5. Star Logic
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
            } else {
                s.classList.remove('active');
            }
        });
    }

    // 6. Update Confirm
    function confirmUpdate(e) {
        e.preventDefault(); 
        document.getElementById('editModal').classList.remove('show');

        Swal.fire({
            title: 'Update Review?',
            text: "Your rating and comment will be updated.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004b82',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('editForm').submit(); 
            } else {
                document.getElementById('editModal').classList.add('show');
            }
        });
    }
</script>

<?php if(isset($_SESSION['swal_success'])): ?>
    <script>
        Swal.fire({ title: 'Success!', text: '<?php echo $_SESSION['swal_success']; ?>', icon: 'success', confirmButtonColor: '#004b82', timer: 1500, showConfirmButton: false });
    </script>
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<?php include "footer.php"; ?>