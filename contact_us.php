<?php
session_start();

include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

/**
 * Contact Admin chat page for drivers.
 *
 * Table used:
 *   driver_support_messages (
 *      id INT,
 *      driver_id INT,
 *      sender_type ENUM('driver','admin'),
 *      message TEXT,
 *      created_at DATETIME
 *   )
 */

// Handle new driver message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        $stmt = $conn->prepare("
            INSERT INTO driver_support_messages (driver_id, sender_type, message)
            VALUES (?, 'driver', ?)
        ");
        if ($stmt) {
            $stmt->bind_param("is", $driver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Prevent form resubmission
    header("Location: contact_us.php");
    exit;
}

// Fetch chat history for this driver
$messages = [];
$stmt = $conn->prepare("
    SELECT id, driver_id, sender_type, message, created_at
    FROM driver_support_messages
    WHERE driver_id = ?
    ORDER BY created_at ASC, id ASC
");
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
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
.chat-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 900px;
    margin: 0 auto;
    background: #f5f7fb;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    gap: 10px;
}

.chat-header-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
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
    padding: 16px 16px 12px;
    display: flex;
    flex-direction: column;
    height: 70vh;
    max-height: 600px;
}

/* Messages area */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding-right: 4px;
    margin-bottom: 10px;
}

/* Message row */
.chat-message-row {
    display: flex;
    margin-bottom: 8px;
}

.chat-message-row.driver {
    justify-content: flex-end;
}

.chat-message-row.admin {
    justify-content: flex-start;
}

/* Bubble */
.chat-bubble {
    max-width: 80%;
    padding: 8px 10px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    position: relative;
}

.chat-bubble.driver {
    background: #004b82;
    color: #ffffff;
    border-bottom-right-radius: 3px;
}

.chat-bubble.admin {
    background: #eef4ff;
    color: #2c3e50;
    border-bottom-left-radius: 3px;
}

.chat-meta {
    font-size: 10px;
    color: #999;
    margin-top: 2px;
    text-align: right;
}

.chat-meta.admin {
    text-align: left;
}

/* Empty state */
.chat-empty {
    text-align: center;
    padding: 30px 10px;
    font-size: 13px;
    color: #777;
}

.chat-empty i {
    font-size: 26px;
    color: #cccccc;
    margin-bottom: 6px;
}

/* Input area */
.chat-input-wrapper {
    border-top: 1px solid #e3e6ea;
    padding-top: 8px;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

.chat-input-wrapper textarea {
    flex: 1;
    border-radius: 10px;
    border: 1px solid #d0d4dd;
    padding: 8px 10px;
    font-size: 13px;
    resize: none;
    min-height: 40px;
    max-height: 120px;
    outline: none;
}

.chat-input-wrapper textarea:focus {
    border-color: #004b82;
    box-shadow: 0 0 0 2px rgba(0,75,130,0.12);
}

.chat-input-wrapper button {
    border: none;
    border-radius: 999px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: #004b82;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(0,75,130,0.35);
    white-space: nowrap;
}

.chat-input-wrapper button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}
</style>

<div class="chat-wrapper">
    <div class="chat-header">
        <div class="chat-header-title">
            <h1>Contact Admin</h1>
            <p>Chat with the admin if you have any issues or questions.</p>
        </div>
    </div>

    <div class="chat-card">
        <div class="chat-messages" id="chatMessages">
            <?php if (count($messages) === 0): ?>
                <div class="chat-empty">
                    <i class="fa-regular fa-comments"></i>
                    <div>No messages yet. Send your first message to contact the admin.</div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $isDriver = ($msg['sender_type'] === 'driver');
                        $rowClass = $isDriver ? 'driver' : 'admin';
                        $bubbleClass = $isDriver ? 'driver' : 'admin';
                        $metaClass = $isDriver ? '' : 'admin';

                        $timeLabel = '';
                        if (!empty($msg['created_at'])) {
                            $timeLabel = date("d M Y, h:i A", strtotime($msg['created_at']));
                        }
                    ?>
                    <div class="chat-message-row <?php echo htmlspecialchars($rowClass); ?>">
                        <div class="chat-bubble <?php echo htmlspecialchars($bubbleClass); ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="chat-meta <?php echo htmlspecialchars($metaClass); ?>">
                                <?php echo $isDriver ? 'You • ' : 'Admin • '; ?>
                                <?php echo htmlspecialchars($timeLabel); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" class="chat-input-wrapper" onsubmit="return handleSubmit(event);">
            <textarea 
                name="message" 
                id="chatInput" 
                placeholder="Type your message to the admin..."
            ></textarea>
            <button type="submit" id="chatSendBtn">Send</button>
        </form>
    </div>
</div>

<script>
// Auto-scroll to bottom on page load
(function() {
    var container = document.getElementById('chatMessages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
})();

// Simple front-end validation
function handleSubmit(e) {
    var textarea = document.getElementById('chatInput');
    var btn = document.getElementById('chatSendBtn');
    if (!textarea) return true;

    var value = textarea.value.trim();
    if (value === '') {
        return false;
    }

    btn.disabled = true;
    return true;
}
</script>

<?php
include "footer.php";
?>
