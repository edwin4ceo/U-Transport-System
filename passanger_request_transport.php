<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check if user is logged in
if(!isset($_SESSION['student_id'])){
    // If not logged in, redirect to login page
    redirect("passanger_login.php");
}

// 2. Handle Form Submission
if(isset($_POST['request'])){
    $student_id   = $_SESSION['student_id'];
    $destination  = $_POST['destination'];
    $datetime     = $_POST['date_time'];
    $passengers   = $_POST['passengers'];
    $pickup       = $_POST['pickup'];
    $remark       = $_POST['remark'];

    // Basic Validation
    if(empty($destination) || empty($datetime) || empty($pickup)){
        alert("Please fill in all required fields.");
    } else {
        // 3. Insert into Database
        // Note: Ensure your database has a table named 'bookings' 
        // with columns: id, student_id, destination, date_time, passengers, pickup_point, remark, status
        $sql = "INSERT INTO bookings (student_id, destination, date_time, passengers, pickup_point, remark, status)
                VALUES ('$student_id', '$destination', '$datetime', '$passengers', '$pickup', '$remark', 'Pending')";

        if($conn->query($sql)){
            alert("Transport request submitted successfully!");
            redirect("passanger_booking_history.php"); // Redirect to history page to see status
        } else {
            alert("Error submitting request: " . $conn->error);
        }
    }
}
?>

<?php include "header.php"; ?>

<h2>Request Transport</h2>
<p>Fill in the details below to book a ride.</p>

<form action="" method="POST">
    <label>Destination</label>
    <input type="text" name="destination" required placeholder="Where do you want to go?">

    <label>Date & Time</label>
    <input type="datetime-local" name="date_time" required>

    <label>Number of Passengers</label>
    <input type="number" name="passengers" min="1" max="6" value="1" required>

    <label>Pick-up Point</label>
    <input type="text" name="pickup" required placeholder="e.g., Main Gate, Library">

    <label>Remarks (Optional)</label>
    <input type="text" name="remark" placeholder="Any special requests?">

    <button type="submit" name="request">Submit Request</button>
</form>

<?php include "footer.php"; ?>