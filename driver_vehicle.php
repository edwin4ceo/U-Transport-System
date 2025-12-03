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

/* -------------------------------------------------
   Step 1: Fetch current vehicle for this driver
-------------------------------------------------- */
$current_vehicle = null;

$veh_stmt = $conn->prepare("
    SELECT vehicle_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count
    FROM vehicles
    WHERE driver_id = ?
    LIMIT 1
");

if ($veh_stmt) {
    $veh_stmt->bind_param("i", $driver_id);
    $veh_stmt->execute();
    $veh_result = $veh_stmt->get_result();
    if ($veh_result && $veh_result->num_rows === 1) {
        $current_vehicle = $veh_result->fetch_assoc();
    }
    $veh_stmt->close();
}

/* -------------------------------------------------
   Step 2: Handle vehicle change request submission
-------------------------------------------------- */
if (isset($_POST['request_change'])) {

    $new_vehicle_model  = trim($_POST['new_vehicle_model'] ?? "");
    $new_plate_number   = trim($_POST['new_plate_number'] ?? "");
    $new_vehicle_type   = trim($_POST['new_vehicle_type'] ?? "");
    $new_vehicle_color  = trim($_POST['new_vehicle_color'] ?? "");
    $new_seat_count     = trim($_POST['new_seat_count'] ?? "");

    // Basic validation
    if (
        $new_vehicle_model === "" ||
        $new_plate_number === "" ||
        $new_vehicle_type === "" ||
        $new_vehicle_color === "" ||
        $new_seat_count === ""
    ) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Please fill in all fields in the vehicle change request form.";
        $_SESSION['swal_type']  = "warning";
    } elseif (!ctype_digit($new_seat_count) || (int)$new_seat_count <= 0) {
        $_SESSION['swal_title'] = "Invalid Seat Count";
        $_SESSION['swal_msg']   = "Seat count must be a positive number.";
        $_SESSION['swal_type']  = "warning";
    } else {

        // Optional: block if driver already has a pending request
        $pending_stmt = $conn->prepare("
            SELECT request_id 
            FROM vehicle_change_requests
            WHERE driver_id = ? AND status = 'pending'
            LIMIT 1
        ");

        $has_pending = false;
        if ($pending_stmt) {
            $pending_stmt->bind_param("i", $driver_id);
            $pending_stmt->execute();
            $pending_res = $pending_stmt->get_result();
            if ($pending_res && $pending_res->num_rows > 0) {
                $has_pending = true;
            }
            $pending_stmt->close();
        }

        if ($has_pending) {
            $_SESSION['swal_title'] = "Pending Request";
            $_SESSION['swal_msg']   = "You already have a pending vehicle change request. Please wait for admin approval.";
            $_SESSION['swal_type']  = "info";
        } else {
            // Find current vehicle_id, if exists
            $old_vehicle_id = $current_vehicle ? (int)$current_vehicle['vehicle_id'] : null;

            $insert = $conn->prepare("
                INSERT INTO vehicle_change_requests
                (driver_id, old_vehicle_id, new_vehicle_model, new_plate_number, new_vehicle_type, new_vehicle_color, new_seat_count, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            if ($insert) {
                // old_vehicle_id may be null if driver has no vehicle yet
                if ($old_vehicle_id === null) {
                    $null = null;
                    $insert->bind_param(
                        "isssssi",
                        $driver_id,
                        $null,
                        $new_vehicle_model,
                        $new_plate_number,
                        $new_vehicle_type,
                        $new_vehicle_color,
                        $new_seat_count
                    );
                } else {
                    $insert->bind_param(
                        "iissssi",
                        $driver_id,
                        $old_vehicle_id,
                        $new_vehicle_model,
                        $new_plate_number,
                        $new_vehicle_type,
                        $new_vehicle_color,
                        $new_seat_count
                    );
                }

                if ($insert->execute()) {
                    $_SESSION['swal_title'] = "Request Submitted";
                    $_SESSION['swal_msg']   = "Your vehicle change request has been submitted and is awaiting admin approval.";
                    $_SESSION['swal_type']  = "success";
                } else {
                    $_SESSION['swal_title'] = "Error";
                    $_SESSION['swal_msg']   = "Failed to submit your request. Please try again.";
                    $_SESSION['swal_type']  = "error";
                }

                $insert->close();
            } else {
                $_SESSION['swal_title'] = "Error";
                $_SESSION['swal_msg']   = "Database error (insert request).";
                $_SESSION['swal_type']  = "error";
            }
        }
    }

    // Redirect to avoid resubmission on refresh
    redirect("driver_vehicle.php");
    exit;
}

/* -------------------------------------------------
   Step 3: Optionally fetch last request status
-------------------------------------------------- */
$last_request = null;

$req_stmt = $conn->prepare("
    SELECT request_id, status, created_at
    FROM vehicle_change_requests
    WHERE driver_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

if ($req_stmt) {
    $req_stmt->bind_param("i", $driver_id);
    $req_stmt->execute();
    $req_res = $req_stmt->get_result();
    if ($req_res && $req_res->num_rows === 1) {
        $last_request = $req_res->fetch_assoc();
    }
    $req_stmt->close();
}

include "header.php";
?>

<style>
.vehicle-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1100px;
    margin: 0 auto;
}

.vehicle-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
}

.vehicle-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.vehicle-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.vehicle-grid {
    display: grid;
    grid-template-columns: 1.4fr 1.8fr;
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
    margin-bottom: 10px;
}

.card-subtitle {
    font-size: 12px;
    color: #777;
    margin-bottom: 12px;
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

.empty-text {
    font-size: 13px;
    color: #999;
}

/* Request form */
.form-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #004b82;
    margin-bottom: 8px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px 16px;
}

.form-group-full {
    grid-column: 1 / 3;
}

.form-group label {
    display: block;
    font-size: 12px;
    color: #444;
    margin-bottom: 4px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus {
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

.request-status {
    margin-top: 10px;
    font-size: 12px;
    color: #666;
}

.request-status span.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.badge-pending {
    background: #fff7e6;
    color: #d48806;
}
.badge-approved {
    background: #e6fffb;
    color: #08979c;
}
.badge-rejected {
    background: #fff2f0;
    color: #cf1322;
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
    <div class="vehicle-header-title">
        <h1>Vehicle & Transport Settings</h1>
        <p>View your current vehicle and submit a request if you need to change your transport details.</p>
    </div>

    <div class="vehicle-grid">
        <!-- Left: Current vehicle info -->
        <div class="card">
            <div class="card-title">Current Vehicle</div>
            <div class="card-subtitle">
                This is the vehicle currently registered to your driver account.
            </div>

            <?php if ($current_vehicle): ?>
                <div class="info-row">
                    <div class="info-label">Model</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($current_vehicle['vehicle_model']); ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Plate Number</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($current_vehicle['plate_number']); ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Type</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($current_vehicle['vehicle_type']); ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Color</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($current_vehicle['vehicle_color']); ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Seat Count</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($current_vehicle['seat_count']); ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-text">
                    You do not have a vehicle registered yet.  
                    Please submit a request on the right to register your first vehicle.
                </p>
            <?php endif; ?>

            <?php if ($last_request): ?>
                <div class="request-status">
                    Last request status:
                    <?php
                        $status = $last_request['status'];
                        $badgeClass = "badge-pending";
                        if ($status === "approved") {
                            $badgeClass = "badge-approved";
                        } elseif ($status === "rejected") {
                            $badgeClass = "badge-rejected";
                        }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                    </span>
                    <span>
                        (submitted on <?php echo htmlspecialchars(date("d M Y", strtotime($last_request['created_at']))); ?>)
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Request change form -->
        <div class="card">
            <div class="form-section-title">Request Vehicle Change</div>
            <div class="card-subtitle">
                Fill in the details of the vehicle you want to use. Your request will be reviewed by the admin.
            </div>

            <form method="post" action="">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="new_vehicle_model">New Vehicle Model</label>
                        <input type="text" id="new_vehicle_model" name="new_vehicle_model" placeholder="e.g. Perodua Myvi" required>
                    </div>

                    <div class="form-group">
                        <label for="new_plate_number">New Plate Number</label>
                        <input type="text" id="new_plate_number" name="new_plate_number" placeholder="e.g. WXX 1234" required>
                    </div>

                    <div class="form-group">
                        <label for="new_vehicle_type">Vehicle Type</label>
                        <input type="text" id="new_vehicle_type" name="new_vehicle_type" placeholder="e.g. Sedan, Hatchback" required>
                    </div>

                    <div class="form-group">
                        <label for="new_vehicle_color">Vehicle Color</label>
                        <input type="text" id="new_vehicle_color" name="new_vehicle_color" placeholder="e.g. White, Black" required>
                    </div>

                    <div class="form-group">
                        <label for="new_seat_count">Seat Count</label>
                        <input type="number" id="new_seat_count" name="new_seat_count" min="1" placeholder="e.g. 4" required>
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
