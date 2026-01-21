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
$total_bookings   = getCount($conn, "bookings");
$completed_rides  = getCount($conn, "bookings", "WHERE status='Completed'");

// 2. Calculate Revenue (Sum of fares for non-cancelled rides)
$rev_query = mysqli_query($conn, "SELECT SUM(fare) as total FROM bookings WHERE status != 'Cancelled' AND status != 'Rejected'");
$total_revenue = mysqli_fetch_assoc($rev_query)['total'] ?? 0;

// 3. Fetch Detailed Data: Recent Bookings
$sql_bookings = "
    SELECT b.id, b.date_time, b.destination, b.pickup_point, b.fare, b.status, 
           s.name as student_name, d.full_name as driver_name 
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    ORDER BY b.date_time DESC LIMIT 20";
$res_bookings = mysqli_query($conn, $sql_bookings);

// 4. Fetch Detailed Data: Driver Fleet
$sql_drivers = "
    SELECT d.full_name, d.email, d.phone_number, d.verification_status, 
           v.vehicle_model, v.plate_number, v.vehicle_color
    FROM drivers d
    LEFT JOIN vehicles v ON d.driver_id = v.driver_id
    ORDER BY d.created_at DESC";
$res_drivers = mysqli_query($conn, $sql_drivers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>System Reports | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .report-meta { color: #6b7280; font-size: 0.9rem; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-content h4 { margin: 0; font-size: 0.85rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-content .number { margin: 5px 0 0; font-size: 1.5rem; font-weight: 700; color: #111827; }

        .bg-blue { background: #eff6ff; color: #3b82f6; }
        .bg-green { background: #f0fdf4; color: #22c55e; }
        .bg-purple { background: #f3e8ff; color: #a855f7; }
        .bg-orange { background: #fff7ed; color: #f97316; }

        /* Tables */
        .section-title { font-size: 1.2rem; font-weight: 700; color: #1f2937; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th { background: #f9fafb; text-align: left; padding: 12px 15px; font-weight: 600; color: #4b5563; text-transform: uppercase; font-size: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        td { padding: 12px 15px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-verified { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Print Button */
        .btn-print { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-print:hover { background: #374151; }

        /* PRINT STYLES */
        @media print {
            .admin-header, .btn-print, .admin-nav, header { display: none !important; }
            body { background: white; padding: 0; margin: 0; }
            .dashboard-container { margin: 0; width: 100%; max-width: 100%; padding: 0; }
            .stat-card, .card-table { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; }
            .section-title { color: black; border-bottom: 2px solid black; }
            a { text-decoration: none; color: black; }
        }
    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <div class="page-header">
            <div>
                <h2 style="margin:0; color:#111827; font-size:1.8rem;">System Report</h2>
                <div class="report-meta">Generated on: <?php echo date("F d, Y - h:i A"); ?></div>
            </div>
            <button onclick="window.print()" class="btn-print">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>

        <h3 class="section-title">Executive Summary</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-content">
                    <h4>Total Bookings</h4>
                    <div class="number"><?php echo $total_bookings; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="stat-content">
                    <h4>Est. Revenue</h4>
                    <div class="number">RM <?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fa-solid fa-car"></i></div>
                <div class="stat-content">
                    <h4>Active Drivers</h4>
                    <div class="number"><?php echo $verified_drivers; ?> / <?php echo $total_drivers; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-purple"><i class="fa-solid fa-users"></i></div>
                <div class="stat-content">
                    <h4>Total Passengers</h4>
                    <div class="number"><?php echo $total_passengers; ?></div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Recent Transactions (Last 20)</h3>
        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Passenger</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Fare</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($res_bookings) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_bookings)): ?>
                        <tr>
                            <td><?php echo date("d M Y, h:i A", strtotime($row['date_time'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                            </td>
                            <td>
                                <div style="font-size:0.85rem; color:#6b7280;">From: <?php echo htmlspecialchars($row['pickup_point']); ?></div>
                                <div style="font-size:0.85rem;">To: <?php echo htmlspecialchars($row['destination']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                            <td style="font-weight:600;">RM <?php echo number_format($row['fare'], 2); ?></td>
                            <td>
                                <?php 
                                    $s = strtolower($row['status']);
                                    $class = ($s == 'completed' || $s == 'accepted') ? 'badge-completed' : (($s == 'cancelled' || $s == 'rejected') ? 'badge-cancelled' : 'badge-pending');
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">No booking data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 class="section-title">Driver Fleet Overview</h3>
        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>Driver Name</th>
                        <th>Contact</th>
                        <th>Vehicle Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($res_drivers) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_drivers)): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($row['email']); ?></div>
                                <div style="color:#6b7280; font-size:0.8rem;"><?php echo htmlspecialchars($row['phone_number']); ?></div>
                            </td>
                            <td>
                                <?php if($row['vehicle_model']): ?>
                                    <div><?php echo htmlspecialchars($row['vehicle_model']); ?> <span style="color:#6b7280;">(<?php echo htmlspecialchars($row['vehicle_color']); ?>)</span></div>
                                    <div style="font-family:monospace; background:#f3f4f6; display:inline-block; padding:2px 5px; border-radius:4px; font-size:0.8rem; margin-top:2px;">
                                        <?php echo htmlspecialchars($row['plate_number']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#9ca3af;">No Vehicle</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $v = strtolower($row['verification_status']);
                                    $vClass = ($v == 'verified') ? 'badge-verified' : 'badge-pending';
                                ?>
                                <span class="badge <?php echo $vClass; ?>"><?php echo ucfirst($v); ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#9ca3af;">No drivers registered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:center; color:#9ca3af; font-size:0.8rem; margin-top:50px;">
            End of Report • U-Transport System
        </div>

    </div>
</main>
</body>
</html>