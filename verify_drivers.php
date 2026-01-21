<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// HANDLE ACTIONS
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

// FETCH DATA
$search = "";
$sql = "SELECT * FROM drivers WHERE verification_status = 'pending'";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR identification_id LIKE '%$search%')";
}
$sql .= " ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify Drivers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; }
        
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 250px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-search { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-clear { background: #9ca3af; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center;}

        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f9fafb; }
        tr:last-child td { border-bottom: none; }

        .btn-action { border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #fecaca; }
    </style>
</head>
<body>
    <main class="dashboard-container">
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-user-check"></i> Driver Approvals</h2>
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Search applicants..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="verify_drivers.php" class="btn-clear"><i class="fa-solid fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if($success_msg): ?><script>Swal.fire({icon:'success', title:'Success', text:'<?php echo $success_msg; ?>', timer:1500, showConfirmButton:false});</script><?php endif; ?>

        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Applicant Details</th>
                        <th>Contact Info</th>
                        <th>License / ID</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?php echo $row['driver_id']; ?></td>
                                <td>
                                    <div style="font-weight:600; color:#111827;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div style="font-size:0.85rem; color:#6b7280;">Driver Applicant</div>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <div><i class="fa-regular fa-id-card"></i> <?php echo htmlspecialchars($row['identification_id']); ?></div>
                                    <div style="margin-top:2px; font-size:0.85rem; color:#6b7280;">Lic: <?php echo htmlspecialchars($row['license_number']); ?></div>
                                </td>
                                <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display:inline-flex; gap:8px;">
                                        <input type="hidden" name="driver_id" value="<?php echo $row['driver_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-action btn-approve" onclick="return confirm('Approve this driver?');">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Reject this driver?');">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No pending applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>