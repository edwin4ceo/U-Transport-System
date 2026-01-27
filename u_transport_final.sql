-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 10:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u_transport`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `full_name`, `phone_number`, `password`, `role`) VALUES
(1, 'admin', 'admin@mmu.edu.my', 'FMD Administrator', '0123558148', 'admin123', 'admin'),
(3, 'edwin4ceo', 'edwin@mmu.edu.my', 'Edwin Teo Yuan Jing', '01130098978', '123456', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_resets`
--

CREATE TABLE `admin_password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_ref` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `date_time` datetime NOT NULL,
  `passengers` int(11) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `pickup_point` varchar(255) NOT NULL,
  `remark` text DEFAULT NULL,
  `fare` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('Pending','Cash','DuitNow') DEFAULT 'Pending',
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `student_id`, `driver_id`, `destination`, `date_time`, `passengers`, `vehicle_type`, `pickup_point`, `remark`, `fare`, `status`, `created_at`, `payment_method`, `payment_status`) VALUES
(1, NULL, '1231200805', NULL, 'Melaka, Melaka City - No,123 Jalan Melaka ', '2026-01-09 13:46:00', 6, 'MPV', 'MMU Main Gait', '', 0.00, 'Rejected', '2026-01-09 05:44:40', 'Pending', 'Unpaid'),
(2, NULL, '1231201251', 3, 'Melaka, Melaka City - No,123 Jalan Melaka ', '2026-01-10 16:01:00', 6, 'MPV', 'MMU Main Gait', '', 0.00, 'COMPLETED', '2026-01-09 08:01:56', 'Pending', 'Unpaid'),
(3, NULL, '1231200805', 4, 'Melaka, Alor Gajah - No,123 Jalan Melaka ', '2026-01-12 21:26:00', 6, 'MPV', 'MMU Main Gait', '', 0.00, 'Accepted', '2026-01-12 13:01:09', 'Pending', 'Unpaid'),
(4, NULL, '1231201251', 3, 'Melaka, Alor Gajah - No,123 Jalan Melaka ', '2026-01-13 02:30:00', 6, 'MPV', 'MMU Main Gait', '', 0.00, 'COMPLETED', '2026-01-12 18:31:14', 'Pending', 'Unpaid'),
(5, NULL, '1231201251', NULL, 'Kuala Lumpur/Selangor, Kuala Lumpur - 2, Persiaran Jalil 8, Bukit Jalil, 57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-14 12:00:00', 6, 'MPV', 'MMU Library', '', 0.00, 'Cancelled', '2026-01-12 22:41:26', 'Pending', 'Unpaid'),
(6, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 2, Persiaran Jalil 8, Bukit Jalil, 57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-13 12:00:00', 6, 'MPV', 'MMU Main Gate', '', 0.00, 'Cancelled', '2026-01-12 22:49:58', 'Pending', 'Unpaid'),
(7, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 2, Persiaran Jalil 8, Bukit Jalil, 57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-13 12:00:00', 6, 'MPV', 'MMU Library', '', 0.00, 'COMPLETED', '2026-01-12 23:09:36', 'Pending', 'Unpaid'),
(8, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 2, Persiaran Jalil 8, Bukit Jalil, 57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-14 12:00:00', 6, 'MPV', 'MMU Library', '', 450.00, 'COMPLETED', '2026-01-12 23:47:32', 'Pending', 'Unpaid'),
(9, NULL, '1231201251', 3, 'Johor, Johor Bahru - Jalan Premium Outlets, Bandar Indahpura, 81000 Kulai, Johor Darul Ta\'zim', '2026-01-16 16:00:00', 6, 'MPV', 'MMU FOL Building', '', 637.50, 'COMPLETED', '2026-01-13 00:07:50', 'Pending', 'Unpaid'),
(10, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 2, Persiaran Jalil 8, Bukit Jalil, 57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-13 17:00:00', 6, 'MPV', 'MMU Back Gate', '', 450.00, 'COMPLETED', '2026-01-13 00:10:30', 'Pending', 'Unpaid'),
(11, NULL, '1231201251', 3, 'Johor, Muar - 123. jalan ahli', '2026-01-13 15:25:00', 6, 'MPV', 'MMU FOL Building', '', 637.50, 'Completed', '2026-01-13 07:27:03', 'Cash', 'Paid'),
(12, NULL, '1231201251', 3, 'Melaka, Klebang - No,123 Jalan Melaka ', '2026-01-19 12:00:00', 1, 'Sedan', 'MMU Library', '', 12.00, 'COMPLETED', '2026-01-19 08:59:24', 'Pending', 'Unpaid'),
(13, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Putrajaya - IOI Resort City, 62502 Sepang, Wilayah Persekutuan Putrajaya', '2026-01-19 12:00:00', 1, 'Sedan', 'MMU Male Hostel', '2 small luggage', 60.00, 'COMPLETED', '2026-01-19 09:16:10', 'Pending', 'Unpaid'),
(14, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 121, Jln Ampang, Kuala Lumpur, 50450 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-19 12:00:00', 1, 'Sedan', 'MMU Main Gate', '2 small luggages', 60.00, 'COMPLETED', '2026-01-19 09:29:14', 'Pending', 'Unpaid'),
(15, NULL, '1231201251', 3, 'Kuala Lumpur/Selangor, Kuala Lumpur - 121, Jln Ampang, Kuala Lumpur, 50450 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', '2026-01-19 18:50:00', 1, 'Sedan', 'MMU Back Gate', '2 small luggages', 60.00, 'COMPLETED', '2026-01-19 09:38:32', 'Pending', 'Unpaid'),
(16, NULL, '1231201251', 3, 'Johor, Johor Bahru - Jalan Premium Outlets, Bandar Indahpura, 81000 Kulai, Johor Darul Ta\'zim', '2026-01-19 10:00:00', 1, 'Sedan', 'MMU Library', '', 85.00, 'COMPLETED', '2026-01-19 09:46:44', 'Pending', 'Unpaid'),
(17, NULL, '1231201251', 3, 'Melaka, Melaka City - No,123 Jalan Melaka ', '2026-01-19 12:00:00', 1, 'Sedan', 'MMU Library', '', 12.00, 'Completed', '2026-01-19 11:46:33', 'Cash', 'Paid'),
(18, NULL, '1231201251', 3, 'Melaka, Melaka City - 123. jalan ahli', '2026-01-21 00:00:00', 6, 'MPV', 'MMU FOL Building', '', 90.00, 'COMPLETED', '2026-01-21 15:46:19', 'Pending', 'Unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `user_name`, `user_email`, `subject`, `message`, `created_at`) VALUES
(1, 'Ali Passenger', 'ali@student.mmu.edu.my', 'App Issue', 'I cannot find the driver contact number.', '2025-11-25 23:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `identification_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `duitnow_qr` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `full_name`, `identification_id`, `email`, `phone_number`, `gender`, `license_number`, `license_expiry`, `password`, `created_at`, `verification_status`, `profile_image`, `phone`, `bio`, `duitnow_qr`) VALUES
(3, 'NG ZHE JUN', '1231200805', '1231200805@student.mmu.edu.my', '+60169592825', 'Male', 'A 09081122', '2031-12-25', '$2y$10$d8D7RtRkUL1LtZVJcIZw7utb3nFDvtEU96pvx0xmE7K3sf8P5LhGq', '2025-11-27 21:16:52', 'verified', '1768288648_p_WhatsApp Image 2026-01-13 at 2.38.45 PM.jpeg', '+60123654798', 'hi i am zhejun', 'qr_3_1768292919.jpeg'),
(4, 'WONG SOON KIT', '1231201251', '1231201251@student.mmu.edu.my', '012556789', NULL, 'A 00987654', '2033-12-29', '$2y$10$4p9bhXMvsznFMDedI0ki4.n8tN1dRljxNwUKXNry6lVt7Ecmtd2jC', '2026-01-07 09:35:09', 'verified', NULL, NULL, NULL, NULL),
(5, 'EDWIN TEO YUAN JING', '1231203190', '1231203190@student.mmu.edu.my', '0123789456', NULL, 'A 00998877', '2032-10-27', '$2y$10$ZhT1x38vG3DLNXLJp4qIZOv0D9EKAEqMVxbR4.0aRoqz3iz4V6mXy', '2026-01-07 10:56:01', 'verified', NULL, NULL, NULL, NULL),
(8, 'zhejun', '1231200805', 'jacky.chua.zheng@student.mmu.edu.my', '0123456789', NULL, 'A 00987654', '2026-01-13', '$2y$10$.HKztSogW90accZKZHMbxOm7j2CIw7vVB.zISBQY.BHq2CqndmN.q', '2026-01-11 14:55:09', 'verified', NULL, NULL, NULL, NULL),
(9, 'KEE CHENG WEI', '1231200999', 'kee.cheng.wei@student.mmu.edu.my', '012-4567891', 'Male', 'A 00888888', '2031-11-30', '$2y$10$DiBSdHPVtvSBRS94b.R12eAqcdl2kah6E1nZ.a3rmn2DTJRgZJSgq', '2026-01-20 07:02:15', 'verified', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `driver_password_resets`
--

CREATE TABLE `driver_password_resets` (
  `id` int(11) NOT NULL,
  `driver_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_reset_otps`
--

CREATE TABLE `driver_reset_otps` (
  `id` int(11) NOT NULL,
  `driver_id` int(10) UNSIGNED NOT NULL,
  `otp_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_reset_otps`
--

INSERT INTO `driver_reset_otps` (`id`, `driver_id`, `otp_hash`, `expires_at`, `attempts`, `created_at`) VALUES
(1, 3, '9087bff4ee5a4c50553a8a9096963d8d75dea9601fee74de289bbf21bcc25608', '2025-12-30 10:05:32', 0, '2025-12-30 08:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `driver_support_messages`
--

CREATE TABLE `driver_support_messages` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `sender_type` enum('driver','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_support_messages`
--

INSERT INTO `driver_support_messages` (`id`, `driver_id`, `sender_type`, `message`, `created_at`, `is_read`) VALUES
(58, 3, 'driver', 'hi', '2026-01-13 02:26:33', 1),
(59, 3, 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2026-01-13 02:26:33', 1),
(60, 3, 'driver', 'd', '2026-01-13 02:27:11', 1),
(61, 3, 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2026-01-13 02:27:11', 1),
(62, 3, 'driver', 'd', '2026-01-13 02:29:26', 1),
(63, 3, 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2026-01-13 02:29:26', 1),
(64, 3, 'driver', 'wei', '2026-01-13 15:30:31', 1),
(65, 3, 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2026-01-13 15:30:31', 1),
(66, 3, 'admin', 'lai', '2026-01-13 15:30:54', 1),
(67, 3, 'admin', '78', '2026-01-13 15:31:26', 1),
(68, 3, 'admin', '78', '2026-01-13 15:32:20', 1),
(69, 3, 'admin', '78', '2026-01-13 15:32:22', 1),
(70, 3, 'admin', '65', '2026-01-13 15:32:32', 1),
(71, 3, 'admin', '65', '2026-01-13 15:47:46', 1),
(72, 3, 'driver', 'dsdsd', '2026-01-13 15:48:16', 1),
(73, 3, 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2026-01-13 15:48:16', 1),
(74, 3, 'driver', 'hi', '2026-01-21 23:01:50', 1),
(75, 3, 'admin', 'hi', '2026-01-21 23:06:32', 1),
(76, 3, 'admin', 'how can i help you', '2026-01-21 23:43:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `favourite_drivers`
--

CREATE TABLE `favourite_drivers` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `passenger_id` varchar(50) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reply` text DEFAULT NULL,
  `reply_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `passenger_id`, `driver_id`, `rating`, `comment`, `created_at`, `reply`, `reply_at`) VALUES
(1, 2, '1231201251', 3, 5, '', '2026-01-12 18:42:53', 'thanks', '2026-01-13 03:51:45'),
(2, 4, '1231201251', 3, 5, '', '2026-01-12 19:08:54', 'thanks', '2026-01-13 03:51:45'),
(3, 7, '1231201251', 3, 5, '', '2026-01-13 03:50:59', 'thanks', '2026-01-13 03:51:45'),
(4, 9, '1231201251', 3, 5, '', '2026-01-13 03:51:22', 'thanks', '2026-01-13 03:51:45');

-- --------------------------------------------------------

--
-- Table structure for table `ride_chat_messages`
--

CREATE TABLE `ride_chat_messages` (
  `id` int(11) NOT NULL,
  `booking_ref` varchar(100) NOT NULL,
  `sender_type` varchar(20) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ride_chat_messages`
--

INSERT INTO `ride_chat_messages` (`id`, `booking_ref`, `sender_type`, `sender_id`, `sender_name`, `message`, `created_at`, `is_read`) VALUES
(36, '4', 'driver', '3', 'NG ZHE JUN', 'kkk', '2026-01-12 19:01:16', 1),
(37, '3_2026-01-13 12:00:00', 'system', '0', 'System', 'Please be ready 10-15 minutes before the departure time to avoid unnecessary delays.', '2026-01-12 23:03:32', 0);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT 'Male',
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `student_id`, `email`, `password`, `phone`, `gender`, `profile_image`) VALUES
(1, 'Wong', '1231201251', '1231201251@student.mmu.edu.my', '$2y$10$/M4yf6wmTxO1aj3NB21c1.Y43ygUg6HzHw5W6cZNfEEQNO97eERA2', '+601114024118', 'Male', NULL),
(5, 'edwin', '1231203190', '1231203190@student.mmu.edu.my', '$2y$10$rTmeZDN5fByGVHYDoU0ERuyq/D801/FjckxrSaOBr2VL/E7ZViAaG', NULL, 'Male', NULL),
(11, 'NG ZHE JUN', '1231200805', '1231200805@student.mmu.edu.my', '$2y$10$XlgZTXBFsDM9R6P343Ou6uHQPOsIdo5xrp8jtlmdahp83gMr6cUIq', '60123456789', 'Male', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_support_messages`
--

CREATE TABLE `student_support_messages` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `sender_type` enum('student','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_support_messages`
--

INSERT INTO `student_support_messages` (`id`, `student_id`, `sender_type`, `message`, `created_at`, `is_read`) VALUES
(1, '1231201251', 'student', 'hii', '2025-12-21 13:27:01', 1),
(2, '1231201251', 'student', 'hello', '2025-12-21 13:27:09', 1),
(3, '1231201251', 'student', 'hi', '2025-12-21 14:04:41', 1),
(5, '1231201251', 'student', 'hi', '2025-12-21 14:09:03', 1),
(7, '1231201251', 'student', 'hi', '2025-12-21 14:11:43', 1),
(9, '1231201251', 'student', 'hii', '2025-12-21 14:12:18', 1),
(11, '1231201251', 'student', 'hii', '2025-12-21 14:14:45', 1),
(12, '1231201251', 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2025-12-21 14:14:45', 0),
(13, '1231201251', 'student', 'hi', '2025-12-22 16:05:45', 1),
(14, '1231201251', 'admin', 'Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.', '2025-12-22 16:05:45', 0),
(15, '3', '', 'hi', '2026-01-21 14:29:13', 0),
(16, '3', '', 'hi', '2026-01-21 14:56:56', 0),
(17, '3', '', 'hi', '2026-01-21 14:58:56', 0),
(18, '3', '', 'hi', '2026-01-21 14:59:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(10) UNSIGNED NOT NULL,
  `driver_id` int(10) UNSIGNED NOT NULL,
  `vehicle_model` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `seat_count` tinyint(3) UNSIGNED DEFAULT 4,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `road_tax_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `driver_id`, `vehicle_model`, `plate_number`, `vehicle_type`, `vehicle_color`, `seat_count`, `created_at`, `road_tax_expiry`, `insurance_expiry`) VALUES
(2, 3, 'TOYOTA ALPHARD', 'SJ 1 M', 'MPV', 'WHITE', 6, '2025-11-27 21:16:52', '2027-02-28', '2027-02-28'),
(3, 4, 'Mercedes S400h', 'J 1', 'Sedan', 'Black', 3, '2026-01-07 09:35:09', NULL, NULL),
(4, 5, 'HONDA CIVIC TYPE R', 'QER 1', 'SEDAN', 'GREY', 4, '2026-01-07 10:56:01', NULL, NULL),
(10, 9, 'BMW 520i', 'JPK 8', 'Sedan', 'Black', 5, '2026-01-20 07:02:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_change_requests`
--

CREATE TABLE `vehicle_change_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `driver_id` int(10) UNSIGNED NOT NULL,
  `vehicle_model` varchar(100) NOT NULL,
  `old_vehicle_id` int(10) UNSIGNED DEFAULT NULL,
  `new_vehicle_model` varchar(100) NOT NULL,
  `new_plate_number` varchar(20) NOT NULL,
  `new_vehicle_type` varchar(50) DEFAULT NULL,
  `new_vehicle_color` varchar(30) DEFAULT NULL,
  `new_seat_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `road_tax_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `vehicle_color` varchar(50) NOT NULL,
  `seat_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_change_requests`
--

INSERT INTO `vehicle_change_requests` (`request_id`, `driver_id`, `vehicle_model`, `old_vehicle_id`, `new_vehicle_model`, `new_plate_number`, `new_vehicle_type`, `new_vehicle_color`, `new_seat_count`, `road_tax_expiry`, `insurance_expiry`, `status`, `admin_comment`, `created_at`, `processed_at`, `plate_number`, `vehicle_type`, `vehicle_color`, `seat_count`) VALUES
(1, 3, '', 2, 'Myvi', 'WWS 1111', 'Hatchback', 'White', 4, NULL, NULL, 'approved', NULL, '2025-12-11 04:05:28', '2026-01-07 00:50:41', '', '', '', 0),
(2, 3, '', 2, 'HONDA CITY', 'JP 1', 'Sedan', 'Black', 4, NULL, NULL, 'approved', NULL, '2026-01-07 07:51:03', '2026-01-07 00:59:37', '', '', '', 0),
(3, 3, '', 2, 'Myvi', 'www 333', 'Hatchback', 'White', 4, NULL, NULL, 'approved', NULL, '2026-01-08 04:14:42', '2026-01-07 21:19:23', '', '', '', 0),
(4, 3, '', 2, 'TOYOTA ALPHARD', 'N 1', 'MPV', 'White', 1, NULL, NULL, 'approved', NULL, '2026-01-09 05:36:18', '2026-01-08 22:36:31', '', '', '', 0),
(5, 3, '', 2, 'TOYOTA ALPHARD', 'N 1', 'MPV', 'White', 6, '2027-01-09', '2026-01-10', 'approved', NULL, '2026-01-09 07:54:29', '2026-01-09 00:54:53', '', '', '', 0),
(6, 3, 'TOYOTA ALPHARD', NULL, '', '', NULL, NULL, NULL, '2027-01-07', '2027-01-07', 'approved', NULL, '2026-01-12 21:23:05', '2026-01-12 21:32:03', 'N 1', 'MPV', 'White', 6),
(7, 3, 'NISSAN GTR', NULL, '', '', NULL, NULL, NULL, '2027-06-30', '2027-06-30', 'approved', NULL, '2026-01-17 17:50:09', '2026-01-17 17:50:29', 'SK 1 H', 'Sedan', 'WHITE', 2),
(8, 3, 'NISSAN GTR', NULL, '', '', NULL, NULL, NULL, '2027-06-30', '2027-06-30', 'approved', NULL, '2026-01-17 17:50:35', '2026-01-17 17:51:11', 'SK 1 H', 'Sedan', 'WHITE', 2),
(9, 3, 'NISSAN GTR', NULL, '', '', NULL, NULL, NULL, '2027-06-30', '2027-06-30', 'approved', NULL, '2026-01-17 17:51:15', '2026-01-17 17:52:08', 'SK 1 H', 'Sedan', 'WHITE', 2),
(10, 3, 'TOYOTA ALPHARD', NULL, '', '', NULL, NULL, NULL, '2027-02-28', '2027-02-28', 'approved', NULL, '2026-01-21 14:19:35', '2026-01-21 14:30:23', 'SJ 1 M', 'MPV', 'WHITE', 6);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `driver_password_resets`
--
ALTER TABLE `driver_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indexes for table `driver_reset_otps`
--
ALTER TABLE `driver_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `driver_support_messages`
--
ALTER TABLE `driver_support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_driver_id_created_at` (`driver_id`,`created_at`);

--
-- Indexes for table `favourite_drivers`
--
ALTER TABLE `favourite_drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `ride_chat_messages`
--
ALTER TABLE `ride_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_ref` (`booking_ref`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_support_messages`
--
ALTER TABLE `student_support_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `uq_vehicle_driver` (`driver_id`),
  ADD UNIQUE KEY `uq_vehicle_plate` (`plate_number`);

--
-- Indexes for table `vehicle_change_requests`
--
ALTER TABLE `vehicle_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_vcr_driver` (`driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `driver_password_resets`
--
ALTER TABLE `driver_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_reset_otps`
--
ALTER TABLE `driver_reset_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `driver_support_messages`
--
ALTER TABLE `driver_support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `favourite_drivers`
--
ALTER TABLE `favourite_drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ride_chat_messages`
--
ALTER TABLE `ride_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_support_messages`
--
ALTER TABLE `student_support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vehicle_change_requests`
--
ALTER TABLE `vehicle_change_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `driver_password_resets`
--
ALTER TABLE `driver_password_resets`
  ADD CONSTRAINT `fk_driver_password_resets` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_reset_otps`
--
ALTER TABLE `driver_reset_otps`
  ADD CONSTRAINT `fk_driver_reset_otps` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_change_requests`
--
ALTER TABLE `vehicle_change_requests`
  ADD CONSTRAINT `fk_vcr_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
