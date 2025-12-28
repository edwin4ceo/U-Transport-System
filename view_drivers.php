<?php
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: admin_login.php"); 
    exit(); 
}

$search = "";
$sql = "SELECT * FROM users WHERE role='driver'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR driver_license_id LIKE '%$search%')";
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Drivers | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 10px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 0.9rem; }
        th { background-color: #2c3e50; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f9f9f9; }
        
        /* --- FIXED LAYOUT STYLES --- */
        .admin-toolbar {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            
            /* Master Flex Container: Left = Form, Right = Text */
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100%;
            box-sizing: border-box;
        }

        .unique-search-form {
            /* Force horizontal layout for input + button */
            display: flex !important; 
            flex-direction: row !important;
            align-items: center !important; 
            gap: 0 !important; /* Remove gap, we handle it with border-radius */
            margin: 0 !important;
            padding: 0 !important;
        }

        .unique-input {
            width: 300px !important;
            height: 40px !important;
            padding: 0 15px !important;
            border: 1px solid #ccc !important;
            border-right: none !important; /* Merge with button */
            border-radius: 5px 0 0 5px !important;
            outline: none !important;
            margin: 0 !important;
            display: inline-block !important;
            box-sizing: border-box !important;
        }

        .unique-btn {
            width: 50px !important;
            height: 40px !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #0056b3 !important; 
            color: white !important;
            border: 1px solid #0056b3 !important;
            border-radius: 0 5px 5px 0 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        
        .unique-btn:hover { background-color: #004494 !important; }

        .reset-link {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            margin-left: 15px;
            white-space: nowrap;
        }

        .count-label {
            font-size: 0.9rem; 
            color: #7f8c8d; 
            white-space: nowrap;
        }

        .status-verified { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .status-pending { color: #d35400; font-weight: bold; background: #fdebd0; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .status-rejected { color: #c0392b; font-weight: bold; background: #fadbd8; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.5rem; margin:0;"><i class="fa-solid fa-car"></i> Drivers List</h1>
            <a href="admin_dashboard.php" style="color: #ecf0f1; text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container" style="margin-top: 20px;">
            
            <div class="admin-toolbar">
                <form method="GET" class="unique-search-form">
                    <input type="text" name="search" class="unique-input" placeholder="Search name, email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="unique-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <?php if(!empty($search)): ?>
                        <a href="view_drivers.php" class="reset-link">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="count-label">
                    Total Drivers: <strong><?php echo mysqli_num_rows($result); ?></strong>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>License ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['driver_license_id']); ?></td>
                                    <td>
                                        <?php 
                                            $status = strtolower($row['verification_status']);
                                            $class = 'status-' . $status; 
                                        ?>
                                        <span class="<?php echo $class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 30px; color: #999;">No drivers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>