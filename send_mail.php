function sendDriverOtpEmail(string $toEmail, string $toName, string $otp): bool
{
    $mail = new PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_GMAIL@gmail.com';
    $mail->Password   = 'YOUR_APP_PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($mail->Username, 'U-Transport');
    $mail->addAddress($toEmail, $toName ?: $toEmail);

    $mail->isHTML(true);
    $mail->Subject = 'U-Transport Driver Password Reset PIN';

    $mail->Body = "
        <p>Hello {$toName},</p>
        <p>Your password reset PIN is:</p>
        <h2>{$otp}</h2>
        <p>This PIN is valid for 10 minutes.</p>
    ";

    $mail->AltBody = "Your PIN: {$otp} (valid for 10 minutes)";
    $mail->send();
    return true;
}
