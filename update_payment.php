<?php
session_start();
include "db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['driver_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

$booking_id = $_GET['id'];
$method = $_GET['method']; // Expecting 'Cash' or 'DuitNow'

// Update the booking: Set status to Completed, record payment method, and mark as Paid
$stmt = $conn->prepare("UPDATE bookings SET status = 'Completed', payment_method = ?, payment_status = 'Paid' WHERE id = ?");
$stmt->bind_param("si", $method, $booking_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>