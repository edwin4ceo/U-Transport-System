<?php
session_start();
include "db_connect.php";

// Set header to JSON explicitly
header('Content-Type: application/json');

if (!isset($_SESSION['driver_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$driver_id = $_SESSION['driver_id'];
$response = [
    'pending_bookings' => 0,
    'chat_unread' => 0,
    'admin_unread' => 0
];

// 1. Check Pending Bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE driver_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['pending_bookings'] = (int)$row['total'];
}

// 2. Check Student Chat Messages (Unread)
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM ride_chat_messages r
    JOIN bookings b ON r.booking_ref = b.id
    WHERE b.driver_id = ? AND r.sender_type = 'student' AND r.is_read = 0
");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['chat_unread'] = (int)$row['total'];
}

// 3. Check Admin Support Messages (Unread)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM driver_support_messages WHERE driver_id = ? AND sender_type = 'admin' AND is_read = 0");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['admin_unread'] = (int)$row['total'];
}

echo json_encode($response);
?>