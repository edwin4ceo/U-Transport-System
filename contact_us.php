<?php
// FUNCTION: START SESSION
session_start();

// SECTION: INCLUDES
include "db_connect.php";
include "function.php";

// SECTION: PHPMAILER SETUP
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 1. IDENTIFY USER STATUS ---
$is_guest = true;
$current_role = '';
$current_id = '';

if (isset($_SESSION['driver_id'])) {
    $is_guest = false;
    $current_role = 'driver';
    $current_id = $_SESSION['driver_id'];
} elseif (isset($_SESSION['student_id'])) {
    $is_guest = false;
    $current_role = 'student';
    $current_id = $_SESSION['student_id'];
}

// --- 2. HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CASE A: GUEST EMAIL
    if ($is_guest && isset($_POST['send_email'])) {
        $name     = htmlspecialchars($_POST['name']);
        $email    = htmlspecialchars($_POST['email']);
        $msg_body = htmlspecialchars($_POST['message']);

        if(empty($name) || empty($email) || empty($msg_body)){
            $_SESSION['swal_title'] = "Missing Info"; 
            $_SESSION['swal_msg'] = "Please fill in all fields."; 
            $_SESSION['swal_type'] = "warning";
        } else {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'soonkit0726@gmail.com'; 
                $mail->Password   = 'oprh ldrk nwvg eyiv';    
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('soonkit0726@gmail.com', 'U-Transport Guest');
                $mail->addAddress('soonkit0726@gmail.com'); 
                $mail->addReplyTo($email, $name);
                $mail->isHTML(true);
                $mail->Subject = "Inquiry: $name";
                $mail->Body    = "<p><strong>From:</strong> $name ($email)</p><p>$msg_body</p>";
                $mail->send();
                $_SESSION['swal_title'] = "Sent!"; 
                $_SESSION['swal_msg'] = "We will contact you shortly."; 
                $_SESSION['swal_type'] = "success";
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Error"; 
                $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}"; 
                $_SESSION['swal_type'] = "error";
            }
        }
        header("Location: contact_us.php");
        exit;
    }

    // CASE B: LOGGED-IN CHAT
    if (!$is_guest && isset($_POST['chat_message'])) {
        $message = trim($_POST['chat_message']);
        if ($message !== '') {
            $auto_reply = "Thank you for your message. This is an automated reply. Our support team will get back to you within 5-10 minutes. We appreciate your patience.";
            
            if ($current_role === 'driver') {
                $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message) VALUES (?, 'driver', ?)");
                $stmt->bind_param("is", $current_id, $message); $stmt->execute(); $stmt->close();
                $stmt = $conn->prepare("INSERT INTO driver_support_messages (driver_id, sender_type, message) VALUES (?, 'admin', ?)");
                $stmt->bind_param("is", $current_id, $auto_reply); $stmt->execute(); $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message) VALUES (?, 'student', ?)");
                $stmt->bind_param("ss", $current_id, $message); $stmt->execute(); $stmt->close();
                $stmt = $conn->prepare("INSERT INTO student_support_messages (student_id, sender_type, message) VALUES (?, 'admin', ?)");
                $stmt->bind_param("ss", $current_id, $auto_reply); $stmt->execute(); $stmt->close();
            }
        }
        header("Location: contact_us.php");
        exit;
    }
}

