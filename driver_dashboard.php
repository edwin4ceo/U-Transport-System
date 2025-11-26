<?php
session_start();
include "db_connect.php";

// If you are not logged in, return to the login page
if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

/* --------------- Small function: Grab driver information --------------- */
function getDriver($conn, $driver_id) {
    $sql = "SELECT * FROM drivers WHERE driver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$alert = "";

/* --------------- 1. Edit profile (Including driver's license) --------------- */
if (isset($_POST['update_profile'])) {
    $full_name      = $_POST['full_name'];
    $phone          = $_POST['phone'];
    $license_number = $_POST['license_number'];
    $license_expiry = $_POST['license_expiry'];

    $sql = "UPDATE drivers 
            SET full_name = ?, phone = ?, license_number = ?, license_expiry = ?
            WHERE driver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $full_name, $phone, $license_number, $license_expiry, $driver_id);

    $alert = $stmt->execute() ? "Profile updated." : "Failed to update profile.";
}

/* --------------- 2. Add transport --------------- */
if (isset($_POST['add_transport'])) {
    $vehicle_type    = $_POST['vehicle_type'];
    $vehicle_model   = $_POST['vehicle_model'];
    $destination     = $_POST['destination_area'];
    $available_days  = $_POST['available_days'];
    $time_from       = $_POST['time_from'];
    $time_to         = $_POST['time_to'];
    $price_per_trip  = $_POST['price_per_trip'];
    $payment_method  = $_POST['payment_method'];

    $sql = "INSERT INTO transports
            (driver_id, vehicle_type, vehicle_model, destination_area, 
             available_days, time_from, time_to, price_per_trip, payment_method)
            VALUES (?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssds",
        $driver_id,
        $vehicle_type,
        $vehicle_model,
        $destination,
        $available_days,
        $time_from,
        $time_to,
        $price_per_trip,
        $payment_method
    );

    $alert = $stmt->execute() ? "Transport added." : "Failed to add transport.";
}

/* --------------- 3. Accept / Reject booking --------------- */
if (isset($_POST['update_booking_status'])) {
    $booking_id = $_POST['booking_id'];
    $status     = $_POST['status']; // accepted / rejected

    $sql = "UPDATE bookings SET status = ? 
            WHERE booking_id = ? AND driver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $booking_id, $driver_id);

    $alert = $stmt->execute() ? "Booking status updated." : "Failed to update booking.";
}

/* --------------- 4. Forum：ask questions --------------- */
if (isset($_POST['add_question'])) {
    $title = $_POST['question_title'];
    $body  = $_POST['question_body'];

    $sql = "INSERT INTO forum_questions (driver_id, title, body) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $driver_id, $title, $body);

    $alert = $stmt->execute() ? "Question posted." : "Failed to post question.";
}

/* --------------- 5. Forum：reply --------------- */
if (isset($_POST['add_reply'])) {
    $question_id = $_POST['question_id'];
    $reply_text  = $_POST['reply_text'];

    $sql = "INSERT INTO forum_replies (question_id, driver_id, reply_text) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $question_id, $driver_id, $reply_text);

    $alert = $stmt->execute() ? "Reply posted." : "Failed to post reply.";
}

