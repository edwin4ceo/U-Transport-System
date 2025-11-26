<?php
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])){
    redirect("login.php");
}

if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    $destination  = $_POST['destination'];
    $datetime     = $_POST['date_time'];
    $passengers   = $_POST['passengers'];
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];

    $sql = "INSERT INTO bookings (student_id, destination, date_time, passengers, pickup_point, remark, status)
            VALUES ('$student_id', '$destination', '$datetime', '$passengers', '$pickup', '$remark', 'Pending')";

    if($conn->query($sql)){
        alert("Transport request submitted!");
        redirect("booking_history.php");
    } else {
        alert("Error submitting request.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Transport</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<?php include "template.php"; ?>

<div class="container">
<div class="content-area">

<h2>Request Transport</h2>

<form action="" method="POST">
    <label>Destination</label>
    <input type="text" name="destination" required>

    <label>Date & Time</label>
    <input type="datetime-local" name="date_time" required>

    <label>Number of Passengers</label>
    <input type="number" name="passengers" required>

    <label>Pick-up Point</label>
    <input type="text" name="pickup" required>

    <label>Remarks (Optional)</label>
    <input type="text" name="remark">

    <button type="submit" name="request">Submit Request</button>
</form>

</div>
</div>

</body>
</html>
