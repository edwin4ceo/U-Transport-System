<?php
session_start();
include "db_connect.php";
include "function.php";

// Check if there is registration data in session
// If not, it means the user tries to access this page directly without registering
if (!isset($_SESSION['temp_register_data'])) {
    header("Location: passanger_register.php");
    exit();
}

// Handle Verification Form Submission
if (isset($_POST['verify_btn'])) {
    $user_entered_code = $_POST['otp_input'];
    $correct_code = $_SESSION['temp_register_data']['otp_code'];

    // Check if code matches
    if ($user_entered_code == $correct_code) {
        // --- CODE MATCHED: INSERT INTO DATABASE ---
        
        $name = $_SESSION['temp_register_data']['name'];
        $sid  = $_SESSION['temp_register_data']['student_id'];
        $email = $_SESSION['temp_register_data']['email'];
        $pass = $_SESSION['temp_register_data']['password_hash']; // Already hashed in previous step

        $sql = "INSERT INTO students (name, student_id, email, password) 
                VALUES ('$name','$sid','$email','$pass')";

        if ($conn->query($sql)) {
            // Success: Clear temporary session data
            unset($_SESSION['temp_register_data']);
            
            // Set success message for SweetAlert (if used in login page)
            $_SESSION['swal_title'] = "Registration Successful!";
            $_SESSION['swal_msg'] = "Your account has been verified. Please login.";
            $_SESSION['swal_type'] = "success";
            
            // Redirect to Login Page
            redirect("passanger_login.php");
        } else {
            // Database Error
            $_SESSION['swal_title'] = "Database Error";
            $_SESSION['swal_msg'] = "Error: " . $conn->error;
            $_SESSION['swal_type'] = "error";
        }

    } else {
        // Code Mismatch
        $_SESSION['swal_title'] = "Invalid Code";
        $_SESSION['swal_msg'] = "The code you entered is incorrect. Please check your email.";
        $_SESSION['swal_type'] = "error";
    }
}
?>

<?php include "header.php"; ?>

<style>
    .verify-container {
        max-width: 450px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    .verify-container h2 {
        color: #333;
        margin-bottom: 10px;
    }
    .verify-container p {
        color: #666;
        margin-bottom: 20px;
    }
    .otp-input {
        width: 100%;
        padding: 12px;
        margin: 15px 0;
        text-align: center;
        font-size: 1.5rem;
        letter-spacing: 8px;
        border: 2px solid #ddd;
        border-radius: 6px;
    }
    .btn-verify {
        width: 100%;
        padding: 12px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-verify:hover {
        background-color: #218838;
    }
    .resend-link {
        display: block;
        margin-top: 15px;
        font-size: 0.9rem;
        color: #007bff;
        text-decoration: none;
    }
    .resend-link:hover {
        text-decoration: underline;
    }
</style>

<div class="verify-container">
    <h2>Verify Email</h2>
    <p>We have sent a 4-digit verification code to:<br> 
       <strong><?php echo htmlspecialchars($_SESSION['temp_register_data']['email']); ?></strong>
    </p>

    <form action="" method="POST">
        <label for="otp_input" style="font-weight:bold; display:block; text-align:left;">Enter Code:</label>
        <input type="number" id="otp_input" name="otp_input" class="otp-input" placeholder="0000" required>
        
        <button type="submit" name="verify_btn" class="btn-verify">Verify & Register</button>
    </form>
    
    <a href="passanger_register.php" class="resend-link">Wrong Email? Go back to Register</a>
</div>

<?php include "footer.php"; ?>