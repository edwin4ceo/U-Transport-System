<?php
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Fetch all messages
$sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Feedback | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .msg-card { background: white; padding: 15px; margin-bottom: 15px; border-left: 4px solid #f39c12; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .msg-header { display: flex; justify-content: space-between; margin-bottom: 10px; color: #7f8c8d; font-size: 0.9rem; }
        .msg-subject { font-weight: bold; color: #2c3e50; font-size: 1.1rem; }
        .msg-body { margin-top: 10px; line-height: 1.5; }
    </style>
</head>
<body>
    <header style="background-color: #2c3e50; color: white; padding: 15px 0;">
        <div class="container">
            <h1><i class="fa-solid fa-envelope-open-text"></i> User Feedback</h1>
            <a href="admin_dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
    </header>

    <main>
        <div class="container">
            <h3>Inbox Messages</h3>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="msg-card">
                        <div class="msg-header">
                            <span>From: <strong><?php echo htmlspecialchars($row['user_name']); ?></strong> (<?php echo htmlspecialchars($row['user_email']); ?>)</span>
                            <span><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="msg-subject"><?php echo htmlspecialchars($row['subject']); ?></div>
                        <div class="msg-body"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999; margin-top:30px;">No feedback messages received yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>