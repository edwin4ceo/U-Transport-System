<?php

// Define database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // MySQL password
define('DB_NAME', 'u_transport'); // The database name

// Create a database connection
// FIX: Changed variable name from $connection to $conn to match other files
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if the connection was successful
if (!$conn) {
    // If connection fails, stop the script and display an error message.
    die("Database connection failed: " . mysqli_connect_error());
}

// Optional: Set the character set to utf8mb4 for full Unicode support
mysqli_set_charset($conn, "utf8mb4");

?>