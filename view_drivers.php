<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER
require_once 'admin_header.php'; 

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

$search = "";

// --- UPDATED SQL: Join 'drivers' with 'vehicles' table ---
$sql = "SELECT d.*, v.vehicle_model, v.plate_number, v.vehicle_type, v.vehicle_color 
        FROM drivers d 
        LEFT JOIN vehicles v ON d.driver_id = v.driver_id 
        WHERE d.verification_status = 'verified'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by Driver Name, Email, ID, OR Vehicle Plate/Model
    $sql .= " AND (d.full_name LIKE '%$search%' 
                OR d.email LIKE '%$search%' 
                OR d.identification_id LIKE '%$search%'
                OR v.plate_number LIKE '%$search%'
                OR v.vehicle_model LIKE '%$search%')";
}

$sql .= " ORDER BY d.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Driver List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        /* Search Box Styles */
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 250px; outline: none; }
        .btn-search { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        
        /* Table Styles */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f9fafb; }

        /* Badge Styles */
        .plate-badge { 
            background: #111827; color: #fff; padding: 3px 8px; 
            border-radius: 4px; font-family: monospace; font-size: 0.85rem; letter-spacing: 1px;
        }
        .vehicle-info { font-weight: 600; color: #374151; display: block; }
        .vehicle-sub { font-size: 0.8rem; color: #6b7280; }
        
        .no-vehicle { color: #9ca3af; font-style: italic; font-size: 0.85rem; }
    </style>
</head>
<body>

<main class="dashboard-container">
    <div class="container">
        
        <div class="page-header">
            <h2 style="color:#111827; margin:0; font-size:1.5rem; font-weight:700;"><i class="fa-solid fa-users-gear"></i> Driver List</h2>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Name, Plate No, Model..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Contact Info</th>
                            <th>Vehicle Details</th> <th>License ID</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td style="font-weight:600; color:#111827;">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </td>
                                    <td><?php echo ucfirst(htmlspecialchars($row['gender'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['email']); ?></div>
                                        <div style="font-size:0.8rem; color:#6b7280; margin-top:2px;">
                                            <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($row['phone_number']); ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if (!empty($row['vehicle_model'])): ?>
                                            <span class="vehicle-info">
                                                <?php echo htmlspecialchars($row['vehicle_model']); ?> 
                                                <span style="font-weight:normal; color:#6b7280;">(<?php echo htmlspecialchars($row['vehicle_color']); ?>)</span>
                                            </span>
                                            <div style="margin-top:4px;">
                                                <span class="plate-badge"><?php echo htmlspecialchars($row['plate_number']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-vehicle">No Vehicle Assigned</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                                    <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="admin_driver_chat.php?driver_id=<?php echo $row['driver_id']; ?>" style="color:#2563eb; text-decoration:none; font-weight:500;">
                                            <i class="fa-solid fa-comment-dots"></i> Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 30px; color: #9ca3af;">No drivers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

</body>
</html>