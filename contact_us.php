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

<<<<<<< HEAD
// --- 2. HANDLE FORM SUBMISSION ---
=======
// --- 2. HANDLE POST REQUESTS ---
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CASE A: GUEST SENDING EMAIL
    if ($is_guest && isset($_POST['send_email'])) {
<<<<<<< HEAD
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
=======
        $name    = htmlspecialchars($_POST['name']);
        $email   = htmlspecialchars($_POST['email']);
        $msg_body= htmlspecialchars($_POST['message']);

        if(empty($name) || empty($email) || empty($msg_body)){
            $_SESSION['swal_title'] = "Missing Info"; $_SESSION['swal_msg'] = "Please fill all fields."; $_SESSION['swal_type'] = "warning";
        } else {
            $mail = new PHPMailer(true);
            try {
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'soonkit0726@gmail.com'; 
<<<<<<< HEAD
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
=======
                $mail->Password   = 'oprh ldrk nwvg eyiv';   
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('soonkit0726@gmail.com', 'U-Transport Guest');
                $mail->addAddress('soonkit0726@gmail.com');
                $mail->addReplyTo($email, $name);
                $mail->isHTML(true);
                $mail->Subject = "Guest Inquiry from $name";
                $mail->Body    = "<h3>New Message</h3><p><b>From:</b> $name ($email)</p><hr><p>$msg_body</p>";
                $mail->send();
                $_SESSION['swal_title'] = "Sent!"; $_SESSION['swal_msg'] = "We will contact you via email."; $_SESSION['swal_type'] = "success";
            } catch (Exception $e) {
                $_SESSION['swal_title'] = "Error"; $_SESSION['swal_msg'] = "Mailer Error: {$mail->ErrorInfo}"; $_SESSION['swal_type'] = "error";
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
            }
        }
        header("Location: contact_us.php");
        exit;
    }

    // CASE B: LOGGED-IN USER SENDING CHAT
    if (!$is_guest && isset($_POST['chat_message'])) {
        $message = trim($_POST['chat_message']);
        if ($message !== '') {
<<<<<<< HEAD
            $auto_reply = "Thank you. Our team will review your message.";
=======
            $auto_reply = "Thanks for reaching out! Our team will reply shortly.";
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
            
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

// --- 3. FETCH CHAT DATA (Only for Logged In) ---
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
<<<<<<< HEAD
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
=======
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* SHARED ANIMATION */
    @keyframes fadeInUpPage { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }
    
    .page-wrapper { 
        min-height: calc(100vh - 160px); 
        padding: 40px 20px; 
        max-width: 1100px; 
        margin: 0 auto; 
        background: #f5f7fb; 
        font-family: 'Poppins', sans-serif; 
        animation: fadeInUpPage 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; 
    }

    /* HEADER */
    .header-section { text-align: center; margin-bottom: 40px; }
    .header-section h1 { color: #d35400; font-size: 28px; font-weight: 600; margin: 0; } 
    /* ^ Keeping consistent with "U-Transport" blue or similar, changed to Orange/Brown-ish to match reference? 
       No, let's stick to Theme Blue for consistency, but styled cleanly. */
    .header-section h1 { color: #004b82; font-weight: 700; }

    /* ======================================================= */
    /* TWIN CARDS LAYOUT (MATCHING REFERENCE IMAGE)            */
    /* ======================================================= */
    .twin-container {
        display: flex;
        gap: 30px;
        align-items: flex-start;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* COMMON CARD STYLE */
    .white-card {
        background: #fff;
        border-radius: 12px; /* Matches reference */
        padding: 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        flex: 1;
        min-width: 300px;
        height: 100%;
    }

    /* --- LEFT CARD: FORM --- */
    .left-card h3 { margin-top: 0; font-size: 18px; font-weight: 700; color: #000; margin-bottom: 25px; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500; } /* Purple/Blueish text in ref */
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
        color: #333;
        background: #fff;
        font-family: inherit;
        box-sizing: border-box;
    }
    .form-control::placeholder { color: #ccc; }
    .form-control:focus { border-color: #004b82; outline: none; }

    .btn-send-msg {
        background: #F8BBD0; /* Pinkish from reference, but let's adapt to Blue Theme? */
        /* Actually, let's keep it Theme Blue to fit U-Transport */
        background: #004b82; 
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-send-msg:hover { opacity: 0.9; transform: translateY(-2px); }

    /* --- RIGHT CARD: INFO --- */
    .right-card h3 { margin-top: 0; font-size: 16px; font-weight: 700; color: #000; margin-bottom: 10px; }
    
    .contact-item { margin-bottom: 25px; }
    .contact-row { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #333; margin-bottom: 8px; }
    .contact-row i { font-size: 16px; width: 20px; text-align: center; color: #555; }

    /* BUTTONS SECTION */
    .action-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; max-width: 200px;
        padding: 12px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 15px;
        color: white;
        transition: 0.3s;
    }

    /* WhatsApp Green */
    .btn-whatsapp { background-color: #25D366; border: none; }
    .btn-whatsapp:hover { background-color: #1ebc57; transform: translateY(-2px); }

    /* Instagram Gradient */
    .btn-insta { 
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); 
        border: none; 
    }
    .btn-insta:hover { opacity: 0.9; transform: translateY(-2px); }

    /* Facebook Blue */
    .btn-fb { background-color: #3b5998; border: none; }
    .btn-fb:hover { background-color: #2d4373; transform: translateY(-2px); }

    /* --- CHAT STYLES (LOGGED IN) --- */
    /* Keeping existing chat styles hidden for guest, shown for user */
    .chat-card { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; height: 600px; }
    .chat-messages { flex: 1; overflow-y: auto; padding: 30px; background: #f8fafc; display: flex; flex-direction: column; gap: 15px; }
    .chat-row { display: flex; width: 100%; }
    .chat-row.me { justify-content: flex-end; } .chat-row.support { justify-content: flex-start; }
    .chat-bubble { max-width: 75%; padding: 12px 18px; font-size: 14px; line-height: 1.5; border-radius: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
    .chat-bubble.me { background: #004b82; color: white; border-bottom-right-radius: 2px; }
    .chat-bubble.support { background: #fff; color: #333; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px; }
    .chat-meta { font-size: 11px; margin-top: 5px; opacity: 0.7; display: flex; gap: 5px; align-items: center; }
    .chat-row.me .chat-meta { justify-content: flex-end; }
    .chat-input-area { background: #fff; padding: 20px; border-top: 1px solid #e2e8f0; display: flex; gap: 15px; align-items: flex-end; }
    .chat-input-area textarea { flex: 1; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 20px; padding: 12px; height: 50px; resize: none; font-family: inherit; }
    .chat-input-area textarea:focus { background: #fff; border-color: #004b82; outline: none; }
    .btn-chat-send { width: 50px; height: 50px; background: #004b82; color: white; border-radius: 50%; border: none; font-size: 18px; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .btn-chat-send:hover { background: #003660; transform: scale(1.05); }
    .badge-new { background: #ef4444; color: white; padding: 1px 5px; border-radius: 4px; font-size: 10px; font-weight: bold; }
</style>

<div class="page-wrapper">

    <?php if($is_guest): ?>
        <div class="header-section">
            <h1>Contact Us</h1>
        </div>

        <div class="twin-container">
            
            <div class="white-card left-card">
                <h3>Send a Message</h3>
                <form action="" method="POST" onsubmit="this.querySelector('button').innerHTML='Sending...'">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Your name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="8" placeholder="Write your message here..." required></textarea>
                    </div>

                    <button type="submit" name="send_email" class="btn-send-msg">Send</button>
                </form>
            </div>

            <div class="white-card right-card" style="flex: 0.7; border-left: 1px solid #f0f0f0;">
                
                <div class="contact-item">
                    <h3>Contact No</h3>
                    <div class="contact-row">
                        <i class="fa-solid fa-phone"></i> +60 6-252 3000
                    </div>
                </div>

                <div class="contact-item">
                    <h3>Email Address</h3>
                    <div class="contact-row">
                        <i class="fa-regular fa-envelope"></i> help@utransport.mmu.edu.my
                    </div>
                </div>

                <div class="contact-item">
                    <h3>Click here ↓</h3>
                    <a href="https://wa.me/601114024118" target="_blank" class="action-btn btn-whatsapp">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp Us
                    </a>
                </div>

                <div class="contact-item">
                    <h3>Follow Us</h3>
                    <a href="https://www.instagram.com/u_transportsystem/" target="_blank" class="action-btn btn-insta">
                        <i class="fa-brands fa-instagram"></i> Instagram
                    </a>
                    <a href="#" class="action-btn btn-fb">
                        <i class="fa-brands fa-facebook-f"></i> Facebook
                    </a>
                </div>

            </div>
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6

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

<<<<<<< HEAD
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
=======
    <?php else: ?>
        <div class="header-section">
            <h1>Support Chat</h1>
            <p>Connected as <strong><?php echo ucfirst($current_role); ?></strong></p>
        </div>

        <div class="chat-card">
            <div class="chat-messages" id="chatMessages">
                <?php if(empty($messages)): ?>
                    <div style="text-align:center; color:#94a3b8; margin-top:50px;">
                        <i class="fa-regular fa-comments" style="font-size:40px; margin-bottom:10px;"></i>
                        <p>No messages yet. Start a conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $msg): 
                        $isMe = ($msg['sender_type'] === 'driver' || $msg['sender_type'] === 'student');
                        $isUnread = ($msg['sender_type'] === 'admin' && $msg['is_read'] == 0);
                        $time = date("h:i A", strtotime($msg['created_at']));
                    ?>
                        <div class="chat-row <?php echo $isMe ? 'me' : 'support'; ?>">
                            <div style="max-width: 100%;">
                                <div class="chat-bubble <?php echo $isMe ? 'me' : 'support'; ?>">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="chat-meta">
                                    <?php if($isUnread) echo '<span class="badge-new">NEW</span>'; ?>
                                    <span><?php echo $time; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" class="chat-input-area" onsubmit="return validateChat()">
                <textarea name="chat_message" id="chatInput" placeholder="Type your message..." required></textarea>
                <button type="submit" class="btn-chat-send"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>

        <script>
            var chatBox = document.getElementById("chatMessages");
            chatBox.scrollTop = chatBox.scrollHeight;
            document.getElementById("chatInput").addEventListener("keydown", function(e) {
                if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    if(this.value.trim() !== "") this.form.submit();
                }
            });
            function validateChat() { return document.getElementById("chatInput").value.trim() !== ""; }
        </script>
    <?php endif; ?>

</div>

<?php 
// ALERT HANDLING
if(isset($_SESSION['swal_title'])): ?>
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
<script>
    Swal.fire({
        title: '<?php echo $_SESSION['swal_title']; ?>',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#004b82'
    });
</script>
<<<<<<< HEAD
<?php unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']); endif; ?>

<?php include "footer.php"; ?>
=======
<?php 
    unset($_SESSION['swal_title'], $_SESSION['swal_msg'], $_SESSION['swal_type']);
endif; 
include "footer.php"; 
?>
>>>>>>> 0fbdbf3c16157a3b8c531d5cf390ee35f6c152e6
