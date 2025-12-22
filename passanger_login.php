<?php
session_start();
include "db_connect.php";
include "function.php";

// Redirect if already logged in -> Go to Dashboard
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Check if the email exists in the database
    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        // Email found, now check password
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            // CASE A: Success
            $_SESSION['student_id'] = $row['student_id']; 
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful! Redirecting...");
            redirect("passenger_home.php"); 
        } 
        else {
            // CASE B: Wrong Password
            $_SESSION['swal_title'] = "Incorrect Password";
            $_SESSION['swal_msg'] = "The password you entered is incorrect.";
            $_SESSION['swal_type'] = "error";
            $_SESSION['swal_btn_text'] = "Try Again";
        }
    } 
    else {
        // CASE C: Email Not Found
        $_SESSION['swal_title'] = "Email Not Found";
        $_SESSION['swal_msg'] = "This email is not registered in our system.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Register Now";
        $_SESSION['swal_btn_link'] = "passanger_register.php";
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
    }
}
?>

<?php include "header.php"; ?>

<style>
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    /* Password Eye Icon Style */
    .password-wrapper {
        position: relative;
        width: 100%;
    }
    .password-wrapper input {
        width: 100%;
        padding-right: 40px; /* Space for the icon */
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 35%; /* Center vertically */
        transform: translateY(-50%);
        cursor: pointer;
        color: #7f8c8d;
        z-index: 10;
        font-size: 1.1rem;
        user-select: none; 
    }
    .toggle-password:hover { color: #005A9C; }
</style>

<h2>Login</h2>
<p>Welcome back! Please login to continue.</p>

<form action="" method="POST">
    <label>Email</label>
    <input type="email" name="email" id="emailInput" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required placeholder="Your MMU Email">

    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="passwordInput" required placeholder="Your Password">
        <i class="fa-solid fa-eye-slash toggle-password" id="eyeIcon"></i>
    </div>

    <button type="submit" name="login">Login</button>
</form>

<div style="margin-top: 15px; display: flex; justify-content: space-between;">
    <a href="passanger_register.php">Create an Account</a>
    <a href="passanger_forgot_password.php">Forgot Password?</a>
</div>

<script>
    // 1. Domain Validation on Blur (User clicks away)
    const emailInput = document.getElementById('emailInput');
    
    emailInput.addEventListener('blur', function() {
        const val = this.value;
        const requiredDomain = "@student.mmu.edu.my";

        // Only check if field is not empty
        if (val.length > 0) {
            if (!val.endsWith(requiredDomain)) {
                // Show SweetAlert Warning
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email Format',
                    text: 'Please login using your @student.mmu.edu.my email address.',
                    confirmButtonColor: '#005A9C'
                });
            }
        }
    });

    // 2. Password Toggle Function (Press and Hold)
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    function showPassword() {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }

    function hidePassword() {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    }

    // Mouse Events
    eyeIcon.addEventListener('mousedown', showPassword);
    eyeIcon.addEventListener('mouseup', hidePassword);
    eyeIcon.addEventListener('mouseleave', hidePassword);

    // Touch Events (Mobile)
    eyeIcon.addEventListener('touchstart', function(e) {
        e.preventDefault();
        showPassword();
    });
    eyeIcon.addEventListener('touchend', hidePassword);
</script>

<?php include "footer.php"; ?>