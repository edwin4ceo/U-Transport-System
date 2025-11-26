<?php
// driver_dashboard_api.php
session_start();
include "db_connect.php";  // 里面要有 $conn
include "function.php";    // 可选，看你是否有自用函数

// 必须登入后才能用
if (!isset($_SESSION['driver_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unauthorized: please login as driver.'
    ]);
    exit;
}

$driver_id = $_SESSION['driver_id'];

// 统一用 JSON
header('Content-Type: application/json; charset=utf-8');

// 取得 action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No action specified.'
    ]);
    exit;
}

// 小工具函数：安全取值
function get_param($name, $default = null, $method = 'POST') {
    if ($method === 'GET') {
        return isset($_GET[$name]) ? trim($_GET[$name]) : $default;
    }
    return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
}

// 根据 action 分发
switch ($action) {

    // ====================== 1. Edit Profile ======================
    case 'get_profile':
        // 读 driver profile
        $sql  = "SELECT id, name, email, phone, student_id, license_no, license_expiry 
                 FROM drivers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();

        echo json_encode([
            'status'  => 'success',
            'data'    => $profile
        ]);
        break;

    case 'update_profile':
        // 更新个人资料 + 驾照资料（不含文件上传，文件可独立 API 处理）
        $name           = get_param('name');
        $phone          = get_param('phone');
        $student_id     = get_param('student_id');
        $license_no     = get_param('license_no');
        $license_expiry = get_param('license_expiry');

        if (!$name || !$phone || !$license_no || !$license_expiry) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Missing required fields.'
            ]);
            break;
        }

        $sql  = "UPDATE drivers 
                 SET name = ?, phone = ?, student_id = ?, 
                     license_no = ?, license_expiry = ?
                 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi",
            $name,
            $phone,
            $student_id,
            $license_no,
            $license_expiry,
            $driver_id
        );
        $ok = $stmt->execute();

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Profile updated.' : 'Failed to update profile.'
        ]);
        break;

    // 如果你要驾照文件上传，可以做一个独立 action，使用 $_FILES
    // case 'upload_license_file': ...


    // ====================== 2. Add Transport ======================
    case 'add_transport':
        $vehicle_type   = get_param('vehicle_type');
        $vehicle_model  = get_param('vehicle_model');
        $destination    = get_param('destination_area');
        $available_days = $_POST['available_days'] ?? []; // 数组
        $time_from      = get_param('time_from');
        $time_to        = get_param('time_to');
        $price_per_trip = get_param('price_per_trip');
        $pricing_notes  = get_param('pricing_notes');
        $payment_method = $_POST['payment_method'] ?? []; // 数组

        if (!$vehicle_type || !$vehicle_model || !$destination || !$time_from || !$time_to || !$price_per_trip) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Missing required fields.'
            ]);
            break;
        }

        // 简单做法：数组转成逗号分隔字串存 DB
        $days_str     = implode(',', $available_days);
        $payment_str  = implode(',', $payment_method);

        $sql = "INSERT INTO transports
                (driver_id, vehicle_type, vehicle_model, destination_area,
                 available_days, time_from, time_to, price_per_trip, pricing_notes, payment_method)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssiss",
            $driver_id,
            $vehicle_type,
            $vehicle_model,
            $destination,
            $days_str,
            $time_from,
            $time_to,
            $price_per_trip,
            $pricing_notes,
            $payment_str
        );
        $ok = $stmt->execute();

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Transport added.' : 'Failed to add transport.',
            'transport_id' => $ok ? $stmt->insert_id : null
        ]);
        break;

    case 'list_transports':
        // 列出自己所有 transport
        $sql = "SELECT * FROM transports WHERE driver_id = ? ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $rows
        ]);
        break;


    // ================== 3. View transport booking request ==================
    case 'list_bookings':
        // 假设 bookings 里有 driver_id
        $sql = "SELECT b.*, u.name AS passenger_name, u.email AS passenger_email
                FROM bookings b
                JOIN users u ON b.passenger_id = u.id
                WHERE b.driver_id = ?
                ORDER BY b.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $rows
        ]);
        break;

    case 'update_booking_status':
        // 接受 / 拒绝 booking
        $booking_id = (int) get_param('booking_id');
        $new_status = get_param('status'); // 'accepted' or 'rejected'

        if (!$booking_id || !in_array($new_status, ['accepted', 'rejected'])) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid parameters.'
            ]);
            break;
        }

        // 确保这个 booking 是属于当前 driver
        $sql  = "UPDATE bookings 
                 SET status = ?
                 WHERE id = ? AND driver_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $booking_id, $driver_id);
        $ok = $stmt->execute();

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Booking updated.' : 'Failed to update booking.'
        ]);
        break;


    // ====================== 4. Ratings & Reviews ======================
    case 'list_ratings':
        // 列出乘客给这个司机的评分评论
        $sql = "SELECT r.*, u.name AS passenger_name
                FROM ratings r
                JOIN users u ON r.passenger_id = u.id
                WHERE r.driver_id = ?
                ORDER BY r.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        $sum  = 0;
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $sum   += (float)$row['rating'];  // rating 字段
            $count++;
        }

        $avg = $count > 0 ? round($sum / $count, 2) : 0;

        echo json_encode([
            'status'       => 'success',
            'average'      => $avg,
            'total_review' => $count,
            'data'         => $rows
        ]);
        break;


    // ====================== 5. Q & A Chat (Forum) ======================
    case 'create_forum_post':
        $content = get_param('content');
        if (!$content) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Content is required.'
            ]);
            break;
        }

        $sql = "INSERT INTO forum_posts (driver_id, content, created_at)
                VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $driver_id, $content);
        $ok = $stmt->execute();

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Post created.' : 'Failed to create post.',
            'post_id' => $ok ? $stmt->insert_id : null
        ]);
        break;

    case 'list_forum_posts':
        // 简单列出帖子（可加分页）
        $sql = "SELECT p.*, d.name AS driver_name
                FROM forum_posts p
                JOIN drivers d ON p.driver_id = d.id
                ORDER BY p.created_at DESC
                LIMIT 50";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $rows
        ]);
        break;

    case 'create_forum_reply':
        $post_id = (int) get_param('post_id');
        $content = get_param('content');
        if (!$post_id || !$content) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Post ID and content are required.'
            ]);
            break;
        }

        $sql = "INSERT INTO forum_replies (post_id, driver_id, content, created_at)
                VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $driver_id, $content);
        $ok = $stmt->execute();

        echo json_encode([
            'status'   => $ok ? 'success' : 'error',
            'message'  => $ok ? 'Reply added.' : 'Failed to add reply.'
        ]);
        break;


    // ====================== 6. Contact Us (Feedback) ======================
    case 'send_feedback':
        $subject  = get_param('subject');
        $category = get_param('category');
        $message  = get_param('message');

        if (!$subject || !$category || !$message) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Subject, category and message are required.'
            ]);
            break;
        }

        $sql = "INSERT INTO driver_feedback 
                (driver_id, subject, category, message, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $driver_id, $subject, $category, $message);
        $ok = $stmt->execute();

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Feedback sent.' : 'Failed to send feedback.'
        ]);
        break;


    // ====================== 默认 / 未知 action ======================
    default:
        echo json_encode([
            'status'  => 'error',
            'message' => 'Unknown action: ' . $action
        ]);
        break;
}
