<?php
session_start();
include "db_connect.php";

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

// 1. 检查待处理订单 (注意这里的 'Pending' 必须与你数据库一致)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE driver_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$response['pending_bookings'] = (int)$stmt->get_result()->fetch_assoc()['total'];

// 2. 检查学生聊天消息
$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM ride_chat_messages r
    JOIN bookings b ON r.booking_ref = b.id
    WHERE b.driver_id = ? AND r.sender_type = 'student' AND r.is_read = 0
");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$response['chat_unread'] = (int)$stmt->get_result()->fetch_assoc()['total'];

// 3. 检查管理员支持消息
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM driver_support_messages WHERE driver_id = ? AND sender_type = 'admin' AND is_read = 0");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$response['admin_unread'] = (int)$stmt->get_result()->fetch_assoc()['total'];

header('Content-Type: application/json');
echo json_encode($response);