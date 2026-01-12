<?php
session_start();
require_once 'db_connect.php';
// Use the new header
require_once 'admin_header.php';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing_id'])) {
    $listing_id = intval($_POST['delete_listing_id']);
    mysqli_query($conn, "DELETE FROM transportlistings WHERE listing_id = $listing_id");
    echo "<script>Swal.fire('Deleted!', 'Listing removed.', 'success');</script>";
}

// LOGIC FIX: Join 'drivers' table
$sql = "SELECT t.*, d.full_name FROM transportlistings t JOIN drivers d ON t.driver_id = d.driver_id ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<main class="dashboard-container">
    <div class="container">
        <h2><i class="fa-solid fa-list"></i> Transport Listings</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%; background:white; border-collapse:collapse;">
                <thead><tr style="background:#2c3e50; color:white;"><th style="padding:10px;">Driver</th><th style="padding:10px;">Route</th><th style="padding:10px;">Time</th><th style="padding:10px;">Price</th><th style="padding:10px;">Action</th></tr></thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($row['origin'] . ' -> ' . $row['destination']); ?></td>
                        <td style="padding:10px;"><?php echo date('d M, h:i A', strtotime($row['departure_time'])); ?></td>
                        <td style="padding:10px;">RM <?php echo $row['price']; ?></td>
                        <td style="padding:10px;">
                            <form method="POST" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="delete_listing_id" value="<?php echo $row['listing_id']; ?>">
                                <button type="submit" style="background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body></html>