<?php
session_start();

include "db_connect.php";
include "function.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Get basic driver info
$full_name        = "Driver";
$email            = "";
$car_model        = "";
$car_plate_number = "";

$stmt = $conn->prepare("
    SELECT full_name, email, car_model, car_plate_number
    FROM drivers
    WHERE driver_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row              = $result->fetch_assoc();
        $full_name        = $row['full_name'];
        $email            = $row['email'];
        $car_model        = $row['car_model'];
        $car_plate_number = $row['car_plate_number'];
    }
    $stmt->close();
}

include "header.php";
?>

<style>
body { background: #f5f7fb; }

.dashboard-wrapper {
    min-height: calc(100vh - 140px);
    padding: 30px 24px 40px;
    max-width: 1320px;
    margin: 0 auto;
}

/* Header */
.dashboard-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 22px;
}

.dashboard-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dashboard-title h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #004b82;
}

.dashboard-subtitle {
    font-size: 13px;
    color: #666;
}

/* Blue driver label */
.driver-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #e6f4ff;
    color: #005a9c;
    font-size: 12px;
    font-weight: 500;
}
.driver-chip span.icon {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #005a9c;
    color: white;
    font-size: 11px;
}

/* Only Edit Profile button remains */
.dashboard-actions-top {
    display: flex;
    gap: 8px;
}

.btn-outline {
    border-radius: 999px;
    border: 1px solid #005a9c;
    background: #fff;
    color: #005a9c;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-outline:hover {
    background: #005a9c;
    color: #fff;
}

/* Removed logout button completely */

/* Layout grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 3fr;
    gap: 18px;
    margin-top: 10px;
}

/* Card */
.card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid #e3e6ea;
    padding: 18px 18px 16px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.card-title {
    font-size: 15px;
    font-weight: 600;
    color: #004b82;
}
.card-tag {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 999px;
    background: #eaf7ff;
    color: #0077c2;
    font-weight: 500;
}

/* Profile rows */
.profile-row { margin-bottom: 8px; }
.profile-label { font-size: 12px; color: #888; }
.profile-value { font-size: 13px; font-weight: 500; }

/* Quick Actions - square buttons */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.quick-card {
    border-radius: 18px;
    border: 1px solid #d3d8dd;
    background: #ffffff;
    height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 18px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    transition: 0.2s;
}

.quick-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px rgba(0,0,0,0.15);
}

.quick-icon { font-size: 28px; color: #005a9c; margin-bottom: 8px; }
.quick-title {
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    color: #004b82;
}

.quick-link {
    margin-top: 8px;
    font-size: 11px;
    color: #005a9c;
    text-decoration: none;
    font-weight: 600;
}

.section-title {
    margin-top: 24px;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
}
</style>

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">Manage your transport services, bookings, and profile.</p>

            <div class="driver-chip">
                <span class="icon"><i class="fa-solid fa-car-side"></i></span>
                <span>Registered MMU Driver</span>
            </div>
        </div>

        <div class="dashboard-actions-top">
            <a href="driver_profile_edit.php" class="btn-outline">
                <i class="fa-regular fa-user"></i>Edit profile
            </a>
        </div>
    </div>

    <!-- Main dashboard grid -->
    <div class="dashboard-grid">

        <!-- Profile Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Driver & Vehicle</h3>
                <span class="card-tag">Overview</span>
            </div>
            <div class="card-body">

                <div class="profile-row">
                    <span class="profile-label">Name</span>
                    <span class="profile-value"><?php echo $full_name; ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value"><?php echo $email; ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Car Model</span>
                    <span class="profile-value"><?php echo $car_model ?: "Not set yet"; ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Car Plate Number</span>
                    <span class="profile-value"><?php echo $car_plate_number ?: "Not set yet"; ?></span>
                </div>

            </div>

            <div class="card-footer">
                <a href="driver_profile_edit.php" class="card-link">Update profile & vehicle</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
                <span class="card-tag">Start here</span>
            </div>

            <div class="card-body">
                <div class="quick-actions-grid">

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-car"></i></div>
                        <div class="quick-title">Add / Edit Transport</div>
                        <a href="driver_transport_manage.php" class="quick-link">Open →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="quick-title">Booking Requests</div>
                        <a href="driver_booking_requests.php" class="quick-link">View →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-calendar-day"></i></div>
                        <div class="quick-title">Today's Trips</div>
                        <a href="driver_trips_today.php" class="quick-link">Open →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-star"></i></div>
                        <div class="quick-title">Ratings & Reviews</div>
                        <a href="driver_ratings.php" class="quick-link">View →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-comments"></i></div>
                        <div class="quick-title">Q&A / Chat</div>
                        <a href="driver_forum.php" class="quick-link">Go →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-headset"></i></div>
                        <div class="quick-title">Contact Admin</div>
                        <a href="contact_us.php" class="quick-link">Contact →</a>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>
