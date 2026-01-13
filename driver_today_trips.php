<?php
session_start();
include "db_connect.php";
include "function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}
$driver_id = $_SESSION['driver_id'];

// --- SQL Logic: Strictly filter by CURRENT DATE ONLY ---
$trips = [];
$stmt = $conn->prepare("
    SELECT b.*, s.name AS passenger_name, s.phone AS passenger_phone 
    FROM bookings b 
    LEFT JOIN students s ON b.student_id = s.student_id 
    WHERE b.driver_id = ? 
      AND DATE(b.date_time) = CURDATE() 
    ORDER BY b.date_time ASC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $trips[] = $row; }
    $stmt->close();
}

include "header.php";
?>

<style>
    .today-wrapper { padding: 20px 15px; max-width: 500px; margin: 0 auto; background: #f8fafc; }
    
    /* Date Header */
    .date-focus-header { 
        text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; 
    }
    .date-focus-header h1 { font-size: 20px; color: #004b82; margin: 0; font-weight: 800; }
    .date-focus-header span { font-size: 14px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

    /* Compact Card Design */
    .trip-card { 
        background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px; 
        border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
        position: relative;
    }
    .trip-time { font-weight: 800; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 6px; }
    
    .status-tag { 
        position: absolute; top: 15px; right: 15px; font-size: 10px; padding: 2px 8px; 
        border-radius: 4px; font-weight: 700; text-transform: uppercase; 
    }
    .status-completed { background: #dcfce7; color: #15803d; }
    .status-accepted { background: #e0f2fe; color: #0369a1; }
    .status-cancelled { background: #fee2e2; color: #b91c1c; }

    .trip-details { font-size: 13px; color: #475569; margin: 12px 0; line-height: 1.5; }
    .route-path { 
        background: #f8fafc; border-left: 3px solid #cbd5e1; padding: 8px 12px; 
        margin-top: 8px; border-radius: 0 6px 6px 0; 
    }

    .payment-summary { 
        display: flex; justify-content: space-between; align-items: center; 
        padding-top: 10px; border-top: 1px dotted #e2e8f0; 
    }
    .cash-label { font-size: 12px; font-weight: 600; color: #64748b; }
    .cash-amount { font-size: 18px; font-weight: 800; color: #16a34a; }
</style>

<div class="today-wrapper">
    <div class="date-focus-header">
        <span>Schedule For</span>
        <h1><?php echo date("l, d F Y"); ?></h1> </div>
    
    <?php if (empty($trips)): ?>
        <div style="text-align:center; padding: 60px 20px; color:#94a3b8;">
            <i class="fa-solid fa-calendar-day" style="font-size: 40px; opacity: 0.3; margin-bottom: 15px;"></i>
            <p>No trips scheduled for today.</p>
        </div>
    <?php else: ?>
        <?php foreach ($trips as $row): ?>
            <div class="trip-card">
                <span class="status-tag status-<?php echo strtolower($row['status']); ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                </span>

                <div class="trip-time">
                    <i class="fa-regular fa-clock" style="color: #004b82;"></i> 
                    <?php echo date("h:i A", strtotime($row['date_time'])); ?>
                </div>
                
                <div class="trip-details">
                    <strong>Passenger:</strong> <?php echo htmlspecialchars($row['passenger_name']); ?><br>
                    <div class="route-path">
                        <i class="fa-solid fa-location-dot" style="color: #004b82; width: 15px;"></i> <?php echo htmlspecialchars($row['pickup_point']); ?><br>
                        <i class="fa-solid fa-arrow-down" style="font-size: 10px; margin-left: 3px; color: #cbd5e1;"></i><br>
                        <i class="fa-solid fa-map-pin" style="color: #e53e3e; width: 15px;"></i> <?php echo htmlspecialchars($row['destination']); ?>
                    </div>
                </div>

                <div class="payment-summary">
                    <span class="cash-label">To Collect:</span>
                    <span class="cash-amount">RM <?php echo number_format($row['fare'], 2); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>