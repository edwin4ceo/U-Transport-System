<?php
session_start();

include "../db_connect.php";
include "../function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("../driver_login.php");
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];

$room_id = 0;
if (isset($_GET['room_id'])) $room_id = (int)$_GET['room_id'];
if ($room_id <= 0 && isset($_POST['room_id'])) $room_id = (int)$_POST['room_id'];
if ($room_id <= 0) die("Invalid room");

/* Room context + authorization */
$stmt = $conn->prepare("
    SELECT cr.booking_id, b.driver_id, b.student_id, b.status
    FROM chat_rooms cr
    JOIN bookings b ON b.id = cr.booking_id
    WHERE cr.room_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$ctx = $stmt->get_result()->fetch_assoc();

if (!$ctx) die("Room not found");
if ((int)$ctx['driver_id'] !== $driver_id) die("Unauthorized");

/* Optional read-only status */
$status = (string)($ctx['status'] ?? '');
$read_only = in_array(strtolower($status), ['completed','cancelled'], true);

/* Resolve driver -> users.user_id via email (auto-create if missing) */
$stmt = $conn->prepare("SELECT email, full_name FROM drivers WHERE driver_id = ? LIMIT 1");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$driverRow = $stmt->get_result()->fetch_assoc();
if (!$driverRow) die("Driver not found");

$driver_email = (string)$driverRow['email'];
$driver_name  = (string)$driverRow['full_name'];

$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $driver_email);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();

if (!$userRow) {
    $role = "driver";
    $password_hash = password_hash("temp1234", PASSWORD_DEFAULT);
    $phone = "";

    $stmt = $conn->prepare("
        INSERT INTO users (full_name, email, password_hash, phone_number, role)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $driver_name, $driver_email, $password_hash, $phone, $role);
    $stmt->execute();

    $my_user_id = (int)$conn->insert_id;
} else {
    $my_user_id = (int)$userRow['user_id'];
}

/* Student name for header: bookings.student_id (varchar) -> students.student_id (varchar) */
$student_code = (string)($ctx['student_id'] ?? '');
$stmt = $conn->prepare("SELECT name FROM students WHERE student_id = ? LIMIT 1");
$stmt->bind_param("s", $student_code);
$stmt->execute();
$stuRow = $stmt->get_result()->fetch_assoc();
$student_name = $stuRow['name'] ?? 'Student';

/* AJAX fetch messages */
if (isset($_GET['ajax']) && (int)$_GET['ajax'] === 1) {
    header("Content-Type: application/json");
    $after_id = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;

    $stmt = $conn->prepare("
        SELECT message_id, sender_id, message_text, sent_at
        FROM chat_messages
        WHERE room_id = ? AND message_id > ?
        ORDER BY message_id ASC
        LIMIT 200
    ");
    $stmt->bind_param("ii", $room_id, $after_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["ok" => true, "messages" => $messages, "me" => $my_user_id]);
    exit;
}

/* POST send message */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");

    if ($read_only) {
        echo json_encode(["ok" => false, "error" => "Chat is read-only"]);
        exit;
    }

    $text = trim($_POST['message_text'] ?? '');
    if ($text === '') {
        echo json_encode(["ok" => false, "error" => "Empty message"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, sender_id, message_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $room_id, $my_user_id, $text);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE chat_rooms SET last_message_at = NOW() WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();

    echo json_encode(["ok" => true]);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; }
    .topbar { background:#fff; padding:12px 16px; border-bottom:1px solid #ddd; position:sticky; top:0; }
    .title { font-weight:700; }
    .sub { font-size:12px; color:#666; margin-top:4px; }
    .chat { padding:16px; max-width:900px; margin:0 auto; }
    .bubble { max-width:70%; padding:10px 12px; margin:8px 0; border-radius:14px; line-height:1.35; }
    .me { margin-left:auto; background:#dff7df; }
    .them { margin-right:auto; background:#fff; border:1px solid #e6e6e6; }
    .meta { font-size:11px; color:#777; margin-top:4px; }
    .composer { position:sticky; bottom:0; background:#fff; border-top:1px solid #ddd; padding:10px; }
    .row { display:flex; gap:8px; max-width:900px; margin:0 auto; }
    textarea { flex:1; resize:none; height:44px; padding:10px; border:1px solid #ccc; border-radius:10px; }
    button { padding:10px 14px; border:none; border-radius:10px; cursor:pointer; }
    button:disabled { opacity:.5; cursor:not-allowed; }
    .notice { font-size:12px; color:#b00; padding:10px 16px; max-width:900px; margin:0 auto; }
  </style>
</head>
<body>

<div class="topbar">
  <div class="title"><?php echo htmlspecialchars($student_name); ?></div>
  <div class="sub">Booking #<?php echo (int)$ctx['booking_id']; ?> · Status: <?php echo htmlspecialchars($status); ?></div>
</div>

<?php if ($read_only): ?>
  <div class="notice">This booking is completed/cancelled. Chat is read-only.</div>
<?php endif; ?>

<div class="chat" id="chat"></div>

<div class="composer">
  <div class="row">
    <textarea id="message" placeholder="Type a message..." <?php echo $read_only ? "disabled" : ""; ?>></textarea>
    <button id="sendBtn" <?php echo $read_only ? "disabled" : ""; ?>>Send</button>
  </div>
</div>

<script>
const ROOM_ID = <?php echo (int)$room_id; ?>;
const MY_USER_ID = <?php echo (int)$my_user_id; ?>;
let lastMessageId = 0;

function escapeHtml(str){
  return str.replace(/[&<>"']/g, s => ({
    "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
  }[s]));
}

function appendMessages(messages){
  const chat = document.getElementById("chat");
  const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 140);

  for (const m of messages){
    const bubble = document.createElement("div");
    bubble.className = "bubble " + (parseInt(m.sender_id) === MY_USER_ID ? "me" : "them");
    bubble.innerHTML = `<div>${escapeHtml(m.message_text)}</div><div class="meta">${escapeHtml(m.sent_at)}</div>`;
    chat.appendChild(bubble);
    lastMessageId = Math.max(lastMessageId, parseInt(m.message_id));
  }

  if (nearBottom) window.scrollTo(0, document.body.scrollHeight);
}

async function fetchNew(){
  const res = await fetch(`room.php?ajax=1&room_id=${ROOM_ID}&after_id=${lastMessageId}`);
  const data = await res.json();
  if (data.ok && data.messages.length) appendMessages(data.messages);
}

async function sendMessage(){
  const ta = document.getElementById("message");
  const text = ta.value.trim();
  if (!text) return;

  const res = await fetch("room.php", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: new URLSearchParams({room_id: ROOM_ID, message_text: text})
  });

  const data = await res.json();
  if (!data.ok) return alert(data.error || "Send failed");

  ta.value = "";
  await fetchNew();
}

document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("message").addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey){
    e.preventDefault();
    sendMessage();
  }
});

fetchNew();
setInterval(fetchNew, 2000);
</script>

</body>
</html>
