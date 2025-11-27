<?php
session_start();

include "db_connect.php";
include "function.php";

// If already logged in, redirect to dashboard
if (isset($_SESSION['driver_id'])) {
    header("Location: driver_dashboard.php");
    exit;
}

// Handle login form submit
if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['swal_title'] = "Missing Fields";
        $_SESSION['swal_msg']   = "Email and password cannot be empty.";
        $_SESSION['swal_type']  = "warning";
    } else {
        // Check driver from DB
        $stmt = $conn->prepare("SELECT driver_id, password FROM drivers WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();

                if (password_verify($password, $row['password'])) {
                    // Login success
                    $_SESSION['driver_id'] = $row['driver_id'];

                    $_SESSION['swal_title'] = "Login Successful";
                    $_SESSION['swal_msg']   = "Welcome back, driver!";
                    $_SESSION['swal_type']  = "success";

                    header("Location: driver_dashboard.php");
                    exit;
                } else {
                    // Wrong password
                    $_SESSION['swal_title'] = "Login Failed";
                    $_SESSION['swal_msg']   = "Incorrect email or password.";
                    $_SESSION['swal_type']  = "error";
                }
            } else {
                // No such driver
                $_SESSION['swal_title'] = "Login Failed";
                $_SESSION['swal_msg']   = "No driver account found with this email.";
                $_SESSION['swal_type']  = "error";
            }

            $stmt->close();
        } else {
            // SQL error
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
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus {
        border-color: #005A9C;
        box-shadow: 0 0 0 2px rgba(0, 90, 156, 0.15);
    }

    .login-actions {
        margin-top: -4px;
        margin-bottom: 8px;
        display: flex;
        justify-content: flex-end;
        font-size: 13px;
    }

    .login-actions a {
        color: #005A9C;
        text-decoration: none;
    }

    .login-actions a:hover {
        text-decoration: underline;
    }

    .btn-login {
        width: 100%;
        border: none;
        padding: 11px 14px;
        border-radius: 999px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #005A9C, #27ae60);
        color: #fff;
        margin-top: 4px;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    }

    .btn-login:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(0,0,0,0.18);
    }

    .btn-login:active {
        transform: translateY(0);
        box-shadow: 0 6px 12px rgba(0,0,0,0.18);
    }

    .login-footer-links {
        margin-top: 16px;
        font-size: 13px;
        color: #777;
    }

    .login-footer-links a {
        color: #005A9C;
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer-links a:hover {
        text-decoration: underline;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <i class="fa-solid fa-car"></i>
            </div>
            <h2>Driver Login</h2>
            <p class="login-subtitle">Sign in to manage your transport services.</p>
        </div>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Driver Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your MMU email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="login-actions">
                <a href="driver_forgot_password.php">Forgot password?</a>
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

<?php
include "footer.php";
?>
