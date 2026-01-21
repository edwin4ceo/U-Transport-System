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
            echo "<script>Swal.fire({icon: 'success', title: 'Deleted!', text: 'Staff member has been removed.', timer: 1500, showConfirmButton: false});</script>";
        } else {
            echo "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Could not delete staff.'});</script>";
        }
    } else {
        echo "<script>Swal.fire({icon: 'error', title: 'Action Denied', text: 'You cannot delete your own account!'});</script>";
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

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Staff | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; }
        
        /* Search & Action Bar */
        .action-bar { display: flex; gap: 10px; }
        .search-form { display: flex; gap: 5px; }
        .search-input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; width: 220px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-search { background: #1f2937; color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-reset { background: #9ca3af; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; }
        
        .btn-add { background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(39, 174, 96, 0.2); }
        .btn-add:hover { background: #219150; }

        /* Table Design */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f9fafb; }

        /* Profile Placeholder */
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* Text Styles */
        .staff-name { font-weight: 600; color: #111827; display: block; }
        .staff-username { font-size: 0.85rem; color: #6b7280; }
        .contact-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 0.9rem; }
        .contact-row i { color: #9ca3af; width: 16px; text-align: center; }
        
        /* Badges */
        .role-badge { background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        .btn-delete { background: #fee2e2; color: #991b1b; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-delete:hover { background: #fecaca; }
    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-users-gear"></i> Manage Staff Team</h2>
            
            <div class="action-bar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search staff..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="manage_staff.php" class="btn-reset"><i class="fa-solid fa-times"></i></a>
                    <?php endif; ?>
                </form>

                <a href="admin_register.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add Staff
                </a>
            </div>
        </div>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Profile</th>
                            <th>Staff Details</th>
                            <th>Contact Info</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <div class="profile-icon">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="staff-name"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                        <span class="staff-username">@<?php echo htmlspecialchars($row['username']); ?></span>
                                    </td>

                                    <td>
                                        <div class="contact-row">
                                            <i class="fa-regular fa-envelope"></i> 
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </div>
                                        <div class="contact-row">
                                            <i class="fa-solid fa-phone"></i> 
                                            <?php echo htmlspecialchars($row['phone_number']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="role-badge">STAFF</span>
                                    </td>

                                    <td>
                                        <form method="POST" id="delete_form_<?php echo $row['id']; ?>">
                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                            <button type="button" class="btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')">
                                                <i class="fa-regular fa-trash-can"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px; color: #9ca3af;">
                                    <i class="fa-solid fa-user-slash" style="font-size:2rem; margin-bottom:10px;"></i><br>
                                    <?php echo empty($search) ? "No staff members found." : "No staff found matching '$search'."; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function confirmDelete(id, username) {
        Swal.fire({
            title: 'Remove Staff?',
            html: `Are you sure you want to remove <b>${username}</b>?<br>They will no longer be able to access the admin panel.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Remove!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete_form_' + id).submit();
            }
        });
    }
    </script>

</body>
</html>