// --- 3. FETCH MESSAGES ---
$messages = [];
if (!$is_guest) {
    if ($current_role === 'driver') {
        $sql = "SELECT * FROM driver_support_messages WHERE driver_id = ? ORDER BY created_at ASC";
        $upd = "UPDATE driver_support_messages SET is_read = 1 WHERE driver_id = ? AND sender_type = 'admin' AND is_read = 0";
    } else {
        $sql = "SELECT * FROM student_support_messages WHERE student_id = ? ORDER BY created_at ASC";
        $upd = "UPDATE student_support_messages SET is_read = 1 WHERE student_id = ? AND sender_type = 'admin' AND is_read = 0";
    }
    $stmt = $conn->prepare($sql);
    if($current_role === 'driver') $stmt->bind_param("i", $current_id); else $stmt->bind_param("s", $current_id);
    $stmt->execute(); $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $messages[] = $row;
    $stmt->close();

    $stmt = $conn->prepare($upd);
    if($current_role === 'driver') $stmt->bind_param("i", $current_id); else $stmt->bind_param("s", $current_id);
    $stmt->execute(); $stmt->close();
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* ========================================================= */
    /* 1. RESET DEFAULT STYLES                                   */
    /* ========================================================= */
    .content-area {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    /* PAGE WRAPPER */
    .page-wrapper {
        min-height: calc(100vh - 160px);
        background: #f5f7fb; 
        padding: 40px 20px;
        font-family: 'Poppins', sans-serif;
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    .container-custom {
        max-width: 850px;
        margin: 0 auto;
        position: relative; 
    }

    /* HEADER */
    .header-relative {
        position: relative;
        text-align: center;
        margin-bottom: 30px;
        min-height: 50px;
        display: flex; 
        align-items: flex-start;
        justify-content: center;
    }

    /* Back Button */
    .btn-back-abs {
        position: absolute;
        left: 0;
        top: 0;
        display: inline-flex; align-items: center; gap: 8px;
        color: #64748b; font-weight: 600; font-size: 14px;
        text-decoration: none; transition: 0.2s;
        background: white; padding: 8px 16px; border-radius: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        z-index: 10;
        cursor: pointer;
    }
    .btn-back-abs:hover { color: #004b82; transform: translateX(-3px); border-color: #004b82; }

    .header-titles {
        max-width: 80%;
    }
    .header-titles h1 { margin: 0; font-size: 28px; font-weight: 700; color: #004b82; line-height: 1.2; }
    .header-titles p { margin: 5px 0 0; font-size: 15px; color: #64748b; }

    /* CARD STYLES */
    .main-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        overflow: hidden;
        display: flex; 
        flex-direction: column;
    }

    .guest-layout { display: flex; flex-wrap: wrap; width: 100%; }
    
    .contact-info-panel {
        flex: 1; background: #004b82; color: white; padding: 40px; min-width: 280px;
    }
    .contact-info-panel h3 { margin-top: 0; font-size: 20px; }
    .info-list { margin-top: 30px; }
    .info-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; font-size: 14px; }
    .info-row i { width: 30px; height: 30px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    .form-panel { flex: 1.5; padding: 40px; min-width: 300px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px; }
    .form-input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; box-sizing: border-box; }
    .form-input:focus { border-color: #004b82; outline: none; }
    .btn-submit { background: #004b82; color: white; padding: 12px 25px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; }
    .btn-submit:hover { background: #00365e; transform: translateY(-2px); }

    /* CHAT STYLES */
    .chat-container {
        display: flex; 
        flex-direction: column;
        height: 600px; 
        width: 100%;
    }

    .chat-header-bar {
        padding: 15px 25px;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: #fff;
        flex-shrink: 0; 
    }
    .support-status { font-size: 12px; color: #718096; background: #f7fafc; padding: 4px 10px; border-radius: 20px; }

    .chat-messages-area {
        flex: 1; 
        background: #f8fafc;
        padding: 20px;
        overflow-y: auto; 
        display: flex; 
        flex-direction: column; 
        gap: 15px;
    }

    .message-row { display: flex; width: 100%; }
    .message-row.me { justify-content: flex-end; }
    .message-row.them { justify-content: flex-start; }

    .message-bubble {
        max-width: 55%; 
        padding: 12px 18px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.5;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    .message-row.me .message-bubble { 
        background: #004b82; color: white; 
        border-bottom-right-radius: 2px; 
    }
    
    .message-row.them .message-bubble { 
        background: white; color: #2d3748; 
        border: 1px solid #e2e8f0; 
        border-bottom-left-radius: 2px; 
    }
    
    .message-row.them.admin-reply .message-bubble {
        text-align: justify; 
    }

    .msg-meta { font-size: 10px; margin-top: 5px; opacity: 0.7; text-align: right; display: block; }

    /* [CRITICAL FIX] CHAT INPUT AREA LAYOUT */
    .chat-footer-wrapper {
        padding: 20px 25px;
        background: white;
        border-top: 1px solid #f1f5f9;
        flex-shrink: 0; 
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Force Flex Row */
    .chat-form-flex {
        display: flex !important;
        flex-direction: row !important; /* Ensure horizontal */
        align-items: center !important;
        gap: 15px !important;
        width: 100%;
        margin: 0;
    }

    /* Input: Grey Rounded Bar */
    .chat-input-grey { 
        flex-grow: 1; /* Fill remaining space */
        border: 1px solid #e2e8f0 !important;
        background: #f8fafc !important; 
        outline: none !important; 
        font-size: 14px; 
        color: #333;
        padding: 0 20px;
        border-radius: 30px; 
        height: 50px; 
        box-sizing: border-box;
        transition: 0.2s;
    }
    .chat-input-grey:focus { border-color: #004b82 !important; background: #fff !important; }
    
    /* Button: Independent Circle */
    .btn-send-round { 
        width: 50px !important; 
        height: 50px !important; 
        min-width: 50px !important; /* Prevent shrinking */
        border-radius: 50% !important; 
        background: #004b82; 
        color: white; 
        border: none; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        flex-shrink: 0; /* Do not shrink */
        transition: 0.2s;
        font-size: 18px; 
        box-shadow: 0 4px 10px rgba(0, 75, 130, 0.2);
    }
    .btn-send-round:hover { background: #003660; transform: scale(1.05); }
    
    /* Responsive */
    @media (max-width: 768px) {
        .guest-layout { flex-direction: column; }
        .header-relative { flex-direction: column; align-items: center; gap: 10px; }
        .btn-back-abs { position: static; margin-bottom: 10px; }
    }
</style>

<div class="page-wrapper">
    <div class="container-custom">

        <div class="header-relative">
            <a href="qa_forum.php" class="btn-back-abs">
                <i class="fa-solid fa-arrow-left"></i> Back to FAQ
            </a>
            <div class="header-titles">
                <?php if ($is_guest): ?>
                    <h1>Contact Us</h1>
                    <p>Have questions? We'd love to hear from you.</p>
                <?php else: ?>
                    <h1>Support Center</h1>
                    <p>We are here to assist you with any inquiries.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-card">
            
            <?php if ($is_guest): ?>
                <div class="guest-layout">
                    <div class="contact-info-panel">
                        <h3>Get in Touch</h3>
                        <p style="opacity:0.8; font-size:14px; line-height:1.6; margin-bottom:30px;">
                            Fill up the form and our Team will get back to you within 24 hours.
                        </p>
                        <div class="info-list">
                            <div class="info-row"><i class="fa-solid fa-phone"></i> +60 6-252 3000</div>
                            <div class="info-row"><i class="fa-solid fa-envelope"></i> help@utransport.mmu.edu.my</div>
                            <div class="info-row"><i class="fa-solid fa-map-pin"></i> MMU Melaka Campus</div>
                        </div>
                    </div>
                    <div class="form-panel">
                        <form method="POST" onsubmit="this.querySelector('button').innerHTML='Sending...'">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-input" placeholder="Your Name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" placeholder="Your Email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-input" rows="4" placeholder="How can we help?" required></textarea>
                            </div>
                            <button type="submit" name="send_email" class="btn-submit">Send Message</button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="chat-container">
                    <div class="chat-header-bar">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:40px; height:40px; background:#ebf8ff; color:#004b82; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                <i class="fa-solid fa-headset"></i>
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:16px;">Customer Support</h3>
                                <span style="font-size:12px; color:#94a3b8;">Usually replies instantly</span>
                            </div>
                        </div>
                        <div class="support-status"><span style="color:#22c55e;">●</span> Online</div>
                    </div>

                    <div class="chat-messages-area" id="msgContainer">
                        <?php if(empty($messages)): ?>
                            <div style="text-align:center; margin-top:60px; color:#cbd5e0;">
                                <i class="fa-regular fa-comments" style="font-size:40px; margin-bottom:10px;"></i>
                                <p>No messages yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($messages as $msg): 
                                $sender = $msg['sender_type'];
                                $isMe   = ($sender === 'driver' || $sender === 'student');
                                $isAdmin = ($sender === 'admin');
                                $time   = date("h:i A", strtotime($msg['created_at']));
                                $extraClass = $isAdmin ? 'admin-reply' : '';
                            ?>
                                <div class="message-row <?php echo $isMe ? 'me' : 'them ' . $extraClass; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        <span class="msg-meta"><?php echo $time; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="chat-footer-wrapper">
                        <form method="POST" onsubmit="return checkInput()" class="chat-form-flex">
                            <input type="text" name="chat_message" id="chatField" class="chat-input-grey" placeholder="Type your message..." autocomplete="off">
                            <button type="submit" class="btn-send-round"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
                
                <script>
                    var box = document.getElementById("msgContainer");
                    if(box) box.scrollTop = box.scrollHeight;
                    function checkInput() { return document.getElementById("chatField").value.trim() !== ""; }
                </script>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php 
if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<?php 
    unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']);
endif; 
include "footer.php"; 
?>