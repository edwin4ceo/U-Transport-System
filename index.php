<?php 
// Include the Header (Navigation Bar)
include "header.php"; 
?>

<h2>Welcome to the U-Transport System!</h2>
<p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>

<h3>Quick Search</h3>
<form action="search_transport.php" method="GET">
    <label for="destination">Where to?</label>
    <input type="text" id="destination" name="destination" placeholder="e.g., University Library">
    <button type="submit">Search Rides</button>
</form>

<?php 
// Include the Footer (Copyright)
include "footer.php"; 
?>