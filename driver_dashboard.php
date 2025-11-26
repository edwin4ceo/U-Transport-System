<?php
session_start();
include "db_connect.php";
include "function.php";

// Check if the driver is logged in
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Fetch driver profile data from "drivers" table
$driver = null;
$driver_stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_id = ?");
$driver_stmt->bind_param("i", $driver_id);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
if ($driver_result->num_rows === 1) {
    $driver = $driver_result->fetch_assoc();
}
$driver_stmt->close();

// Fetch existing transport data (if any) from "transports" table
$transport = null;
$transport_stmt = $conn->prepare("SELECT * FROM transports WHERE driver_id = ? LIMIT 1");
$transport_stmt->bind_param("i", $driver_id);
$transport_stmt->execute();
$transport_result = $transport_stmt->get_result();
if ($transport_result->num_rows === 1) {
    $transport = $transport_result->fetch_assoc();
}
$transport_stmt->close();

// Fetch booking requests (example table name: bookings)
$booking_rows = [];
if ($conn->query("SHOW TABLES LIKE 'bookings'")->num_rows === 1) {
    $booking_stmt = $conn->prepare("
        SELECT b.*
        FROM bookings b
        WHERE b.driver_id = ?
        ORDER BY b.request_time DESC
    ");
    $booking_stmt->bind_param("i", $driver_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    while ($row = $booking_result->fetch_assoc()) {
        $booking_rows[] = $row;
    }
    $booking_stmt->close();
}

// Fetch ratings (example table name: ratings)
$rating_rows = [];
$average_rating = null;
if ($conn->query("SHOW TABLES LIKE 'ratings'")->num_rows === 1) {
    $rating_stmt = $conn->prepare("
        SELECT r.*
        FROM ratings r
        WHERE r.driver_id = ?
        ORDER BY r.created_at DESC
    ");
    $rating_stmt->bind_param("i", $driver_id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result();
    while ($row = $rating_result->fetch_assoc()) {
        $rating_rows[] = $row;
    }
    $rating_stmt->close();

    // Calculate average rating
    $avg_result = $conn->query("SELECT AVG(rating) AS avg_rating FROM ratings WHERE driver_id = " . (int)$driver_id);
    if ($avg_row = $avg_result->fetch_assoc()) {
        $average_rating = $avg_row['avg_rating'];
    }
}

// Fetch Q&A messages (example table: forum_messages)
$forum_rows = [];
if ($conn->query("SHOW TABLES LIKE 'forum_messages'")->num_rows === 1) {
    $forum_stmt = $conn->prepare("
        SELECT f.*
        FROM forum_messages f
        WHERE f.driver_id = ?
        ORDER BY f.created_at DESC
        LIMIT 20
    ");
    $forum_stmt->bind_param("i", $driver_id);
    $forum_stmt->execute();
    $forum_result = $forum_stmt->get_result();
    while ($row = $forum_result->fetch_assoc()) {
        $forum_rows[] = $row;
    }
    $forum_stmt->close();
}

?>
<?php include "header.php"; ?>

<style>
    .dashboard-container {
        max-width: 900px;
        margin: 20px auto 80px auto;
        background: #ffffff;
        padding: 20px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .dashboard-section {
        margin-bottom: 30px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }

    .dashboard-section:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .section-description {
        font-size: 0.95rem;
        margin-bottom: 15px;
        color: #555;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-box {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 12px 15px;
        text-align: left;
        border: 1px solid #e0e0e0;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #666;
    }

    .stat-value {
        font-size: 1.4rem;
        font-weight: bold;
        margin-top: 5px;
    }

    .dashboard-form label {
        display: block;
        margin-top: 10px;
        font-weight: 500;
    }

    .dashboard-form input,
    .dashboard-form select,
    .dashboard-form textarea {
        width: 100%;
        padding: 8px;
        margin-top: 4px;
        border-radius: 4px;
        border: 1px solid #ccc;
        font-size: 0.95rem;
    }

    .dashboard-form textarea {
        resize: vertical;
    }

    .dashboard-form button {
        margin-top: 15px;
        padding: 8px 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .dashboard-form button.btn-secondary {
        background-color: #6c757d;
    }

    .dashboard-form button.btn-danger {
        background-color: #dc3545;
    }

    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .dashboard-table th,
    .dashboard-table td {
        border: 1px solid #ddd;
        padding: 8px;
        vertical-align: top;
    }

    .dashboard-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .text-small {
        font-size: 0.85rem;
        color: #666;
    }

    .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .badge-pending {
        background-color: #ffc107;
        color: #000;
    }

    .badge-accepted {
        background-color: #28a745;
        color: #fff;
    }

    .badge-rejected {
        background-color: #dc3545;
        color: #fff;
    }
</style>

<h2>Driver Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($driver['name'] ?? 'Driver'); ?>. Manage your profile, transport, and bookings here.</p>

<div class="dashboard-container">

    <!-- Quick stats -->
    <div class="dashboard-section">
        <div class="section-title">Overview</div>
        <div class="section-description">
            A quick summary of your activity as a driver.
        </div>

        <div class="dashboard-grid">
            <div class="stat-box">
                <div class="stat-label">Pending Booking Requests</div>
                <div class="stat-value">
                    <?php
                    $pendingCount = 0;
                    foreach ($booking_rows as $b) {
                        if (isset($b['status']) && $b['status'] === 'pending') {
                            $pendingCount++;
                        }
                    }
                    echo (int)$pendingCount;
                    ?>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-label">Average Rating</div>
                <div class="stat-value">
                    <?php
                    if ($average_rating !== null) {
                        echo number_format($average_rating, 1);
                    } else {
                        echo "N/A";
                    }
                    ?>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-label">Total Completed Trips</div>
                <div class="stat-value">
                    <?php
                    $completedCount = 0;
                    foreach ($booking_rows as $b) {
                        if (isset($b['status']) && $b['status'] === 'completed') {
                            $completedCount++;
                        }
                    }
                    echo (int)$completedCount;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="dashboard-section">
        <div class="section-title">Edit Profile</div>
        <div class="section-description">
            Update your basic driver information.
        </div>

        <form class="dashboard-form" action="update_profile.php" method="post">
            <input type="hidden" name="driver_id" value="<?php echo (int)$driver_id; ?>">

            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($driver['name'] ?? ''); ?>" required>

            <label>Identification ID (IC/Passport/License)</label>
            <input type="text" name="identification_id" value="<?php echo htmlspecialchars($driver['identification_id'] ?? ''); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>" readonly>

            <label>Car Model</label>
            <input type="text" name="car_model" value="<?php echo htmlspecialchars($driver['car_model'] ?? ''); ?>" required>

            <label>Car Plate Number</label>
            <input type="text" name="car_plate_number" value="<?php echo htmlspecialchars($driver['car_plate_number'] ?? ''); ?>" required>

            <button type="submit">Save Profile</button>
        </form>
    </div>

    <!-- Add / Update Transport -->
    <div class="dashboard-section">
        <div class="section-title">Transport Settings</div>
        <div class="section-description">
            Define your vehicle category, areas covered, availability, and pricing.
        </div>

        <form class="dashboard-form" action="save_transport.php" method="post">
            <input type="hidden" name="driver_id" value="<?php echo (int)$driver_id; ?>">
            <?php if ($transport): ?>
                <input type="hidden" name="transport_id" value="<?php echo (int)$transport['transport_id']; ?>">
            <?php endif; ?>

            <label>Vehicle Category</label>
            <select name="vehicle_category" required>
                <option value="">-- Select --</option>
                <option value="MPV"   <?php echo ($transport && $transport['vehicle_category'] === 'MPV')   ? 'selected' : ''; ?>>MPV</option>
                <option value="Sedan" <?php echo ($transport && $transport['vehicle_category'] === 'Sedan') ? 'selected' : ''; ?>>Sedan</option>
                <option value="SUV"   <?php echo ($transport && $transport['vehicle_category'] === 'SUV')   ? 'selected' : ''; ?>>SUV</option>
            </select>

            <label>Vehicle Model</label>
            <input type="text" name="vehicle_model" required
                   value="<?php echo htmlspecialchars($transport['vehicle_model'] ?? $driver['car_model'] ?? ''); ?>">

            <label>Vehicle Plate Number</label>
            <input type="text" name="vehicle_plate" required
                   value="<?php echo htmlspecialchars($transport['vehicle_plate'] ?? $driver['car_plate_number'] ?? ''); ?>">

            <label>Destination / Area Covered</label>
            <input type="text" name="destination" required
                   placeholder="Example: Penang Island, USM Area"
                   value="<?php echo htmlspecialchars($transport['destination'] ?? ''); ?>">

            <label>Day Availability</label>
            <input type="text" name="day_available" required
                   placeholder="Example: Mon–Fri, Weekends Only"
                   value="<?php echo htmlspecialchars($transport['day_available'] ?? ''); ?>">

            <label>Time Availability</label>
            <input type="text" name="time_available" required
                   placeholder="Example: 7AM–10AM / 5PM–9PM"
                   value="<?php echo htmlspecialchars($transport['time_available'] ?? ''); ?>">

            <label>Price</label>
            <input type="text" name="price" required
                   value="<?php echo htmlspecialchars($transport['price'] ?? ''); ?>">

            <label>Payment Method</label>
            <select name="payment_method" required>
                <option value="">-- Select --</option>
                <option value="Cash" 
                    <?php echo ($transport && $transport['payment_method'] === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                <option value="Online Banking" 
                    <?php echo ($transport && $transport['payment_method'] === 'Online Banking') ? 'selected' : ''; ?>>Online Banking</option>
                <option value="E-Wallet" 
                    <?php echo ($transport && $transport['payment_method'] === 'E-Wallet') ? 'selected' : ''; ?>>E-Wallet</option>
            </select>

            <button type="submit">Save Transport Settings</button>
        </form>
    </div>

    <!-- Booking Requests -->
    <div class="dashboard-section">
        <div class="section-title">Booking Requests</div>
        <div class="section-description">
            View and manage booking requests from passengers.
        </div>

        <?php if (count($booking_rows) === 0): ?>
            <p class="text-small">No booking requests found.</p>
        <?php else: ?>
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Passenger</th>
                    <th>Pickup / Drop-off</th>
                    <th>Date & Time</th>
                    <th>Price</th>
                    <th>Status / Action</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($booking_rows as $b): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($b['passenger_name'] ?? 'Passenger'); ?></td>
                        <td>
                            <strong>From:</strong> <?php echo htmlspecialchars($b['pickup_location'] ?? '-'); ?><br>
                            <strong>To:</strong> <?php echo htmlspecialchars($b['dropoff_location'] ?? '-'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($b['request_time'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['price'] ?? ''); ?></td>
                        <td>
                            <?php
                            $status = $b['status'] ?? 'pending';
                            $badgeClass = 'badge-pending';
                            if ($status === 'accepted') $badgeClass = 'badge-accepted';
                            if ($status === 'rejected') $badgeClass = 'badge-rejected';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>

                            <?php if ($status === 'pending'): ?>
                                <form action="update_booking_status.php" method="post" style="margin-top: 6px;">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$b['booking_id']; ?>">
                                    <button type="submit" name="status" value="accepted">Accept</button>
                                    <button type="submit" name="status" value="rejected" class="btn-danger">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Ratings & Reviews -->
    <div class="dashboard-section">
        <div class="section-title">Ratings & Reviews</div>
        <div class="section-description">
            Feedback from passengers about your service.
        </div>

        <?php if (count($rating_rows) === 0): ?>
            <p class="text-small">No ratings or reviews yet.</p>
        <?php else: ?>
            <?php foreach ($rating_rows as $r): ?>
                <div style="margin-bottom: 12px; padding: 10px; border-radius: 6px; background: #f8f9fa; border: 1px solid #e0e0e0;">
                    <strong><?php echo htmlspecialchars($r['passenger_name'] ?? 'Passenger'); ?></strong>
                    <span style="margin-left: 8px;">
                        Rating: <?php echo (int)$r['rating']; ?>/5
                    </span>
                    <div class="text-small">
                        <?php echo htmlspecialchars($r['comment'] ?? ''); ?>
                    </div>
                    <div class="text-small">
                        <?php echo htmlspecialchars($r['created_at'] ?? ''); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Q & A Chat (Forum) -->
    <div class="dashboard-section">
        <div class="section-title">Q & A Forum</div>
        <div class="section-description">
            Ask and answer questions related to your transport service.
        </div>

        <form class="dashboard-form" action="post_forum_message.php" method="post">
            <input type="hidden" name="driver_id" value="<?php echo (int)$driver_id; ?>">
            <label>Your Message</label>
            <textarea name="message" rows="3" required></textarea>
            <button type="submit">Post Message</button>
        </form>

        <?php if (count($forum_rows) > 0): ?>
            <div style="margin-top: 15px;">
                <?php foreach ($forum_rows as $f): ?>
                    <div style="margin-bottom: 10px; padding: 8px; border-radius: 6px; background: #f9f9f9; border: 1px solid #e0e0e0;">
                        <div class="text-small">
                            <strong><?php echo htmlspecialchars($f['author_name'] ?? 'User'); ?></strong>
                            <span style="margin-left: 8px;"><?php echo htmlspecialchars($f['created_at'] ?? ''); ?></span>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($f['message'] ?? '')); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contact Us (Feedback) -->
    <div class="dashboard-section">
        <div class="section-title">Contact Us / Feedback</div>
        <div class="section-description">
            Send feedback or report issues about the system to the admin.
        </div>

        <form class="dashboard-form" action="send_feedback.php" method="post">
            <input type="hidden" name="driver_id" value="<?php echo (int)$driver_id; ?>">

            <label>Subject</label>
            <input type="text" name="subject" required>

            <label>Message</label>
            <textarea name="message" rows="4" required></textarea>

            <button type="submit">Submit Feedback</button>
        </form>
    </div>

</div>

<?php include "footer.php"; ?>
