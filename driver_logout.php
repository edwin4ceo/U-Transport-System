<?php
session_start();

// Remove all session data
session_unset();
session_destroy();

// Redirect to driver login page after logout
header("Location: driver_login.php");
exit;
?>
