<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// --- HANDLE ACTIONS ---
$success_msg = "";
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['driver_id'])) {
    $driver_id = intval($_POST['driver_id']);
    $action = $_POST['action'];
    $new_status = ($action === 'approve') ? 'verified' : (($action === 'reject') ? 'rejected' : '');

    if ($new_status) {
        $update_sql = "UPDATE drivers SET verification_status = '$new_status' WHERE driver_id = $driver_id";
        if (mysqli_query($conn, $update_sql)) {
            $success_msg = "Driver status updated to " . ucfirst($new_status) . "!";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}

// --- FETCH DATA ---
$search = "";
$sql = "SELECT d.*, 
               v.vehicle_model, v.plate_number, v.vehicle_type, v.vehicle_color, v.seat_count 
        FROM drivers d 
        LEFT JOIN vehicles v ON d.driver_id = v.driver_id 
        WHERE d.verification_status = 'pending'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (d.full_name LIKE '%$search%' 
                OR d.email LIKE '%$search%' 
                OR d.identification_id LIKE '%$search%'
                OR v.plate_number LIKE '%$search%')";
}

$sql .= " ORDER BY d.created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify Drivers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; }
        
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 250px; outline: none; transition: 0.2s; }
        .btn-search { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-clear { background: #9ca3af; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center;}

        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.9rem; vertical-align: top; }
        tr:hover { background: #f9fafb; }
        tr:last-child td { border-bottom: none; }

        .btn-action { border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #fecaca; }

        .info-label { font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; display: block; margin-top: 6px; margin-bottom: 2px;}
        .info-value { font-size: 0.9rem; color: #111827; }
        .expired { color: #dc2626; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .badge-vehicle { background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>
    <main class="dashboard-container">
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-user-check"></i> Driver Approvals</h2>
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Search name, plate, ID..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="verify_drivers.php" class="btn-clear"><i class="fa-solid fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if($success_msg): ?><script>Swal.fire({icon:'success', title:'Success', text:'<?php echo $success_msg; ?>', timer:1500, showConfirmButton:false});</script><?php endif; ?>
        <?php if($error_msg): ?><script>Swal.fire({icon:'error', title:'Error', text:'<?php echo $error_msg; ?>'});</script><?php endif; ?>

        <div class="card-table">
            <div style="overflow-x:auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">Applicant</th>
                            <th style="width: 20%;">Contact Info</th>
                            <th style="width: 15%;">License Details</th>
                            <th style="width: 25%;">Vehicle Info</th>
                            <th style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>#<?php echo $row['driver_id']; ?></td>
                                    
                                    <td>
                                        <div style="font-weight:700; font-size:1rem; color:#111827;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="info-label">ID / Matrix</div>
                                        <div class="info-value"><?php echo htmlspecialchars($row['identification_id']); ?></div>
                                        <div class="info-label">Gender</div>
                                        <div class="info-value"><?php echo ucfirst(htmlspecialchars($row['gender'])); ?></div>
                                    </td>

                                    <td>
                                        <div class="info-label" style="margin-top:0;">Email</div>
                                        <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" style="color:#2563eb; text-decoration:none;"><?php echo htmlspecialchars($row['email']); ?></a></div>
                                        <div class="info-label">Phone</div>
                                        <div class="info-value"><?php echo htmlspecialchars($row['phone_number']); ?></div>
                                        <div class="info-label">Applied On</div>
                                        <div class="info-value"><?php echo date("d M Y", strtotime($row['created_at'])); ?></div>
                                    </td>

                                    <td>
                                        <div class="info-label" style="margin-top:0;">License No.</div>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($row['license_number']); ?></div>
                                        
                                        <div class="info-label">Expiry Date</div>
                                        <?php 
                                            $expiry = $row['license_expiry'];
                                            $is_expired = (strtotime($expiry) < time());
                                            $expiry_class = $is_expired ? 'expired' : '';
                                            $expiry_text = date("d M Y", strtotime($expiry));
                                            if ($is_expired) $expiry_text .= " (EXPIRED)";
                                        ?>
                                        <div class="info-value <?php echo $expiry_class; ?>">
                                            <?php echo $expiry_text; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if($row['vehicle_model']): ?>
                                            <div style="font-weight:700; color:#374151;">
                                                <?php echo htmlspecialchars($row['vehicle_model']); ?> 
                                                <span style="font-weight:400; color:#6b7280;">(<?php echo htmlspecialchars($row['vehicle_color']); ?>)</span>
                                            </div>
                                            
                                            <div style="margin-top:5px;">
                                                <span class="badge badge-vehicle"><?php echo htmlspecialchars($row['plate_number']); ?></span>
                                            </div>

                                            <div style="display:flex; gap:15px; margin-top:8px;">
                                                <div>
                                                    <span class="info-label">Type</span>
                                                    <span><?php echo htmlspecialchars($row['vehicle_type']); ?></span>
                                                </div>
                                                <div>
                                                    <span class="info-label">Seats</span>
                                                    <span><?php echo htmlspecialchars($row['seat_count']); ?> Pax</span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#9ca3af; font-style:italic;">No Vehicle Registered</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <form method="POST" id="form_<?php echo $row['driver_id']; ?>" style="display:flex; flex-direction:column; gap:8px;">
                                            <input type="hidden" name="driver_id" value="<?php echo $row['driver_id']; ?>">
                                            <input type="hidden" name="action" id="action_<?php echo $row['driver_id']; ?>">
                                            
                                            <button type="button" class="btn-action btn-approve" onclick="confirmAction(<?php echo $row['driver_id']; ?>, 'approve', '<?php echo htmlspecialchars($row['full_name']); ?>')">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                            
                                            <button type="button" class="btn-action btn-reject" onclick="confirmAction(<?php echo $row['driver_id']; ?>, 'reject', '<?php echo htmlspecialchars($row['full_name']); ?>')">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#9ca3af;">
                                    <i class="fa-regular fa-folder-open" style="font-size:2rem; margin-bottom:10px;"></i><br>
                                    No pending driver applications found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function confirmAction(driverId, actionType, driverName) {
        // Define colors and texts based on action
        const isApprove = actionType === 'approve';
        const titleText = isApprove ? 'Approve Driver?' : 'Reject Driver?';
        const bodyText = isApprove 
            ? `Are you sure you want to verify <b>${driverName}</b>? They will be able to log in immediately.` 
            : `Are you sure you want to reject <b>${driverName}</b>? This cannot be undone easily.`;
        const confirmBtnColor = isApprove ? '#166534' : '#d33';
        const confirmBtnText = isApprove ? 'Yes, Approve!' : 'Yes, Reject!';

        Swal.fire({
            title: titleText,
            html: bodyText,
            icon: isApprove ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: confirmBtnText,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Set the hidden action input value
                document.getElementById('action_' + driverId).value = actionType;
                // Submit the specific form
                document.getElementById('form_' + driverId).submit();
            }
        });
    }
    </script>

</body>
</html>