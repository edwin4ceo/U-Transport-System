<?php
// check_notifications.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Count unread messages from DRIVERS only
$sql = "SELECT COUNT(*) as unread_count FROM driver_support_messages 
        WHERE sender_type = 'driver' AND is_read = 0";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

echo json_encode(['unread_count' => (int)$row['unread_count']]);
?>