/* --------------- 6. Contact Us / Feedback --------------- */
if (isset($_POST['send_feedback'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $sql = "INSERT INTO feedbacks (driver_id, subject, message) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $driver_id, $subject, $message);

    $alert = $stmt->execute() ? "Feedback sent. Thank you!" : "Failed to send feedback.";
}

/* --------------- Load to display --------------- */
$driver     = getDriver($conn, $driver_id);

$transports = $conn->query(
    "SELECT * FROM transports 
     WHERE driver_id = $driver_id 
     ORDER BY created_at DESC"
);

$bookings = $conn->query("SELECT * FROM bookings");
    "SELECT b.*, t.vehicle_type, t.vehicle_model
     FROM bookings b
     JOIN transports t ON b.transport_id = t.transport_id
     WHERE b.driver_id = $driver_id
     ORDER BY b.created_at DESC"
);

$ratings = $conn->query(
    "SELECT r.*, b.requester_name, b.pickup_location, b.dropoff_location
     FROM ratings r
     JOIN bookings b ON r.booking_id = b.booking_id
     WHERE r.driver_id = $driver_id
     ORDER BY r.created_at DESC"
);

$questions = $conn->query(
    "SELECT q.*, d.full_name
     FROM forum_questions q
     JOIN drivers d ON q.driver_id = d.driver_id
     ORDER BY q.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard</title>
    <style>
        body{font-family:Arial,sans-serif;margin:0;background:#f5f5f5;}
        .header{background:#007bff;color:#fff;padding:12px 18px;
                display:flex;justify-content:space-between;align-items:center;}
        .header a{color:#fff;text-decoration:none;margin-left:8px;}
        .container{display:flex;}
        .sidebar{width:220px;background:#fff;border-right:1px solid #ddd;min-height:100vh;}
        .sidebar button{width:100%;padding:10px 14px;border:none;background:none;
                        text-align:left;cursor:pointer;border-bottom:1px solid #eee;}
        .sidebar button:hover,.sidebar button.active{background:#f0f0f0;}
        .content{flex:1;padding:18px;}
        .card{background:#fff;border:1px solid #ddd;border-radius:4px;
              padding:14px;margin-bottom:18px;}
        .card h2{margin:0 0 10px;font-size:18px;}
        label{display:block;margin-top:8px;font-size:14px;}
        input,textarea,select{width:100%;padding:7px;margin-top:3px;
              box-sizing:border-box;border:1px solid #ccc;border-radius:3px;font-size:14px;}
        textarea{resize:vertical;}
        .btn{margin-top:10px;padding:7px 14px;border:none;border-radius:3px;
             font-size:14px;cursor:pointer;}
        .btn-primary{background:#007bff;color:#fff;}
        .btn-danger{background:#dc3545;color:#fff;}
        .btn-secondary{background:#6c757d;color:#fff;}
        .alert{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;
               padding:8px 12px;border-radius:3px;margin-bottom:12px;}
        table{width:100%;border-collapse:collapse;font-size:14px;}
        th,td{border:1px solid #ddd;padding:6px 8px;}
        th{background:#f1f1f1;}
        .section{display:none;}
        .section.active{display:block;}
    </style>
    <script>
        function showSection(id){
            document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
            document.getElementById(id).classList.add('active');

            document.querySelectorAll('.sidebar button').forEach(b=>b.classList.remove('active'));
            document.getElementById('btn_'+id).classList.add('active');
        }
        window.onload=function(){showSection('profile');};
    </script>
</head>
<body>

<div class="header">
    <div>Driver Dashboard</div>
    <div>
        Welcome, <?php echo htmlspecialchars($driver['full_name']); ?>
        <a href="driver_logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- left menu -->
    <div class="sidebar">
        <button id="btn_profile"  onclick="showSection('profile')">Edit Profile</button>
        <button id="btn_transport"onclick="showSection('transport')">Add Transport</button>
        <button id="btn_bookings" onclick="showSection('bookings')">View Booking Requests</button>
        <button id="btn_ratings"  onclick="showSection('ratings')">Ratings & Reviews</button>
        <button id="btn_forum"    onclick="showSection('forum')">Q & A Chat (Forum)</button>
        <button id="btn_feedback" onclick="showSection('feedback')">Contact Us (Feedback)</button>
    </div>

    <!-- Right content -->
    <div class="content">
        <?php if ($alert): ?>
            <div class="alert"><?php echo htmlspecialchars($alert); ?></div>
        <?php endif; ?>

        <!-- 1. Edit Profile -->
        <div id="profile" class="section">
            <div class="card">
                <h2>Edit Profile (Driving License)</h2>
                <form method="post">
                    <label>Full Name</label>
                    <input type="text" name="full_name"
                           value="<?php echo htmlspecialchars($driver['full_name']); ?>" required>

                    <label>Email (read only)</label>
                    <input type="email" value="<?php echo htmlspecialchars($driver['email']); ?>" disabled>

                    <label>Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo htmlspecialchars($driver['phone']); ?>">

                    <label>Driving License Number</label>
                    <input type="text" name="license_number"
                           value="<?php echo htmlspecialchars($driver['license_number']); ?>" required>

                    <label>Driving License Expiry Date</label>
                    <input type="date" name="license_expiry"
                           value="<?php echo htmlspecialchars($driver['license_expiry']); ?>" required>

                    <button type="submit" name="update_profile" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>

        <!-- 2. Add Transport -->
        <div id="transport" class="section">
            <div class="card">
                <h2>Add Transport</h2>
                <form method="post">
                    <label>Vehicle Type</label>
                    <input type="text" name="vehicle_type" placeholder="Car, Motorbike, Van..." required>

                    <label>Vehicle Model</label>
                    <input type="text" name="vehicle_model" placeholder="Perodua Myvi 1.3" required>

                    <label>Destination (Area / Location covered)</label>
                    <input type="text" name="destination_area" placeholder="MMU, Cyberjaya area" required>

                    <label>Day Availability</label>
                    <input type="text" name="available_days" placeholder="Mon–Fri / Weekend only" required>

                    <label>Time From</label>
                    <input type="time" name="time_from" required>

                    <label>Time To</label>
                    <input type="time" name="time_to" required>

                    <label>Price (per trip)</label>
                    <input type="number" step="0.01" name="price_per_trip" required>

                    <label>Payment Method</label>
                    <input type="text" name="payment_method" placeholder="Cash, TnG eWallet..." required>

                    <button type="submit" name="add_transport" class="btn btn-primary">Add</button>
                </form>
            </div>

            <div class="card">
                <h2>Your Transport List</h2>
                <table>
                    <tr>
                        <th>Vehicle</th>
                        <th>Destination</th>
                        <th>Days</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                    <?php if ($transports && $transports->num_rows > 0): ?>
                        <?php while ($t = $transports->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['vehicle_type']." - ".$t['vehicle_model']); ?></td>
                                <td><?php echo htmlspecialchars($t['destination_area']); ?></td>
                                <td><?php echo htmlspecialchars($t['available_days']); ?></td>
                                <td><?php echo htmlspecialchars($t['time_from']." - ".$t['time_to']); ?></td>
                                <td><?php echo htmlspecialchars($t['price_per_trip']); ?></td>
                                <td><?php echo htmlspecialchars($t['payment_method']); ?></td>
                                <td><?php echo $t['is_active'] ? "Active" : "Inactive"; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No transport added.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- 3. View Booking Requests -->
        <div id="bookings" class="section">
            <div class="card">
                <h2>Transport Booking Requests</h2>
                <table>
                    <tr>
                        <th>Requester</th>
                        <th>Contact</th>
                        <th>Vehicle</th>
                        <th>Route</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($bookings && $bookings->num_rows > 0): ?>
                        <?php while ($b = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['requester_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($b['requester_email']); ?><br>
                                    <?php echo htmlspecialchars($b['requester_phone']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($b['vehicle_type']." - ".$b['vehicle_model']); ?></td>
                                <td><?php echo htmlspecialchars($b['pickup_location']); ?> → <?php echo htmlspecialchars($b['dropoff_location']); ?></td>
                                <td><?php echo htmlspecialchars($b['travel_date']); ?><br><?php echo htmlspecialchars($b['travel_time']); ?></td>
                                <td><?php echo htmlspecialchars($b['status']); ?></td>
                                <td>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <button class="btn btn-primary" name="update_booking_status">Accept</button>
                                        </form>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn btn-danger" name="update_booking_status">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No booking requests.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- 4. Ratings & Reviews -->
        <div id="ratings" class="section">
            <div class="card">
                <h2>Ratings & Reviews</h2>
                <table>
                    <tr>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>From</th>
                        <th>Route</th>
                        <th>Date</th>
                    </tr>
                    <?php if ($ratings && $ratings->num_rows > 0): ?>
                        <?php while ($r = $ratings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$r['rating']; ?>/5</td>
                                <td><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></td>
                                <td><?php echo htmlspecialchars($r['requester_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['pickup_location']); ?> → <?php echo htmlspecialchars($r['dropoff_location']); ?></td>
                                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No ratings yet.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- 5. Forum -->
        <div id="forum" class="section">
            <div class="card">
                <h2>Q & A Chat (Forum) – Ask Question</h2>
                <form method="post">
                    <label>Title</label>
                    <input type="text" name="question_title" required>

                    <label>Question</label>
                    <textarea name="question_body" rows="3" required></textarea>

                    <button type="submit" name="add_question" class="btn btn-primary">Post Question</button>
                </form>
            </div>

            <div class="card">
                <h2>Forum Threads</h2>
                <?php if ($questions && $questions->num_rows > 0): ?>
                    <?php while ($q = $questions->fetch_assoc()): ?>
                        <div style="border-bottom:1px solid #ddd;padding:8px 0;">
                            <strong><?php echo htmlspecialchars($q['title']); ?></strong><br>
                            <small>By <?php echo htmlspecialchars($q['full_name']); ?> | <?php echo htmlspecialchars($q['created_at']); ?></small>
                            <p><?php echo nl2br(htmlspecialchars($q['body'])); ?></p>

                            <?php
                            $qid = $q['question_id'];
                            $replies = $conn->query(
                                "SELECT fr.*, d.full_name
                                 FROM forum_replies fr
                                 LEFT JOIN drivers d ON fr.driver_id = d.driver_id
                                 WHERE fr.question_id = $qid
                                 ORDER BY fr.created_at ASC"
                            );
                            ?>
                            <?php if ($replies && $replies->num_rows > 0): ?>
                                <div style="margin-left:15px;border-left:2px solid #eee;padding-left:8px;">
                                    <?php while ($rep = $replies->fetch_assoc()): ?>
                                        <p>
                                            <strong><?php echo htmlspecialchars($rep['full_name'] ?? 'Admin'); ?>:</strong>
                                            <?php echo nl2br(htmlspecialchars($rep['reply_text'])); ?><br>
                                            <small><?php echo htmlspecialchars($rep['created_at']); ?></small>
                                        </p>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p style="margin-left:15px;color:#777;">No replies yet.</p>
                            <?php endif; ?>

                            <form method="post" style="margin-top:6px;">
                                <input type="hidden" name="question_id" value="<?php echo $q['question_id']; ?>">
                                <label>Your reply</label>
                                <textarea name="reply_text" rows="2" required></textarea>
                                <button type="submit" name="add_reply" class="btn btn-secondary">Reply</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No questions yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 6. Contact Us / Feedback -->
        <div id="feedback" class="section">
            <div class="card">
                <h2>Contact Us (Feedback)</h2>
                <form method="post">
                    <label>Subject</label>
                    <input type="text" name="subject" required>

                    <label>Message</label>
                    <textarea name="message" rows="4" required></textarea>

                    <button type="submit" name="send_feedback" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>
