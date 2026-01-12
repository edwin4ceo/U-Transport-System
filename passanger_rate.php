<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])) {
    redirect("passanger_login.php");
}
$student_id = $_SESSION['student_id'];

// 2. Validate URL Parameter
if(!isset($_GET['booking_id'])){
    echo "<script>alert('Error: No booking ID provided.'); window.location.href='passanger_rides.php';</script>";
    exit;
}
$booking_id = $_GET['booking_id'];

// 3. Fetch Data
// Step A: Verify ownership
$stmt = $conn->prepare("SELECT status, driver_id FROM bookings WHERE id = ? AND student_id = ?");
$stmt->bind_param("is", $booking_id, $student_id);
$stmt->execute();
$check_basic = $stmt->get_result()->fetch_assoc();

if (!$check_basic) {
    echo "<script>alert('Booking not found or access denied.'); window.location.href='passanger_rides.php';</script>";
    exit;
}

if (strtoupper($check_basic['status']) !== 'COMPLETED') {
    echo "<script>alert('This ride is not completed yet.'); window.location.href='passanger_rides.php';</script>";
    exit;
}

// Step B: Fetch Driver Details
$stmt = $conn->prepare("
    SELECT 
        b.id, 
        b.driver_id, 
        d.full_name AS driver_name,
        v.vehicle_model,
        v.plate_number
    FROM bookings b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_data = $stmt->get_result()->fetch_assoc();

$driver_name = $booking_data['driver_name'] ?? "Unknown Driver";
$car_info = ($booking_data['vehicle_model'] ?? 'Car') . ' • ' . ($booking_data['plate_number'] ?? 'No Plate');
$driver_id = $booking_data['driver_id'];


// 4. Handle Form Submission
if(isset($_POST['submit_review'])){
    $rating = $_POST['rating'] ?? 0;
    $comment = trim($_POST['comment']);
    $add_to_fav = isset($_POST['add_to_fav']); // Check if checkbox is ticked

    if(empty($rating)){
        echo "<script>alert('Please select a star rating.');</script>";
    } else {
        // A. Check duplicate review
        $check = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
        $check->bind_param("i", $booking_id);
        $check->execute();
        if($check->get_result()->num_rows > 0){
             echo "<script>alert('You have already rated this ride.'); window.location.href='passanger_rides.php';</script>";
             exit;
        }

        // B. Save Review
        $ins = $conn->prepare("INSERT INTO reviews (booking_id, passenger_id, driver_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("isiis", $booking_id, $student_id, $driver_id, $rating, $comment);
        
        if($ins->execute()){
            
            // C. [NEW LOGIC] Handle Favourite Driver
            if($add_to_fav){
                // Check if already in favourites to prevent duplicates
                $check_fav = $conn->prepare("SELECT id FROM favourite_drivers WHERE student_id = ? AND driver_id = ?");
                $check_fav->bind_param("si", $student_id, $driver_id);
                $check_fav->execute();
                
                if($check_fav->get_result()->num_rows == 0){
                    // Not in favourites yet, so add it
                    $ins_fav = $conn->prepare("INSERT INTO favourite_drivers (student_id, driver_id) VALUES (?, ?)");
                    $ins_fav->bind_param("si", $student_id, $driver_id);
                    $ins_fav->execute();
                }
            }

            $success = true; // Trigger SweetAlert
        } else {
            $error = "System Error: " . $conn->error;
        }
    }
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* --- PAGE STYLES --- */
.rate-wrapper {
    min-height: calc(100vh - 160px); 
    padding: 40px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.rate-card {
    background: white;
    width: 100%;
    max-width: 480px; 
    border-radius: 16px;
    padding: 35px 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    text-align: center;
}

/* Driver Profile */
.driver-avatar-large {
    width: 72px; height: 72px;
    background: #edf2f7; border-radius: 50%; margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #718096;
}

.driver-name { font-size: 18px; font-weight: 700; color: #2d3748; margin-bottom: 4px; }

.car-info {
    font-size: 13px; color: #718096; margin-bottom: 30px;
    background: #f7fafc; display: inline-block; padding: 4px 12px;
    border-radius: 20px; border: 1px solid #edf2f7;
}

/* Star Rating */
.star-rating {
    display: flex; justify-content: center; gap: 8px; margin-bottom: 25px;
    flex-direction: row-reverse; 
}
.star-rating input { display: none; }
.star-rating label { font-size: 34px; color: #e2e8f0; cursor: pointer; transition: all 0.2s; }
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label { color: #f59e0b; transform: scale(1.1); }

/* Textarea */
textarea {
    width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px;
    resize: none; font-family: inherit; font-size: 14px; margin-bottom: 15px;
    background: #fff; transition: border 0.2s;
}
textarea:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }

/* [NEW] Favourite Checkbox Styling */
.fav-option {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; margin-bottom: 25px; cursor: pointer;
    padding: 10px; border-radius: 8px; transition: background 0.2s;
}
.fav-option:hover { background: #fff5f5; }
.fav-option input[type="checkbox"] {
    accent-color: #e53e3e; transform: scale(1.2); cursor: pointer;
}
.fav-text { font-size: 14px; color: #4a5568; font-weight: 500; }
.fav-icon { color: #e53e3e; }

/* Buttons */
.btn-submit {
    width: 100%; padding: 12px;
    background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);
    color: white; border: none; border-radius: 50px;
    font-size: 15px; font-weight: 600; cursor: pointer;
    transition: transform 0.2s; box-shadow: 0 4px 6px rgba(49, 130, 206, 0.2);
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(49, 130, 206, 0.3); }

.btn-back {
    display: block; margin-top: 18px; color: #718096;
    font-size: 13px; text-decoration: none; font-weight: 500;
}
.btn-back:hover { color: #2d3748; }

</style>

<div class="rate-wrapper">
    <div class="rate-card">
        
        <div class="driver-avatar-large">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="driver-name"><?php echo htmlspecialchars($driver_name); ?></div>
        <div class="car-info"><?php echo htmlspecialchars($car_info); ?></div>
        
        <p style="font-size:15px; font-weight:500; color:#4a5568; margin-bottom:15px;">How was your ride?</p>

        <form method="POST">
            
            <div class="star-rating">
                <input type="radio" name="rating" id="star5" value="5"><label for="star5" title="Excellent"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star4" value="4"><label for="star4" title="Good"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star3" value="3"><label for="star3" title="Average"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star2" value="2"><label for="star2" title="Poor"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star1" value="1"><label for="star1" title="Terrible"><i class="fa-solid fa-star"></i></label>
            </div>

            <textarea name="comment" rows="4" placeholder="Share your experience (optional)..."></textarea>
            
            <label class="fav-option">
                <input type="checkbox" name="add_to_fav" value="1">
                <i class="fa-solid fa-heart fav-icon"></i>
                <span class="fav-text">Add driver to Favourites</span>
            </label>

            <button type="submit" name="submit_review" class="btn-submit">Submit Review</button>
            <a href="passanger_rides.php" class="btn-back">Cancel</a>

        </form>
    </div>
</div>

<?php if(isset($success)): ?>
<script>
    Swal.fire({
        title: 'Thank You!',
        text: 'Your rating has been submitted successfully.',
        icon: 'success',
        confirmButtonColor: '#3182ce',
        confirmButtonText: 'Back to Rides'
    }).then((result) => {
        window.location.href = 'passanger_rides.php';
    });
</script>
<?php endif; ?>

<?php if(isset($error)): ?>
<script>
    Swal.fire({
        title: 'Oops!',
        text: '<?php echo $error; ?>',
        icon: 'error',
        confirmButtonColor: '#e53e3e'
    });
</script>
<?php endif; ?>

<?php include "footer.php"; ?>