<?php
// 1. Start Session & Connect DB (Must be first)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db_connect.php';

// 2. Security Check
// Allow both Admin AND Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: admin_login.php");
    exit();
}

// 3. Get Selected Student ID
$selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// 4. Logic: Mark messages as READ
if ($selected_student_id !== '') {
    $mark_read_stmt = $conn->prepare("UPDATE student_support_messages SET is_read = 1 WHERE student_id = ? AND sender_type = 'student'");
    $mark_read_stmt->bind_param("s", $selected_student_id);
    $mark_read_stmt->execute();
    $mark_read_stmt->close();
}

// 5. Logic: Handle Sending New Message (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_student_id = $_POST['student_id'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($post_student_id !== '' && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message, is_read) VALUES (?, 'admin', ?, 1)");
        if ($stmt) {
            $stmt->bind_param("ss", $post_student_id, $message);
            $stmt->execute();
            $stmt->close();
        }
        
        // REDIRECT (Logic done, safe to redirect now)
        header("Location: admin_student_chat.php?student_id=" . urlencode($post_student_id));
        exit();
    }
}

// 6. Logic: Fetch Chat History
$messages = [];
if ($selected_student_id !== '') {
    $sql = "SELECT * FROM student_support_messages WHERE student_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// 7. Logic: Fetch List of Students (Active Chats + Others)
$students_list = [];
$list_sql = "
    SELECT s.student_id, s.name, 
    (SELECT COUNT(*) FROM student_support_messages WHERE student_id = s.student_id AND sender_type = 'student' AND is_read = 0) as unread
    FROM students s
    ORDER BY unread DESC, s.name ASC
";
$list_res = mysqli_query($conn, $list_sql);
while($row = mysqli_fetch_assoc($list_res)){
    $students_list[] = $row;
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
    .student-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; }
    .student-item:hover, .student-item.active { background-color: #f0f4f8; border-left: 4px solid #2c3e50; }
    .badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold; }

    /* Chat Area */
    .chat-area { flex: 1; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; overflow: hidden; }
    .chat-header { padding: 15px; background: #2c3e50; color: white; font-weight: bold; }
    .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
    
    /* Messages */
    .chat-message-row { display: flex; width: 100%; }
    .chat-message-row.admin { justify-content: flex-end; }
    .chat-message-row.student { justify-content: flex-start; }
    
    .chat-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; font-size: 0.95rem; line-height: 1.4; position: relative; }
    .chat-bubble.admin { background-color: #8e44ad; color: white; border-bottom-right-radius: 2px; } /* Purple for Admin/Student chat */
    .chat-bubble.student { background-color: #e5e5ea; color: #333; border-bottom-left-radius: 2px; }
    .chat-meta { font-size: 0.7rem; margin-top: 5px; opacity: 0.7; text-align: right; }

    /* Input Area */
    .chat-input-wrapper { padding: 15px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; }
    .chat-input-wrapper textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none; height: 50px; }
    .chat-input-wrapper button { padding: 0 25px; background: #8e44ad; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .chat-input-wrapper button:hover { background: #732d91; }
</style>

<main class="dashboard-container">
    <div class="container">
        <h2 style="color:#2c3e50; margin:0;"><i class="fa-solid fa-user-graduate"></i> Student Support Chat</h2>

        <div class="chat-layout">
            
            <div class="chat-sidebar">
                <?php if (count($students_list) > 0): ?>
                    <?php foreach ($students_list as $s): ?>
                        <a href="admin_student_chat.php?student_id=<?php echo urlencode($s['student_id']); ?>" 
                           class="student-item <?php echo ($selected_student_id == $s['student_id']) ? 'active' : ''; ?>">
                            <div>
                                <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($s['name']); ?>
                            </div>
                            <?php if ($s['unread'] > 0): ?>
                                <span class="badge"><?php echo $s['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; color:#999;">No students found.</div>
                <?php endif; ?>
            </div>

            <div class="chat-area">
                <div class="chat-header">
                    <?php 
                        if ($selected_student_id !== '') {
                            $s_name = "Unknown";
                            foreach($students_list as $sl) { if($sl['student_id'] == $selected_student_id) $s_name = $sl['name']; }
                            echo "Chat with: " . htmlspecialchars($s_name);
                        } else {
                            echo "Select a student to start chatting";
                        }
                    ?>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php if ($selected_student_id === ''): ?>
                        <div style="text-align:center; margin-top:50px; color:#aaa;">
                            <i class="fa-regular fa-comments" style="font-size:3rem;"></i><br><br>
                            Select a student from the left to view messages.
                        </div>
                    <?php elseif (empty($messages)): ?>
                        <div style="text-align:center; margin-top:50px; color:#aaa;">No messages yet.</div>
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
    </div>

    <script>
        // Auto-scroll to bottom of chat
        var container = document.getElementById("chatMessages");
        if (container) container.scrollTop = container.scrollHeight;
    </script>
</main>
</body>
</html>