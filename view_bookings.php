<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

// 1. Security Check
// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// 2. Fetch Bookings with Search Logic
$search = "";
$sql = "SELECT b.*, 
               s.name AS student_name,    
               d.full_name AS driver_name,
               d.driver_id
        FROM bookings b 
        JOIN students s ON b.student_id = s.student_id 
        LEFT JOIN drivers d ON b.driver_id = d.driver_id";

// Add Search Condition
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " WHERE (s.name LIKE '%$search%' 
                 OR d.full_name LIKE '%$search%' 
                 OR b.destination LIKE '%$search%'
                 OR b.pickup_point LIKE '%$search%')";
}

$sql .= " ORDER BY b.created_at DESC";

$result = mysqli_query($conn, $sql);

// Error handling if table doesn't exist or columns are wrong
if (!$result) {
    die("Error fetching bookings: " . mysqli_error($conn));
}
?>

<main class="dashboard-container">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-list-check"></i> Booking History</h2>
            
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search Passenger, Driver, Location..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px; width: 300px;">
                <button type="submit" style="padding:8px 15px; background:#2c3e50; color:white; border:none; border-radius:4px; cursor:pointer;">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="view_bookings.php" style="padding:8px 15px; background:#95a5a6; color:white; border-radius:4px; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background-color: #2c3e50; color: white; text-align: left;">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Passenger</th>
                        <th style="padding: 12px;">Driver</th>
                        <th style="padding: 12px;">Route</th>
                        <th style="padding: 12px;">Date/Time</th>
                        <th style="padding: 12px;">Pax</th>
                        <th style="padding: 12px;">Remark</th>
                        <th style="padding: 12px;">Status</th>
                        <th style="padding: 12px;">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td style="padding: 12px;">
                                    <?php if($row['driver_name']): ?>
                                        <a href="admin_driver_chat.php?driver_id=<?php echo $row['driver_id']; ?>" style="color:#2980b9; text-decoration:none;">
                                            <?php echo htmlspecialchars($row['driver_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#999;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <strong>From:</strong> <?php echo htmlspecialchars($row['pickup_point']); ?><br>
                                    <strong>To:</strong> <?php echo htmlspecialchars($row['destination']); ?>
                                </td>
                                <td style="padding: 12px;"><?php echo date('d M, h:i A', strtotime($row['date_time'])); ?></td>
                                <td style="padding: 12px;"><?php echo $row['passengers']; ?></td>
                                <td style="padding: 12px; color:#777; font-style:italic;"><?php echo htmlspecialchars($row['remark']); ?></td>
                                <td style="padding: 12px;">
                                    <?php 
                                        $st = strtolower($row['status']);
                                        $color = ($st == 'accepted' || $st == 'completed') ? 'green' : (($st == 'rejected' || $st == 'cancelled') ? 'red' : 'orange');
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:bold;"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:30px; color:#999;">
                                <?php echo empty($search) ? "No bookings found." : "No bookings matching '$search'."; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>