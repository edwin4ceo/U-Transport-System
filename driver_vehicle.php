<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Auth Check
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];
$success_msg = "";
$error_msg = "";

// 2. Handle Form Submission (Request Vehicle Change)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_request_change'])) {
    
    // Check if there is already a pending request
    $check = $conn->query("SELECT request_id FROM vehicle_change_requests WHERE driver_id='$driver_id' AND status='pending'");
    
    if ($check->num_rows > 0) {
        $error_msg = "You already have a pending request. Please wait for Admin approval.";
    } else {
        // Get Input
        $model = trim($_POST['model']);
        $plate = trim($_POST['plate']);
        $type  = trim($_POST['type']);
        $color = trim($_POST['color']);
        $seats = intval($_POST['seats']);
        
        $road_tax = $_POST['road_tax_expiry'];
        $insurance = $_POST['insurance_expiry'];

        // Validation
        if (empty($model) || empty($plate)) {
            $error_msg = "Vehicle Model and Plate Number are required.";
        } elseif ($seats > 6) { 
            // Server-side safety check for seat limit
            $error_msg = "Seat capacity cannot exceed 6.";
        } else {
            // Insert Request
            $stmt = $conn->prepare("INSERT INTO vehicle_change_requests (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count, road_tax_expiry, insurance_expiry, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            // Clean lowercase types
            $stmt->bind_param("issssiss", $driver_id, $model, $plate, $type, $color, $seats, $road_tax, $insurance);
            
            if ($stmt->execute()) {
                $success_msg = "Request submitted successfully! Admin will review your details.";
            } else {
                $error_msg = "Failed to submit request.";
            }
        }
    }
}

// 3. Fetch Current Vehicle Data
$v_stmt = $conn->prepare("SELECT * FROM vehicles WHERE driver_id = ?");
$v_stmt->bind_param("i", $driver_id);
$v_stmt->execute();
$vehicle = $v_stmt->get_result()->fetch_assoc();

// 4. Fetch Latest Request Status (for display)
$r_stmt = $conn->prepare("SELECT * FROM vehicle_change_requests WHERE driver_id = ? ORDER BY created_at DESC LIMIT 1");
$r_stmt->bind_param("i", $driver_id);
$r_stmt->execute();
$last_req = $r_stmt->get_result()->fetch_assoc();

include "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* --- 样式：经典居中布局 (Cleaned) --- */
:root {
    --primary: #004b82;
    --primary-dark: #00365e;
    --accent: #3182ce;
    --bg-color: #f8f9fc;
    --card-bg: #ffffff;
    --text-main: #1a202c;
    --text-light: #718096;
    --border-radius: 16px;
    --shadow: 0 4px 20px rgba(0,0,0,0.03);
}

body {
    background-color: var(--bg-color);
    font-family: 'Inter', sans-serif;
}

/* 居中布局设置 */
.page-wrapper { 
    max-width: 1200px;      /* 限制最大宽度 */
    width: 95%;             
    margin: 0 auto 40px;    /* 居中 */
    padding: 20px; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    display: grid; 
    grid-template-columns: 380px 1fr; 
    gap: 30px; 
}

/* Card Container */
.info-card { 
    background: white; border-radius: 16px; 
    padding: 35px; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
    border: 1px solid #eef2f6; height: fit-content;
}

.card-header {
    font-size: 18px; font-weight: 700; color: #2d3748; 
    padding-bottom: 15px; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
}

/* Current Vehicle Styling (Left Column) */
.vehicle-display-box { text-align: center; margin-bottom: 25px; }

.v-icon {
    width: 90px; height: 90px; background: #ebf8ff; color: #3182ce;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 36px; margin: 0 auto 15px;
    box-shadow: 0 4px 10px rgba(49, 130, 206, 0.15);
}

.v-plate {
    background: #1a202c; color: white; padding: 6px 16px; border-radius: 6px;
    font-family: monospace; font-size: 16px; font-weight: 700; display: inline-block;
    border: 2px solid #cbd5e0; letter-spacing: 1px;
}

