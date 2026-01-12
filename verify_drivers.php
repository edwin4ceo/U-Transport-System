<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// --- 2. HANDLE APPROVE/REJECT ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['driver_id'])) {
        $driver_id = intval($_POST['driver_id']);
        $action = $_POST['action'];
        
        $new_status = '';
        if ($action === 'approve') {
            $new_status = 'verified'; // Check if your ENUM allows 'verified'
        } elseif ($action === 'reject') {
            $new_status = 'rejected';
        }

        if ($new_status) {
            // UPDATED: Update 'drivers' table instead of 'users'
            $update_sql = "UPDATE drivers SET verification_status = '$new_status' WHERE driver_id = $driver_id";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_msg = "Driver status updated to " . ucfirst($new_status) . "!";
            } else {
                $error_msg = "Error updating record: " . mysqli_error($conn);
            }
        }
    }
}

// --- 3. FETCH PENDING DRIVERS ---
// UPDATED: Select from 'drivers' table where status is 'pending'
$sql = "SELECT * FROM drivers WHERE verification_status = 'pending' ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Drivers | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Simple Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-approve { background-color: #27ae60; }
        .btn-reject { background-color: #c0392b; }
        .no-data { padding: 20px; text-align: center; color: #7f8c8d; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <main>
        <div class="container">
            <h3>Pending Driver Applications</h3>

            <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if (isset($error_msg)) echo "<div class='alert alert-error'>$error_msg</div>"; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>IC / ID</th>
                        <th>License No.</th>
                        <th>Reg Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['driver_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['identification_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                                <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
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
                        <tr>
                            <td colspan="7" class="no-data">No pending driver applications found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>