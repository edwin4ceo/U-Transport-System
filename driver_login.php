<?php
session_start();

include "db_connect.php";
include "function.php";

if (isset($_SESSION['driver_id'])) {
    redirect("driver_dashboard.php");
}

if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Email and password cannot be empty.";
        $_SESSION['swal_type']  = "warning";
    } 
    else {
        $stmt = $conn->prepare("SELECT driver_id, password FROM drivers WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();

                if (password_verify($password, $row['password'])) {

                    $_SESSION['driver_id'] = $row['driver_id'];

                    $_SESSION['swal_title'] = "Login Successful";
                    $_SESSION['swal_msg']   = "Welcome back, driver!";
                    $_SESSION['swal_type']  = "success";

                    redirect("driver_dashboard.php");
                } 
                else {
                    $_SESSION['swal_title'] = "Login Failed";
                    $_SESSION['swal_msg']   = "Incorrect email or password.";
                    $_SESSION['swal_type']  = "error";
                }
            } 
            else {
                $_SESSION['swal_title'] = "Login Failed";
                $_SESSION['swal_msg']   = "No driver account found with this email.";
                $_SESSION['swal_type']  = "error";
            }

            $stmt->close();
        } 
        else {
            $_SESSION['swal_title'] = "Error";
            $_SESSION['swal_msg']   = "Database error. Please try again later.";
            $_SESSION['swal_type']  = "error";
        }
    }
}

include "header.php";
?>

<style>
    body {
        background: #f5f7fb;
    }
    .login-wrapper {
        min-height: calc(100vh - 140px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 15px;
    }
    .login-card {
        background-color: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        max-width: 420px;
        width: 100%;
        padding: 30px 28px 26px;
        text-align: center;
        border: 1px solid #e0e0e0;
    }
    .login-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid #27ae60;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 28px;
        color: #27ae60;
    }
    .login-card h2 {
        margin: 0;
        font-size: 24px;
        color: #005A9C;
        font-weight: 700;
    }
    .login-subtitle {
        margin-top: 6px;
        margin-bottom: 22px;
        color: #666;
        font-size: 14px;
    }
    .form-group {
        text-align: left;
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        margin-bottom: 6px;
        color: #333;
        font-weight: 500;
    }
    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
    }
    .form-group input:focus {
        border-color: #005A9C;
        box-shadow: 0 0 0 2px rgba(0, 90, 156, 0.15);
    }
    .login-actions {
        text-align: right;
        font-size: 13px;
        margin-bottom: 8px;
    }
    .btn-login {
        width: 100%;
        padding: 11px 14px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(135deg, #005A9C, #27ae60);
        color: white;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
        transition: 0.1s;
    }
    .btn-login:hover {
        transform: translateY(-1px);
    }

    .login-footer-links {
        margin-top: 16px;
        font-size: 13px;
        color: #777;
    }
    .login-footer-links a {
        color: #005A9C;
        text-decoration: none;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-icon">
            <i class="fa-solid fa-car"></i>
        </div>
        <h2>Driver Login</h2>
        <p class="login-subtitle">Sign in to manage your transport services.</p>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Driver Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn-login">
                Login as Driver
            </button>
        </form>

        <div class="login-footer-links">
            New driver? <a href="driver_register.php">Create an account</a>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>
