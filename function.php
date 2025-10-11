<?php

/**
 * Executes an SQL query and returns the result.
 * It handles query errors automatically.
 *
 * @param mysqli $connection The active database connection object.
 * @param string $sql The SQL query string to execute.
 * @return mysqli_result|bool The result object for SELECT queries, or true/false for other queries.
 */
function executeQuery($connection, $sql) {
    $result = mysqli_query($connection, $sql);

    // Check for query errors
    if (!$result) {
        die("SQL Query Failed: " . mysqli_error($connection));
    }

    return $result;
}


// --- EXAMPLE USAGE: Displaying all registered drivers ---

// 1. Include the necessary files
require_once 'db_connect.php';
// require_once 'functions.php'; // This line would be in a different file like 'drivers.php'

// 2. Define the SQL query
$sql = "SELECT user_id, full_name, email, vehicle_details FROM Users WHERE role = 'driver' AND verification_status = 'verified'";

// 3. Execute the query using the function
$result = executeQuery($connection, $sql);

// 4. Process and display the results
if (mysqli_num_rows($result) > 0) {
    echo "<h2>Verified Drivers</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Vehicle</th></tr>";

    // Loop through each row of the result
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['vehicle_details'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "No verified drivers found.";
}

// 5. Close the connection when you're done
mysqli_close($connection);

?>