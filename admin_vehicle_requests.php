<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "db_connect.php";
// include "function.php"; // Uncomment if needed

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

/* -------------------------------------------------
   Handle Approve / Reject Actions
-------------------------------------------------- */
if (isset($_POST['process_request'])) {
    $request_id = $_POST['request_id'];
    $action     = $_POST['action']; 
    
    // Get current timestamp for 'processed_at'
    $current_time = date("Y-m-d H:i:s");

    // Fetch details of the specific request
    $stmt = $conn->prepare("SELECT * FROM vehicle_change_requests WHERE request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request && $request['status'] === 'pending') {
        if ($action === 'reject') {
            // --- REJECT LOGIC ---
            $update = $conn->prepare("UPDATE vehicle_change_requests SET status = 'rejected', processed_at = ? WHERE request_id = ?");
            $update->bind_param("si", $current_time, $request_id);
            $update->execute();
            $update->close();
            
            echo "<script>alert('Request Rejected.'); window.location.href='admin_vehicle_requests.php';</script>";
            exit;

        } elseif ($action === 'approve') {
            // --- APPROVE LOGIC ---
            $conn->begin_transaction(); // Start transaction

            try {
                $driver_id = $request['driver_id'];
                $old_vid   = $request['old_vehicle_id'];
                
                // Prepare variables from the request
                $model = $request['new_vehicle_model'];
                $plate = $request['new_plate_number'];
                $type  = $request['new_vehicle_type'];
                $color = $request['new_vehicle_color'];
                $seat  = $request['new_seat_count'];

                // A. Update existing vehicle if driver already has one
                if ($old_vid) {
                    $up_veh = $conn->prepare("UPDATE vehicles SET vehicle_model=?, plate_number=?, vehicle_type=?, vehicle_color=?, seat_count=? WHERE vehicle_id=? AND driver_id=?");
                    $up_veh->bind_param("ssssiii", $model, $plate, $type, $color, $seat, $old_vid, $driver_id);
                    $up_veh->execute();
                    $up_veh->close();
                } 
                // B. Insert new vehicle if driver has none
                else {
                    $in_veh = $conn->prepare("INSERT INTO vehicles (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count) VALUES (?, ?, ?, ?, ?, ?)");
                    $in_veh->bind_param("issssi", $driver_id, $model, $plate, $type, $color, $seat);
                    $in_veh->execute();
                    $in_veh->close();
                }

                // C. Update request status AND processed_at
                $up_req = $conn->prepare("UPDATE vehicle_change_requests SET status = 'approved', processed_at = ? WHERE request_id = ?");
                $up_req->bind_param("si", $current_time, $request_id);
                $up_req->execute();
                $up_req->close();

                // Commit changes
                $conn->commit();
                echo "<script>alert('Request Approved Successfully.'); window.location.href='admin_vehicle_requests.php';</script>";
                exit;

            } catch (Exception $e) {
                // Rollback if any error occurs
                $conn->rollback(); 
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='admin_vehicle_requests.php';</script>";
                exit;
            }
        }
    }
    // Redirect to prevent form resubmission
    header("Location: admin_vehicle_requests.php");
    exit;
}

