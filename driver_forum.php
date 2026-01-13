<?php
session_start();

include "db_connect.php";
include "function.php";

/* 1. Auth check */
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];

$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.status,
        b.student_id, 
        COALESCE(s.name, 'Student') AS student_name,
        (SELECT COUNT(*) FROM ride_chat_messages m 
         WHERE m.booking_ref = b.id 
         AND m.sender_type != 'driver' 
         AND m.is_read = 0) AS unread_count
    FROM bookings b
    LEFT JOIN students s ON s.student_id = b.student_id 
    WHERE b.driver_id = ?
    ORDER BY b.id DESC
");

if (!$stmt) {
    die("Query error: " . $conn->error);
}

$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>

<style>
    .forum-container { max-width: 1000px; margin: 30px auto; padding: 0 16px; font-family: sans-serif; }
    .forum-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .table-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 16px; background: #f8f9fa; color: #555; font-size: 14px; }
    td { padding: 16px; border-top: 1px solid #eee; vertical-align: middle; }
    
    .btn-chat {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 16px; border-radius: 6px; 
        background: #005a9c; color: #fff; text-decoration: none; font-size: 14px;
        border: none; position: relative;
    }
    .btn-chat:hover { background: #004b82; }

    .msg-badge {
        background-color: #e74c3c;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 700;
        min-width: 18px;
        text-align: center;
        margin-left: 5px;
    }
</style>

<div class="forum-container">
  <div class="forum-header">
    <div>
      <h2 style="margin:0; color:#004b82;">Chat List</h2>
      <p style="margin:5px 0 0; color:#666;">Select a booking to chat.</p>
    </div>
    <a href="driver_dashboard.php" style="text-decoration:none; padding:8px 16px; border:1px solid #004b82; border-radius:6px; color:#004b82;">Back to Dashboard</a>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Student</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><span style="color:#777; font-weight:bold;">#<?php echo (int)$row['booking_id']; ?></span></td>
              <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                  <div style="font-size:12px; color:#999;"><?php echo htmlspecialchars($row['student_id']); ?></div>
              </td>
              <td>
                  <span style="font-size:12px; font-weight:600; color:#555;">
                    <?php echo htmlspecialchars(strtoupper($row['status'])); ?>
                  </span>
              </td>
              <td>
                <a href="ride_chat.php?room=<?php echo (int)$row['booking_id']; ?>" class="btn-chat">
                  Open Chat
                  <?php if ($row['unread_count'] > 0): ?>
                    <span class="msg-badge"><?php echo $row['unread_count']; ?></span>
                  <?php endif; ?>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">No chats found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$stmt->close();
include "footer.php";
?>