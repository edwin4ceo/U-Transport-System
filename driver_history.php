<?php
session_start();

include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];
$history = [];

// Fetch bookings
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id, b.pickup_point, b.destination, b.date_time, b.passengers, b.remark, b.status,
        s.name AS passenger_name, s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    WHERE b.driver_id = ?
    ORDER BY b.date_time DESC, b.id DESC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) { while ($row = $result->fetch_assoc()) { $history[] = $row; } }
    $stmt->close();
}

include "header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Global Styles */
    body { background-color: #f4f7fe; font-family: 'Poppins', sans-serif; color: #2b3674; }
    
    .history-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px 20px 80px; /* Extra bottom padding for mobile */
    }

    /* Header Section */
    .page-header { margin-bottom: 25px; text-align: center; }
    .page-header h1 { font-size: 26px; font-weight: 700; color: #004b82; margin: 0; }
    .page-header p { color: #a3aed0; font-size: 14px; margin-top: 5px; }

    /* Modern Search Bar (Icon Removed) */
    .search-box-wrapper {
        position: relative;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(112, 144, 176, 0.08);
        border-radius: 30px;
        background: white;
    }
    
    .search-input {
        width: 100%;
        /* Padding adjusted: removed left space for icon */
        padding: 16px 25px; 
        border: none;
        border-radius: 30px;
        font-size: 15px;
        color: #2b3674;
        background: transparent;
        outline: none;
        transition: all 0.2s;
        text-align: center; /* Center placeholder text */
    }
    .search-input:focus { box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1); }

    /* Card Styling */
    .history-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid transparent;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .history-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-color: #eef2f6;
    }

    /* Status Strip on the left */
    .status-strip {
        position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
    }
    .strip-completed { background: #05cd99; } /* Green */
    .strip-cancelled { background: #ee5d50; } /* Red */
    .strip-pending { background: #ffce20; }   /* Yellow */

    /* Card Layout */
    .card-top { display: flex; justify-content: space-between; margin-bottom: 12px; }
    .trip-date { font-size: 13px; color: #a3aed0; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    
    .route-display { margin-bottom: 15px; padding-left: 10px; border-left: 2px solid #eef2f6; }
    .route-text { font-size: 15px; font-weight: 600; line-height: 1.4; color: #1b2559; }
    
    .card-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 12px; border-top: 1px dashed #eef2f6;
    }
    .passenger-info { font-size: 13px; font-weight: 500; color: #707eae; display: flex; align-items: center; gap: 6px; }
    
    /* Badges */
    .status-badge {
        padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-completed { background: #e6fdf6; color: #05cd99; }
    .badge-cancelled { background: #fff5f5; color: #ee5d50; }
    .badge-pending { background: #fffbf0; color: #ffce20; }

    /* Empty State */
    .empty-state { text-align: center; padding: 50px 20px; color: #a3aed0; }
    .empty-state i { font-size: 40px; margin-bottom: 15px; display: block; opacity: 0.5; }
</style>

<div class="history-wrapper">
    <div class="page-header">
        <h1>Rides History</h1>
        <p>Review your past journeys and earnings</p>
    </div>

    <div class="search-box-wrapper">
        <input type="text" id="historySearchInput" class="search-input" placeholder="Type passenger name, location or ID...">
    </div>

    <div id="historyList">
        <?php if (count($history) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                No history found. Time to hit the road!
            </div>
        <?php else: ?>
            <?php foreach ($history as $row): ?>
                <?php
                    // Data Processing
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? 'PENDING'));
                    
                    // Style Logic
                    if (in_array($statusRaw, ['COMPLETED', 'FINISHED'])) {
                        $stripClass = "strip-completed"; $badgeClass = "badge-completed";
                    } elseif (in_array($statusRaw, ['CANCELLED', 'REJECTED'])) {
                        $stripClass = "strip-cancelled"; $badgeClass = "badge-cancelled";
                    } else {
                        $stripClass = "strip-pending"; $badgeClass = "badge-pending";
                    }

                    $routeText = ($row['pickup_point'] && $row['destination']) 
                                 ? htmlspecialchars($row['pickup_point']) . ' <i class="fa-solid fa-arrow-right-long" style="color:#a3aed0; font-size:12px; margin:0 5px;"></i> ' . htmlspecialchars($row['destination'])
                                 : "Trip #$id";
                ?>
                <div class="history-card">
                    <div class="status-strip <?php echo $stripClass; ?>"></div>
                    
                    <div class="card-top">
                        <div class="trip-date"><i class="fa-regular fa-calendar-alt"></i> <?php echo $datetime; ?></div>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusRaw; ?></span>
                    </div>

                    <div class="route-display">
                        <div class="route-text"><?php echo $routeText; ?></div>
                    </div>

                    <div class="card-footer">
                        <div class="passenger-info">
                            <i class="fa-solid fa-user-circle"></i>
                            <?php echo htmlspecialchars($row['passenger_name'] ?? 'Guest'); ?>
                            <?php if($row['passengers'] > 1): ?>
                                <span style="background:#f4f7fe; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:5px;">+<?php echo $row['passengers']-1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:11px; color:#a3aed0; font-weight:600;">ID: #<?php echo $id; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time Search Logic
document.getElementById('historySearchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let cards = document.querySelectorAll('.history-card');
    
    cards.forEach(card => {
        let text = card.innerText.toLowerCase();
        card.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<?php include "footer.php"; ?>