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

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CASE A: GUEST SENDING EMAIL
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
                // SMTP Config
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
                $_SESSION['swal_title'] = "Sent Successfully"; 
                $_SESSION['swal_msg'] = "We will contact you shortly via email."; 
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

    // CASE B: LOGGED-IN USER SENDING CHAT
    if (!$is_guest && isset($_POST['chat_message'])) {
        $message = trim($_POST['chat_message']);
        if ($message !== '') {
            $auto_reply = "Thank you. Our team will review your message.";
            
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

// --- 3. FETCH CHAT HISTORY ---
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Global Reset */
    .page-wrapper {
        font-family: 'Inter', sans-serif;
        background-color: #f5f7fb;
        min-height: calc(100vh - 150px);
        padding: 40px 20px;
        box-sizing: border-box;
    }

    .container {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* Header */
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .page-header h1 {
        color: #1a202c;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 10px;
    }
    .page-header p {
        color: #718096;
        font-size: 15px;
    }

    /* =========================
       GUEST MODE STYLES
       ========================= */
    .contact-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        display: flex;
        flex-wrap: wrap;
        border: 1px solid #e2e8f0;
    }

    /* Left Side: Info Panel */
    .contact-info {
        flex: 1;
        background: #004b82; /* Brand Blue */
        color: #fff;
        padding: 50px 40px;
        min-width: 300px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .contact-info h3 { margin-top: 0; font-size: 22px; font-weight: 600; }
    .contact-info p { opacity: 0.9; margin-bottom: 30px; line-height: 1.6; }

    .info-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; font-size: 14px; }
    .info-item i { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    /* Social Media Links */
    .social-links-area { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 25px; }
    .social-btn {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: flex; align-items: center; justify-content: center;
        color: #fff; text-decoration: none;
        transition: all 0.3s ease; font-size: 18px;
    }
    .social-btn:hover { background: #fff; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    
    /* Brand Colors on Hover */
    .social-btn.whatsapp:hover { color: #25D366; }
    .social-btn.instagram:hover { color: #C13584; }
    
    /* Right Side: Form Panel */
    .contact-form {
        flex: 1.5;
        padding: 50px 40px;
        background: #fff;
        min-width: 300px;
    }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px; }
    .form-input {
        width: 100%; padding: 12px 15px; border: 1px solid #cbd5e0;
        border-radius: 8px; font-size: 14px; color: #2d3748; background: #fff;
        box-sizing: border-box; transition: all 0.2s;
    }
    .form-input:focus { border-color: #004b82; outline: none; box-shadow: 0 0 0 3px rgba(0, 75, 130, 0.1); }

    .btn-submit {
        background: #004b82; color: #fff; border: none;
        padding: 12px 30px; border-radius: 8px; font-weight: 600;
        cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-submit:hover { background: #00365e; transform: translateY(-2px); }

    /* =========================
       LOGGED-IN MODE STYLES
       ========================= */
    .chat-layout {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        height: 700px; /* Fixed height */
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        padding: 20px;
        border-bottom: 1px solid #edf2f7;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-header-user { display: flex; align-items: center; gap: 15px; }
    .header-icon { width: 45px; height: 45px; background: #ebf8ff; color: #004b82; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .header-text h2 { margin: 0; font-size: 18px; color: #1a202c; font-weight: 700; }
    .header-text p { margin: 0; font-size: 13px; color: #718096; }

    .chat-body {
        flex: 1;
        background: #f7fafc;
        padding: 30px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .msg-group { display: flex; width: 100%; }
    .msg-group.me { justify-content: flex-end; }
    .msg-group.other { justify-content: flex-start; }

    .msg-bubble {
        max-width: 60%;
        padding: 15px 20px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.5;
        position: relative;
    }
    
    .msg-group.me .msg-bubble {
        background: #004b82; color: #fff;
        border-bottom-right-radius: 2px;
    }
    .msg-group.other .msg-bubble {
        background: #fff; color: #2d3748;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 2px;
    }

    .msg-time { font-size: 11px; margin-top: 5px; opacity: 0.7; display: block; text-align: right; }

    .chat-footer {
        padding: 20px;
        background: #fff;
        border-top: 1px solid #edf2f7;
    }
    .chat-input-box {
        display: flex; gap: 15px;
        background: #f7fafc; padding: 10px;
        border-radius: 50px; border: 1px solid #e2e8f0;
    }
    .chat-input {
        flex: 1; border: none; background: transparent;
        padding: 10px 15px; font-size: 14px; outline: none;
    }
    .btn-send {
        width: 45px; height: 45px; border-radius: 50%;
        background: #004b82; color: #fff; border: none;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: 0.2s;
    }
    .btn-send:hover { background: #00365e; transform: scale(1.05); }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-card { flex-direction: column; }
        .msg-bubble { max-width: 85%; }
    }
</style>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <?php if ($is_guest): ?>
                <h1>Contact Us</h1>
                <p>Have questions? We'd love to hear from you.</p>
            <?php else: ?>
                <h1>Support Center</h1>
                <p>Connected as <strong><?php echo ucfirst($current_role); ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if ($is_guest): ?>
            <div class="contact-card">
                <div class="contact-info">
                    <div>
                        <h3>Get in Touch</h3>
                        <p>Fill up the form and our Team will get back to you within 24 hours.</p>
                        
                        <div class="info-item">
                            <i class="fa-solid fa-phone"></i>
                            <span>+60 6-252 3000</span>
                        </div>
                        <div class="info-item">
                            <i class="fa-solid fa-envelope"></i>
                            <span>help@utransport.mmu.edu.my</span>
                        </div>
                        <div class="info-item">
                            <i class="fa-solid fa-map-location-dot"></i>
                            <span>Multimedia University, Melaka Campus</span>
                        </div>
                    </div>

                    <div class="social-links-area">
                        <p style="font-size:12px; opacity:0.7; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;">Connect with us</p>
                        <div style="display:flex; gap:15px;">
                            <a href="https://wa.me/601114024118" target="_blank" class="social-btn whatsapp">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                            <a href="https://www.instagram.com/u_transportsystem?igsh=MTB0eXV2cGw2aTY3bw==" target="_blank" class="social-btn instagram">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <form action="" method="POST" onsubmit="this.querySelector('.btn-submit').innerHTML = 'Sending...'">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-input" rows="4" placeholder="How can we help?" required></textarea>
                        </div>
                        <button type="submit" name="send_email" class="btn-submit">
                            Send Message <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="chat-layout">
                <div class="chat-header">
                    <div class="chat-header-user">
                        <div class="header-icon"><i class="fa-solid fa-headset"></i></div>
                        <div class="header-text">
                            <h2>Customer Support</h2>
                            <p>Typically replies in a few minutes</p>
                        </div>
                    </div>
                    <div>
                        <span style="font-size:12px; color:#718096; background:#f0f4f8; padding:5px 12px; border-radius:20px;">
                            <span style="color:#48bb78;">●</span> Online
                        </span>
                    </div>
                </div>

                <div class="chat-body" id="chatContainer">
                    <?php if(empty($messages)): ?>
                        <div style="text-align:center; margin-top:100px; color:#a0aec0;">
                            <i class="fa-regular fa-comments" style="font-size:50px; margin-bottom:15px;"></i>
                            <p>No conversation yet. Start by sending a message.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($messages as $msg): 
                            $isMe = ($msg['sender_type'] === 'driver' || $msg['sender_type'] === 'student');
                            $time = date("h:i A", strtotime($msg['created_at']));
                        ?>
                            <div class="msg-group <?php echo $isMe ? 'me' : 'other'; ?>">
                                <div class="msg-bubble">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    <span class="msg-time"><?php echo $time; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="chat-footer">
                    <form method="POST" onsubmit="return validateChat()">
                        <div class="chat-input-box">
                            <input type="text" name="chat_message" id="chatInput" class="chat-input" placeholder="Type your message here..." autocomplete="off">
                            <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                // Auto scroll
                var chatBox = document.getElementById("chatContainer");
                if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;

                function validateChat() {
                    return document.getElementById("chatInput").value.trim() !== "";
                }
            </script>
        <?php endif; ?>

    </div>
</div>

<?php if(isset($_SESSION['swal_title'])): ?>
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<?php unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']); endif; ?>

<?php include "footer.php"; ?>