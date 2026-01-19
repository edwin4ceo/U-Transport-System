<?php
// ==========================================
// SECTION 1: SETUP & CONFIGURATION
// ==========================================
session_start();
include "db_connect.php";
include "function.php";

// [FIX] PHPMailer Paths
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Identify user role
$is_guest = true;
$current_role = '';
$current_id = '';

if (isset($_SESSION['driver_id'])) {
    $is_guest = false; $current_role = 'driver'; $current_id = $_SESSION['driver_id'];
} elseif (isset($_SESSION['student_id'])) {
    $is_guest = false; $current_role = 'student'; $current_id = $_SESSION['student_id'];
}

// ==========================================
// SECTION 2: HANDLE GUEST EMAIL SENDING
// ==========================================
if ($is_guest && isset($_POST['send_email'])) {
    $name    = htmlspecialchars($_POST['name']);
    $user_email   = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'soonkit0726@gmail.com';      
        $mail->Password   = 'oprh ldrk nwvg eyiv';        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('soonkit0726@gmail.com', 'U-Transport System');
        $mail->addAddress('help.u.transport.system@gmail.com'); 
        $mail->addReplyTo($user_email, $name); 

        $mail->isHTML(true);
        $mail->Subject = "New Support Inquiry from $name";
        $mail->Body    = "<h3>New Inquiry Received</h3><p><b>Name:</b> $name</p><p><b>Email:</b> $user_email</p><p><b>Message:</b><br>$message</p>";

        $mail->send();
        
        // 设置成功信息
        $_SESSION['swal_title'] = "Sent successfully!";
        $_SESSION['swal_msg'] = "We will get back to you within 7 working days. Thank you.";
        $_SESSION['swal_type'] = "success";

        // [FIX] 防止刷新重复发送
        header("Location: contact_us.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['swal_title'] = "Error";
        $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}";
        $_SESSION['swal_type'] = "error";
        header("Location: contact_us.php");
        exit();
    }
}

