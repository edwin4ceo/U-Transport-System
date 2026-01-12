<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

// Helper function for counts
function getCount($conn, $table, $condition = "") {
    $r = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table $condition");
    return $r ? mysqli_fetch_assoc($r)['count'] : 0;
}

// 1. Summary Statistics
$total_passengers = getCount($conn, "students");
$total_drivers    = getCount($conn, "drivers");
$verified_drivers = getCount($conn, "drivers", "WHERE verification_status='verified'");
$pending_drivers  = getCount($conn, "drivers", "WHERE verification_status='pending'");
$total_bookings   = getCount($conn, "bookings");
$completed_rides  = getCount($conn, "bookings", "WHERE status='Completed'");

// 2. Fetch Detailed Data: Recent Bookings (Limit 50 for report)
$sql_bookings = "
    SELECT b.id, b.date_time, b.destination, b.status, s.name as student_name, d.full_name as driver_name 
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    ORDER BY b.date_time DESC LIMIT 50";
$res_bookings = mysqli_query($conn, $sql_bookings);

// 3. Fetch Detailed Data: Driver Fleet
$sql_drivers = "
    SELECT d.full_name, d.email, d.phone_number, d.verification_status, v.vehicle_model, v.plate_number 
    FROM drivers d
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    ORDER BY d.created_at DESC";
$res_drivers = mysqli_query($conn, $sql_drivers);
?>

<style>
    /* Screen Styles */
    .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .report-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid #2c3e50; }
    .report-card h4 { margin: 0; color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; }
    .report-card .number { font-size: 2rem; font-weight: bold; color: #2c3e50; margin: 10px 0; }
    
    .data-table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 30px; font-size: 0.9rem; }
    .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
    .data-table th { background-color: #f8f9fa; color: #333; }
    
    .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin: 30px 0 15px; color: #005A9C; }
    
    /* Print Button */
    .btn-print { background-color: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
    .btn-print:hover { background-color: #219150; }

    /* PRINT MEDIA QUERY - Hides sidebar/buttons when printing */
    @media print {
        header, .admin-header, .btn-print, .admin-nav { display: none !important; }
        body { background-color: white; font-size: 12pt; }
        .dashboard-container { margin: 0; width: 100%; }
        .report-card { border: 1px solid #ddd; box-shadow: none; }
        .data-table th { background-color: #eee !important; -webkit-print-color-adjust: exact; }
    }
</style>

<main class="dashboard-container">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2 style="margin:0; color:#2c3e50;">System Report</h2>
                <p style="margin:0; color:#777;">Generated on: <?php echo date("d M Y, h:i A"); ?></p>
            </div>
            <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        </div>

        <h3 class="section-title">Overview Statistics</h3>
        <div class="report-grid">
            <div class="report-card">
                <h4>Total Passengers</h4>
                <div class="number"><?php echo $total_passengers; ?></div>
            </div>
            <div class="report-card">
                <h4>Total Drivers</h4>
                <div class="number"><?php echo $total_drivers; ?></div>
            </div>
            <div class="report-card">
                <h4>Total Bookings</h4>
                <div class="number"><?php echo $total_bookings; ?></div>
            </div>
            <div class="report-card" style="border-top-color: #27ae60;">
                <h4>Completed Rides</h4>
                <div class="number"><?php echo $completed_rides; ?></div>
            </div>
        </div>

        <h3 class="section-title">Recent Booking Log (Last 50)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Passenger</th>
                    <th>Driver</th>
                    <th>Destination</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($res_bookings) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($res_bookings)): ?>
                    <tr>
                        <td><?php echo date("d/m/y H:i", strtotime($row['date_time'])); ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 class="section-title">Driver Fleet & Vehicles</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Contact</th>
                    <th>Vehicle Model</th>
                    <th>Plate Number</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($res_drivers) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($res_drivers)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']) . "<br>" . htmlspecialchars($row['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['vehicle_model'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['plate_number'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                                $st = $row['verification_status']; 
                                $color = ($st=='verified')?'green':(($st=='rejected')?'red':'orange');
                                echo "<strong style='color:$color'>".ucfirst($st)."</strong>";
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No drivers registered.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="text-align:center; font-size:0.8rem; color:#999; margin-top:50px;">
            --- End of Report ---
        </div>

    </div>
</main>
</body>
</html>