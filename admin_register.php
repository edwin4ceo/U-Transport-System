<?php
session_start(); 
require_once 'db_connect.php';
require_once 'admin_header.php';

// --- STRICT SECURITY CHECK ---
// ONLY 'admin' can access. 'staff' must be blocked.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_dashboard.php"); 
    exit();
}

$alert_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation
    if (!preg_match('/@mmu\.edu\.my$/i', $email)) {
        $alert_script = "Swal.fire({ icon: 'warning', title: 'Restricted Domain', text: 'Registration is restricted to @mmu.edu.my accounts only.', confirmButtonColor: '#f39c12' });";
    }
    elseif ($password !== $confirm_password) {
        $alert_script = "Swal.fire({ icon: 'error', title: 'Password Error', text: 'Passwords do not match.' });";
    }
    else {
        // 2. Check if email/username exists
        $check = mysqli_query($conn, "SELECT * FROM admins WHERE email = '$email' OR username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $alert_script = "Swal.fire({ icon: 'error', title: 'Duplicate Found', text: 'Username or Email already exists.' });";
        } else {
            // 3. Register New Staff
            $sql = "INSERT INTO admins (full_name, username, email, phone_number, password, role) 
                    VALUES ('$full_name', '$username', '$email', '$phone', '$password', 'staff')";

            if (mysqli_query($conn, $sql)) {
                $alert_script = "Swal.fire({ icon: 'success', title: 'Success', text: 'New Staff account created!' }).then(() => { window.location.href = 'manage_staff.php'; });";
            } else {
                $alert_script = "Swal.fire({ icon: 'error', title: 'Database Error', text: '" . mysqli_error($conn) . "' });";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Staff | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 10px; }
        .back-btn { font-size: 0.9rem; color: #6b7280; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 5px; margin-bottom: 10px; transition: 0.2s; }
        .back-btn:hover { color: #111827; }

        /* Card Style */
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
        }

        .form-title-section { text-align: center; margin-bottom: 30px; }
        .form-title-section h2 { margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 700; }
        .form-title-section p { color: #6b7280; margin-top: 5px; }

        /* Grid Layout for Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 0.9rem; }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box; /* Fix padding issue */
        }
        .form-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .full-width { grid-column: span 2; }

        /* Buttons */
        .btn-reg {
            width: 100%;
            padding: 14px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-reg:hover { background: #219150; transform: translateY(-1px); }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <main class="dashboard-container">
        
        <a href="manage_staff.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Staff List</a>

        <div class="form-card">
            <div class="form-title-section">
                <div style="width: 50px; height: 50px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5rem;">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h2>Register New Staff</h2>
                <p>Create a secure account for a new administration member.</p>
            </div>

            <form method="POST">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required placeholder="e.g. Ali Bin Abu">
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required placeholder="e.g. ali_staff">
                    </div>

                    <div class="form-group">
                        <label>Staff Email (@mmu.edu.my)</label>
                        <input type="email" name="email" required placeholder="staff@mmu.edu.my">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" required placeholder="e.g. 0123456789">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="••••••••">
                    </div>

                    <div class="full-width">
                        <button type="submit" class="btn-reg">
                            <i class="fa-solid fa-check-circle"></i> Create Account
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </main>

    <?php if(!empty($alert_script)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $alert_script; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>