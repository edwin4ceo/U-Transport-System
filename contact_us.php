<?php
session_start();

include "db_connect.php";
include "function.php";

// --- 1. IDENTIFY USER ROLE (DRIVER OR STUDENT) ---
$current_role = ""; // Will be 'driver' or 'student'
$current_id = "";

if (isset($_SESSION['driver_id'])) {
    // User is a Driver
    $current_role = 'driver';
    $current_id = $_SESSION['driver_id'];
} 
elseif (isset($_SESSION['student_id'])) {
    // User is a Student (Passenger)
    // NOTE: Ensure your login page sets $_SESSION['student_id'] correctly
    $current_role = 'student';
    $current_id = $_SESSION['student_id'];
} 
else {
    // No one is logged in, redirect to login page
    // You can change this to redirect to a landing page if preferred
    redirect("passanger_login.php"); 
    exit;
}

// --- 2. HANDLE MESSAGE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        if ($current_role === 'driver') {
            // Insert into Driver Table
            $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message) VALUES (?, 'driver', ?)");
            $stmt->bind_param("is", $current_id, $message); // Assuming driver_id is INT
        } else {
            // Insert into Student Table
            $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message) VALUES (?, 'student', ?)");
            $stmt->bind_param("ss", $current_id, $message); // Assuming student_id is STRING (e.g., 121110xxxx)
        }
        
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        }
    }

    // Refresh page to prevent form resubmission
    header("Location: contact_us.php");
    exit;
}

// --- 3. FETCH CHAT HISTORY ---
$messages = [];

if ($current_role === 'driver') {
    // Fetch Driver Messages
    $sql = "SELECT id, driver_id, sender_type, message, created_at 
            FROM driver_support_messages 
            WHERE driver_id = ? 
            ORDER BY created_at ASC, id ASC";
} else {
    // Fetch Student Messages
    $sql = "SELECT id, student_id, sender_type, message, created_at 
            FROM student_support_messages 
            WHERE student_id = ? 
            ORDER BY created_at ASC, id ASC";
}

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
}

/* Alignment Classes */
.chat-message-row.me { justify-content: flex-end; }
.chat-message-row.admin { justify-content: flex-start; }

.chat-bubble {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    position: relative;
}

/* User Bubble Style */
.chat-bubble.me {
    background: #004b82;
    color: #ffffff;
    border-bottom-right-radius: 2px;
}

/* Admin Bubble Style */
.chat-bubble.admin {
    background: #eef4ff;
    color: #2c3e50;
    border-bottom-left-radius: 2px;
}

.chat-meta {
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.8;
    text-align: right;
}
.chat-bubble.admin .chat-meta { text-align: left; }

.chat-empty {
    text-align: center;
    padding: 40px 10px;
    color: #777;
    font-size: 14px;
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
    padding: 12px 15px;
    font-size: 14px;
    resize: none;
    min-height: 45px;
    max-height: 150px;
    font-family: inherit;
}

.chat-input-wrapper textarea:focus {
    outline: none;
    border-color: #004b82;
}

.chat-input-wrapper button {
    flex: 0 0 auto;
    width: 100px;
    border: none;
    border-radius: 20px;
    padding: 12px 16px;
    font-size: 14px;
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
</style>

<div class="chat-wrapper">
    <div class="chat-header-title">
        <h1>Contact Support (<?php echo ucfirst($current_role); ?>)</h1>
        <p>Chat with the admin if you have any issues or questions.</p>
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
                        // Check who sent the message to decide alignment
                        // 'driver' or 'student' = Me (The User)
                        // 'admin' = The Admin
                        $sender = $msg['sender_type'];
                        $isMe = ($sender === 'driver' || $sender === 'student');
                        
                        $rowClass = $isMe ? 'me' : 'admin';
                        $bubbleClass = $isMe ? 'me' : 'admin';
                        $displayName = $isMe ? "You" : "Admin";
                        $timeLabel = date("d M Y, h:i A", strtotime($msg['created_at']));
                    ?>

                    <div class="chat-message-row <?= $rowClass ?>">
                        <div class="chat-bubble <?= $bubbleClass ?>">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            <div class="chat-meta">
                                <?= $displayName ?> • <?= $timeLabel ?>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <form method="post" class="chat-input-wrapper">
            <textarea 
                name="message"
                placeholder="Type your message to the admin..."
                required
            ></textarea>

            <button type="submit">Send</button>
        </form>
    </div>
</div>

<script>
// Scroll to the bottom of the chat container automatically
var container = document.getElementById("chatMessages");
if (container) {
    container.scrollTop = container.scrollHeight;
}
</script>

<?php include "footer.php"; ?>