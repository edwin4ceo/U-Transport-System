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
        $msg = "<div style='color:green; margin-bottom:15px; padding:10px; background:#e8f5e9; border-radius:5px;'>Review deleted successfully.</div>";
    }
}

// --- FILTER LOGIC ---
$search = "";
$rating_filter = "";
$where_clauses = [];

// 1. Check for Text Search (Passenger, Driver, or Comment)
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where_clauses[] = "(s.name LIKE '%$search%' OR d.full_name LIKE '%$search%' OR r.comment LIKE '%$search%')";
}

// 2. Check for Rating Filter
if (isset($_GET['rating']) && $_GET['rating'] !== "") {
    $rating_filter = intval($_GET['rating']);
    $where_clauses[] = "r.rating = $rating_filter";
}

// --- SQL QUERY ---
$sql = "SELECT r.*, 
               s.name AS passenger_name, 
               d.full_name AS driver_name 
        FROM reviews r 
        JOIN students s ON r.passenger_id = s.student_id 
        JOIN drivers d ON r.driver_id = d.driver_id";

// Apply Filters if any exist
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY r.created_at DESC";

$result = mysqli_query($conn, $sql);

// Error handling
if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reviews | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; font-size: 0.9rem; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
        th { background-color: #2c3e50; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f9f9f9; }
        
        .star { color: #f1c40f; font-size: 0.8rem; }
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-delete:hover { background-color: #c0392b; }

        /* --- NEW FILTER TOOLBAR STYLES --- */
        .admin-toolbar {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 250px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-filter {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-filter:hover { background-color: #2980b9; }

        .btn-reset {
            padding: 8px 15px;
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .btn-reset:hover { background-color: #7f8c8d; }

        .count-label { color: #7f8c8d; font-size: 0.9rem; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h1><i class="fa-solid fa-star-half-stroke"></i> Manage Reviews</h1>
                <a href="admin_dashboard.php" style="color: white; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container" style="margin-top: 30px;">
            
            <div class="admin-toolbar">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search passenger, driver or comment..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="rating" class="filter-select">
                        <option value="">All Ratings</option>
                        <option value="5" <?php if($rating_filter === 5) echo 'selected'; ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" <?php if($rating_filter === 4) echo 'selected'; ?>>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" <?php if($rating_filter === 3) echo 'selected'; ?>>⭐⭐⭐ (3 Stars)</option>
                        <option value="2" <?php if($rating_filter === 2) echo 'selected'; ?>>⭐⭐ (2 Stars)</option>
                        <option value="1" <?php if($rating_filter === 1) echo 'selected'; ?>>⭐ (1 Star)</option>
                    </select>

                    <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    
                    <?php if(!empty($search) || $rating_filter !== ""): ?>
                        <a href="manage_reviews.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="count-label">
                    Found: <strong><?php echo mysqli_num_rows($result); ?></strong> reviews
                </div>
            </div>

            <?php if(isset($msg)) echo $msg; ?>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From (Passenger)</th>
                            <th>To (Driver)</th>
                            <th style="min-width: 100px;">Rating</th>
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
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong>
                                        <br><small style="color:#777;"><?php echo htmlspecialchars($row['passenger_id']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
                                    <td>
                                        <?php 
                                        // Display Stars
                                        for($i=0; $i<$row['rating']; $i++) echo "<i class='fa-solid fa-star star'></i>"; 
                                        // Display empty stars for balance? Optional.
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="delete_review_id" value="<?php echo $row['review_id']; ?>">
                                            <button type="submit" class="btn-delete" title="Delete Review">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#7f8c8d;">
                                    <i class="fa-regular fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
                                    No reviews found matching your search.
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