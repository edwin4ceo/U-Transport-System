<?php
session_start();

include "db_connect.php";
// include "function.php"; // Removed if not strictly needed, or keep if you use custom functions

// 1. SECURITY CHECK (Matches Dashboard)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$selected_driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

/**
 * Admin view of driver–admin chat.
 * Uses table: driver_support_messages
 */

// Handle new admin message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_driver_id = (int)($_POST['driver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($selected_driver_id > 0 && $message !== '') {
        $stmt = $conn->prepare("
            INSERT INTO driver_support_messages (driver_id, sender_type, message)
            VALUES (?, 'admin', ?)
        ");
        if ($stmt) {
            $stmt->bind_param("is", $selected_driver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: admin_driver_chat.php?driver_id=" . $selected_driver_id);
    exit;
}

// Fetch list of drivers who have messages (or you can fetch all drivers)
$drivers = [];
$result = $conn->query("
    SELECT DISTINCT d.driver_id, d.full_name, d.email
    FROM driver_support_messages m
    JOIN drivers d ON m.driver_id = d.driver_id
    ORDER BY d.full_name ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

// Fetch chat history for selected driver
$messages = [];
if ($selected_driver_id > 0) {
    $stmt = $conn->prepare("
        SELECT id, driver_id, sender_type, message, created_at
        FROM driver_support_messages
        WHERE driver_id = ?
        ORDER BY created_at ASC, id ASC
    ");
    if ($stmt) {
        $stmt->bind_param("i", $selected_driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Support Chat | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; margin: 0; font-family: sans-serif; }

        /* --- Admin Header Styles (Copied from Dashboard) --- */
        .admin-header {
            background-color: #2c3e50;
            color: white;
            padding: 0;
            height: 70px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo-section h1 { font-size: 1.5rem; margin: 0; }
        .admin-nav ul { list-style: none; display: flex; gap: 20px; padding: 0; margin: 0; }
        .admin-nav a { color: #bdc3c7; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .admin-nav a:hover { color: white; }
        .nav-divider { width: 1px; background: rgba(255,255,255,0.2); height: 25px; margin: 0 10px; }

        /* --- Chat Specific Styles --- */
        .chat-wrapper {
            min-height: calc(100vh - 160px);
            padding: 30px 10px 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .chat-header-title { margin-bottom: 20px; }
        .chat-header-title h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50; /* Changed to match admin theme */
        }
        .chat-header-title p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Driver selector */
        .chat-controls {
            margin: 0 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            width: fit-content;
        }

        .chat-controls select {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #bdc3c7;
            font-size: 14px;
            outline: none;
        }

        /* Card Layout */
        .chat-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #ecf0f1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 65vh;
            max-height: 600px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
            margin-bottom: 15px;
        }

        .chat-message-row {
            display: flex;
            margin-bottom: 10px;
        }

        .chat-message-row.driver { justify-content: flex-start; }
        .chat-message-row.admin  { justify-content: flex-end; }

        .chat-bubble {
            max-width: 75%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }

        /* Colors based on sender */
        .chat-bubble.driver {
            background: #ecf0f1; /* Light grey */
            color: #2c3e50;
            border-bottom-left-radius: 2px;
        }

        .chat-bubble.admin {
            background: #2980b9; /* Admin Blue */
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .chat-meta {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.8;
            text-align: right;
        }
        .chat-bubble.driver .chat-meta { text-align: left; color: #7f8c8d; }
        .chat-bubble.admin .chat-meta { color: #d6eaf8; }

        .chat-empty {
            text-align: center;
            padding-top: 100px;
            color: #95a5a6;
            font-size: 15px;
        }
        .chat-empty i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }

        /* Input Area */
        .chat-input-wrapper {
            width: 100%;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }

        .chat-input-wrapper textarea {
            flex: 1;
            border-radius: 8px;
            border: 1px solid #bdc3c7;
            padding: 12px;
            font-size: 14px;
            resize: none;
            min-height: 45px;
            font-family: inherit;
        }

        .chat-input-wrapper textarea:focus {
            border-color: #2980b9;
            outline: none;
            box-shadow: 0 0 0 2px rgba(41, 128, 185, 0.2);
        }

        .chat-input-wrapper button {
            border: none;
            border-radius: 8px;
            padding: 0 25px;
            height: 45px; /* Match textarea min-height */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #27ae60; /* Green for action */
            color: #fff;
            transition: background 0.2s;
        }
        .chat-input-wrapper button:hover { background: #219150; }
        .chat-input-wrapper button:disabled { background: #95a5a6; cursor: not-allowed; }

    </style>
</head>
<body>

    <header class="admin-header">
        <div class="container">
            <div class="logo-section">
                <h1><i class="fa-solid fa-building-user"></i> FMD Staff</h1>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php">Home</a></li>
                    <li><a href="verify_drivers.php">Approve</a></li>
                    <li><a href="view_drivers.php">Drivers</a></li>
                    <li><a href="view_passengers.php">Passengers</a></li>
                    <li><a href="view_bookings.php">Bookings</a></li>
                    <li><a href="manage_reviews.php">Reviews</a></li>
                    <li><a href="view_feedback.php">Feedback</a></li>
                    
                    <li class="nav-divider"></li>
                    <li><a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                    <li><a href="logout.php" style="color:#e74c3c;"><i class="fa-solid fa-right-from-bracket"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="chat-wrapper">
        <div class="chat-header-title">
            <h1><i class="fa-solid fa-comments"></i> Driver Support Chat</h1>
            <p>View conversations and reply to driver inquiries directly.</p>
        </div>

        <form method="get" class="chat-controls">
            <span><strong>Select Driver:</strong></span>
            <select name="driver_id" onchange="this.form.submit()">
                <option value="0">-- Choose a driver --</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?php echo (int)$d['driver_id']; ?>"
                        <?php echo $selected_driver_id == $d['driver_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['full_name']) . " (ID: " . (int)$d['driver_id'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="chat-card">
            <div class="chat-messages" id="chatMessages">

                <?php if ($selected_driver_id === 0): ?>
                    <div class="chat-empty">
                        <i class="fa-solid fa-address-book"></i><br>
                        Please select a driver from the dropdown above to view the conversation.
                    </div>

                <?php elseif (count($messages) === 0): ?>
                    <div class="chat-empty">
                        <i class="fa-regular fa-comments"></i><br>
                        No messages yet from this driver.
                    </div>

                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            // On admin page: sender_type 'driver' => left, 'admin' => right
                            $isAdmin = ($msg['sender_type'] === 'admin');
                            $rowClass   = $isAdmin ? 'admin' : 'driver';
                            $bubbleClass= $isAdmin ? 'admin' : 'driver';
                            $timeLabel  = date("d M, g:i A", strtotime($msg['created_at']));
                        ?>
                        <div class="chat-message-row <?php echo $rowClass; ?>">
                            <div class="chat-bubble <?php echo $bubbleClass; ?>">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                <div class="chat-meta">
                                    <?php echo $isAdmin ? 'You • ' : 'Driver • '; ?>
                                    <?php echo $timeLabel; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <?php if ($selected_driver_id > 0): ?>
                <form method="post" class="chat-input-wrapper" onsubmit="return handleAdminSubmit(event);">
                    <input type="hidden" name="driver_id" value="<?php echo (int)$selected_driver_id; ?>">

                    <textarea 
                        name="message"
                        id="adminMessage"
                        placeholder="Type your reply to the driver..."
                    ></textarea>

                    <button type="submit" id="adminSendBtn"><i class="fa-solid fa-paper-plane"></i> Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Scroll to bottom automatically
    var container = document.getElementById("chatMessages");
    if (container) container.scrollTop = container.scrollHeight;

    function handleAdminSubmit(e) {
        var t = document.getElementById("adminMessage");
        var b = document.getElementById("adminSendBtn");
        if (!t) return true;
        if (t.value.trim() === "") {
            e.preventDefault();
            return false;
        }
        b.disabled = true;
        b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        return true;
    }
    </script>

</body>
</html>