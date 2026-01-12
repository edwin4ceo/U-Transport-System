<?php
session_start();

include "db_connect.php";
include "function.php";

/* Auth guard: Ensure driver is logged in */
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];

/* List driver's bookings for chat.
   [UPDATED] Added a subquery to count unread messages for each specific booking.
*/
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.status,
        b.student_id, 
        COALESCE(s.name, 'Student') AS student_name,
        (SELECT COUNT(*) FROM ride_chat_messages m 
         WHERE m.booking_ref = b.id 
         AND m.sender_type = 'student' 
         AND m.is_read = 0) AS unread_count
    FROM bookings b
    LEFT JOIN students s ON s.student_id = b.student_id 
    WHERE b.driver_id = ?
    ORDER BY b.id DESC
");

// NOTE: I used 'ON s.student_id = b.student_id' above. 
// If your 'bookings' table stores the student's numerical ID (id) instead of the string ID (student_id), 
// please change it to: ON s.id = b.student_id

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>

<style>
    /* Simple Badge Style for Unread Count */
    .msg-badge {
        display: inline-block;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: bold;
        margin-left: 5px;
    }
</style>

<div style="max-width: 1000px; margin: 30px auto; padding: 0 16px;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0 0 6px;">Chat List</h2>
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
          <th style="text-align:left; padding:12px;">Booking ID</th>
          <th style="text-align:left; padding:12px;">Student</th>
          <th style="text-align:left; padding:12px;">Status</th>
          <th style="text-align:left; padding:12px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr style="border-top:1px solid #eee;">
              <td style="padding:12px;">
                  #<?php echo (int)$row['booking_id']; ?>
              </td>
              <td style="padding:12px;">
                  <?php echo htmlspecialchars($row['student_name']); ?>
                  <div style="font-size:11px; color:#999;"><?php echo htmlspecialchars($row['student_id']); ?></div>
              </td>
              <td style="padding:12px;">
                  <?php 
                    $st = strtolower($row['status']);
                    $color = '#666';
                    if($st=='pending') $color='#f39c12';   // Orange
                    if($st=='approved') $color='#27ae60';  // Green
                    if($st=='rejected' || $st=='cancelled') $color='#c0392b'; // Red
                  ?>
                  <span style="color:<?php echo $color; ?>; font-weight:500;">
                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                  </span>
              </td>
              <td style="padding:12px;">
               <a href="ride_chat.php?room=<?php echo (int)$row['booking_id']; ?>"
   style="display:inline-block; padding:8px 12px; border-radius:10px; background:#005a9c; color:#fff; text-decoration:none;">
  Open Chat
  
  <?php if ($row['unread_count'] > 0): ?>
    <span class="msg-badge"><?php echo $row['unread_count']; ?></span>
  <?php endif; ?>
</a>
                  
                  <?php if ($row['unread_count'] > 0): ?>
                    <span class="msg-badge"><?php echo $row['unread_count']; ?></span>
                  <?php endif; ?>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" style="padding:20px; text-align:center; color:#777;">
                No bookings found.
            </td>
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