<?php
// 1. Start Session & Connect DB (Do this first)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db_connect.php';

// 2. Security Check (Must be before any HTML)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// 3. Get Selected Driver ID
$selected_driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

// 4. Logic: Mark messages as READ
if ($selected_driver_id > 0) {
    $mark_read_stmt = $conn->prepare("UPDATE driver_support_messages SET is_read = 1 WHERE driver_id = ? AND sender_type = 'driver'");
    $mark_read_stmt->bind_param("i", $selected_driver_id);
    $mark_read_stmt->execute();
    $mark_read_stmt->close();
}

// 5. Logic: Handle Sending New Message (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_driver_id = (int)($_POST['driver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($post_driver_id > 0 && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message, is_read) VALUES (?, 'admin', ?, 1)");
        if ($stmt) {
            $stmt->bind_param("is", $post_driver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
        
        // REDIRECT (This caused the error before because it was below the HTML)
        header("Location: admin_driver_chat.php?driver_id=" . $post_driver_id);
        exit();
    }
}

// 6. Logic: Fetch Chat History
$messages = [];
if ($selected_driver_id > 0) {
    $sql = "SELECT * FROM driver_support_messages WHERE driver_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// 7. Logic: Fetch List of Drivers (for the sidebar)
// We get drivers who have sent messages + verified drivers
$drivers_list = [];
$list_sql = "
    SELECT d.driver_id, d.full_name, 
    (SELECT COUNT(*) FROM driver_support_messages WHERE driver_id = d.driver_id AND sender_type = 'driver' AND is_read = 0) as unread
    FROM drivers d 
    WHERE d.verification_status = 'verified'
    ORDER BY unread DESC, d.full_name ASC
";
$list_res = mysqli_query($conn, $list_sql);
while($row = mysqli_fetch_assoc($list_res)){
    $drivers_list[] = $row;
}

// =========================================================
// 8. NOW INCLUDE THE HEADER (HTML Output Starts Here)
// =========================================================
require_once 'admin_header.php'; 
?>

<style>
    .chat-layout { display: flex; height: calc(100vh - 100px); margin-top: 20px; gap: 20px; }
    
    /* Sidebar */
    .chat-sidebar { width: 300px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow-y: auto; }
    .driver-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; }
    .driver-item:hover, .driver-item.active { background-color: #f0f4f8; border-left: 4px solid #2c3e50; }
    .badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold; }

    /* Chat Area */
    .chat-area { flex: 1; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; overflow: hidden; }
    .chat-header { padding: 15px; background: #2c3e50; color: white; font-weight: bold; }
    .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
    
    /* Messages */
    .chat-message-row { display: flex; width: 100%; }
    .chat-message-row.admin { justify-content: flex-end; }
    .chat-message-row.driver { justify-content: flex-start; }
    
    .chat-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; font-size: 0.95rem; line-height: 1.4; position: relative; }
    .chat-bubble.admin { background-color: #3498db; color: white; border-bottom-right-radius: 2px; }
    .chat-bubble.driver { background-color: #e5e5ea; color: #333; border-bottom-left-radius: 2px; }
    .chat-meta { font-size: 0.7rem; margin-top: 5px; opacity: 0.7; text-align: right; }

    /* Input Area */
    .chat-input-wrapper { padding: 15px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; }
    .chat-input-wrapper textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none; height: 50px; }
    .chat-input-wrapper button { padding: 0 25px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .chat-input-wrapper button:hover { background: #1a252f; }
</style>

<main class="dashboard-container">
    <div class="container">
        <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-headset"></i> Driver Support Chat</h2>

        <div class="chat-layout">
            
            <div class="chat-sidebar">
                <?php if (count($drivers_list) > 0): ?>
                    <?php foreach ($drivers_list as $d): ?>
                        <a href="admin_driver_chat.php?driver_id=<?php echo $d['driver_id']; ?>" 
                           class="driver-item <?php echo ($selected_driver_id == $d['driver_id']) ? 'active' : ''; ?>">
                            <div>
                                <i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($d['full_name']); ?>
                            </div>
                            <?php if ($d['unread'] > 0): ?>
                                <span class="badge"><?php echo $d['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; color:#999;">No verified drivers found.</div>
                <?php endif; ?>
            </div>

            <div class="chat-area">
                <div class="chat-header">
                    <?php 
                        if ($selected_driver_id > 0) {
                            // Find name from our list or fetch it
                            $d_name = "Unknown";
                            foreach($drivers_list as $dl) { if($dl['driver_id'] == $selected_driver_id) $d_name = $dl['full_name']; }
                            echo "Chat with: " . htmlspecialchars($d_name);
                        } else {
                            echo "Select a driver to start chatting";
                        }
                    ?>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php if ($selected_driver_id == 0): ?>
                        <div style="text-align:center; margin-top:50px; color:#aaa;">
                            <i class="fa-regular fa-comments" style="font-size:3rem;"></i><br><br>
                            Select a driver from the left to view messages.
                        </div>
                    <?php elseif (empty($messages)): ?>
                        <div style="text-align:center; margin-top:50px; color:#aaa;">No messages yet. Say hello!</div>
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
                        <input type="hidden" name="driver_id" value="<?php echo htmlspecialchars($selected_driver_id); ?>">
                        <textarea name="message" required placeholder="Type reply..."></textarea>
                        <button type="submit">Send</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        var container = document.getElementById("chatMessages");
        if (container) container.scrollTop = container.scrollHeight;
    </script>
</main>
</body>
</html>