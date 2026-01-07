<?php
session_start();
include "db_connect.php";
include "function.php";

// Ensure only logged-in Admin can access
if (!isset($_SESSION['admin_id'])) {
    redirect("admin_login.php");
    exit;
}

/* -------------------------------------------------
   Handle Approve / Reject Actions
-------------------------------------------------- */
if (isset($_POST['process_request'])) {
    $request_id = $_POST['request_id'];
    $action     = $_POST['action']; // 'approve' or 'reject'

    // 1. Fetch details of the specific request
    $stmt = $conn->prepare("SELECT * FROM vehicle_change_requests WHERE request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request && $request['status'] === 'pending') {
        
        if ($action === 'reject') {
            // --- REJECT LOGIC ---
            // Just update the status to 'rejected'
            $update = $conn->prepare("UPDATE vehicle_change_requests SET status = 'rejected' WHERE request_id = ?");
            $update->bind_param("i", $request_id);
            $update->execute();
            $update->close();

            $_SESSION['swal_title'] = "Rejected";
            $_SESSION['swal_msg']   = "The request has been rejected.";
            $_SESSION['swal_type']  = "info";

        } elseif ($action === 'approve') {
            // --- APPROVE LOGIC ---
            
            // Start transaction to ensure data consistency
            $conn->begin_transaction();

            try {
                $driver_id = $request['driver_id'];
                $old_vid   = $request['old_vehicle_id'];
                
                // Prepare new data variables
                $model = $request['new_vehicle_model'];
                $plate = $request['new_plate_number'];
                $type  = $request['new_vehicle_type'];
                $color = $request['new_vehicle_color'];
                $seat  = $request['new_seat_count'];

                // A. If driver already has a vehicle -> Update existing vehicle record
                if ($old_vid) {
                    $up_veh = $conn->prepare("
                        UPDATE vehicles 
                        SET vehicle_model=?, plate_number=?, vehicle_type=?, vehicle_color=?, seat_count=?
                        WHERE vehicle_id=? AND driver_id=?
                    ");
                    $up_veh->bind_param("ssssiii", $model, $plate, $type, $color, $seat, $old_vid, $driver_id);
                    $up_veh->execute();
                    $up_veh->close();
                } 
                // B. If driver has no vehicle yet -> Insert new vehicle record
                else {
                    $in_veh = $conn->prepare("
                        INSERT INTO vehicles (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $in_veh->bind_param("issssi", $driver_id, $model, $plate, $type, $color, $seat);
                    $in_veh->execute();
                    $in_veh->close();
                }

                // C. Update request status to 'approved'
                $up_req = $conn->prepare("UPDATE vehicle_change_requests SET status = 'approved' WHERE request_id = ?");
                $up_req->bind_param("i", $request_id);
                $up_req->execute();
                $up_req->close();

                // Commit the transaction
                $conn->commit();

                $_SESSION['swal_title'] = "Approved";
                $_SESSION['swal_msg']   = "Vehicle details have been updated successfully.";
                $_SESSION['swal_type']  = "success";

            } catch (Exception $e) {
                // Rollback changes if an error occurs
                $conn->rollback(); 
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Database error: " . $e->getMessage();
                $_SESSION['swal_type']  = "error";
            }
        }
    } else {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg']   = "Request not found or already processed.";
        $_SESSION['swal_type']  = "error";
    }

    // Redirect to prevent form resubmission on refresh
    redirect("admin_vehicle_requests.php");
    exit;
}

/* -------------------------------------------------
   Fetch Pending Requests
   (Join driver table to get driver's name)
-------------------------------------------------- */
// Note: Ensure 'd.username' matches the column name in your 'drivers' table
$sql = "
    SELECT r.*, d.username as driver_name 
    FROM vehicle_change_requests r
    JOIN drivers d ON r.driver_id = d.driver_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
";
$result = $conn->query($sql);

include "header.php"; // Admin header
?>

<style>
/* CSS styles reused from previous design */
.admin-wrapper {
    padding: 30px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-title {
    color: #004b82;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
}

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

.req-info {
    flex: 1;
    min-width: 300px;
}

.req-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.driver-badge {
    background: #e8f4ff;
    color: #004b82;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.req-date {
    font-size: 12px;
    color: #888;
}

.comparison-grid {
    display: grid;
    grid-template-columns: 100px 1fr;
    gap: 8px 15px;
    font-size: 13px;
}

.lbl { color: #888; font-weight: 500; }
.val { color: #333; font-weight: 600; }
.highlight { color: #d35400; } /* Highlight for new values */

.req-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 120px;
}

.btn-approve, .btn-reject {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    text-align: center;
    width: 100%;
}

.btn-approve {
    background: #27ae60;
    color: white;
}
.btn-approve:hover { background: #219150; }

.btn-reject {
    background: #e74c3c;
    color: white;
}
.btn-reject:hover { background: #c0392b; }

.empty-state {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 14px;
}
</style>

<div class="admin-wrapper">
    <div class="page-title">Pending Vehicle Requests</div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="req-card">
                <div class="req-info">
                    <div class="req-header">
                        <span class="driver-badge"><?php echo htmlspecialchars($row['driver_name']); ?></span>
                        <span class="req-date">Requested on: <?php echo date("d M Y, H:i", strtotime($row['created_at'])); ?></span>
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
                    <form method="post" onsubmit="return confirm('Are you sure you want to APPROVE this vehicle change?');">
                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" name="process_request" class="btn-approve">Approve</button>
                    </form>

                    <form method="post" onsubmit="return confirm('Are you sure you want to REJECT this request?');">
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
        </div>
    <?php endif; ?>

</div>

<?php include "footer.php"; ?>