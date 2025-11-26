<?php
// ----------- PROCESS FORM SUBMISSION -----------
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Database connection
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "utransport";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Database Connection Failed: " . $conn->connect_error);
    }

    // Get form inputs
    $name        = $_POST['name'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $license_no  = $_POST['license_no'];
    $password    = $_POST['password'];
    $confirm_pw  = $_POST['confirm_password'];

    // Validate MMU email
    if (!str_ends_with($email, "@student.mmu.edu.my")) {
        $message = "❌ You must register using your MMU student email.";
    }
    // Check password match
    elseif ($password !== $confirm_pw) {
        $message = "❌ Passwords do not match.";
    }
    else {
        // Encrypt password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $sql = "INSERT INTO drivers (name, email, phone, license_no, password) 
                VALUES ('$name', '$email', '$phone', '$license_no', '$hashed')";

        if ($conn->query($sql) === TRUE) {
            $message = "✅ Registration Successful! You may now login.";
        } else {
            $message = "❌ Error: Email already used or invalid data.";
        }
