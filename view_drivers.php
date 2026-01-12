<?php
// 1. PHP Logic (Keep this)
session_start();
require_once 'db_connect.php';

// 2. INCLUDE THE NEW HEADER (This replaces the HTML <head> and <header>)
require_once 'admin_header.php'; 

// 3. The rest of your PHP logic
$search = "";
$sql = "SELECT * FROM drivers WHERE verification_status = 'verified'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR identification_id LIKE '%$search%')";
}
$sql .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<main class="dashboard-container">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-users-gear"></i> Driver List</h2>
            
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search drivers..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; border:1px solid #ccc; border-radius:4px;">
                <button type="submit" style="padding:8px 15px; background:#2c3e50; color:white; border:none; border-radius:4px; cursor:pointer;">Search</button>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background-color: #2c3e50; color: white; text-align: left;">
                        <th style="padding: 12px;">Name</th>
                        <th style="padding: 12px;">Email</th>
                        <th style="padding: 12px;">Phone</th>
                        <th style="padding: 12px;">License ID</th>
                        <th style="padding: 12px;">Joined</th>
                        <th style="padding: 12px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px; font-weight:bold;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($row['license_number']); ?></td>
                                <td style="padding: 12px;"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                <td style="padding: 12px;">
                                    <a href="admin_driver_chat.php?driver_id=<?php echo $row['driver_id']; ?>" style="color:#2980b9; text-decoration:none;">
                                        <i class="fa-solid fa-comment"></i> Chat
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color: #999;">No verified drivers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>