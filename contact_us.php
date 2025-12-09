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

    header("Location: contact_us.php");
    exit;
}

// Fetch chat history
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

.chat-message-row.driver { justify-content: flex-end; }
.chat-message-row.admin { justify-content: flex-start; }

.chat-bubble {
    max-width: 80%;
    padding: 8px 10px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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
}

.chat-empty {
    text-align: center;
    padding: 30px 10px;
    color: #777;
    font-size: 13px;
}

/* INPUT BAR FIXED */
.chat-input-wrapper {
    width: 100%;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    border-top: 1px solid #e3e6ea;
    padding-top: 10px;
}

.chat-input-wrapper textarea {
    flex: 1 1 auto;
    width: 100%;
    box-sizing: border-box;

    border-radius: 10px;
    border: 1px solid #d0d4dd;
    padding: 10px;
    font-size: 13px;
    resize: none;
    min-height: 40px;
    max-height: 150px;
}

.chat-input-wrapper button {
    flex: 0 0 auto;
    width: 120px;

    border: none;
    border-radius: 999px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: #004b82;
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,75,130,0.35);
}
</style>

<div class="chat-wrapper">
    <div class="chat-header-title">
        <h1>Contact Admin</h1>
        <p>Chat with the admin if you have any issues or questions.</p>
    </div>

    <div class="chat-card">
        <div class="chat-messages" id="chatMessages">

            <?php if (count($messages) === 0): ?>
                <div class="chat-empty">
                    <i class="fa-regular fa-comments"></i><br>
                    No messages yet. Start the conversation!
                </div>

            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $isDriver = ($msg['sender_type'] === 'driver');
                        $rowClass = $isDriver ? 'driver' : 'admin';
                        $bubbleClass = $isDriver ? 'driver' : 'admin';
                        $timeLabel = date("d M Y, h:i A", strtotime($msg['created_at']));
                    ?>

                    <div class="chat-message-row <?= $rowClass ?>">
                        <div class="chat-bubble <?= $bubbleClass ?>">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            <div class="chat-meta">
                                <?= $isDriver ? "You • " : "Admin • " ?> <?= $timeLabel ?>
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
            ></textarea>

            <button type="submit">Send</button>
        </form>
    </div>
</div>

<script>
// Scroll to bottom automatically
var container = document.getElementById("chatMessages");
if (container) container.scrollTop = container.scrollHeight;
</script>

<?php include "footer.php"; ?>
