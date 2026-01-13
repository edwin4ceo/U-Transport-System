<?php
session_start();
include "db_connect.php";

// INCLUDE THE NEW HEADER
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: admin_login.php"); exit; }

// --- Processing Logic ---
$alert_fire = ""; 

if (isset($_POST['process_request'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; 
    $current_time = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("SELECT * FROM vehicle_change_requests WHERE request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request && $request['status'] === 'pending') {
        if ($action === 'reject') {
            $update = $conn->prepare("UPDATE vehicle_change_requests SET status = 'rejected', processed_at = ? WHERE request_id = ?");
            $update->bind_param("si", $current_time, $request_id);
            if($update->execute()) {
                $alert_fire = "Swal.fire({ icon: 'success', title: 'Rejected', text: 'Request has been rejected.', confirmButtonColor: '#e74c3c' }).then(() => { window.location.href='admin_vehicle_requests.php'; });";
            }
            $update->close();
        } elseif ($action === 'approve') {
            $conn->begin_transaction();
            try {
                $driver_id = $request['driver_id'];
                $old_vid = $request['old_vehicle_id']; // This might be NULL for new drivers
                
                // Get New Data
                $model = $request['new_vehicle_model']; // Note: Ensure your DB column names match these keys. 
                // Based on previous context, your table columns are actually: vehicle_model, plate_number etc in vehicle_change_requests table.
                // Let's fix the variable assignment based on STANDARD column names you added.
                
                $model = $request['vehicle_model']; // Correct column name
                $plate = $request['plate_number'];
                $type  = $request['vehicle_type'];
                $color = $request['vehicle_color'];
                $seat  = $request['seat_count'];
                $road  = $request['road_tax_expiry'];
                $ins   = $request['insurance_expiry'];

                // 1. Check if driver already has a vehicle (Logic: Update existing OR Insert new)
                // We check the 'vehicles' table directly to be safe
                $check_v = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE driver_id = ?");
                $check_v->bind_param("i", $driver_id);
                $check_v->execute();
                $res_v = $check_v->get_result();
                $existing_vehicle = $res_v->fetch_assoc();
                $check_v->close();

                if ($existing_vehicle) {
                    // --- UPDATE EXISTING VEHICLE ---
                    $vid = $existing_vehicle['vehicle_id'];
                    $up_veh = $conn->prepare("UPDATE vehicles SET vehicle_model=?, plate_number=?, vehicle_type=?, vehicle_color=?, seat_count=?, road_tax_expiry=?, insurance_expiry=? WHERE vehicle_id=?");
                    // Types: s=string, i=int. (model, plate, type, color, seat(i), road, ins, vid(i))
                    $up_veh->bind_param("ssssissi", $model, $plate, $type, $color, $seat, $road, $ins, $vid);
                    $up_veh->execute();
                } else {
                    // --- INSERT NEW VEHICLE ---
                    $in_veh = $conn->prepare("INSERT INTO vehicles (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count, road_tax_expiry, insurance_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    // Types: i=driver_id, s...
                    $in_veh->bind_param("issssiss", $driver_id, $model, $plate, $type, $color, $seat, $road, $ins);
                    $in_veh->execute();
                }

                // 2. Mark Request as Approved
                $up_req = $conn->prepare("UPDATE vehicle_change_requests SET status = 'approved', processed_at = ? WHERE request_id = ?");
                $up_req->bind_param("si", $current_time, $request_id);
                $up_req->execute();

                $conn->commit();
                $alert_fire = "Swal.fire({ icon: 'success', title: 'Approved', text: 'Vehicle details updated successfully.', confirmButtonColor: '#27ae60' }).then(() => { window.location.href='admin_vehicle_requests.php'; });";

            } catch (Exception $e) {
                $conn->rollback();
                // Show error message for debugging if needed (remove .$e->getMessage() in production)
                $alert_fire = "Swal.fire({ icon: 'error', title: 'Error', text: 'Database error: " . addslashes($e->getMessage()) . "' });";
            }
        }
    }
}

// Fetch Pending Requests
$sql = "SELECT r.*, d.full_name as driver_name, d.email as driver_email FROM vehicle_change_requests r LEFT JOIN drivers d ON r.driver_id = d.driver_id WHERE r.status = 'pending' ORDER BY r.created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vehicle Requests | FMD Staff</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: -apple-system, sans-serif; }
        .admin-wrapper { padding: 30px 5%; max-width: 1200px; margin: 0 auto; }
        .page-title { color: #004b82; font-size: 24px; font-weight: 700; margin-bottom: 20px; }
        
        .req-card { 
            background: #fff; border-radius: 12px; border: 1px solid #e3e6ea; 
            padding: 25px; margin-bottom: 20px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; 
        }
        .req-info { flex: 1; min-width: 300px; }
        
        .req-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .driver-badge { background: #e8f4ff; color: #004b82; padding: 5px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; }
        .req-date { font-size: 12px; color: #888; }
        
        .comparison-grid { 
            display: grid; grid-template-columns: 120px 1fr; gap: 8px 15px; font-size: 14px; 
            background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e0;
        }
        .lbl { color: #64748b; font-weight: 600; }
        .val { color: #333; font-weight: 600; }
        .highlight { color: #004b82; font-weight: 700; }

        .req-actions { display: flex; flex-direction: column; gap: 10px; min-width: 140px; }
        .btn-approve, .btn-reject { padding: 12px 20px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; text-align: center; width: 100%; color: white; }
        .btn-approve { background: #27ae60; box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2); }
        .btn-approve:hover { background: #219150; transform: translateY(-1px); }
        .btn-reject { background: #e74c3c; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2); }
        .btn-reject:hover { background: #c0392b; transform: translateY(-1px); }
        .empty-state { text-align: center; padding: 50px; color: #a0aec0; font-size: 16px; }
    </style>
</head>
<body>

    <div class="admin-wrapper">
        <div class="page-title">Pending Vehicle Requests</div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="req-card">
                    <div class="req-info">
                        <div class="req-header">
                            <span class="driver-badge"><i class="fa-solid fa-user-tie"></i> <?php echo isset($row['driver_name']) ? htmlspecialchars($row['driver_name']) : 'Driver #' . $row['driver_id']; ?></span>
                            <span class="req-date"><i class="fa-regular fa-clock"></i> <?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></span>
                        </div>
                        
                        <div class="comparison-grid">
                            <div class="lbl">New Model:</div>
                            <div class="val highlight"><?php echo htmlspecialchars($row['vehicle_model']); ?></div>
                            
                            <div class="lbl">Plate Number:</div>
                            <div class="val highlight" style="font-family:monospace; font-size:15px;"><?php echo htmlspecialchars($row['plate_number']); ?></div>
                            
                            <div class="lbl">Details:</div>
                            <div class="val"><?php echo htmlspecialchars($row['vehicle_color']); ?> • <?php echo htmlspecialchars($row['vehicle_type']); ?> • <?php echo htmlspecialchars($row['seat_count']); ?> Seats</div>
                            
                            <div class="lbl">Road Tax:</div>
                            <div class="val"><?php echo htmlspecialchars($row['road_tax_expiry']); ?></div>

                            <div class="lbl">Insurance:</div>
                            <div class="val"><?php echo htmlspecialchars($row['insurance_expiry']); ?></div>
                        </div>
                    </div>

                    <div class="req-actions">
                        <form method="post" onsubmit="return confirm('Confirm APPROVE this vehicle update?');">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" name="process_request" class="btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Confirm REJECT this vehicle update?');">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" name="process_request" class="btn-reject"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="req-card empty-state">
                <i class="fa-solid fa-clipboard-check" style="font-size:48px; margin-bottom:15px; opacity:0.5;"></i>
                <p>No pending vehicle requests found.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if(!empty($alert_fire)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $alert_fire; ?>
            });
        </script>
    <?php endif; ?>
</body>
</html>