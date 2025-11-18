<?php
session_start();
require_once 'db_connect.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// --- 2. HANDLE DELETE ACTION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing_id'])) {
    $listing_id = intval($_POST['delete_listing_id']);
    $delete_sql = "DELETE FROM transportlistings WHERE listing_id = $listing_id";
    if (mysqli_query($connection, $delete_sql)) {
        $success_msg = "Listing removed successfully.";
    } else {
        $error_msg = "Error removing listing: " . mysqli_error($connection);
    }
}

// --- 3. FETCH LISTINGS WITH DRIVER INFO ---
// We use JOIN to get the driver's name from the 'users' table instead of just showing 'driver_id'
$sql = "SELECT t.*, u.full_name 
        FROM transportlistings t 
        JOIN users u ON t.driver_id = u.user_id 
        ORDER BY t.created_at DESC";
$result = mysqli_query($connection, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Listings | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; font-size: 0.9rem; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .status-active { color: green; font-weight: bold; }
        .status-completed { color: blue; }
        .status-cancelled { color: red; }
        .btn-delete { background-color: #c0392b; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-list"></i> Manage Listings</h1>
                <nav>
                    <a href="admin_dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h3>All Transport Listings</h3>

            <?php if (isset($success_msg)) echo "<div style='color: green; margin-bottom: 15px;'>$success_msg</div>"; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Driver</th>
                        <th>Route</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['listing_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <strong>From:</strong> <?php echo htmlspecialchars($row['origin']); ?><br>
                                    <strong>To:</strong> <?php echo htmlspecialchars($row['destination']); ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['departure_time'])); ?></td>
                                <td>RM <?php echo $row['price']; ?></td>
                                <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this listing?');">
                                        <input type="hidden" name="delete_listing_id" value="<?php echo $row['listing_id']; ?>">
                                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px;">No listings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>