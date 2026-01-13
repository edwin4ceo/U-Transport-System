<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check Login
$is_student = isset($_SESSION['student_id']);
$is_driver  = isset($_SESSION['driver_id']);

if (!$is_student && !$is_driver) {
    redirect("index.php");
}

// 2. Get Room ID (Booking ID)
$room_ref = isset($_GET['room']) ? intval($_GET['room']) : 0;
if ($room_ref == 0) {
    die("Error: Invalid Chat Room ID.");
}

// --- [LOGIC START] Check Ride Status ---
// We need to know if the ride is still active or completed.
$status_stmt = $conn->prepare("SELECT status FROM bookings WHERE id = ?");
$status_stmt->bind_param("i", $room_ref);
$status_stmt->execute();
$status_res = $status_stmt->get_result();

if($status_res->num_rows == 0){
    die("Error: Booking not found.");
}

$booking_data = $status_res->fetch_assoc();
$current_status = strtoupper($booking_data['status']);

// Define which statuses allow chatting.
// Chat is OPEN only if status is ACCEPTED, ONGOING, or ARRIVED.
// If COMPLETED, CANCELLED, or REJECTED, chat becomes Read-Only.
$is_chat_active = in_array($current_status, ['ACCEPTED', 'ONGOING', 'ARRIVED', 'IN PROGRESS']);

// ----------------------------------

// 3. Mark Messages as Read (Driver Side)
// If the user is a driver, entering the room marks ALL messages in this booking as read.
if ($is_driver) {
    $update_stmt = $conn->prepare("UPDATE ride_chat_messages SET is_read = 1 WHERE booking_ref = ?");
    $update_stmt->bind_param("i", $room_ref);
    $update_stmt->execute();
    $update_stmt->close();
}

// 4. Identify Current User
if ($is_student) {
    $sender_id = $_SESSION['student_id'];
    $sender_type = 'student';
    // Get Student Name
    $u = $conn->query("SELECT name FROM students WHERE student_id='$sender_id'")->fetch_assoc();
    $sender_name = $u['name'];
} else {
    $sender_id = $_SESSION['driver_id'];
    $sender_type = 'driver';
    // Get Driver Name
    $u = $conn->query("SELECT full_name FROM drivers WHERE driver_id='$sender_id'")->fetch_assoc();
    $sender_name = $u['full_name'];
}

// 5. Handle Message Submission
// Only allow sending if the chat is currently ACTIVE.
if (isset($_POST['send_msg']) && $is_chat_active) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO ride_chat_messages (booking_ref, sender_type, sender_id, sender_name, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $room_ref, $sender_type, $sender_id, $sender_name, $msg);
        $stmt->execute();
    }
    // Refresh page to show new message
    header("Location: ride_chat.php?room=" . $room_ref);
    exit;
}

// 6. Fetch Chat History
$chat_history = [];
$stmt = $conn->prepare("SELECT * FROM ride_chat_messages WHERE booking_ref = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $room_ref);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $chat_history[] = $r;
}

include "header.php";
?>

