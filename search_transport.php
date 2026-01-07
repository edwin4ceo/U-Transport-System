<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// 2. Logic: Find Active Rides with Available Seats
// We group bookings by Driver + DateTime to form a "Trip"
// Then we calculate: Vehicle Capacity - Sum(Passengers)
$sql = "
    SELECT 
        b.driver_id,
        b.date_time,
        b.destination,
        b.vehicle_type,
        d.full_name AS driver_name,
        v.seat_count,
        v.plate_number,
        v.vehicle_model,
        SUM(b.passengers) as total_occupied
    FROM bookings b
    JOIN drivers d ON b.driver_id = d.driver_id
    JOIN vehicles v ON d.driver_id = v.driver_id
    WHERE b.status = 'Accepted' 
      AND b.date_time > NOW() -- Only future rides
    GROUP BY b.driver_id, b.date_time
    ORDER BY b.date_time ASC
";

$available_rides = [];
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate remaining seats
        // Default seat count is 4 if not set in DB
        $capacity = isset($row['seat_count']) ? (int)$row['seat_count'] : 4;
        $occupied = (int)$row['total_occupied'];
        $remaining = $capacity - $occupied;

        // Only show if there are seats left
        if ($remaining > 0) {
            $row['remaining_seats'] = $remaining;
            $available_rides[] = $row;
        }
    }
}

include "header.php";
?>

<style>
/* Reuse the nice Card Design */
.search-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
    background: #f5f7fb;
}
.header-title h1 { margin: 0; font-size: 22px; font-weight: 700; color: #004b82; }
.header-title p { margin: 0; font-size: 13px; color: #666; }
.ride-card { background: #ffffff; border-radius: 16px; border: 1px solid #e3e6ea; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 15px; transition: transform 0.2s; }
.ride-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
.route-text { font-size: 16px; font-weight: 700; color: #004b82; margin-bottom: 5px; }
.info-row { display: flex; gap: 15px; font-size: 13px; color: #555; margin-bottom: 12px; align-items: center; }
.seat-badge { background-color: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; }
.btn-join { background-color: #009688; color: white; padding: 8px 20px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 13px; display: inline-block; }
.btn-join:hover { background-color: #00796b; }
.empty-state { text-align: center; padding: 40px; color: #777; }
</style>

<div class="search-wrapper">
    <div style="margin-bottom: 20px;">
        <div class="header-title">
            <h1>Find a Ride</h1>
            <p>Join existing trips and save costs by carpooling.</p>
        </div>
    </div>

    <?php if (empty($available_rides)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-car-side" style="font-size: 40px; margin-bottom: 15px; color: #ccc;"></i>
            <p>No available carpool rides found at the moment.</p>
            <a href="passanger_request_transport.php" style="color: #2196F3; font-weight: bold;">Request a new ride instead?</a>
        </div>
    <?php else: ?>
        <?php foreach ($available_rides as $ride): ?>
            <div class="ride-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="route-text">
                            To: <?php echo htmlspecialchars($ride['destination']); ?>
                        </div>
                        <div class="info-row">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($ride['date_time'])); ?></span>
                            <span><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($ride['driver_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span style="background: #f5f5f5; padding: 3px 8px; border-radius: 4px;">
                                <i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($ride['vehicle_model']); ?> (<?php echo htmlspecialchars($ride['plate_number']); ?>)
                            </span>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <div style="margin-bottom: 10px;">
                            <span class="seat-badge">
                                <?php echo $ride['remaining_seats']; ?> Seats Left
                            </span>
                        </div>
                        
                        <a href="passanger_request_transport.php?join_driver=<?php echo $ride['driver_id']; ?>&join_date=<?php echo urlencode($ride['date_time']); ?>&join_dest=<?php echo urlencode($ride['destination']); ?>" class="btn-join">
                            Join Ride <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include "footer.php"; ?>