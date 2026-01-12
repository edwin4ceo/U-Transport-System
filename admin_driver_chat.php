<?php
session_start();
include "db_connect.php";

// INCLUDE THE NEW HEADER (This replaces all the HTML/CSS/Menu code)
require_once 'admin_header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$selected_driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

// --- UPDATE 1: Mark messages as READ when viewing a specific driver ---
if ($selected_driver_id > 0) {
    $mark_read_stmt = $conn->prepare("UPDATE driver_support_messages SET is_read = 1 WHERE driver_id = ? AND sender_type = 'driver'");
    $mark_read_stmt->bind_param("i", $selected_driver_id);
    $mark_read_stmt->execute();
    $mark_read_stmt->close();
}

// Handle new admin message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_driver_id = (int)($_POST['driver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($selected_driver_id > 0 && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message, is_read) VALUES (?, 'admin', ?, 1)");
        // Note: Admin messages are 'read' by default or irrelevant for this check
        if ($stmt) {
            $stmt->bind_param("is", $selected_driver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_driver_chat.php?driver_id=" . $selected_driver_id);
    exit;
}

// Fetch list of drivers who have messages
// --- UPDATE 2: Add logic to show UNREAD count next to driver name ---
$drivers = [];
$result = $conn->query("
    SELECT d.driver_id, d.full_name, d.email,
    (SELECT COUNT(*) FROM driver_support_messages WHERE driver_id = d.driver_id AND sender_type='driver' AND is_read=0) as unread
    FROM driver_support_messages m
    JOIN drivers d ON m.driver_id = d.driver_id
    GROUP BY d.driver_id
    ORDER BY unread DESC, d.full_name ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

// Fetch chat history
$messages = [];
if ($selected_driver_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM driver_support_messages WHERE driver_id = ? ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param("i", $selected_driver_id);
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
    <title>Driver Support Chat | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <style>
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
        .chat-message-row.driver { justify-content: flex-start; }
        .chat-message-row.admin  { justify-content: flex-end; }
        
        .chat-bubble { max-width: 75%; padding: 10px 14px; border-radius: 12px; font-size: 14px; position: relative; }
        .chat-bubble.driver { background: #ecf0f1; color: #2c3e50; border-bottom-left-radius: 2px; }
        .chat-bubble.admin { background: #2980b9; color: white; border-bottom-right-radius: 2px; }
        
        .chat-meta { font-size: 11px; margin-top: 4px; opacity: 0.8; text-align: right; }
        .chat-bubble.driver .chat-meta { text-align: left; }
        
        .chat-input-wrapper { display: flex; gap: 10px; border-top: 1px solid #ecf0f1; padding-top: 15px; }
        .chat-input-wrapper textarea { flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #bdc3c7; resize: none; }
        .chat-input-wrapper button { background: #27ae60; color: white; border: none; padding: 0 25px; border-radius: 8px; cursor: pointer; }
        
        /* Unread Badge Style */
        .unread-badge { background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-left: 5px; }
    </style>
</head>
<body>

    <div class="chat-wrapper">
        <div class="chat-header-title">
            <h1><i class="fa-solid fa-comments"></i> Driver Support Chat</h1>
        </div>

        <form method="get" class="chat-controls" style="margin-bottom: 15px; background: white; padding: 10px; border-radius: 8px;">
            <span><strong>Select Driver:</strong></span>
            <select name="driver_id" onchange="this.form.submit()" style="padding: 5px;">
                <option value="0">-- Choose a driver --</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?php echo (int)$d['driver_id']; ?>" <?php echo $selected_driver_id == $d['driver_id'] ? 'selected' : ''; ?>>
                        <?php 
                        echo htmlspecialchars($d['full_name']); 
                        if($d['unread'] > 0) { echo " (" . $d['unread'] . " New)"; } 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="chat-card">
            <div class="chat-messages" id="chatMessages">
                <?php if ($selected_driver_id === 0): ?>
                    <div style="text-align:center; padding-top:100px; color:#999;">Select a driver to start chatting.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            $isAdmin = ($msg['sender_type'] === 'admin');
                            $rowClass = $isAdmin ? 'admin' : 'driver';
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

            <?php if ($selected_driver_id > 0): ?>
                <form method="post" class="chat-input-wrapper">
                    <input type="hidden" name="driver_id" value="<?php echo (int)$selected_driver_id; ?>">
                    <textarea name="message" required placeholder="Type reply..."></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // 1. Scroll Chat to Bottom
        var container = document.getElementById("chatMessages");
        if (container) container.scrollTop = container.scrollHeight;

        // 2. Poll for New Messages (Every 3 seconds)
        let lastUnreadCount = 0;
        
        function checkMessages() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    let currentUnread = data.unread_count;

                    // If unread messages increased, show popup
                    if (currentUnread > lastUnreadCount && lastUnreadCount !== 0) {
                        const audio = new Audio('https://proxy.notificationsounds.com/notification-sounds/completed-577/download/file-sounds-1149-completed.mp3'); // Optional Sound
                        audio.play().catch(e => console.log("Audio blocked"));
                        
                        Swal.fire({
                            position: 'top-end',
                            icon: 'info',
                            title: 'New Message!',
                            text: 'You have a new message from a driver.',
                            showConfirmButton: false,
                            timer: 3000,
                            toast: true,
                            background: '#e3f2fd'
                        });
                        
                        // Optional: Refresh page automatically if specific driver is not selected
                         // location.reload(); 
                    }
                    lastUnreadCount = currentUnread;
                });
        }

        // Initialize and start interval
        fetch('check_notifications.php').then(r => r.json()).then(d => lastUnreadCount = d.unread_count);
        setInterval(checkMessages, 3000); // Check every 3 seconds
    </script>

</body>
</html>