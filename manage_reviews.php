<?php
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Handle Delete
if (isset($_POST['delete_review_id'])) {
    $review_id = intval($_POST['delete_review_id']);
    $del_sql = "DELETE FROM reviews WHERE review_id = $review_id";
    if(mysqli_query($conn, $del_sql)) {
        $msg = "<div style='color:green; margin-bottom:15px;'>Review deleted successfully.</div>";
    }
}

// Fetch Reviews (Joining with Users table to get names)
$sql = "SELECT r.*, p.full_name AS passenger_name, d.full_name AS driver_name 
        FROM reviews r 
        JOIN users p ON r.passenger_id = p.user_id 
        JOIN users d ON r.driver_id = d.user_id 
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reviews | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; font-size: 0.9rem; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .star { color: #f1c40f; }
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h1><i class="fa-solid fa-star-half-stroke"></i> Manage Reviews</h1>
                <a href="admin_dashboard.php" style="color: white;">Back to Dashboard</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h3>Passenger Reviews & Ratings</h3>
            <?php if(isset($msg)) echo $msg; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From (Passenger)</th>
                        <th>To (Driver)</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['review_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['passenger_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
                                <td>
                                    <?php 
                                    // Display Stars
                                    for($i=0; $i<$row['rating']; $i++) echo "<i class='fa-solid fa-star star'></i>"; 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this review?');">
                                        <input type="hidden" name="delete_review_id" value="<?php echo $row['review_id']; ?>">
                                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No reviews found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>