// ==========================================
// SECTION 3: FETCH CHAT DATA (LOGGED IN)
// ==========================================
$chat_messages = [];
if (!$is_guest) {
    $chat_sql = "SELECT * FROM student_support_messages WHERE student_id = '$current_id' ORDER BY created_at ASC";
    $chat_res = $conn->query($chat_sql);
    if ($chat_res) { while ($row = $chat_res->fetch_assoc()) { $chat_messages[] = $row; } }
}

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .content-area { background: transparent !important; box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    .contact-wrapper {
        max-width: 1100px; margin: 40px auto; padding: 0 20px;
        font-family: 'Poppins', sans-serif; animation: fadeInUpPage 0.8s ease;
        position: relative;
    }

    .btn-back-nav {
        position: absolute; left: 20px; top: 0;
        display: inline-flex; align-items: center; gap: 8px; color: #64748b;
        text-decoration: none; font-size: 14px; font-weight: 600;
        transition: 0.3s; padding: 10px 18px; background: white; border-radius: 50px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; z-index: 10;
    }

    .contact-header-title { text-align: center; margin-bottom: 40px; }
    .contact-header-title h1 { margin: 0; font-size: 32px; font-weight: 700; color: #004b82; }
    /* 顶部副标题大小 */
    .contact-header-title p { margin: 8px 0 0; font-size: 15px; color: #64748b; }

    .contact-container {
        display: grid; grid-template-columns: 420px 1fr; gap: 0;
        background: #fff; border-radius: 30px; overflow: hidden;
        box-shadow: 0 10px 50px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; min-height: 600px;
    }

    .support-sidebar {
        padding: 40px; background: #fafbfc; border-right: 1px solid #f1f5f9;
        display: flex; flex-direction: column; align-items: center; text-align: center;
    }
    .support-visual {
        width: 110px; height: 110px; margin-bottom: 20px;
        background: #fff; border-radius: 50%; padding: 5px;
        box-shadow: 0 8px 25px rgba(0,75,130,0.1); overflow: hidden; border: 3px solid #fff;
    }
    .support-visual img { width: 100%; height: 100%; object-fit: cover; }

    .support-sidebar h3 { color: #1e293b; font-size: 24px; font-weight: 700; margin-bottom: 10px; }
    
    /* [FIX] 调整为 15px，与顶部的 "Your journey's comfort" 一样大 */
    .sidebar-desc { 
        color: #64748b; 
        font-size: 15px !important; 
        line-height: 1.6; 
        margin-bottom: 25px; 
        width: 100%;
    }

    /* Email 卡片样式及动画 */
    .email-sky-card {
        margin-bottom: 30px; background: #e0f2fe; padding: 18px; border-radius: 20px;
        display: flex; align-items: center; gap: 12px; border: 1.5px solid #0ea5e9;
        width: 100%; box-sizing: border-box; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .email-sky-card i { color: #0ea5e9; font-size: 20px; transition: 0.3s; }
    .email-sky-card span { font-size: 14px; font-weight: 700; color: #0369a1; word-break: break-all; text-align: left; }

    /* [NEW] Email 悬停小动画 */
    .email-sky-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(14, 165, 233, 0.15);
        background: #d1edff;
    }
    .email-sky-card:hover i {
        transform: rotate(15px) scale(1.2);
        color: #0284c7;
    }

    /* Expanding Pills */
    .social-stack { display: flex; flex-direction: column; gap: 18px; width: 100%; align-items: flex-start; }
    .expanding-pill {
        display: inline-flex; align-items: center; width: 50px; height: 50px; border-radius: 25px;
        overflow: hidden; text-decoration: none; transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eee; white-space: nowrap;
    }
    .pill-icon { min-width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .pill-text { opacity: 0; padding-right: 25px; font-size: 13.5px; font-weight: 700; transition: opacity 0.3s ease 0.15s; }
    .expanding-pill:hover { width: 210px; }
    .expanding-pill:hover .pill-text { opacity: 1; }
    
    .pill-wa { color: #25d366; } .pill-wa:hover { background: #25d366; color: #fff; }
    .pill-ig { color: #e1306c; } .pill-ig:hover { background: linear-gradient(45deg, #f09433, #dc2743, #bc1888); color: #fff; }

    .interaction-panel { padding: 50px; background: #fff; }
    .field-group { margin-bottom: 20px; }
    .field-group label { display: block; font-size: 12px; font-weight: 700; color: #004b82; margin-bottom: 8px; text-transform: uppercase; }
    .field-input { width: 100%; padding: 14px 18px; border-radius: 12px; border: 1.5px solid #f1f5f9; background: #f8fafc; font-size: 14.5px; transition: 0.3s; box-sizing: border-box; }
    .field-input:focus { border-color: #004b82; background: #fff; outline: none; }
    textarea.field-input { resize: vertical; min-height: 140px; }

    .btn-submit-container { display: flex; justify-content: center; margin-top: 20px; }
    .btn-pill-action {
        display: inline-flex !important; align-items: center; justify-content: center;
        width: fit-content !important; padding: 12px 55px !important; 
        background-color: #004b82 !important; color: white !important; 
        border: none !important; border-radius: 50px !important; 
        font-size: 15px !important; font-weight: 600 !important; cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 75, 130, 0.2);
    }

    @media (max-width: 900px) { .contact-container { grid-template-columns: 1fr; } .btn-back-nav { position: static; margin-bottom: 20px; } }
</style>

<div class="contact-wrapper">
    <?php if($is_guest): ?>
        <a href="passanger_login.php" class="btn-back-nav"><i class="fa-solid fa-chevron-left"></i> Back to Login</a>
    <?php else: ?>
        <a href="FAQ.php" class="btn-back-nav"><i class="fa-solid fa-chevron-left"></i> Back to FAQ</a>
    <?php endif; ?>

    <div class="contact-header-title">
        <h1>Contact Us</h1>
        <p>Your journey's comfort is our priority.</p>
    </div>

    <div class="contact-container">
        <div class="support-sidebar">
            <div class="support-visual">
                <img src="uploads/logo.jpg" alt="Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
            </div>
            <h3>Support Hub</h3>
            <p class="sidebar-desc">Need a quick answer? Reach out through our official channels below.</p>
            
            <div class="email-sky-card">
                <i class="fa-solid fa-paper-plane"></i>
                <span>help.u.transport.system@gmail.com</span>
            </div>
            
            <div class="social-stack">
                <a href="https://wa.me/601114024118" target="_blank" class="expanding-pill pill-wa">
                    <div class="pill-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <div class="pill-text">Chat on WhatsApp</div>
                </a>
                <a href="https://www.instagram.com/u_transportsystem" target="_blank" class="expanding-pill pill-ig">
                    <div class="pill-icon"><i class="fa-brands fa-instagram"></i></div>
                    <div class="pill-text">Follow Instagram</div>
                </a>
            </div>
        </div>

        <div class="interaction-panel">
            <?php if ($is_guest): ?>
                <h3 style="margin-top:0; margin-bottom:25px; font-weight:700; color:#1e293b;">Send an Inquiry</h3>
                <form method="POST" id="emailForm">
                    <div class="field-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="field-input" placeholder="Your Name" required>
                    </div>
                    <div class="field-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="field-input" placeholder="example@mail.com" required>
                    </div>
                    <div class="field-group">
                        <label>Message</label>
                        <textarea name="message" class="field-input" placeholder="How can we assist you today?" required></textarea>
                    </div>
                    <div class="btn-submit-container">
                        <button type="submit" name="send_email" class="btn-pill-action">Send Message</button>
                    </div>
                </form>

                <script>
                document.getElementById('emailForm').addEventListener('submit', function() {
                    Swal.fire({
                        title: 'Sending Email...',
                        text: 'Sending email to our support team...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
                </script>
            <?php else: ?>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f1f5f9;">
                    <div style="width:10px; height:10px; background:#10b981; border-radius:50%; box-shadow: 0 0 10px #10b981;"></div>
                    <div style="font-weight:700; color:#1e293b; font-size:16px;">Support Session</div>
                </div>
                <div class="chat-scroll-box" id="msgList" style="height: 420px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px;">
                    <?php if (empty($chat_messages)): ?>
                        <div style="text-align:center; padding-top:120px; color:#cbd5e1;"><i class="fa-regular fa-comments" style="font-size:50px; display:block; margin-bottom:15px;"></i><p>No messages yet.</p></div>
                    <?php else: ?>
                        <?php foreach ($chat_messages as $m): $isMe = ($m['sender_type'] === $current_role); ?>
                            <div class="msg-bubble <?php echo $isMe ? 'msg-sent' : 'msg-received'; ?>" style="max-width: 85%; padding: 14px 18px; border-radius: 20px; font-size: 14px; line-height: 1.5; <?php echo $isMe ? 'align-self: flex-end; background: #004b82; color: white;' : 'align-self: flex-start; background: #f1f5f9; color: #334155;'; ?>">
                                <?php echo htmlspecialchars($m['message']); ?>
                                <div style="font-size:10px; margin-top:5px; opacity:0.6; text-align:right;"><?php echo date("h:i A", strtotime($m['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="POST" style="margin-top:20px; display:flex; gap:12px;">
                    <input type="text" name="chat_message" style="flex:1; border:1.5px solid #f1f5f9; background:#f8fafc; border-radius:50px; padding:12px 22px; outline:none;" placeholder="Type your message..." required autocomplete="off">
                    <button type="submit" style="width:48px; height:48px; border-radius:50%; background:#004b82; color:#fff; border:none; cursor:pointer;"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
                <script>const ml = document.getElementById('msgList'); if(ml) ml.scrollTop = ml.scrollHeight;</script>
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
        confirmButtonColor: '#004b82',
        confirmButtonText: 'OK' 
    });
</script>
<?php unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']); endif; ?>

<?php include "footer.php"; ?>