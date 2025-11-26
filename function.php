<?php

/**
 * Executes an SQL query and returns the result.
 * It handles query errors automatically.
 */
function executeQuery($connection, $sql) {
    $result = mysqli_query($connection, $sql);

    // Check for query errors
    if (!$result) {
        die("SQL Query Failed: " . mysqli_error($connection));
    }

    return $result;
}

/**
 * Modern Alert Wrapper (Using Session for SweetAlert2)
 * Instead of echoing a script immediately, we save the message to the session.
 * The footer.php will handle the display with a nice animation.
 */
function alert($message){
    // Save the message into a session variable
    $_SESSION['swal_msg'] = $message;
    
    // Auto-detect type based on keywords for better icons
    // If message contains "success", it's green. Otherwise, it's red/error.
    if (stripos($message, 'success') !== false) {
        $_SESSION['swal_type'] = 'success';
        $_SESSION['swal_title'] = 'Great!';
    } else {
        $_SESSION['swal_type'] = 'error';
        $_SESSION['swal_title'] = 'Oops...';
    }
}

/**
 * JavaScript Redirect Wrapper
 * usage: redirect("login.php");
 */
function redirect($url){
    echo "<script>window.location.href='$url';</script>";
    exit;
}

?>