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

// Get basic driver info from database
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
    body {
        background: #f5f7fb;
    }

    .dashboard-wrapper {
        min-height: calc(100vh - 140px);
        padding: 30px 24px 40px;
        max-width: 1120px;
        margin: 0 auto;
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
        margin: 0;
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
        color: #fff;
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

    .btn-logout {
        border-radius: 999px;
        border: 1px solid #e74c3c;
        background: #fff;
        color: #e74c3c;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-logout:hover {
        background: #e74c3c;
        color: #fff;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 3fr;
        gap: 18px;
        margin-top: 10px;
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

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .quick-card {
        border-radius: 14px;
        border: 1px solid #e3e6ea;
        background: #fdfdff;
        padding: 12px 12px 10px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .quick-title {
        font-size: 13px;
        font-weight: 600;
        color: #004b82;
    }

    .quick-desc {
        font-size: 12px;
        color: #666;
        flex: 1;
    }

    .quick-link {
        margin-top: 4px;
        font-size: 11px;
        color: #005a9c;
        text-decoration: none;
        font-weight: 500;
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
    }
</style>

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋</h1>
            <p class="dashboard-subtitle">
                This is your driver dashboard. Manage your transport services, bookings, and profile here.
            </p>
            <div class="driver-chip">
                <span class="icon"><i class="fa-solid fa-car-side"></i></span>
                <span>Registered MMU Driver</span>
            </div>
        </div>

        <div class="dashboard-actions-top">
            <a href="driver_profile_edit.php" class="btn-outline">
                <i class="fa-regular fa-user"></i>
                Edit profile
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left: Profile & vehicle -->
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
                    <span class="profile-label">Car Model</span>
                    <span class="profile-value">
                        <?php echo $car_model ? htmlspecialchars($car_model) : 'Not set yet'; ?>
                    </span>
                </div>

                <div class="profile-row" style="margin-bottom: 0;">
                    <span class="profile-label">Car Plate Number</span>
                    <span class="profile-value">
                        <?php echo $car_plate_number ? htmlspecialchars($car_plate_number) : 'Not set yet'; ?>
                    </span>
                </div>
            </div>
            <div class="card-footer">
                <a href="driver_profile_edit.php" class="card-link">Update profile & vehicle details</a>
            </div>
        </div>

        <!-- Right: Quick actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
                <span class="card-tag">Start here</span>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <div class="quick-card">
                        <div class="quick-title">Add / Edit Transport</div>
                        <div class="quick-desc">
                            Set your route, availability, price, and payment methods.
                        </div>
                            <!-- link to a page you will create -->
                        <a href="driver_transport_manage.php" class="quick-link">Open transport settings →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-title">Booking Requests</div>
                        <div class="quick-desc">
                            View and respond to student booking requests.
                        </div>
                        <a href="driver_booking_requests.php" class="quick-link">View requests →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-title">Today’s Trips</div>
                        <div class="quick-desc">
                            Check your schedule and upcoming pickups for today.
                        </div>
                        <a href="driver_trips_today.php" class="quick-link">View today’s trips →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-title">Ratings & Reviews</div>
                        <div class="quick-desc">
                            See feedback from passengers about your service.
                        </div>
                        <a href="driver_ratings.php" class="quick-link">Open ratings →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-title">Q & A / Chat</div>
                        <div class="quick-desc">
                            Answer questions or chat with passengers in the forum.
                        </div>
                        <a href="driver_forum.php" class="quick-link">Go to Q&A →</a>
                    </div>

                    <div class="quick-card">
                        <div class="quick-title">Contact Admin</div>
                        <div class="quick-desc">
                            Send feedback or report an issue to the system admin.
                        </div>
                        <a href="contact_us.php" class="quick-link">Contact us →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- You can later show real data here (without dummy numbers now) -->
    <h2 class="section-title">Dashboard notes</h2>
    <p class="muted-text">
        This dashboard is ready to connect with your booking, rating, and chat modules.
        Once those pages are built, the links above will lead drivers to manage each feature.
    </p>
</div>

<?php
include "footer.php";
?>
