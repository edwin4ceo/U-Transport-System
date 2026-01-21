<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

$search = "";
$sql = "SELECT b.*, s.name AS student_name, d.full_name AS driver_name, d.driver_id
        FROM bookings b 
        JOIN students s ON b.student_id = s.student_id 
        LEFT JOIN drivers d ON b.driver_id = d.driver_id";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " WHERE (s.name LIKE '%$search%' OR d.full_name LIKE '%$search%' OR b.destination LIKE '%$search%' OR b.pickup_point LIKE '%$search%')";
}
$sql .= " ORDER BY b.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Booking List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        /* Using same styles as verify page for consistency */
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 250px; outline: none; }
        .btn-search { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: top; }
        tr:hover { background: #f9fafb; }

        /* Status Pills */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: uppercase; }
        .badge-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .badge-accepted { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .badge-completed { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .badge-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
        .badge-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

        .route-point { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-green { background: #22c55e; }
        .dot-red { background: #ef4444; }
    </style>
</head>
<body>
    <main class="dashboard-container">
        <div class="page-header">
            <h2 class="page-title" style="font-size:1.5rem; font-weight:700; color:#111827; margin:0;"><i class="fa-solid fa-list-check"></i> All Bookings</h2>
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Passenger, Driver, Location..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>

        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Passenger</th>
                        <th>Driver Assigned</th>
                        <th>Route Details</th>
                        <th>Schedule</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <?php 
                                $status = strtolower($row['status']); 
                                $badgeClass = 'badge-' . $status;
                            ?>
                            <tr>
                                <td style="font-weight:600;">#<?php echo $row['id']; ?></td>
                                <td>
                                    <div style="font-weight:600; color:#111827;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <div style="font-size:0.85rem; color:#6b7280;"><?php echo $row['passengers']; ?> Pax</div>
                                </td>
                                <td>
                                    <?php if($row['driver_name']): ?>
                                        <a href="admin_driver_chat.php?driver_id=<?php echo $row['driver_id']; ?>" style="text-decoration:none; color:#2563eb; font-weight:500;">
                                            <i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($row['driver_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#9ca3af; font-style:italic;">-- Unassigned --</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="route-point"><span class="dot dot-green"></span> <?php echo htmlspecialchars($row['pickup_point']); ?></div>
                                    <div class="route-point"><span class="dot dot-red"></span> <?php echo htmlspecialchars($row['destination']); ?></div>
                                    <?php if($row['remark']): ?>
                                        <div style="margin-top:5px; font-size:0.85rem; color:#6b7280; font-style:italic;">"<?php echo htmlspecialchars($row['remark']); ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($row['date_time'])); ?></div>
                                    <div style="color:#6b7280; font-size:0.9rem;"><?php echo date('h:i A', strtotime($row['date_time'])); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>