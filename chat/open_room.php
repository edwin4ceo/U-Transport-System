<?php
session_start();
include "../db_connect.php";
include "../function.php";

if (!isset($_SESSION['driver_id'])) {
    redirect("../driver_login.php");
    exit;
}

$driver_id  = (int)$_SESSION['driver_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) die("Invalid booking");

/* Authorization: booking belongs to this driver */
$stmt = $conn->prepare("SELECT id, driver_id FROM bookings WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die("Booking not found");
if ((int)$booking['driver_id'] !== $driver_id) die("Unauthorized");

/* One room per booking */
$stmt = $conn->prepare("SELECT room_id FROM chat_rooms WHERE booking_id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    $stmt = $conn->prepare("INSERT INTO chat_rooms (booking_id) VALUES (?)");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $room_id = (int)$conn->insert_id;
} else {
    $room_id = (int)$room['room_id'];
}

header("Location: room.php?room_id=" . $room_id);
exit;
