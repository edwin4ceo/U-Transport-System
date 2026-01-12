<?php
session_start();

include "db_connect.php";
include "function.php";

// --- 1. IDENTIFY USER ROLE ---
$current_role = ""; 
$current_id = "";

if (isset($_SESSION['driver_id'])) {
    $current_role = 'driver';
    $current_id = $_SESSION['driver_id'];
} 
elseif (isset($_SESSION['student_id'])) {
    $current_role = 'student';
    $current_id = $_SESSION['student_id'];
} 
else {
    // Redirect if not logged in
    redirect("passanger_login.php"); 
    exit;
}

// --- 2. HANDLE MESSAGE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        // Auto-reply text
        $auto_reply_text = "Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.";

        if ($current_role === 'driver') {
            // A. DRIVER SIDE
            // Insert User Message
            $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message) VALUES (?, 'driver', ?)");
            $stmt->bind_param("is", $current_id, $message);
            $stmt->execute(); $stmt->close();

            // Insert Auto-Reply
            $stmt_auto = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message) VALUES (?, 'admin', ?)");
            $stmt_auto->bind_param("is", $current_id, $auto_reply_text);
            $stmt_auto->execute(); $stmt_auto->close();

        } else {
            // B. STUDENT SIDE
            // Insert User Message
            $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message) VALUES (?, 'student', ?)");
            $stmt->bind_param("ss", $current_id, $message);
            $stmt->execute(); $stmt->close();

            // Insert Auto-Reply
            $stmt_auto = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message) VALUES (?, 'admin', ?)");
            $stmt_auto->bind_param("ss", $current_id, $auto_reply_text);
            $stmt_auto->execute(); $stmt_auto->close();
        }
    }

    // Refresh page to show new messages and prevent form resubmission
    header("Location: contact_us.php");
    exit;
}

// --- 3. FETCH CHAT HISTORY ---
$messages = [];

// Determine SQL based on role
if ($current_role === 'driver') {
    $sql = "SELECT id, driver_id, sender_type, message, created_at, is_read 
            FROM driver_support_messages 
            WHERE driver_id = ? 
            ORDER BY created_at ASC, id ASC";
} else {
    $sql = "SELECT id, student_id, sender_type, message, created_at, is_read 
            FROM student_support_messages 
            WHERE student_id = ? 
            ORDER BY created_at ASC, id ASC";
}

// Execute Query
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($current_role === 'driver') {
        $stmt->bind_param("i", $current_id);
    } else {
        $stmt->bind_param("s", $current_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    $stmt->close();
}

// --- 4. MARK AS READ (DIRECTLY HERE) ---
// This runs immediately after fetching messages.
// The messages array above still contains the old "is_read = 0" status so we can show "NEW" tags.
// But the database is updated right now, so next time it will be clean.

if ($current_role === 'driver') {
    // Update Driver Messages
    $update_sql = "UPDATE driver_support_messages SET is_read = 1 WHERE driver_id = ? AND sender_type = 'admin' AND is_read = 0";
    $stmt_upd = $conn->prepare($update_sql);
    if ($stmt_upd) {
        $stmt_upd->bind_param("i", $current_id);
        $stmt_upd->execute();
        $stmt_upd->close();
    }
} 
// Optionally add student logic here if needed
/*
elseif ($current_role === 'student') {
    $update_sql = "UPDATE student_support_messages SET is_read = 1 WHERE student_id = ? AND sender_type = 'admin' AND is_read = 0";
    $stmt_upd = $conn->prepare($update_sql);
    if ($stmt_upd) {
        $stmt_upd->bind_param("s", $current_id);
        $stmt_upd->execute();
        $stmt_upd->close();
    }
}
*/

include "header.php";
?>

<style>
/* --- CHAT STYLES --- */
.chat-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 900px;
    margin: 0 auto;
    background: #f5f7fb;
}

.chat-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #004b82;
}

.chat-header-title p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.chat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e3e6ea;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    padding: 16px;
    display: flex;
    flex-direction: column;
    height: 70vh;
    max-height: 600px;
    margin-top: 20px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding-right: 6px;
    margin-bottom: 10px;
}

