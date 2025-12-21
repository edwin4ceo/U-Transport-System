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
    // We receive the combined code from the HIDDEN input field named 'otp_input'
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
            
            // Set success message for SweetAlert
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
        margin: 80px auto;
        padding: 40px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); /* Nicer shadow */
        text-align: center;
        font-family: 'Arial', sans-serif;
    }
    .verify-container h2 {
        color: #333;
        margin-bottom: 10px;
        font-weight: 700;
    }
    .verify-container p {
        color: #666;
        margin-bottom: 30px;
        font-size: 14px;
        line-height: 1.6;
    }

    /* --- NEW OTP STYLES --- */
    .otp-field {
        display: flex;
        justify-content: center;
        gap: 15px; /* Spacing between boxes */
        margin-bottom: 30px;
    }

    .otp-field input {
        width: 50px;
        height: 55px;
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        border: 2px solid #ddd;
        border-radius: 8px;
        background: #f9f9f9;
        transition: all 0.2s ease;
        outline: none;
        color: #333;
    }

    /* Active state (when user clicks) */
    .otp-field input:focus {
        border-color: #28a745;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
        background: #fff;
    }

    /* Hide the spinner arrows for number input */
    .otp-field input::-webkit-outer-spin-button,
    .otp-field input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .otp-field input[type=number] {
        -moz-appearance: textfield; /* Firefox */
    }

    /* Button Style */
    .btn-verify {
        width: 100%;
        padding: 14px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-verify:hover {
        background-color: #218838;
    }
    .resend-link {
        display: block;
        margin-top: 20px;
        font-size: 0.9rem;
        color: #007bff;
        text-decoration: none;
    }
    .resend-link:hover {
        text-decoration: underline;
    }
</style>

<div class="verify-container">
    <h2>Verify Your Account</h2>
    <p>We have sent a 4-digit verification code to:<br> 
       <strong style="color:#333; font-size:16px;"><?php echo htmlspecialchars($_SESSION['temp_register_data']['email']); ?></strong>
    </p>

    <form action="" method="POST" id="otpForm">
        <input type="hidden" name="otp_input" id="full_otp_input">

        <div class="otp-field">
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
            <input type="number" class="otp-box" maxlength="1" required>
        </div>
        
        <button type="submit" name="verify_btn" class="btn-verify">Verify Code</button>
    </form>
    
    <a href="passanger_register.php" class="resend-link">Wrong Email? Go Back</a>
</div>

<script>
    const inputs = document.querySelectorAll(".otp-box");
    const hiddenInput = document.getElementById("full_otp_input");
    const form = document.getElementById("otpForm");

    inputs.forEach((input, index) => {
        // 1. Handle Input (Auto-jump to next)
        input.addEventListener("input", (e) => {
            // Ensure only 1 digit is entered per box
            if (input.value.length > 1) {
                input.value = input.value.slice(0, 1);
            }

            // If user typed a number, move to next box
            if (input.value.length === 1) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
            
            // Update the hidden input value
            updateHiddenInput();
        });

        // 2. Handle Backspace (Jump to previous)
        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && input.value === "") {
                if (index > 0) {
                    inputs[index - 1].focus();
                }
            }
        });
    });

    // Function to combine all 4 boxes into the hidden input
    function updateHiddenInput() {
        let code = "";
        inputs.forEach((input) => {
            code += input.value;
        });
        hiddenInput.value = code;
    }

    // Ensure hidden input is filled before submit (just in case)
    form.addEventListener("submit", (e) => {
        updateHiddenInput();
    });
</script>

<?php include "footer.php"; ?>