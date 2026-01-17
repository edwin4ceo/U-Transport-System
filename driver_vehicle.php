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
            $error_msg = "Seat capacity cannot exceed 6.";
        } else {
            // Insert Request
            $stmt = $conn->prepare("INSERT INTO vehicle_change_requests (driver_id, vehicle_model, plate_number, vehicle_type, vehicle_color, seat_count, road_tax_expiry, insurance_expiry, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
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

// 4. Fetch Latest Request Status
$r_stmt = $conn->prepare("SELECT * FROM vehicle_change_requests WHERE driver_id = ? ORDER BY created_at DESC LIMIT 1");
$r_stmt->bind_param("i", $driver_id);
$r_stmt->execute();
$last_req = $r_stmt->get_result()->fetch_assoc();

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- Reuse Core Dashboard Styles --- */
    :root { --primary: #004b82; --bg-color: #f8f9fc; --card-bg: #ffffff; --text-main: #1a202c; --text-light: #718096; }
    body { background: var(--bg-color); font-family: 'Inter', sans-serif; }
    
    .dashboard-wrapper { max-width: 1200px; width: 95%; margin: 0 auto 40px; padding: 20px; box-sizing: border-box; }
    
    /* Header Styles */
    .dashboard-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #edf2f7; }
    .dashboard-title h1 { margin: 0 0 5px 0; font-size: 26px; font-weight: 800; color: var(--text-main); }
    .dashboard-subtitle { font-size: 14px; color: var(--text-light); margin: 0; }
    
    .btn-back { background: white; color: var(--primary); border: 1px solid #cbd5e0; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-back:hover { background: #f7fafc; border-color: var(--primary); }

    /* --- LAYOUT UPDATED: Top-Bottom Stack --- */
    /* Changed grid-template-columns to 1fr so cards stack vertically */
    .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 30px; }

    /* Card Styles */
    .modern-card { background: var(--card-bg); border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #eef2f6; height: fit-content; }
    .card-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f7fafc; }
    .card-title-text { font-size: 18px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px; }

    /* --- Page Specific Styles --- */
    
    /* Current Vehicle Display */
    .vehicle-display-box { text-align: center; margin-bottom: 25px; }
    .v-icon { width: 80px; height: 80px; background: #f0f7ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 15px; }
    .v-plate { background: #1a202c; color: white; padding: 6px 16px; border-radius: 8px; font-family: monospace; font-size: 15px; font-weight: 700; display: inline-block; letter-spacing: 1px; }
    .v-model { font-size: 18px; font-weight: 700; color: var(--text-main); margin: 15px 0 5px; }
    
    /* Info List - Added max-width for better readability in full-width card */
    .info-list { max-width: 800px; margin: 0 auto; }
    .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; font-size: 14px; }
    .info-label { color: var(--text-light); }
    .info-val { color: var(--text-main); font-weight: 600; text-align: right; }

    /* Form Styles */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px; }
    .form-input { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e0; border-radius: 10px; font-size: 14px; outline: none; transition: border 0.2s; background: #fff; box-sizing: border-box; font-family: 'Inter', sans-serif; }
    .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 75, 130, 0.1); }
    
    .btn-submit { background: var(--primary); color: white; border: none; padding: 14px 30px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; display: block; width: 100%; transition: background 0.2s; margin-top: 10px; }
    .btn-submit:hover { background: #00365e; }

    /* Status Badges */
    .status-badge { padding: 15px; border-radius: 10px; margin-top: 25px; font-size: 13px; display: flex; align-items: center; gap: 10px; font-weight: 600; justify-content: center; }
    .sb-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; }
    .sb-approved { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
    .sb-rejected { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }

    /* Policy Box */
    .policy-box { background: #ebf8ff; color: #2c5282; padding: 20px; border-radius: 12px; font-size: 13px; margin-bottom: 25px; border: 1px solid #bee3f8; line-height: 1.6; }

    /* Responsive */
    @media (max-width: 768px) {
        .grid-2 { grid-template-columns: 1fr; gap: 10px; }
    }
</style>

<div class="dashboard-wrapper">
    
    <?php if($success_msg): ?>
        <script>Swal.fire({ title: 'Success', text: '<?php echo $success_msg; ?>', icon: 'success', confirmButtonColor: '#004b82' });</script>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <script>Swal.fire({ title: 'Error', text: '<?php echo $error_msg; ?>', icon: 'error', confirmButtonColor: '#004b82' });</script>
    <?php endif; ?>

    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Manage Vehicle 🚗</h1>
            <p class="dashboard-subtitle">View your active vehicle or request an update.</p>
        </div>
        <div class="header-actions">
            <a href="driver_dashboard.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="modern-card">
            <div class="card-title-row"><div class="card-title-text"><i class="fa-solid fa-car-side"></i> Current Vehicle</div></div>
            
            <?php if($vehicle): ?>
                <div class="vehicle-display-box">
                    <div class="v-icon"><i class="fa-solid fa-car"></i></div>
                    <div class="v-plate"><?php echo htmlspecialchars($vehicle['plate_number']); ?></div>
                    <div class="v-model"><?php echo htmlspecialchars($vehicle['vehicle_model']); ?></div>
                    <div style="font-size:13px; color:#718096;"><?php echo htmlspecialchars($vehicle['vehicle_color']); ?> • <?php echo htmlspecialchars($vehicle['vehicle_type']); ?></div>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span class="info-label">Seat Capacity</span>
                        <span class="info-val"><?php echo htmlspecialchars($vehicle['seat_count']); ?> Pax</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Road Tax</span>
                        <span class="info-val" style="<?php echo (!empty($vehicle['road_tax_expiry']) && strtotime($vehicle['road_tax_expiry']) < time()) ? 'color:#e53e3e;' : 'color:#2f855a;'; ?>">
                            <?php echo !empty($vehicle['road_tax_expiry']) ? date("d M Y", strtotime($vehicle['road_tax_expiry'])) : 'N/A'; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Insurance</span>
                        <span class="info-val" style="<?php echo (!empty($vehicle['insurance_expiry']) && strtotime($vehicle['insurance_expiry']) < time()) ? 'color:#e53e3e;' : 'color:#2f855a;'; ?>">
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

        <div class="modern-card">
            <div class="card-title-row"><div class="card-title-text"><i class="fa-solid fa-pen-to-square"></i> Request Update</div></div>

            <?php 
            // Logic: If pending, show message instead of form
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
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <i class="fa-solid fa-circle-info"></i> <strong>Note:</strong>
                    </div>
                    To ensure passenger safety, drivers may only have <strong>one active vehicle</strong>. Submitting this form will replace your current vehicle details upon Admin approval.
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
</div>

<?php include "footer.php"; ?>