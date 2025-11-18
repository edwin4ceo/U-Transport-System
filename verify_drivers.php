<?php
session_start();
require_once 'db_connect.php';

// --- 1. SECURITY CHECK ---
// Ensure only logged-in Admins can access this page
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
            $new_status = 'verified';
        } elseif ($action === 'reject') {
            $new_status = 'rejected';
        }

        if ($new_status) {
            $update_sql = "UPDATE users SET verification_status = '$new_status' WHERE user_id = $driver_id";
            if (mysqli_query($connection, $update_sql)) {
                $success_msg = "Driver status updated to " . ucfirst($new_status) . "!";
            } else {
                $error_msg = "Error updating record: " . mysqli_error($connection);
            }
        }
    }
}

// --- 3. FETCH PENDING DRIVERS ---
// Select only users who are 'driver' AND have status 'pending'
$sql = "SELECT * FROM users WHERE role = 'driver' AND verification_status = 'pending'";
$result = mysqli_query($connection, $sql);
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

    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-user-check"></i> Verify Drivers</h1>
                <nav>
                    <a href="admin_dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </nav>
            </div>
        </div>
    </header>

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
                        <th>License ID</th>
                        <th>Vehicle Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['driver_license_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['vehicle_details']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="driver_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-action btn-approve">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Are you sure you want to reject this driver?');">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No pending driver applications found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>