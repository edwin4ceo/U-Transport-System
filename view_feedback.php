<?php
session_start();
require_once 'db_connect.php';

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: admin_login.php"); exit(); }

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
        .msg-card { background: white; padding: 20px; margin-bottom: 15px; border-left: 5px solid #f39c12; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .msg-header { display: flex; justify-content: space-between; margin-bottom: 10px; color: #7f8c8d; font-size: 0.9rem; }
        .msg-subject { font-weight: bold; color: #2c3e50; font-size: 1.1rem; margin-bottom: 8px;}
        .msg-body { margin-top: 10px; line-height: 1.6; color: #333; margin-bottom: 15px;}
        .btn-reply { display: inline-block; background-color: #2c3e50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 0.9rem; transition: background 0.3s; }
        .btn-reply:hover { background-color: #34495e; }
    </style>
</head>
<body>

    <main>
        <div class="container" style="margin-top: 20px;">
            <h3 style="color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px;">Inbox Messages</h3>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="msg-card">
                        <div class="msg-header">
                            <span><i class="fa-solid fa-user"></i> <strong><?php echo htmlspecialchars($row['user_name']); ?></strong> &lt;<?php echo htmlspecialchars($row['user_email']); ?>&gt;</span>
                            <span><i class="fa-regular fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="msg-subject">Subject: <?php echo htmlspecialchars($row['subject']); ?></div>
                        <div class="msg-body"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                        
                        <a href="mailto:<?php echo htmlspecialchars($row['user_email']); ?>?subject=Re: <?php echo rawurlencode($row['subject']); ?>&body=Hi <?php echo rawurlencode($row['user_name']); ?>,%0D%0A%0D%0AThank you for your feedback regarding '<?php echo rawurlencode($row['subject']); ?>'.%0D%0A%0D%0A[Your response here]" 
                           class="btn-reply"><i class="fa-solid fa-reply"></i> Reply via Email</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 50px; background:white; border-radius:8px; color:#999;">
                    <i class="fa-regular fa-envelope" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>No feedback messages received yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>