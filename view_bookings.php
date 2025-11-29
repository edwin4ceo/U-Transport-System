<?php
session_start();
require_once 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// 2. Fetch Bookings
// We join with the 'users' table to show the Student Name instead of just 'student_id'
$sql = "SELECT b.*, u.full_name 
        FROM bookings b 
        JOIN users u ON b.student_id = u.user_id 
        ORDER BY b.created_at DESC";

$result = mysqli_query($conn, $sql);

// Error handling if table doesn't exist or columns are wrong
if (!$result) {
    die("Error fetching bookings: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking List | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; font-size: 0.9rem; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .status-pending { color: orange; font-weight: bold; }
        .status-confirmed { color: green; font-weight: bold; }
        .status-cancelled { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-address-book"></i> Student Bookings</h1>
                <nav>
                    <a href="admin_dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h3>All Booking Requests</h3>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Pickup & Destination</th>
                        <th>Date & Time</th>
                        <th>Pax</th>
                        <th>Remark</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                    <br><small>(ID: <?php echo $row['student_id']; ?>)</small>
                                </td>
                                <td>
                                    <strong>From:</strong> <?php echo htmlspecialchars($row['pickup_point']); ?><br>
                                    <strong>To:</strong> <?php echo htmlspecialchars($row['destination']); ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['date_time'])); ?></td>
                                <td><?php echo $row['passengers']; ?></td>
                                <td><?php echo htmlspecialchars($row['remark']); ?></td>
                                <td class="status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:20px;">No bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>