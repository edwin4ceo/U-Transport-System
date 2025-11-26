<?php
session_start();
require_once 'db_connect.php';

// --- 1. SECURITY CHECK ---
// Only Admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// --- 2. HANDLE DELETE ACTION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id'])) {
    $user_id_to_delete = intval($_POST['delete_user_id']);
    
    // Prevent Admin from deleting themselves
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $error_msg = "You cannot delete your own account while logged in.";
    } else {
        $delete_sql = "DELETE FROM users WHERE user_id = $user_id_to_delete";
        if (mysqli_query($conn, $delete_sql)) {
            $success_msg = "User account deleted successfully.";
        } else {
            $error_msg = "Error deleting user: " . mysqli_error($conn);
        }
    }
}

// --- 3. FETCH ALL USERS ---
// Order by ID descending to see newest users first
$sql = "SELECT * FROM users ORDER BY user_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .role-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .role-passenger { background-color: #3498db; color: white; }
        .role-driver { background-color: #27ae60; color: white; }
        .role-admin { background-color: #8e44ad; color: white; }
        
        .btn-delete { background-color: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-delete:hover { background-color: #c0392b; }
        
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fa-solid fa-users-gear"></i> Manage Users</h1>
                <nav>
                    <a href="admin_dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h3>All Registered Users</h3>

            <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if (isset($error_msg)) echo "<div class='alert alert-error'>$error_msg</div>"; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
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
                                <td>
                                    <?php 
                                        $role_class = 'role-' . $row['role'];
                                        echo "<span class='role-badge $role_class'>" . ucfirst($row['role']) . "</span>"; 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if($row['role'] == 'driver') {
                                            echo ucfirst($row['verification_status']); 
                                        } else {
                                            echo "-";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['user_id'] != $_SESSION['user_id']): // Hide delete button for yourself ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_user_id" value="<?php echo $row['user_id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small>(You)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:20px;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>