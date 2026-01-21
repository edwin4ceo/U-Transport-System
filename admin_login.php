<?php
session_start();
require_once 'db_connect.php';

$alert_script = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. Validate Email Format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $alert_script = "Swal.fire({ icon: 'error', title: 'Invalid Email', text: 'Please enter a valid email.', confirmButtonColor: '#2c3e50' });";
    } 
    // 2. Validate Domain
    elseif (!preg_match('/@mmu\.edu\.my$/i', $email)) {
        $alert_script = "Swal.fire({ icon: 'warning', title: 'Restricted Domain', text: 'Admin login is restricted to @mmu.edu.my accounts only.', confirmButtonColor: '#f39c12' });";
   }
    else {
        // QUERY: Select from 'admins' table
        $sql = "SELECT * FROM admins WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            die("<div style='background:red; color:white; padding:20px;'><strong>CRITICAL DATABASE ERROR:</strong><br>" . mysqli_error($conn) . "</div>");
        }

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Check password
            if ($password === $user['password']) {
                // Login Success
                $_SESSION['user_id'] = $user['id']; 
                $_SESSION['full_name'] = $user['full_name'];
                
                // [FIXED] USE THE ROLE FROM THE DATABASE
                $_SESSION['role'] = $user['role']; 
                $_SESSION['email'] = $user['email'];
                
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $alert_script = "Swal.fire({ icon: 'error', title: 'Login Failed', text: 'Incorrect password.', confirmButtonColor: '#c0392b' });";
            }
        } else {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Access Denied', text: 'No admin account found with that email.', confirmButtonColor: '#c0392b' });";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FMD Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* MODERN CSS RESET */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); /* Deep Blue Gradient */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Glassmorphism Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        /* Top Accent Line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, #005A9C, #00C6FF);
        }

        .login-header { margin-bottom: 30px; }
        .login-header i { 
            font-size: 3.5rem; 
            background: -webkit-linear-gradient(#005A9C, #00C6FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: inline-block;
        }
        .login-header h2 { color: #1e293b; font-weight: 700; font-size: 1.5rem; margin-bottom: 5px; }
        .login-header p { color: #64748b; font-size: 0.9rem; }

        /* Form Styling */
        .form-group { margin-bottom: 20px; text-align: left; position: relative; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }
        
        .input-wrapper { position: relative; }
        .input-wrapper i.icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Space for icon */
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #005A9C;
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 90, 156, 0.1);
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: 0.3s;
        }
        .toggle-password:hover { color: #005A9C; }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #005A9C, #004170);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 90, 156, 0.3);
        }
        .btn-login:active { transform: translateY(0); }

        /* Links */
        .auth-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            font-size: 0.9rem;
        }
        .auth-links a {
            color: #005A9C;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }
        .auth-links a:hover { text-decoration: underline; color: #003056; }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <i class="fa-solid fa-user-shield"></i>
            <h2>Staff Portal Login</h2>
            <p>Secure Access for U-Transport Management</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope icon"></i>
                    <input type="email" name="email" class="form-control" placeholder="staff@mmu.edu.my" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock icon"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="auth-links">
            <a href="admin_forgot_password.php">Forgot Password?</a>
        </div>
        
        <div style="margin-top: 20px; font-size: 0.8rem; color: #94a3b8;">
            &copy; <?php echo date("Y"); ?> FMD Staff System.
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

    <?php if(!empty($alert_script)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $alert_script; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>