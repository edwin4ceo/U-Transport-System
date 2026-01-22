<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER
require_once 'admin_header.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

$search = "";

// FETCH STUDENTS
$sql = "SELECT * FROM students";
$where_clauses = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%')";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Passengers | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; }
        
        /* Search Box */
        .search-box { display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 250px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-search { background: #1f2937; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-reset { background: #9ca3af; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center;}

        /* Table Design */
        .card-table { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; text-align: left; padding: 15px; font-weight: 600; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f9fafb; }
        
        /* Profile Image */
        .profile-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
        .profile-placeholder { width: 45px; height: 45px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* Typography */
        .student-name { font-weight: 600; color: #111827; font-size: 1rem; }
        .student-id { color: #6b7280; font-size: 0.85rem; margin-top: 2px; }
        .info-label { font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-right: 5px; }
        .gender-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .gender-male { background: #dbeafe; color: #1e40af; }
        .gender-female { background: #fce7f3; color: #9d174d; }
    </style>
</head>
<body>

    <main class="dashboard-container">
        <div class="page-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <h2 class="page-title"><i class="fa-solid fa-users-viewfinder"></i> Passenger List</h2>
                <span style="background:#e5e7eb; padding:2px 10px; border-radius:12px; font-size:0.85rem; font-weight:600; color:#4b5563;">
                    Total: <?php echo mysqli_num_rows($result); ?>
                </span>
            </div>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Search name, ID, email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="view_passengers.php" class="btn-reset"><i class="fa-solid fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-table">
            <div style="overflow-x: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Profile</th>
                            <th>Student Details</th>
                            <th>Contact Information</th>
                            <th>Account Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['profile_image']) && file_exists("uploads/profile_images/" . $row['profile_image'])): ?>
                                            <img src="uploads/profile_images/<?php echo htmlspecialchars($row['profile_image']); ?>" class="profile-img" alt="Profile">
                                        <?php else: ?>
                                            <div class="profile-placeholder">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="student-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="student-id"><i class="fa-regular fa-id-card"></i> <?php echo htmlspecialchars($row['student_id']); ?></div>
                                        <div style="margin-top:5px;">
                                            <?php 
                                                $gender = ucfirst(strtolower($row['gender']));
                                                $gClass = ($gender == 'Female') ? 'gender-female' : 'gender-male';
                                            ?>
                                            <span class="gender-badge <?php echo $gClass; ?>"><?php echo $gender; ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <div><i class="fa-regular fa-envelope" style="color:#9ca3af; width:20px;"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                        <div style="margin-top:5px;"><i class="fa-solid fa-phone" style="color:#9ca3af; width:20px;"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                    </td>

                                    <td>
                                        <span style="color:#059669; font-weight:600; font-size:0.9rem;">
                                            <i class="fa-solid fa-circle-check"></i> Active
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 40px; color: #9ca3af;">
                                    <i class="fa-solid fa-user-slash" style="font-size:2rem; margin-bottom:10px;"></i><br>
                                    No passengers found matching your search.
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