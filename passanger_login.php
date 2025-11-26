<?php
session_start();
include "db_connect.php";
include "function.php";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM students WHERE email='$email'");

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['student_name'] = $row['name'];

            alert("Login successful!");
            redirect("request_transport.php");
        } 
        else {
            alert("Incorrect password.");
        }
    } 
    else {
        alert("Email not found.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - U-Transport</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<?php include "template.php"; ?>

<div class="container">
<div class="content-area">

<h2>Login</h2>

<form action="" method="POST">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" name="login">Login</button>
</form>

</div>
</div>
</body>
</html>
