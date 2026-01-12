<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

// 1. Count Driver Unread
$driver_sql = "SELECT COUNT(*) as count FROM driver_support_messages WHERE sender_type = 'driver' AND is_read = 0";
$driver_res = mysqli_query($conn, $driver_sql);
$driver_count = mysqli_fetch_assoc($driver_res)['count'];

// 2. Count Student Unread
$student_sql = "SELECT COUNT(*) as count FROM student_support_messages WHERE sender_type = 'student' AND is_read = 0";
$student_res = mysqli_query($conn, $student_sql);
$student_count = mysqli_fetch_assoc($student_res)['count'];

// 3. Return Total
echo json_encode([
    'unread_count' => (int)$driver_count + (int)$student_count,
    'driver_unread' => (int)$driver_count,
    'student_unread' => (int)$student_count
]);
?>