<?php 
session_start();
include "db_connect.php";
include "function.php";

// Check if user is logged in. If not, redirect to login.
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

// Include the Header
include "header.php"; 
?>

<h2>Welcome back, <?php echo $_SESSION['student_name']; ?>!</h2>
<p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>

<h3>Quick Search</h3>
<form action="search_transport.php" method="GET">
    <label for="destination">Where to?</label>
    <input type="text" id="destination" name="destination" placeholder="e.g., University Library">
    <button type="submit">Search Rides</button>
</form>

<?php 
// Include the Footer
include "footer.php"; 
?>