<?php
session_start();
include "db_connect.php";
include "function.php";

if(!isset($_SESSION['student_id'])){
    redirect("login.php");
}

$student_id = $_SESSION['student_id'];
$result = $conn->query("SELECT * FROM bookings WHERE student_id='$student_id' ORDER BY date_time DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking History</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<?php include "template.php"; ?>

<div class="container">
<div class="content-area">

<h2>My Booking History</h2>

<table border="1" width="100%" cellpadding="10">
    <tr>
        <th>Destination</th>
        <th>Date & Time</th>
        <th>Passengers</th>
        <th>Pick-up Point</th>
        <th>Status</th>
    </tr>

    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?=$row['destination']?></td>
        <td><?=$row['date_time']?></td>
        <td><?=$row['passengers']?></td>
        <td><?=$row['pickup_point']?></td>
        <td><?=$row['status']?></td>
    </tr>
    <?php } ?>
</table>

</div>
</div>

</body>
</html>
