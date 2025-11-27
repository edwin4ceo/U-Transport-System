<?php
session_start();

session_unset();
session_destroy();

// OPTION A: Go back to role-selection page
// header("Location: index.php");

// OPTION B: Go straight to driver login
header("Location: driver_login.php");
exit;
?>