.chat-message-row {
    display: flex;
    margin-bottom: 8px;
    width: 100%;
}

.chat-message-row.me { justify-content: flex-end; }
.chat-message-row.support { justify-content: flex-start; }

/* --- BUBBLE STYLES (Compact Size) --- */
.chat-bubble {
    max-width: 80%; 
    padding: 8px 12px; 
    border-radius: 14px;
    font-size: 13px; 
    line-height: 1.4; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    position: relative;
    word-wrap: break-word;
    text-align: left; 
}

.chat-bubble.me {
    background: #004b82;
    color: #ffffff;
    border-bottom-right-radius: 2px;
}

.chat-bubble.support {
    background: #eef4ff;
    color: #2c3e50;
    border-bottom-left-radius: 2px;
}

.chat-bubble.unread {
    border: 2px solid #ff6b6b;
    box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
}

.chat-meta {
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.7;
    text-align: right; 
    display: block;
}

.chat-empty {
    text-align: center;
    padding: 40px 10px;
    color: #777;
    font-size: 13px;
}

/* Input Area */
.chat-input-wrapper {
    width: 100%;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    border-top: 1px solid #e3e6ea;
    padding-top: 15px;
}

.chat-input-wrapper textarea {
    flex: 1 1 auto;
    width: 100%;
    box-sizing: border-box;
    border-radius: 20px;
    border: 1px solid #d0d4dd;
    padding: 10px 14px; 
    font-size: 13px; 
    resize: none;
    min-height: 40px;
    max-height: 150px;
    font-family: inherit;
}

.chat-input-wrapper textarea:focus {
    outline: none;
    border-color: #004b82;
}

.chat-input-wrapper button {
    flex: 0 0 auto;
    width: 90px;
    border: none;
    border-radius: 20px;
    padding: 10px 14px; 
    font-size: 13px; 
    font-weight: 600;
    cursor: pointer;
    background: #004b82;
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,75,130,0.2);
    transition: background 0.2s;
}

.chat-input-wrapper button:hover {
    background: #003660;
}

/* Unread badge */
.unread-badge {
    background: #ff6b6b;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
    font-weight: bold;
}
</style>

<div class="chat-wrapper">
    <div class="chat-header-title">
        <h1>Contact Support (<?php echo ucfirst($current_role); ?>)</h1>
        <p>Chat with the support team if you have any issues.</p>
    </div>

    <div class="chat-card">
        <div class="chat-messages" id="chatMessages">

            <?php if (count($messages) === 0): ?>
                <div class="chat-empty">
                    <i class="fa-regular fa-comments" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                    No messages yet. Start the conversation!
                </div>

            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $sender = $msg['sender_type'];
                        $isMe = ($sender === 'driver' || $sender === 'student');
                        // Use the data we fetched initially. 
                        // Even though DB is updated now, $msg['is_read'] still has the old value (0) for this page load.
                        $isUnread = ($sender === 'admin' && $msg['is_read'] == 0);
                        
                        $rowClass = $isMe ? 'me' : 'support';
                        $bubbleClass = $isMe ? 'me' : 'support';
                        if ($isUnread) {
                            $bubbleClass .= ' unread';
                        }
                        $displayName = $isMe ? "You" : "Support Team";
                        $timeLabel = date("d M Y, h:i A", strtotime($msg['created_at']));
                    ?>

                    <div class="chat-message-row <?= $rowClass ?>">
                        <div class="chat-bubble <?= $bubbleClass ?>" id="message-<?= $msg['id'] ?>">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            <span class="chat-meta">
                                <?= $displayName ?> • <?= $timeLabel ?>
                                <?php if ($isUnread): ?>
                                    <span class="unread-badge">NEW</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <form method="post" class="chat-input-wrapper">
            <textarea 
                name="message"
                placeholder="Type your message..."
                required
            ></textarea>

            <button type="submit">Send</button>
        </form>
    </div>
</div>

<script>
// Simple auto-scroll to the bottom of the chat
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById("chatMessages");
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});
</script>

<?php include "footer.php"; ?>