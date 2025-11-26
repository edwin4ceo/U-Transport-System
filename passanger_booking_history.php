<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check if user is logged in
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// 2. Fetch bookings for the logged-in student, ordered by newest first
$sql = "SELECT * FROM bookings WHERE student_id='$student_id' ORDER BY date_time DESC";
$result = $conn->query($sql);
?>

<?php include "header.php"; ?>

<h2>My Booking History</h2>
<p>Here you can track the status of your transport requests.</p>

<div style="overflow-x:auto;">
    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse; margin-top: 20px; border-color: #ddd;">
        <tr style="background-color: #f2f2f2; text-align: left;">
            <th>Destination</th>
            <th>Date & Time</th>
            <th>Passengers</th>
            <th>Pick-up Point</th>
            <th>Remark</th>
            <th>Status</th>
        </tr>

        <?php 
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) { 
                // Determine color based on status
                $statusColor = 'black'; // Default
                if($row['status'] == 'Pending') $statusColor = '#d35400'; // Orange
                if($row['status'] == 'Approved') $statusColor = '#27ae60'; // Green
                if($row['status'] == 'Completed') $statusColor = '#2980b9'; // Blue
                if($row['status'] == 'Rejected' || $row['status'] == 'Cancelled') $statusColor = '#c0392b'; // Red
                
                // Format Date nicely
                $formattedDate = date("d M Y, h:i A", strtotime($row['date_time']));
        ?>
        <tr style="border-bottom: 1px solid #ddd;">
            <td><?php echo $row['destination']; ?></td>
            <td><?php echo $formattedDate; ?></td>
            <td><?php echo $row['passengers']; ?></td>
            <td><?php echo $row['pickup_point']; ?></td>
            <td><?php echo $row['remark']; ?></td>
            <td style="color: <?php echo $statusColor; ?>; font-weight: bold;">
                <?php echo $row['status']; ?>
            </td>
        </tr>
        <?php 
            } 
        } else {
            echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No booking history found.</td></tr>";
        }
        ?>
    </table>
</div>

<?php include "footer.php"; ?>