/* -------------------------------------------------
   Fetch Pending Requests (CORRECT JOIN QUERY)
-------------------------------------------------- */
// Using LEFT JOIN with the 'drivers' table because the FK 'driver_id' points there.
// We select 'd.full_name' as 'driver_name'.
$sql = "
    SELECT r.*, d.full_name as driver_name, d.email as driver_email
    FROM vehicle_change_requests r
    LEFT JOIN drivers d ON r.driver_id = d.driver_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vehicle Requests | FMD Staff</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }

        /* --- Header / Navbar Styles --- */
        .admin-header {
            background-color: #2c3e50;
            color: white;
            padding: 0;
            height: 70px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            width: 90%;
            margin: 0 auto;
        }
        .logo-section h1 { font-size: 1.5rem; margin: 0; }
        .admin-nav ul { list-style: none; display: flex; gap: 20px; padding: 0; margin: 0; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .admin-nav a:hover { color: white; }
        .nav-divider { width: 1px; background: rgba(255,255,255,0.2); height: 25px; margin: 0 10px; }
        
        /* --- Page Specific Styles --- */
        .admin-wrapper { padding: 30px 5%; max-width: 1200px; margin: 0 auto; }
        .page-title { color: #004b82; font-size: 24px; font-weight: 700; margin-bottom: 20px; }
        
        .req-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e3e6ea;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        .req-info { flex: 1; min-width: 300px; }
        .req-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .driver-badge {
            background: #e8f4ff; color: #004b82; padding: 4px 10px;
            border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase;
        }
        .req-date { font-size: 12px; color: #888; }
        .comparison-grid { display: grid; grid-template-columns: 100px 1fr; gap: 8px 15px; font-size: 13px; }
        .lbl { color: #888; font-weight: 500; }
        .val { color: #333; font-weight: 600; }
        .highlight { color: #d35400; }
        
        .req-actions { display: flex; flex-direction: column; gap: 10px; min-width: 120px; }
        .btn-approve, .btn-reject {
            padding: 10px 20px; border: none; border-radius: 8px; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: 0.2s; text-align: center; width: 100%;
        }
        .btn-approve { background: #27ae60; color: white; }
        .btn-approve:hover { background: #219150; }
        .btn-reject { background: #e74c3c; color: white; }
        .btn-reject:hover { background: #c0392b; }
        .empty-state { text-align: center; padding: 40px; color: #999; font-size: 14px; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div class="logo-section">
                <h1><i class="fa-solid fa-building-user"></i> FMD Staff</h1>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php">Home</a></li>
                    <li><a href="verify_drivers.php">Approve</a></li>
                    <li><a href="view_drivers.php">Drivers</a></li>
                    <li><a href="view_passengers.php">Passengers</a></li>
                    <li><a href="view_bookings.php">Bookings</a></li>
                    
                    <li class="nav-divider"></li>
                    <li><a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                    <li><a href="logout.php" style="color:#e74c3c;"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="admin-wrapper">
        <div class="page-title">Pending Vehicle Requests</div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="req-card">
                    <div class="req-info">
                        <div class="req-header">
                            <span class="driver-badge">
                                <?php echo isset($row['driver_name']) ? htmlspecialchars($row['driver_name']) : 'Driver #' . $row['driver_id']; ?>
                            </span>
                            <span class="req-date">Requested on: <?php echo date("d M Y, H:i", strtotime($row['created_at'])); ?></span>
                        </div>
                        
                        <div style="font-size:12px; color:#666; margin-bottom:8px;">
                            Email: <?php echo isset($row['driver_email']) ? htmlspecialchars($row['driver_email']) : '-'; ?>
                        </div>

                        <div class="comparison-grid">
                            <div class="lbl">New Model:</div>
                            <div class="val highlight"><?php echo htmlspecialchars($row['new_vehicle_model']); ?></div>

                            <div class="lbl">New Plate:</div>
                            <div class="val highlight"><?php echo htmlspecialchars($row['new_plate_number']); ?></div>

                            <div class="lbl">Details:</div>
                            <div class="val">
                                <?php echo htmlspecialchars($row['new_vehicle_color']); ?> - 
                                <?php echo htmlspecialchars($row['new_vehicle_type']); ?> 
                                (<?php echo htmlspecialchars($row['new_seat_count']); ?> Seats)
                            </div>

                            <div class="lbl">Type:</div>
                            <div class="val" style="color: #555;">
                                <?php if ($row['old_vehicle_id']): ?>
                                    <span style="color: #f39c12">Change Vehicle</span> (Update existing)
                                <?php else: ?>
                                    <span style="color: #27ae60">New Registration</span> (First time)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="req-actions">
                        <form method="post" onsubmit="return confirm('Approve this request?');">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" name="process_request" class="btn-approve">Approve</button>
                        </form>

                        <form method="post" onsubmit="return confirm('Reject this request?');">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" name="process_request" class="btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="req-card empty-state">
                <p>No pending vehicle requests at the moment.</p>
                <br>
                <?php 
                if (!$result) {
                    echo "Query Error: " . $conn->error; 
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>