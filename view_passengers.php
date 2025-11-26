<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: admin_login.php"); exit(); }

$search = "";
$sql = "SELECT * FROM users WHERE role='passenger'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Passengers</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .search-box { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-box input { padding: 8px; width: 300px; }
        .search-box button { padding: 8px 15px; background-color: #2980b9; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <h1><i class="fa-solid fa-person-walking-luggage"></i> Registered Passengers</h1>
            <a href="admin_dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search by Name or Email" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fa-solid fa-search"></i> Search</button>
                <a href="view_passengers.php" style="padding: 8px 15px; background: #7f8c8d; color: white; text-decoration: none;">Reset</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Join Date</th>
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
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No passengers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>