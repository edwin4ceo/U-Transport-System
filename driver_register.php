<?php
session_start();
// 确保 db_connect.php 和 function.php 路径正确
include "db_connect.php"; 
include "function.php";

// 处理提交的注册表单
if(isset($_POST['register'])){
    $name               = $_POST['name'];
    // 更改字段：Student ID -> IC/Passport Number 或 License ID
    $identification_id  = $_POST['identification_id']; 
    $email              = $_POST['email'];
    $car_model          = $_POST['car_model']; 
    $car_plate_number   = $_POST['car_plate_number']; 
    $password           = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1. **(已移除)** 邮箱域名验证
    // 假设司机可以使用任何邮箱，因此移除了 @student.mmu.edu.my 的限制。
    // 如果您需要其他限制，请告诉我。
    
    // 2. 检查邮箱是否重复 (操作 'drivers' 表)
    // 假设 drivers 表中也需要储存 email 字段
    $check = $conn->query("SELECT * FROM drivers WHERE email='$email'"); 
    if($check->num_rows > 0){
        // 自定义错误提示
        $_SESSION['swal_title'] = "Registration Failed";
        $_SESSION['swal_msg'] = "This email is already registered. Please login instead.";
        $_SESSION['swal_type'] = "warning";
        $_SESSION['swal_btn_text'] = "Login Now";
        $_SESSION['swal_btn_link'] = "driver_login.php"; // 应该重定向到司机登录页
        $_SESSION['swal_show_cancel'] = true;
        $_SESSION['swal_cancel_text'] = "Try Again";
        redirect("driver_register.php");
    }

    // 3. 插入新的司机信息 (操作 'drivers' 表)
    $sql = "INSERT INTO drivers (
                name, 
                identification_id, 
                email, 
                password, 
                car_model, 
                car_plate_number
            ) 
            VALUES (
                '$name',
                '$identification_id',
                '$email',
                '$password',
                '$car_model',
                '$car_plate_number'
            )";

    if($conn->query($sql)){
        // 成功：设置“恭喜”消息用于登录页
        $_SESSION['swal_title'] = "Congratulations!";
        $_SESSION['swal_msg'] = "Driver Registration Successful! Please login to continue.";
        $_SESSION['swal_type'] = "success";
        
        // 重定向到司机登录页
        redirect("driver_login.php"); 
    } else {
        // 确保您的 drivers 表结构与这些字段匹配
        alert("Driver Registration failed: " . $conn->error);
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
</style>

<h2>Register (U-Transport Driver)</h2>
<p>Create your account to offer rides and earn money.</p>

<form action="" method="POST">
    <label>Full Name</label>
    <input type="text" name="name" required placeholder="Enter your full name">

    <label>IC / Passport Number</label>
    <input type="text" name="identification_id" required placeholder="e.g. 901020-04-5678 or A12345678">

    <label>Email Address</label>
    <input type="email" name="email" required placeholder="your.email@example.com">

    <label>Car Model</label>
    <input type="text" name="car_model" required placeholder="e.g. Perodua Myvi">
    
    <label>Car Plate Number</label>
    <input type="text" name="car_plate_number" required placeholder="e.g. WAA 1234 X">

    <label>Password</label>
    <input type="password" name="password" required placeholder="Create a password">

    <button type="submit" name="register">Register</button>
</form>

<div style="margin-top: 15px;">
    <p>Already have an account? <a href="driver_login.php">Login here</a>.</p>
</div>

<?php include "footer.php"; ?>