<?php
// Include PHPMailer classes
// Make sure the path matches where you put the 'PHPMailer' folder
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (isset($_POST['send_btn'])) {

    // Create an instance of PHPMailer
    $mail = new PHPMailer(true);

    try {
        // ================= SERVER SETTINGS =================
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;           // Enable verbose debug output (uncomment for troubleshooting)
        $mail->isSMTP();                                    // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';               // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                           // Enable SMTP authentication
        
        // --- CONFIGURATION REQUIRED HERE ---
        $mail->Username   = 'your_email@gmail.com';         // SMTP username (Your Gmail)
        $mail->Password   = 'xxxx xxxx xxxx xxxx';          // SMTP password (Your 16-digit App Password)
        // -----------------------------------

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;                            // TCP port to connect to

        // ================= RECIPIENTS =================
        $mail->setFrom('your_email@gmail.com', 'U-Transport System'); // Sender info
        
        $recipientEmail = $_POST['email'];
        $mail->addAddress($recipientEmail);                 // Add a recipient

        // ================= CONTENT =================
        $mail->isHTML(true);                                // Set email format to HTML
        $mail->Subject = $_POST['subject'];                 // Email Subject
        $mail->Body    = nl2br($_POST['message']);          // Email Body (nl2br converts line breaks to HTML <br>)
        $mail->AltBody = $_POST['message'];                 // Plain text body for non-HTML mail clients

        // ================= SEND =================
        $mail->send();
        
        // Success Message
        echo "<script>
                alert('Email has been sent successfully!');
                window.location.href = 'email_request.php';
              </script>";

    } catch (Exception $e) {
        // Error Message
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    // Redirect back if accessed directly without submitting
    header("Location: email_request.php");
    exit();
}
?>