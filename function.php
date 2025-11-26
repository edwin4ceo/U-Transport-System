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
 * JavaScript Alert Wrapper
 * usage: alert("Message here");
 */
function alert($message){
    // Escape single quotes to prevent breaking the JS string
    $safe_message = addslashes($message);
    echo "<script>alert('$safe_message');</script>";
}

/**
 * JavaScript Redirect Wrapper
 * usage: redirect("login.php");
 * Note: We use JS redirect instead of PHP header() because
 * you are using alert() before redirecting.
 */
function redirect($url){
    echo "<script>window.location.href='$url';</script>";
    exit;
}

?>