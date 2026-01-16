<?php 
// FUNCTION: START SESSION
session_start();

// FUNCTION: SET TIMEZONE
// Ensure correct time comparisons for upcoming rides
date_default_timezone_set('Asia/Kuala_Lumpur');

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// FUNCTION: CHECK LOGIN STATUS
// Redirect to login if user session is not found
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];
// Retrieve name safely (fallback to 'Student' if session missing)
$student_name = isset($_SESSION['student_name']) ? $_SESSION['student_name'] : "Student";

// =========================================================
// LOGIC: GET UPCOMING RIDE
// Fetches the next confirmed or pending trip to display on the dashboard
// =========================================================
$current_time = date("Y-m-d H:i:s");
$sql = "SELECT * FROM bookings 
        WHERE student_id = ? 
        AND status IN ('Pending', 'Accepted', 'Approved') 
        AND date_time > ? 
        ORDER BY date_time ASC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $student_id, $current_time);
$stmt->execute();
$upcoming_ride = $stmt->get_result()->fetch_assoc();
$stmt->close();

// INCLUDE HEADER (Contains Navigation & Global Styles)
include "header.php"; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    /* ========================================= */
    /* 1. PAGE ENTRANCE ANIMATION (NEW!)         */
    /* ========================================= */
    @keyframes fadeInUpPage {
        0% {
            opacity: 0;
            transform: translateY(60px); /* Start 60px below */
        }
        100% {
            opacity: 1;
            transform: translateY(0);    /* End at normal position */
        }
    }

    /* 2. RESET HEADER CONTAINER (CRITICAL FIX) */
    /* This overrides the default 'full page' white box from header.php */
    .content-area {
        background: transparent !important; /* Make the main page bg transparent */
        box-shadow: none !important;        /* Remove default shadow */
        border: none !important;            /* Remove border */
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* 3. DASHBOARD MAIN CARD (The "White Box" you requested) */
    .dashboard-wrapper {
        background: #ffffff;          /* White background */
        max-width: 1000px;
        width: 90%;                   /* Responsive width */
        margin: 40px auto 60px auto;  /* Center horizontally, add spacing top/bottom */
        padding: 40px;                /* Inner spacing */
        border-radius: 24px;          /* Nice rounded corners */
        box-shadow: 0 15px 40px rgba(0,0,0,0.08); /* Float effect */
        font-family: 'Poppins', sans-serif;

        /* Apply the entrance animation here */
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* 4. WELCOME SECTION */
    .welcome-section {
        margin-bottom: 30px;
        text-align: left; 
    }
    .welcome-section h2 {
        font-size: 28px;
        font-weight: 700;
        color: #004b82; /* Theme Blue */
        margin-bottom: 5px;
        margin-top: 0;
    }
    .welcome-section p {
        font-size: 15px;
        color: #64748b; /* Grey text */
        margin: 0;
    }

    /* 5. STATUS CARD (Upcoming Trip Widget) */
    .status-card {
        background: #f8fafc; /* Very light grey to contrast with white dashboard */
        border-radius: 20px;
        padding: 25px;
        /* Subtle border to define edges on white bg */
        border: 1px solid #e2e8f0; 
        margin-bottom: 35px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.3s ease;
        width: 100%; 
        box-sizing: border-box;
    }
    .status-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }
    .status-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .status-icon-box {
        width: 60px;
        height: 60px;
        background: #ffffff; /* White icon box */
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #005A9C;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .status-info h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }
    .status-info span {
        font-size: 14px;
        color: #666;
        display: block;
    }
    
    /* Status Badge Styling */
    .status-badge {
        background: #dcfce7; /* Green BG */
        color: #166534;      /* Green Text */
        padding: 8px 16px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
    }
    .status-badge.pending { 
        background: #fff7ed; /* Orange BG for Pending */
        color: #9a3412; 
    }

    /* 6. QUICK ACCESS GRID */
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        padding-left: 15px;
        border-left: 4px solid #005A9C; /* Blue Accent Bar */
    }

    .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Responsive Grid */
        gap: 20px;
        width: 100%;
    }

    /* Action Cards (Buttons) */
    .action-card {
        background: #ffffff;
        padding: 30px 20px;
        border-radius: 20px;
        text-align: center;
        text-decoration: none;
        color: #333;
        /* Added border to make them pop against the white dashboard wrapper */
        border: 1px solid #f1f5f9; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 180px; 
    }

    .action-card:hover {
        border-color: #005A9C;
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 90, 156, 0.1);
        background: #f8fbff; /* Slight tint on hover */
    }

    /* Icon Wrappers */
    .icon-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 28px;
        transition: 0.3s;
    }

    /* Individual Card Colors */
    .card-search .icon-wrapper { background: #e0f2fe; color: #0284c7; }
    .card-search:hover .icon-wrapper { background: #0284c7; color: white; }

    .card-request .icon-wrapper { background: #f0fdf4; color: #16a34a; }
    .card-request:hover .icon-wrapper { background: #16a34a; color: white; }

    .card-rides .icon-wrapper { background: #fefce8; color: #ca8a04; }
    .card-rides:hover .icon-wrapper { background: #ca8a04; color: white; }

    .card-profile .icon-wrapper { background: #f3e8ff; color: #9333ea; }
    .card-profile:hover .icon-wrapper { background: #9333ea; color: white; }

    .action-card h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }
    .action-card p {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 5px;
    }

    /* Mobile Responsive Adjustments */
    @media (max-width: 768px) {
        .dashboard-wrapper { 
            padding: 25px; 
            width: 95%;
            margin: 20px auto;
        }
        .status-card { flex-direction: column; text-align: center; gap: 15px; }
        .status-left { flex-direction: column; }
        .quick-access-grid { grid-template-columns: 1fr 1fr; } 
    }
</style>

<div class="dashboard-wrapper">
    
    <div class="welcome-section">
        <h2>Good Day, <?php echo htmlspecialchars($student_name); ?>! 👋</h2>
        <p>Ready to move? Check your upcoming trip or start a new journey.</p>
    </div>

    <?php if($upcoming_ride): ?>
        <div onclick="window.location.href='passanger_rides.php'" style="cursor: pointer;">
            <div class="status-card">
                <div class="status-left">
                    <div class="status-icon-box">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="status-info">
                        <h4>Upcoming Trip</h4>
                        <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars(substr($upcoming_ride['destination'], 0, 30)) . '...'; ?></span>
                        <span><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($upcoming_ride['date_time'])); ?></span>
                    </div>
                </div>
                <div class="status-badge <?php echo ($upcoming_ride['status'] == 'Pending') ? 'pending' : ''; ?>">
                    <?php echo $upcoming_ride['status']; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="status-card" style="border-style: dashed; background: #fff;">
            <div class="status-left">
                <div class="status-icon-box" style="background:#f1f5f9; color:#94a3b8;">
                    <i class="fa-solid fa-car-tunnel"></i>
                </div>
                <div class="status-info">
                    <h4 style="color:#64748b;">No Upcoming Trips</h4>
                    <span>Plan your next journey with U-Transport now.</span>
                </div>
            </div>
            <a href="search_transport.php" class="status-badge" style="background:#005A9C; color:white; text-decoration:none;">Book Now</a>
        </div>
    <?php endif; ?>

    <div class="section-title">Quick Access</div>
    
    <div class="quick-access-grid">
        
        <a href="search_transport.php" class="action-card card-search">
            <div class="icon-wrapper"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h3>Search Rides</h3>
            <p>Find available carpools</p>
        </a>

        <a href="passanger_request_transport.php" class="action-card card-request">
            <div class="icon-wrapper"><i class="fa-solid fa-plus"></i></div>
            <h3>Request Ride</h3>
            <p>Create a custom request</p>
        </a>

        <a href="passanger_rides.php" class="action-card card-rides">
            <div class="icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
            <h3>My Activity</h3>
            <p>Check history & status</p>
        </a>

        <a href="passanger_profile.php" class="action-card card-profile">
            <div class="icon-wrapper"><i class="fa-solid fa-user-gear"></i></div>
            <h3>My Profile</h3>
            <p>Edit account details</p>
        </a>

    </div>

</div>

<?php include "footer.php"; ?>