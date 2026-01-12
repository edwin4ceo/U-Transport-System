<?php
session_start();
include "db_connect.php";

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// 2. MARK MESSAGES AS READ
if ($selected_student_id !== '') {
    $mark_stmt = $conn->prepare("UPDATE student_support_messages SET is_read = 1 WHERE student_id = ? AND sender_type = 'student'");
    $mark_stmt->bind_param("s", $selected_student_id);
    $mark_stmt->execute();
    $mark_stmt->close();
}

// 3. HANDLE NEW ADMIN REPLY
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_student_id = $_POST['student_id'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($selected_student_id !== '' && $message !== '') {
        // Insert message (is_read=1 because admin wrote it)
        $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message, is_read) VALUES (?, 'admin', ?, 1)");
        if ($stmt) {
            $stmt->bind_param("ss", $selected_student_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_student_chat.php?student_id=" . urlencode($selected_student_id));
    exit;
}

// 4. FETCH STUDENTS WITH MESSAGES
$students = [];
$s_sql = "
    SELECT s.student_id, s.name, s.email,
    (SELECT COUNT(*) FROM student_support_messages WHERE student_id = s.student_id AND sender_type='student' AND is_read=0) as unread
    FROM student_support_messages m
    JOIN students s ON m.student_id = s.student_id
    GROUP BY s.student_id
    ORDER BY unread DESC, s.name ASC
";
$result = $conn->query($s_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// 5. FETCH CHAT HISTORY
$messages = [];
if ($selected_student_id !== '') {
    $stmt = $conn->prepare("SELECT * FROM student_support_messages WHERE student_id = ? ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param("s", $selected_student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Support Chat | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; margin: 0; font-family: sans-serif; }
        .admin-header { background-color: #2c3e50; color: white; height: 70px; display: flex; align-items: center; }
        .admin-header .container { display: flex; justify-content: space-between; align-items: center; width: 90%; margin: 0 auto; }
        .admin-nav ul { list-style: none; display: flex; gap: 20px; padding: 0; margin: 0; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; }
        .admin-nav a:hover { color: white; }
        
        .chat-wrapper { max-width: 1000px; margin: 30px auto; padding: 0 10px; }
        .chat-card { background: white; border-radius: 12px; padding: 20px; height: 65vh; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .chat-messages { flex: 1; overflow-y: auto; padding-right: 10px; margin-bottom: 15px; }
        
        .chat-message-row { display: flex; margin-bottom: 10px; }
        .chat-message-row.student { justify-content: flex-start; }
        .chat-message-row.admin  { justify-content: flex-end; }
        
        .chat-bubble { max-width: 75%; padding: 10px 14px; border-radius: 12px; font-size: 14px; position: relative; }
        .chat-bubble.student { background: #ecf0f1; color: #2c3e50; border-bottom-left-radius: 2px; }
        .chat-bubble.admin { background: #8e44ad; color: white; border-bottom-right-radius: 2px; } /* Purple for Student Support */
        
        .chat-meta { font-size: 11px; margin-top: 4px; opacity: 0.8; text-align: right; }
        .chat-bubble.student .chat-meta { text-align: left; }
        
        .chat-input-wrapper { display: flex; gap: 10px; border-top: 1px solid #ecf0f1; padding-top: 15px; }
        .chat-input-wrapper textarea { flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #bdc3c7; resize: none; }
        .chat-input-wrapper button { background: #8e44ad; color: white; border: none; padding: 0 25px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <h1><i class="fa-solid fa-building-user"></i> FMD Staff</h1>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php">Home</a></li>
                    <li><a href="verify_drivers.php">Approve</a></li>
                    <li><a href="view_drivers.php">Drivers</a></li>
                    <li><a href="view_passengers.php">Passengers</a></li>
                    <li><a href="view_bookings.php">Bookings</a></li>
                    <li><a href="admin_student_chat.php" style="color:white;">Support</a></li>
                    <li><a href="admin_profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="chat-wrapper">
        <div class="chat-header-title">
            <h1 style="color:#2c3e50;"><i class="fa-solid fa-user-graduate"></i> Student Support Chat</h1>
        </div>

        <form method="get" class="chat-controls" style="margin-bottom: 15px; background: white; padding: 10px; border-radius: 8px;">
            <span><strong>Select Student:</strong></span>
            <select name="student_id" onchange="this.form.submit()" style="padding: 5px;">
                <option value="">-- Choose a student --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?php echo htmlspecialchars($s['student_id']); ?>" <?php echo $selected_student_id == $s['student_id'] ? 'selected' : ''; ?>>
                        <?php 
                        echo htmlspecialchars($s['name']); 
                        if($s['unread'] > 0) { echo " (" . $s['unread'] . " New)"; } 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="chat-card">
            <div class="chat-messages" id="chatMessages">
                <?php if ($selected_student_id === ''): ?>
                    <div style="text-align:center; padding-top:100px; color:#999;">Select a student to start chatting.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            $isAdmin = ($msg['sender_type'] === 'admin');
                            $rowClass = $isAdmin ? 'admin' : 'student';
                        ?>
                        <div class="chat-message-row <?php echo $rowClass; ?>">
                            <div class="chat-bubble <?php echo $rowClass; ?>">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                <div class="chat-meta"><?php echo date("d M, g:i A", strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($selected_student_id !== ''): ?>
                <form method="post" class="chat-input-wrapper">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                    <textarea name="message" required placeholder="Type reply..."></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        var container = document.getElementById("chatMessages");
        if (container) container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>