.v-model { font-size: 20px; font-weight: 700; color: #2d3748; margin: 15px 0 5px; }

.v-detail-list { margin-top: 20px; text-align: left; }
.v-detail-row {
    display: flex; justify-content: space-between; padding: 12px 0;
    border-bottom: 1px dashed #e2e8f0; font-size: 14px;
}
.v-detail-row:last-child { border-bottom: none; }
.v-label { color: #718096; font-weight: 500; }
.v-val { font-weight: 600; color: #2d3748; }

/* Status Badges */
.status-badge {
    padding: 15px; border-radius: 10px; margin-top: 25px;
    font-size: 13px; display: flex; align-items: center; gap: 10px; font-weight: 600;
}
.sb-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; }
.sb-approved { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
.sb-rejected { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }

/* Form Elements */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; }
.form-input { 
    width: 100%; padding: 12px 16px; border: 1px solid #cbd5e0; border-radius: 10px; 
    font-size: 15px; outline: none; transition: border 0.2s; background: #fff;
}
.form-input:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }

.btn-submit {
    background: #004b82; color: white; border: none; padding: 14px 30px; 
    border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer;
    display: block; width: 100%; box-shadow: 0 4px 6px rgba(0, 75, 130, 0.2);
    transition: background 0.2s;
}
.btn-submit:hover { background: #00365e; }

/* Policy Box */
.policy-box {
    background: #ebf8ff; color: #2c5282; padding: 20px; border-radius: 12px; 
    font-size: 13px; margin-bottom: 25px; border: 1px solid #bee3f8;
}

/* Responsive */
@media (max-width: 900px) {
    .page-wrapper { grid-template-columns: 1fr; }
}
</style>

<div class="page-wrapper">

    <?php if($success_msg): ?>
        <script>Swal.fire('Success', '<?php echo $success_msg; ?>', 'success');</script>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <script>Swal.fire('Error', '<?php echo $error_msg; ?>', 'error');</script>
    <?php endif; ?>

    <div class="info-card">
        <div class="card-header"><i class="fa-solid fa-car-side"></i> Current Vehicle</div>
        
        <?php if($vehicle): ?>
            <div class="vehicle-display-box">
                <div class="v-icon"><i class="fa-solid fa-car"></i></div>
                <div class="v-plate"><?php echo htmlspecialchars($vehicle['plate_number']); ?></div>
                <div class="v-model"><?php echo htmlspecialchars($vehicle['vehicle_model']); ?></div>
                <div style="font-size:13px; color:#718096;"><?php echo htmlspecialchars($vehicle['vehicle_color']); ?> • <?php echo htmlspecialchars($vehicle['vehicle_type']); ?></div>
            </div>

            <div class="v-detail-list">
                <div class="v-detail-row">
                    <span class="v-label">Seat Capacity</span>
                    <span class="v-val"><?php echo htmlspecialchars($vehicle['seat_count']); ?> Pax</span>
                </div>
                <div class="v-detail-row">
                    <span class="v-label">Road Tax Expiry</span>
                    <span class="v-val" style="<?php echo (!empty($vehicle['road_tax_expiry']) && strtotime($vehicle['road_tax_expiry']) < time()) ? 'color:#e53e3e;' : 'color:#2f855a;'; ?>">
                        <?php echo !empty($vehicle['road_tax_expiry']) ? date("d M Y", strtotime($vehicle['road_tax_expiry'])) : 'N/A'; ?>
                    </span>
                </div>
                <div class="v-detail-row">
                    <span class="v-label">Insurance Expiry</span>
                    <span class="v-val" style="<?php echo (!empty($vehicle['insurance_expiry']) && strtotime($vehicle['insurance_expiry']) < time()) ? 'color:#e53e3e;' : 'color:#2f855a;'; ?>">
                        <?php echo !empty($vehicle['insurance_expiry']) ? date("d M Y", strtotime($vehicle['insurance_expiry'])) : 'N/A'; ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:40px 0; color:#cbd5e0;">
                <i class="fa-solid fa-circle-question" style="font-size:40px; margin-bottom:10px;"></i><br>
                No vehicle assigned yet.
            </div>
        <?php endif; ?>

        <?php if($last_req): ?>
            <div class="status-badge sb-<?php echo strtolower($last_req['status']); ?>">
                <?php 
                    if($last_req['status'] == 'pending') echo '<i class="fa-solid fa-clock"></i> Request Pending Review';
                    elseif($last_req['status'] == 'approved') echo '<i class="fa-solid fa-circle-check"></i> Last Request Approved';
                    else echo '<i class="fa-solid fa-circle-xmark"></i> Last Request Rejected';
                ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="info-card">
        <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Request Vehicle Update</div>

        <?php 
        // If pending, hide form and show message
        if($last_req && $last_req['status'] == 'pending'): 
        ?>
            <div style="text-align:center; padding:60px 20px;">
                <i class="fa-solid fa-hourglass-half" style="font-size:50px; color:#cbd5e0; margin-bottom:20px;"></i>
                <h3 style="color:#2d3748; font-size:20px; margin-bottom:10px;">Request Under Review</h3>
                <p style="color:#718096; font-size:14px; line-height:1.6; max-width:400px; margin:0 auto;">
                    You submitted a vehicle update request on <b><?php echo date("d M Y", strtotime($last_req['created_at'])); ?></b>.
                    <br>Please wait for the administrator to approve it before making another request.
                </p>
            </div>
        <?php else: ?>
            
            <div class="policy-box">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <i class="fa-solid fa-circle-info" style="font-size:16px;"></i>
                    <strong style="font-size:14px;">Policy: One Active Vehicle per Driver</strong>
                </div>
                
                <p style="margin:0 0 10px; line-height:1.5;">
                    To maintain the highest safety standards for students, our system limits each driver to <strong>one active vehicle</strong> at a time. This ensures that:
                </p>
                <ul style="margin:0; padding-left:20px; line-height:1.6;">
                    <li><strong>Passenger Safety:</strong> Students can accurately identify your vehicle based on the booking details.</li>
                    <li><strong>Insurance Compliance:</strong> We must verify that the specific vehicle in use has valid Road Tax and Insurance.</li>
                </ul>
                <p style="margin:10px 0 0; font-style:italic; opacity:0.8;">
                    * Submitting this form will replace your current vehicle details upon Admin approval.
                </p>
            </div>

            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">New Vehicle Model</label>
                        <input type="text" name="model" class="form-input" placeholder="e.g. Perodua Myvi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Plate Number</label>
                        <input type="text" name="plate" class="form-input" placeholder="e.g. WXX 1234" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Vehicle Type</label>
                        <select name="type" class="form-input">
                            <option value="Sedan">Sedan</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="SUV">SUV</option>
                            <option value="MPV">MPV</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vehicle Color</label>
                        <input type="text" name="color" class="form-input" placeholder="e.g. White" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Seat Capacity (Pax)</label>
                    <input type="number" name="seats" class="form-input" min="1" max="6" value="4" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Road Tax Expiry</label>
                        <input type="date" name="road_tax_expiry" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Insurance Expiry</label>
                        <input type="date" name="insurance_expiry" class="form-input" required>
                    </div>
                </div>

                <button type="submit" name="btn_request_change" class="btn-submit">Submit Change Request</button>
            </form>

        <?php endif; ?>
    </div>

</div>

<?php include "footer.php"; ?>