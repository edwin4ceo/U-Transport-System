<?php
session_start();

include "db_connect.php";
include "function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

/* ----------------------------------------
   Fetch current vehicle (if any)
----------------------------------------- */
$current_vehicle = null;

$stmt = $conn->prepare("
    SELECT vehicle_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count
    FROM vehicles
    WHERE driver_id = ?
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $current_vehicle = $result->fetch_assoc();
    }
    $stmt->close();
}

/* ----------------------------------------
   Handle vehicle change request
----------------------------------------- */
if (isset($_POST['request_change'])) {

    $new_model  = trim($_POST['new_vehicle_model'] ?? "");
    $new_plate  = trim($_POST['new_plate_number'] ?? "");
    $new_type   = trim($_POST['new_vehicle_type'] ?? "");
    $new_color  = trim($_POST['new_vehicle_color'] ?? "");
    $new_seats  = trim($_POST['new_seat_count'] ?? "");

    if ($new_model === "" || $new_plate === "") {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in at least vehicle model and plate number.";
        $_SESSION['swal_type']  = "warning";
    } elseif ($new_seats !== "" && !ctype_digit($new_seats)) {
        $_SESSION['swal_title'] = "Invalid Seat Count";
        $_SESSION['swal_msg']   = "Seat count must be a positive number.";
        $_SESSION['swal_type']  = "warning";
    } else {

        // Check if there is already a pending request
        $check = $conn->prepare("
            SELECT request_id 
            FROM vehicle_change_requests 
            WHERE driver_id = ? AND status = 'pending'
            LIMIT 1
        ");
        if ($check) {
            $check->bind_param("i", $driver_id);
            $check->execute();
            $res = $check->get_result();
            if ($res && $res->num_rows > 0) {
                $_SESSION['swal_title'] = "Request Exists";
                $_SESSION['swal_msg']   = "You already have a pending vehicle change request. Please wait for admin approval.";
                $_SESSION['swal_type']  = "info";
                $check->close();
                redirect("driver_vehicle.php");
                exit;
            }
            $check->close();
        }

        $old_vehicle_id = $current_vehicle ? (int)$current_vehicle['vehicle_id'] : null;
        $seats_int      = ($new_seats === "") ? null : (int)$new_seats;

        $insert = $conn->prepare("
            INSERT INTO vehicle_change_requests
            (driver_id, old_vehicle_id, new_vehicle_model, new_plate_number,
             new_vehicle_type, new_vehicle_color, new_seat_count)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if ($insert) {
            $insert->bind_param(
                "iissssi",
                $driver_id,
                $old_vehicle_id,
                $new_model,
                $new_plate,
                $new_type,
                $new_color,
                $seats_int
            );

            if ($insert->execute()) {
                $_SESSION['swal_title'] = "Request Submitted";
                $_SESSION['swal_msg']   = "Your vehicle change request has been submitted. Please wait for admin approval.";
                $_SESSION['swal_type']  = "success";
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Failed to submit request. Please try again.";
                $_SESSION['swal_type']  = "error";
            }

            $insert->close();
        } else {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error (insert request).";
            $_SESSION['swal_type']  = "error";
        }
    }

    redirect("driver_vehicle.php");
    exit;
}

/* ----------------------------------------
   Fetch latest request for display
----------------------------------------- */
$latest_request = null;

$req = $conn->prepare("
    SELECT new_vehicle_model, new_plate_number, status, admin_comment, created_at
    FROM vehicle_change_requests
    WHERE driver_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
if ($req) {
    $req->bind_param("i", $driver_id);
    $req->execute();
    $r = $req->get_result();
    if ($r && $r->num_rows === 1) {
        $latest_request = $r->fetch_assoc();
    }
    $req->close();
}

include "header.php";
?>

<style>
.vehicle-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 900px;
    margin: 0 auto;
}

.page-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}
.page-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.vehicle-grid {
    display: grid;
    grid-template-columns: 1.4fr 2fr;
    gap: 18px;
}

.card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 18px 18px 16px;
}

.card-title {
    font-size: 15px;
    font-weight: 600;
    color: #004b82;
    margin-bottom: 8px;
}

.card-subtitle {
    font-size: 12px;
    color: #888;
    margin-bottom: 10px;
}

