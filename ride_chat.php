<?php
session_start();
include "db_connect.php";
include "function.php";

// Check Login
$is_student = isset($_SESSION['student_id']);
$is_driver  = isset($_SESSION['driver_id']);

if (!$is_student && !$is_driver) {
    redirect("index.php");
}

// Get Room ID (DriverID_DateTime)
$room_ref = isset($_GET['room']) ? $_GET['room'] : '';
if (empty($room_ref)) {
    die("Invalid Chat Room");
}

// Identify Sender
if ($is_student) {
    $sender_id = $_SESSION['student_id'];
    $sender_type = 'student';
    // Get Name
    $u = $conn->query("SELECT name FROM students WHERE student_id='$sender_id'")->fetch_assoc();
    $sender_name = $u['name'];
} else {
    $sender_id = $_SESSION['driver_id'];
    $sender_type = 'driver';
    // Get Name
    $u = $conn->query("SELECT full_name FROM drivers WHERE driver_id='$sender_id'")->fetch_assoc();
    $sender_name = $u['full_name'];
}

// Handle Message Send
if (isset($_POST['send_msg'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO ride_chat_messages (booking_ref, sender_type, sender_id, sender_name, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $room_ref, $sender_type, $sender_id, $sender_name, $msg);
        $stmt->execute();
    }
    // Refresh to show new message
    header("Location: ride_chat.php?room=" . urlencode($room_ref));
    exit;
}

// Fetch Messages
$chat_history = [];
$stmt = $conn->prepare("SELECT * FROM ride_chat_messages WHERE booking_ref = ? ORDER BY created_at ASC");
$stmt->bind_param("s", $room_ref);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $chat_history[] = $r;
}

include "header.php";
?>

<style>
.chat-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; height: 80vh; }
.chat-header { background: #004b82; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
.chat-box { flex: 1; padding: 20px; overflow-y: auto; background: #f5f7fb; display: flex; flex-direction: column; gap: 10px; }
.message { max-width: 75%; padding: 10px 14px; border-radius: 12px; font-size: 14px; position: relative; }
.msg-mine { align-self: flex-end; background: #d1e8ff; color: #004b82; border-bottom-right-radius: 2px; }
.msg-other { align-self: flex-start; background: white; border: 1px solid #e0e0e0; border-bottom-left-radius: 2px; }
.msg-info { font-size: 11px; color: #888; margin-bottom: 2px; }
.chat-input-area { padding: 15px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; }
.chat-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
.btn-send { background: #004b82; color: white; border: none; padding: 0 20px; border-radius: 20px; cursor: pointer; }
</style>

<div class="chat-container">
    <div class="chat-header">
        <span><i class="fa-solid fa-users"></i> Ride Group Chat</span>
        <a href="javascript:history.back()" style="color:white; text-decoration:none;"><i class="fa-solid fa-times"></i></a>
    </div>

    <div class="chat-box" id="chatBox">
        <?php if (empty($chat_history)): ?>
            <div style="text-align:center; color:#ccc; margin-top:50px;">No messages yet. Say Hi!</div>
        <?php else: ?>
            <?php foreach ($chat_history as $c): ?>
                <?php 
                    $is_me = ($c['sender_type'] == $sender_type && $c['sender_id'] == $sender_id); 
                    $cls = $is_me ? "msg-mine" : "msg-other";
                ?>
                <div class="message <?php echo $cls; ?>">
                    <?php if (!$is_me): ?>
                        <div class="msg-info"><?php echo htmlspecialchars($c['sender_name']); ?> (<?php echo ucfirst($c['sender_type']); ?>)</div>
                    <?php endif; ?>
                    <div><?php echo htmlspecialchars($c['message']); ?></div>
                    <div style="font-size:10px; text-align:right; opacity:0.6; margin-top:4px;">
                        <?php echo date("h:i A", strtotime($c['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" class="chat-input-area">
        <input type="text" name="message" class="chat-input" placeholder="Type a message..." required autocomplete="off">
        <button type="submit" name="send_msg" class="btn-send"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>

<script>
    // Auto scroll to bottom
    var box = document.getElementById('chatBox');
    box.scrollTop = box.scrollHeight;
</script>