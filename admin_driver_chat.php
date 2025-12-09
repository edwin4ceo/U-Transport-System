<?php
session_start();

include "db_connect.php";
include "function.php";

// Optional: protect with admin login
// if (!isset($_SESSION['admin_id'])) {
//     redirect("admin_login.php");
//     exit;
// }

$selected_driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

/**
 * Admin view of driver–admin chat.
 * Uses table: driver_support_messages
 */

// Handle new admin message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_driver_id = (int)($_POST['driver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($selected_driver_id > 0 && $message !== '') {
        $stmt = $conn->prepare("
            INSERT INTO driver_support_messages (driver_id, sender_type, message)
            VALUES (?, 'admin', ?)
        ");
        if ($stmt) {
            $stmt->bind_param("is", $selected_driver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: admin_driver_chat.php?driver_id=" . $selected_driver_id);
    exit;
}

// Fetch list of drivers who have messages (or you can fetch all drivers)
$drivers = [];
$result = $conn->query("
    SELECT DISTINCT d.driver_id, d.full_name, d.email
    FROM driver_support_messages m
    JOIN drivers d ON m.driver_id = d.driver_id
    ORDER BY d.full_name ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

// Fetch chat history for selected driver
$messages = [];
if ($selected_driver_id > 0) {
    $stmt = $conn->prepare("
        SELECT id, driver_id, sender_type, message, created_at
        FROM driver_support_messages
        WHERE driver_id = ?
        ORDER BY created_at ASC, id ASC
    ");
    if ($stmt) {
        $stmt->bind_param("i", $selected_driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        $stmt->close();
    }
}

// Use your own admin header if you have one
include "header.php";
?>

<style>
.chat-wrapper {
    min-height: calc(100vh - 160px);
    padding: 30px 10px 40px;
    max-width: 1000px;
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

/* driver selector */
.chat-controls {
    margin: 14px 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 13px;
}

.chat-controls select {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #d0d4dd;
    font-size: 13px;
}

/* card layout – same style as driver contact_us */
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

.chat-message-row.driver { justify-content: flex-start; }
.chat-message-row.admin  { justify-content: flex-end; }

.chat-bubble {
    max-width: 80%;
    padding: 8px 10px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

/* On admin page:
   - driver messages = left, light bubble
   - admin (you) messages = right, dark bubble */
.chat-bubble.driver {
    background: #eef4ff;
    color: #2c3e50;
    border-bottom-left-radius: 3px;
}

.chat-bubble.admin {
    background: #004b82;
    color: #ffffff;
    border-bottom-right-radius: 3px;
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

/* input bar – identical pattern to driver contact_us */
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

.chat-input-wrapper textarea:focus {
    border-color: #004b82;
    box-shadow: 0 0 0 2px rgba(0,75,130,0.12);
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
        <h1>Driver Support Chat</h1>
        <p>View and reply to messages from drivers.</p>
    </div>

    <form method="get" class="chat-controls">
        <span>Select driver:</span>
        <select name="driver_id" onchange="this.form.submit()">
            <option value="0">-- Choose a driver --</option>
            <?php foreach ($drivers as $d): ?>
                <option value="<?php echo (int)$d['driver_id']; ?>"
                    <?php echo $selected_driver_id == $d['driver_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['full_name']) . " (ID: " . (int)$d['driver_id'] . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Load</button></noscript>
    </form>

    <div class="chat-card">
        <div class="chat-messages" id="chatMessages">

            <?php if ($selected_driver_id === 0): ?>
                <div class="chat-empty">
                    <i class="fa-regular fa-user"></i><br>
                    Please select a driver to view the conversation.
                </div>

            <?php elseif (count($messages) === 0): ?>
                <div class="chat-empty">
                    <i class="fa-regular fa-comments"></i><br>
                    No messages yet from this driver.
                </div>

            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        // On admin page: sender_type 'driver' => left, 'admin' => right
                        $isAdmin = ($msg['sender_type'] === 'admin');
                        $rowClass   = $isAdmin ? 'admin' : 'driver';
                        $bubbleClass= $isAdmin ? 'admin' : 'driver';
                        $timeLabel  = date("d M Y, h:i A", strtotime($msg['created_at']));
                    ?>
                    <div class="chat-message-row <?php echo $rowClass; ?>">
                        <div class="chat-bubble <?php echo $bubbleClass; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="chat-meta">
                                <?php echo $isAdmin ? 'You • ' : 'Driver • '; ?>
                                <?php echo $timeLabel; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <?php if ($selected_driver_id > 0): ?>
            <form method="post" class="chat-input-wrapper" onsubmit="return handleAdminSubmit(event);">
                <input type="hidden" name="driver_id" value="<?php echo (int)$selected_driver_id; ?>">

                <textarea 
                    name="message"
                    id="adminMessage"
                    placeholder="Type your reply to the driver..."
                ></textarea>

                <button type="submit" id="adminSendBtn">Send</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll to bottom automatically
var container = document.getElementById("chatMessages");
if (container) container.scrollTop = container.scrollHeight;

function handleAdminSubmit(e) {
    var t = document.getElementById("adminMessage");
    var b = document.getElementById("adminSendBtn");
    if (!t) return true;
    if (t.value.trim() === "") {
        e.preventDefault();
        return false;
    }
    b.disabled = true;
    return true;
}
</script>

<?php
include "footer.php";
?>