<style>
/* Main Container */
.chat-container { 
    max-width: 600px; 
    margin: 20px auto; 
    background: white; 
    border-radius: 12px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    overflow: hidden; 
    display: flex; 
    flex-direction: column; 
    height: 80vh; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Header */
.chat-header { 
    background: #004b82; 
    color: white; 
    padding: 15px; 
    font-weight: 600; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}

/* Chat Area */
.chat-box { 
    flex: 1; 
    padding: 20px; 
    overflow-y: auto; 
    background: #f5f7fb; 
    display: flex; 
    flex-direction: column; 
    gap: 12px; 
}

/* Message Bubbles */
.message { 
    max-width: 75%; 
    padding: 10px 14px; 
    border-radius: 12px; 
    font-size: 14px; 
    position: relative; 
    line-height: 1.4;
    word-wrap: break-word;
}
.msg-mine { 
    align-self: flex-end; 
    background: #d1e8ff; 
    color: #004b82; 
    border-bottom-right-radius: 2px; 
}
.msg-other { 
    align-self: flex-start; 
    background: white; 
    border: 1px solid #e2e8f0; 
    border-bottom-left-radius: 2px; 
    color: #2d3748;
}

/* System Message Style */
.msg-system {
    align-self: center;
    background-color: #edf2f7;
    color: #718096;
    font-size: 11px;
    padding: 6px 16px;
    border-radius: 20px;
    margin: 10px 0;
    text-align: center;
    border: 1px solid #e2e8f0;
    max-width: 90%;
    font-weight: 500;
}

.msg-info { 
    font-size: 10px; 
    color: #a0aec0; 
    margin-bottom: 4px; 
    font-weight: 600;
}

/* Input Area */
.chat-input-area { 
    padding: 15px; 
    background: white; 
    border-top: 1px solid #edf2f7; 
    display: flex; 
    gap: 10px; 
}
.chat-input { 
    flex: 1; 
    padding: 10px 15px; 
    border: 1px solid #cbd5e0; 
    border-radius: 25px; 
    outline: none; 
    transition: border 0.2s;
}
.chat-input:focus { border-color: #004b82; }

.btn-send { 
    background: #004b82; 
    color: white; 
    border: none; 
    width: 40px; 
    height: 40px;
    border-radius: 50%; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    transition: background 0.2s;
}
.btn-send:hover { background: #00365e; }

/* Disabled / Closed State */
.chat-closed-area {
    padding: 20px; 
    background: #f7fafc; 
    border-top: 1px solid #e2e8f0; 
    text-align: center; 
    color: #e53e3e; 
    font-weight: 600; 
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
</style>

<div class="chat-container">
    <div class="chat-header">
        <div style="display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-comments"></i> 
            Ride #<?php echo $room_ref; ?> 
            <span style="font-size:10px; text-transform:uppercase; background:rgba(255,255,255,0.2); padding:3px 8px; border-radius:4px; font-weight:700;">
                <?php echo htmlspecialchars($current_status); ?>
            </span>
        </div>
        
        <?php if($is_driver): ?>
            <a href="driver_forum.php" style="color:white; text-decoration:none; font-size:14px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <?php else: ?>
            <a href="passanger_rides.php" style="color:white; text-decoration:none; font-size:14px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <?php endif; ?>
    </div>

    <div class="chat-box" id="chatBox">
        <?php if (empty($chat_history)): ?>
            <div style="text-align:center; color:#cbd5e0; margin-top:60px;">
                <i class="fa-regular fa-paper-plane" style="font-size:32px; margin-bottom:12px;"></i><br>
                Start messaging here...
            </div>
        <?php else: ?>
            <?php foreach ($chat_history as $c): ?>
                <?php 
                    // System Message
                    if ($c['sender_type'] === 'system') {
                        ?>
                        <div class="msg-system">
                            <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($c['message']); ?>
                        </div>
                        <?php
                    } else {
                        // User Message
                        $is_me = ($c['sender_type'] == $sender_type && $c['sender_id'] == $sender_id); 
                        $cls = $is_me ? "msg-mine" : "msg-other";
                        ?>
                        <div class="message <?php echo $cls; ?>">
                            <?php if (!$is_me): ?>
                                <div class="msg-info">
                                    <?php echo htmlspecialchars($c['sender_name']); ?> 
                                    <span style="font-weight:400; opacity:0.7;">(<?php echo ucfirst($c['sender_type']); ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div><?php echo htmlspecialchars($c['message']); ?></div>
                            
                            <div style="font-size:10px; text-align:right; opacity:0.5; margin-top:5px;">
                                <?php echo date("h:i A", strtotime($c['created_at'])); ?>
                            </div>
                        </div>
                        <?php
                    }
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($is_chat_active): ?>
        <form method="POST" class="chat-input-area">
            <input type="text" name="message" class="chat-input" placeholder="Type a message..." required autocomplete="off">
            <button type="submit" name="send_msg" class="btn-send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    <?php else: ?>
        <div class="chat-closed-area">
            <i class="fa-solid fa-lock"></i> 
            This ride is <?php echo strtolower($current_status); ?>. Chat has been closed.
        </div>
    <?php endif; ?>

</div>

<script>
    // Auto-scroll to the bottom of the chat
    var box = document.getElementById('chatBox');
    if(box) {
        box.scrollTop = box.scrollHeight;
    }
</script>

<?php 
// Optional: Include footer if your layout needs it, otherwise remove.
// include "footer.php"; 
?>