.info-row {
    margin-bottom: 8px;
}
.info-label {
    font-size: 12px;
    color: #888;
}
.info-value {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.form-grid-1col {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-group label {
    display: block;
    font-size: 12px;
    color: #444;
    margin-bottom: 4px;
    font-weight: 500;
}
.form-group input {
    width: 100%;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group input:focus {
    border-color: #005a9c;
    box-shadow: 0 0 0 2px rgba(0,90,156,0.18);
}

.btn-primary-pill {
    border: none;
    padding: 9px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, #005a9c, #27ae60);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    transition: 0.15s;
}
.btn-primary-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.2);
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 500;
}
.badge-pending {
    background: #fff4e5;
    color: #d97706;
}
.badge-approved {
    background: #e6f7ec;
    color: #15803d;
}
.badge-rejected {
    background: #fee2e2;
    color: #b91c1c;
}

@media (max-width: 900px) {
    .vehicle-grid {
        grid-template-columns: 1fr;
    }
    .vehicle-wrapper {
        padding: 24px 10px 30px;
    }
}
</style>

<div class="vehicle-wrapper">
    <div class="page-title" style="margin-bottom:16px;">
        <h1>Vehicle Information</h1>
        <p>View your current vehicle and request a change. Changes will only take effect after admin approval.</p>
    </div>

    <div class="vehicle-grid">
        <!-- Current vehicle -->
        <div class="card">
            <div class="card-title">Current Vehicle</div>
            <div class="card-subtitle">This is the vehicle currently registered under your driver account.</div>

            <?php if ($current_vehicle): ?>
                <div class="info-row">
                    <div class="info-label">Model</div>
                    <div class="info-value"><?php echo htmlspecialchars($current_vehicle['vehicle_model']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Plate Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($current_vehicle['plate_number']); ?></div>
                </div>

                <?php if (!empty($current_vehicle['vehicle_type'])): ?>
                <div class="info-row">
                    <div class="info-label">Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($current_vehicle['vehicle_type']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($current_vehicle['vehicle_color'])): ?>
                <div class="info-row">
                    <div class="info-label">Color</div>
                    <div class="info-value"><?php echo htmlspecialchars($current_vehicle['vehicle_color']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($current_vehicle['seat_count'])): ?>
                <div class="info-row">
                    <div class="info-label">Seat Count</div>
                    <div class="info-value"><?php echo (int)$current_vehicle['seat_count']; ?></div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p style="font-size:13px; color:#555;">
                    You do not have a vehicle registered yet. Please contact the system admin to set up your first vehicle.
                </p>
            <?php endif; ?>

            <?php if ($latest_request): ?>
                <hr style="margin:12px 0; border:none; border-top:1px dashed #e2e2e2;">
                <div class="card-subtitle">Latest change request</div>
                <div class="info-row">
                    <div class="info-label">Requested Model</div>
                    <div class="info-value"><?php echo htmlspecialchars($latest_request['new_vehicle_model']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Requested Plate</div>
                    <div class="info-value"><?php echo htmlspecialchars($latest_request['new_plate_number']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <?php
                        $status = $latest_request['status'];
                        $badgeClass = 'badge-pending';
                        if ($status === 'approved') $badgeClass = 'badge-approved';
                        if ($status === 'rejected') $badgeClass = 'badge-rejected';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($latest_request['admin_comment'])): ?>
                <div class="info-row">
                    <div class="info-label">Admin Comment</div>
                    <div class="info-value"><?php echo htmlspecialchars($latest_request['admin_comment']); ?></div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Request form -->
        <div class="card">
            <div class="card-title">Request Vehicle Change</div>
            <div class="card-subtitle">
                Fill in the details of the new vehicle you want to use. Your request will be reviewed by the admin.
            </div>

            <form method="post" action="">
                <div class="form-grid-1col">
                    <div class="form-group">
                        <label for="new_vehicle_model">New Vehicle Model *</label>
                        <input type="text" id="new_vehicle_model" name="new_vehicle_model" required>
                    </div>

                    <div class="form-group">
                        <label for="new_plate_number">New Plate Number *</label>
                        <input type="text" id="new_plate_number" name="new_plate_number" required>
                    </div>

                    <div class="form-group">
                        <label for="new_vehicle_type">New Vehicle Type (optional)</label>
                        <input type="text" id="new_vehicle_type" name="new_vehicle_type" placeholder="e.g. Sedan, MPV">
                    </div>

                    <div class="form-group">
                        <label for="new_vehicle_color">New Vehicle Color (optional)</label>
                        <input type="text" id="new_vehicle_color" name="new_vehicle_color" placeholder="e.g. White">
                    </div>

                    <div class="form-group">
                        <label for="new_seat_count">New Seat Count (optional)</label>
                        <input type="text" id="new_seat_count" name="new_seat_count" placeholder="e.g. 4">
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="submit" name="request_change" class="btn-primary-pill">
                        Submit Change Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include "footer.php";
?>
