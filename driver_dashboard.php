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

// Default values
$full_name       = "Driver";
$email           = "";
$vehicle_model   = "";
$plate_number    = "";
$vehicle_type    = "";
$vehicle_color   = "";
$seat_count      = "";

// Get driver + vehicle info (1 driver, 1 vehicle)
$stmt = $conn->prepare("
    SELECT 
        d.full_name,
        d.email,
        v.vehicle_model,
        v.plate_number,
        v.vehicle_type,
        v.vehicle_color,
        v.seat_count
    FROM drivers d
    LEFT JOIN vehicles v ON v.driver_id = d.driver_id
    WHERE d.driver_id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $full_name     = $row['full_name'];
        $email         = $row['email'];
        $vehicle_model = $row['vehicle_model'] ?? "";
        $plate_number  = $row['plate_number'] ?? "";
        $vehicle_type  = $row['vehicle_type'] ?? "";
        $vehicle_color = $row['vehicle_color'] ?? "";
        $seat_count    = $row['seat_count'] ?? "";
    }

    $stmt->close();
}

include "header.php";
?>

<style>
    body {
        background: #f5f7fb;
    }

    /* ✅ FULL-WIDTH DASHBOARD */
    .dashboard-wrapper {
        min-height: calc(100vh - 140px);
        padding: 30px 40px 40px; /* more breathing space */
        max-width: 100%;
        margin: 0;               /* remove centered container */
        box-sizing: border-box;
    }

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

    /* ✅ Wider + nicer proportions */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1.6fr 2.4fr;
        gap: 36px;
        margin-top: 14px;
    }

    .card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        border: 1px solid #e3e6ea;
        padding: 18px 18px 16px;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .card-title {
        font-size: 15px;
        font-weight: 600;
        color: #004b82;
        margin: 0;
    }

    .card-tag {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #eaf7ff;
        color: #0077c2;
        font-weight: 500;
    }

    .card-body {
        font-size: 13px;
        color: #555;
    }

    .profile-row {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 8px;
    }

    .profile-label {
        font-size: 12px;
        color: #888;
    }

    .profile-value {
        font-size: 13px;
        font-weight: 500;
        color: #333;
    }

    .card-footer {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
    }

    .card-link {
        font-size: 12px;
        color: #005a9c;
        text-decoration: none;
        font-weight: 500;
    }

    .card-link:hover {
        text-decoration: underline;
    }

    /* Quick Actions */
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
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .quick-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(0,0,0,0.15);
    }

    .quick-icon {
        font-size: 28px;
        color: #005a9c;
        margin-bottom: 8px;
    }

    .quick-title {
        font-size: 14px;
        font-weight: 700;
        color: #004b82;
        text-align: center;
    }

    .quick-link {
        margin-top: 8px;
        font-size: 11px;
        color: #005a9c;
        text-decoration: none;
        font-weight: 600;
    }

    .quick-link:hover {
        text-decoration: underline;
    }

    .section-title {
        margin-top: 24px;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 600;
        color: #444;
    }

    .muted-text {
        font-size: 12px;
        color: #888;
    }

    @media (max-width: 900px) {
        .dashboard-wrapper {
            padding: 20px 14px 28px;
        }
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }
        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .dashboard-header {
            align-items: flex-start;
        }
        .dashboard-actions-top {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">
                Manage your transport services, bookings, and profile.
            </p>
            <div class="driver-chip">
                <span class="icon"><i class="fa-solid fa-car-side"></i></span>
                <span>Registered MMU Driver</span>
            </div>
        </div>

        <div class="dashboard-actions-top">
            <a href="driver_profile.php" class="btn-outline">
                <i class="fa-regular fa-user"></i>
                Edit profile
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left: Driver & Vehicle -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Driver & Vehicle</h3>
                <span class="card-tag">Overview</span>
            </div>
            <div class="card-body">
                <div class="profile-row">
                    <span class="profile-label">Name</span>
                    <span class="profile-value"><?php echo htmlspecialchars($full_name); ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value"><?php echo htmlspecialchars($email); ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Vehicle Model</span>
                    <span class="profile-value">
                        <?php echo $vehicle_model ? htmlspecialchars($vehicle_model) : 'Not set yet'; ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Plate Number</span>
                    <span class="profile-value">
                        <?php echo $plate_number ? htmlspecialchars($plate_number) : 'Not set yet'; ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Vehicle Type</span>
                    <span class="profile-value">
                        <?php echo $vehicle_type ? htmlspecialchars($vehicle_type) : 'Not set yet'; ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Vehicle Color</span>
                    <span class="profile-value">
                        <?php echo $vehicle_color ? htmlspecialchars($vehicle_color) : 'Not set yet'; ?>
                    </span>
                </div>

                <div class="profile-row" style="margin-bottom: 0;">
                    <span class="profile-label">Seat Count</span>
                    <span class="profile-value">
                        <?php echo $seat_count ? htmlspecialchars($seat_count) : 'Not set yet'; ?>
                    </span>
                </div>
            </div>
            <div class="card-footer">
                <a href="driver_profile.php" class="card-link">Update profile & vehicle details</a>
            </div>
        </div>

        <!-- Right: Quick Actions -->
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
                        <a href="driver_vehicle.php" class="quick-link">Open →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="quick-title">Booking Requests</div>
                        <a href="driver_booking_requests.php" class="quick-link">View →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-calendar-day"></i></div>
                        <div class="quick-title">Today's Trips</div>
                        <a href="driver_today_trips.php" class="quick-link">Open →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-star"></i></div>
                        <div class="quick-title">Ratings & Reviews</div>
                        <a href="driver_ratings.php" class="quick-link">View →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-icon"><i class="fa-solid fa-comments"></i></div>
                        <div class="quick-title">Chat</div>
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

    <h2 class="section-title">Dashboard notes</h2>
    <p class="muted-text">
        This dashboard is now connected to the vehicles table. 
        Each driver has one active vehicle; when change requests are approved,
        you can update the vehicle information accordingly.
    </p>
</div>

<?php
include "footer.php";
?>
