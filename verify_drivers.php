<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

// --- 1. SECURITY CHECK ---
// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// --- 2. HANDLE APPROVE/REJECT ACTIONS ---
$success_msg = "";
$error_msg = "";

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
            $update_sql = "UPDATE drivers SET verification_status = '$new_status' WHERE driver_id = $driver_id";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_msg = "Driver status updated to " . ucfirst($new_status) . "!";
            } else {
                $error_msg = "Error updating record: " . mysqli_error($conn);
            }
        }
    }
}

// --- 3. FETCH PENDING DRIVERS WITH SEARCH ---
$search = "";
$sql = "SELECT * FROM drivers WHERE verification_status = 'pending'";

// If user typed in the search box
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR identification_id LIKE '%$search%')";
}

$sql .= " ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<main class="dashboard-container">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-user-check"></i> Verify New Drivers</h2>
            
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search Name, Email or ID..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px; width: 250px;">
                <button type="submit" style="padding:8px 15px; background:#2c3e50; color:white; border:none; border-radius:4px; cursor:pointer;">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="verify_drivers.php" style="padding:8px 15px; background:#95a5a6; color:white; border-radius:4px; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(!empty($success_msg)): ?>
            <script>Swal.fire({ icon: 'success', title: 'Success', text: '<?php echo $success_msg; ?>', timer: 2000, showConfirmButton: false });</script>
        <?php endif; ?>
        
        <?php if(!empty($error_msg)): ?>
            <script>Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo $error_msg; ?>' });</script>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background-color: #2c3e50; color: white; text-align: left;">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Full Name</th>
                        <th style="padding: 12px;">Email</th>
                        <th style="padding: 12px;">License No.</th>
                        <th style="padding: 12px;">Applied On</th>
                        <th style="padding: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;"><?php echo $row['driver_id']; ?></td>
                                <td style="padding: 12px;">
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                    <small style="color:#777;"><?php echo htmlspecialchars($row['identification_id']); ?></small>
                                </td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['license_number']); ?></td>
                                <td style="padding: 12px;"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                <td style="padding: 12px;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="driver_id" value="<?php echo $row['driver_id']; ?>">
                                        
                                        <button type="submit" name="action" value="approve" style="background:#27ae60; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; margin-right:5px;" onclick="return confirm('Approve this driver?');">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        
                                        <button type="submit" name="action" value="reject" style="background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;" onclick="return confirm('Reject this driver?');">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #999;">
                                <?php echo empty($search) ? "No pending driver applications found." : "No results found matching '$search'."; ?>
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