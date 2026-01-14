<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

// --- STRICT SECURITY CHECK ---
// Only 'admin' can access. 'staff' cannot see this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE DELETE ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Prevent deleting yourself
    if ($delete_id != $_SESSION['user_id']) {
        $del_sql = "DELETE FROM admins WHERE id = $delete_id AND role = 'staff'";
        if (mysqli_query($conn, $del_sql)) {
            echo "<script>Swal.fire('Deleted!', 'Staff member has been removed.', 'success');</script>";
        } else {
            echo "<script>Swal.fire('Error', 'Could not delete staff.', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Error', 'You cannot delete your own account!', 'error');</script>";
    }
}

// --- FETCH STAFF WITH SEARCH ---
$search = "";
$sql = "SELECT * FROM admins WHERE role = 'staff'";

// If user typed in the search box
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%' OR phone_number LIKE '%$search%')";
}

$sql .= " ORDER BY full_name ASC";
$result = mysqli_query($conn, $sql);
?>

<main class="dashboard-container">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-users-gear"></i> Manage Staff Team</h2>
            
            <div style="display:flex; gap:10px;">
                <form method="GET" style="display:flex; gap:5px;">
                    <input type="text" name="search" placeholder="Search Staff..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px; width: 200px;">
                    <button type="submit" style="padding:8px 15px; background:#2c3e50; color:white; border:none; border-radius:4px; cursor:pointer;">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="manage_staff.php" style="padding:8px 12px; background:#95a5a6; color:white; border-radius:4px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>

                <a href="admin_register.php" style="background:#27ae60; color:white; padding:8px 15px; text-decoration:none; border-radius:4px; font-weight:bold; display:flex; align-items:center;">
                    <i class="fa-solid fa-plus" style="margin-right:5px;"></i> Add New
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background-color: #2c3e50; color: white; text-align: left;">
                        <th style="padding: 12px;">Name</th>
                        <th style="padding: 12px;">Username</th>
                        <th style="padding: 12px;">Email</th>
                        <th style="padding: 12px;">Phone</th>
                        <th style="padding: 12px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px; font-weight:bold;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td style="padding: 12px; color:#555;"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td style="padding: 12px;">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member? This cannot be undone.');">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" style="background:#e74c3c; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer;">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 30px; color: #999;">
                                <?php echo empty($search) ? "No staff members found." : "No staff found matching '$search'."; ?>
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