<?php
session_start();
require_once 'db_connect.php';
require_once 'admin_header.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// --- HANDLE DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $review_id = intval($_POST['delete_review_id']);
    $del_sql = "DELETE FROM reviews WHERE review_id = $review_id";
    if(mysqli_query($conn, $del_sql)) {
        echo "<script>Swal.fire({icon: 'success', title: 'Deleted!', text: 'Review has been removed.', timer: 1500, showConfirmButton: false});</script>";
    } else {
        echo "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Could not delete review.'});</script>";
    }
}

// --- FILTER LOGIC ---
$search = "";
$rating_filter = "";
$where_clauses = [];

// 1. Check for Text Search
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    // Adjusted aliases based on the query below
    $where_clauses[] = "(s.name LIKE '%$search%' OR d.full_name LIKE '%$search%' OR r.comment LIKE '%$search%')";
}

// 2. Check for Rating Filter
if (isset($_GET['rating']) && $_GET['rating'] !== "") {
    $rating_filter = intval($_GET['rating']);
    $where_clauses[] = "r.rating = $rating_filter";
}

// --- SQL QUERY (Using LEFT JOIN to handle NULL drivers or deleted users) ---
$sql = "SELECT r.*, 
               s.name AS passenger_name, 
               d.full_name AS driver_name 
        FROM reviews r 
        LEFT JOIN students s ON r.passenger_id = s.student_id 
        LEFT JOIN drivers d ON r.driver_id = d.driver_id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Reviews | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; }

        /* Filter Toolbar */
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .search-input, .filter-select {
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: 0.2s;
            font-size: 0.95rem;
        }
        .search-input { width: 250px; }
        .search-input:focus, .filter-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .btn-filter { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-reset { background: #9ca3af; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; }

        /* Table Design */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: top; }
        tr:hover { background: #f9fafb; }

        /* Star Ratings */
        .star-rating { color: #d1d5db; font-size: 0.85rem; }
        .star-filled { color: #f59e0b; } /* Amber/Gold color */
        
        /* User Info Styling */
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-icon { width: 35px; height: 35px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        .driver-icon { background: #f3e8ff; color: #7e22ce; }
        
        .info-text { display: flex; flex-direction: column; }
        .name { font-weight: 600; color: #111827; }
        .sub-text { font-size: 0.8rem; color: #6b7280; }

        /* Action Buttons */
        .btn-delete { background: #fee2e2; color: #991b1b; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-delete:hover { background: #fecaca; }
    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-star-half-stroke"></i> Reviews Management</h2>
            
            <form method="GET" class="filter-form">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search review content or name..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="rating" class="filter-select">
                    <option value="">All Ratings</option>
                    <option value="5" <?php if($rating_filter === 5) echo 'selected'; ?>>5 Stars Only</option>
                    <option value="4" <?php if($rating_filter === 4) echo 'selected'; ?>>4 Stars Only</option>
                    <option value="3" <?php if($rating_filter === 3) echo 'selected'; ?>>3 Stars Only</option>
                    <option value="2" <?php if($rating_filter === 2) echo 'selected'; ?>>2 Stars Only</option>
                    <option value="1" <?php if($rating_filter === 1) echo 'selected'; ?>>1 Star Only</option>
                </select>

                <button type="submit" class="btn-filter">Filter</button>
                
                <?php if(!empty($search) || $rating_filter !== ""): ?>
                    <a href="manage_reviews.php" class="btn-reset"><i class="fa-solid fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 250px;">Passenger (From)</th>
                            <th style="width: 250px;">Driver (To)</th>
                            <th style="width: 120px;">Rating</th>
                            <th>Comment</th>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td style="color:#6b7280;">#<?php echo $row['review_id']; ?></td>
                                    
                                    <td>
                                        <div class="user-info">
                                            <div class="user-icon"><i class="fa-solid fa-user"></i></div>
                                            <div class="info-text">
                                                <span class="name"><?php echo htmlspecialchars($row['passenger_name'] ?? 'Unknown'); ?></span>
                                                <span class="sub-text"><?php echo htmlspecialchars($row['passenger_id']); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="user-info">
                                            <div class="user-icon driver-icon"><i class="fa-solid fa-car"></i></div>
                                            <div class="info-text">
                                                <span class="name"><?php echo htmlspecialchars($row['driver_name'] ?? 'System / Unassigned'); ?></span>
                                                <span class="sub-text">Driver ID: <?php echo htmlspecialchars($row['driver_id'] ?? '-'); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="star-rating">
                                            <?php 
                                            $rating = intval($row['rating']);
                                            for($i=1; $i<=5; $i++) {
                                                if($i <= $rating) echo '<i class="fa-solid fa-star star-filled"></i>';
                                                else echo '<i class="fa-solid fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:#6b7280; margin-top:3px; font-weight:600;">
                                            <?php echo $rating; ?>.0 / 5.0
                                        </div>
                                    </td>

                                    <td>
                                        <div style="color:#374151; font-style:italic;">
                                            "<?php echo htmlspecialchars($row['comment']); ?>"
                                        </div>
                                    </td>

                                    <td style="font-size:0.85rem; color:#6b7280;">
                                        <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                    </td>

                                    <td>
                                        <form method="POST" id="delete_form_<?php echo $row['review_id']; ?>">
                                            <input type="hidden" name="delete_review_id" value="<?php echo $row['review_id']; ?>">
                                            <button type="button" class="btn-delete" onclick="confirmDelete(<?php echo $row['review_id']; ?>)">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;">
                                    <i class="fa-regular fa-star" style="font-size:2rem; margin-bottom:10px;"></i><br>
                                    No reviews found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Review?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete_form_' + id).submit();
            }
        });
    }
    </script>

</body>
</html>