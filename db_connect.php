<?php

// Define database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your MySQL password, often empty in a local XAMPP/WAMP setup
define('DB_NAME', 'u_transport_db'); // The database name you created

// Create a database connection
$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if the connection was successful
if (!$connection) {
    // If connection fails, stop the script and display an error message.
    die("Database connection failed: " . mysqli_connect_error());
}

// Optional: Set the character set to utf8mb4 for full Unicode support
mysqli_set_charset($connection, "utf8mb4");

?>