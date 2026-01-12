<?php
session_start();

include "db_connect.php";
include "function.php";

/* Auth guard */
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];

/* List driver's bookings for chat */
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.status,
        COALESCE(s.name, 'Student') AS student_name
    FROM bookings b
    LEFT JOIN students s ON s.id = b.student_id
    WHERE b.driver_id = ?
    ORDER BY b.id DESC
");

if (!$stmt) {
    die("Query preparation failed");
}

$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>

<div style="max-width: 1000px; margin: 30px auto; padding: 0 16px;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0 0 6px;">Chat</h2>
      <p style="margin:0; color:#666;">Select a booking to chat with the student.</p>
    </div>
    <a href="driver_dashboard.php"
       style="text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid #005a9c; color:#005a9c;">
      Back to Dashboard
    </a>
  </div>

  <div style="margin-top:16px; background:#fff; border:1px solid #e5e5e5; border-radius:12px; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:#f7f7f7;">
          <th style="text-align:left; padding:12px;">Booking</th>
          <th style="text-align:left; padding:12px;">Student</th>
          <th style="text-align:left; padding:12px;">Status</th>
          <th style="text-align:left; padding:12px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr style="border-top:1px solid #eee;">
              <td style="padding:12px;">#<?php echo (int)$row['booking_id']; ?></td>
              <td style="padding:12px;"><?php echo htmlspecialchars($row['student_name'] ?? "Student"); ?></td>
              <td style="padding:12px;"><?php echo htmlspecialchars($row['status'] ?? ""); ?></td>
              <td style="padding:12px;">
                <a href="chat/ride_chat.php?booking_id=<?php echo (int)$row['booking_id']; ?>"
                   style="display:inline-block; padding:8px 12px; border-radius:10px; background:#005a9c; color:#fff; text-decoration:none;">
                  Open Chat
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" style="padding:14px; color:#777;">No bookings found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$stmt->close();
include "footer.php";
?>
