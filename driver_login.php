<?php
session_start();
include("db_connect.php");  // include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Empty validation
    if (empty($email) || empty($password)) {
        echo "<script>alert('Email or password cannot be empty'); window.location.href='driver_login.html';</script>";
        exit();
    }

    // Only allow MMU email
    if (!str_ends_with($email, "@student.edu.my")) {
        echo "<script>alert('You must use MMU email to login'); window.location.href='driver_login.html';</script>";
        exit();
    }

    // Query database
    $sql = "SELECT * FROM drivers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check account exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {
            
            // Save session
            $_SESSION['driver_id'] = $row['id'];
            $_SESSION['driver_name'] = $row['name'];
            $_SESSION['driver_email'] = $row['email'];

            echo "<script>alert('Login successful!'); window.location.href='driver_dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Incorrect password'); window.location.href='driver_login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Email not found'); window.location.href='driver_login.html';</script>";
        exit();
    }
}
?>
