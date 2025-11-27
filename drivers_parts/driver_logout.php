<?php
session_start();

// remove all session variables
session_unset();

// destroy the session
session_destroy();

// redirect to index page (or login page if no index.php)
header("Location: index.php");
exit;
?>
