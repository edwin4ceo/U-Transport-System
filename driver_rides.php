<?php
session_start();

include "db_connect.php";
include "function.php";

// 1. Security Check
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];

// ==========================================
// [FIXED] GET DRIVER'S QR CODE (Correct Path)
// ==========================================
$my_qr_code_path = "images/default_qr.png";

$stmt_qr = $conn->prepare("SELECT duitnow_qr FROM drivers WHERE driver_id = ?");
$stmt_qr->bind_param("i", $driver_id);
$stmt_qr->execute();
$res_qr = $stmt_qr->get_result();

if ($row_qr = $res_qr->fetch_assoc()) {
    $db_file = $row_qr['duitnow_qr'];
    
    if (!empty($db_file)) {
        $my_qr_code_path = "uploads/qrcodes/" . $db_file;
    }
}
$stmt_qr->close();


// --- Handle Complete Ride Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_ride_action'])) {
    $booking_id_to_complete = intval($_POST['booking_id']);
    
    $stmt = $conn->prepare("UPDATE bookings SET status = 'COMPLETED' WHERE id = ? AND driver_id = ? AND status = 'ACCEPTED'");
    $stmt->bind_param("ii", $booking_id_to_complete, $driver_id);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Payment Received!',
                    text: 'Ride marked as completed.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => { window.location.href='driver_rides.php'; });
            });
        </script>";
    }
    $stmt->close();
}

// 2. Fetch ONLY ACTIVE rides
$upcoming = [];
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.pickup_point, b.destination, b.date_time, b.passengers, b.remark, b.status, b.fare,
           s.name AS passenger_name, s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    WHERE b.driver_id = ? 
    AND b.status NOT IN ('COMPLETED', 'CANCELLED', 'REJECTED', 'FAILED')
    ORDER BY b.date_time ASC, b.id ASC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) { 
        while ($row = $result->fetch_assoc()) { 
            $upcoming[] = $row; 
        } 
    }
    $stmt->close();
}

include "header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- Styles --- */
    body { background-color: #f4f7fe; font-family: 'Poppins', sans-serif; color: #2b3674; }
    .rides-wrapper { max-width: 800px; margin: 0 auto; padding: 30px 20px 80px; }
    .page-header { margin-bottom: 25px; text-align: center; }
    .page-header h1 { font-size: 26px; font-weight: 700; color: #004b82; margin: 0; }
    .page-header p { color: #a3aed0; font-size: 14px; margin-top: 5px; }
    
    .search-box-wrapper { position: relative; margin-bottom: 30px; box-shadow: 0 10px 25px rgba(112, 144, 176, 0.08); border-radius: 30px; background: white; }
    .search-input { width: 100%; padding: 16px 25px; border: none; border-radius: 30px; font-size: 15px; color: #2b3674; background: transparent; outline: none; transition: all 0.2s; text-align: center; }
    .search-input:focus { box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1); }

    .ride-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 15px; border: 1px solid transparent; box-shadow: 0 4px 12px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .status-strip { position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
    .strip-active { background: #4318ff; }   
    .strip-pending { background: #ffce20; }  

    .card-top { display: flex; justify-content: space-between; margin-bottom: 12px; }
    .trip-date { font-size: 13px; color: #a3aed0; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    .route-text { font-size: 15px; font-weight: 600; line-height: 1.4; color: #1b2559; margin-bottom: 15px; padding-left: 10px; border-left: 2px solid #eef2f6; }
    
    .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed #eef2f6; }
    .passenger-info { font-size: 13px; font-weight: 500; color: #707eae; display: flex; align-items: center; gap: 6px; }
    .status-badge { padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-active { background: #f4f7fe; color: #4318ff; }
    .badge-pending { background: #fffbf0; color: #ffce20; }

    .action-buttons { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eef2f6; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .btn-action { border: none; padding: 10px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; }
    .btn-chat { background: #f4f7fe; color: #4318ff; }
    .btn-complete { background: linear-gradient(90deg, #4318ff 0%, #2b3674 100%); color: white; }
    
    .empty-state, #noResults { text-align: center; padding: 40px; color: #a3aed0; font-size: 14px; }
    #noResults { display: none; }
</style>

<div class="rides-wrapper">
    <div class="page-header">
        <h1>My Rides</h1>
        <p>Active & Upcoming Tasks</p>
    </div>

    <div class="search-box-wrapper">
        <input type="text" id="ridesSearchInput" class="search-input" placeholder="Search passenger, location or ID...">
    </div>

    <div id="activeList">
        <?php if (count($upcoming) === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-mug-hot" style="font-size: 40px; opacity: 0.3; margin-bottom: 15px;"></i><br>
                No active rides at the moment.<br>Relax!
            </div>
        <?php else: ?>
            <?php foreach ($upcoming as $row): ?>
                <?php
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? 'PENDING'));
                    $fare = number_format((float)$row['fare'], 2);
                    
                    if ($statusRaw === 'ACCEPTED') {
                        $stripClass = "strip-active"; $badgeClass = "badge-active";
                    } else {
                        $stripClass = "strip-pending"; $badgeClass = "badge-pending";
                    }
                    
                    $pickup = htmlspecialchars($row['pickup_point']);
                    $dest   = htmlspecialchars($row['destination']);
                    $pName  = htmlspecialchars($row['passenger_name']);
                    $routeText = $pickup . ' <i class="fa-solid fa-arrow-right-long" style="color:#a3aed0; font-size:12px; margin:0 5px;"></i> ' . $dest;
                    $searchData = strtolower("$id $pName $pickup $dest");
                ?>
                <div class="ride-card item-ride" data-search="<?php echo $searchData; ?>">
                    <div class="status-strip <?php echo $stripClass; ?>"></div>
                    <div class="card-top">
                        <div class="trip-date"><i class="fa-regular fa-calendar-alt"></i> <?php echo $datetime; ?></div>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusRaw; ?></span>
                    </div>
                    <div class="route-text"><?php echo $routeText; ?></div>
                    <div class="card-footer">
                        <div class="passenger-info">
                            <i class="fa-solid fa-user-circle"></i> <?php echo $pName; ?>
                            <span style="font-size:11px; color:#a3aed0; margin-left:5px;">(<?php echo $row['passengers']; ?> Pax)</span>
                        </div>
                        <div style="font-size:13px; font-weight:700; color:#15803d;">RM <?php echo $fare; ?></div>
                    </div>

                    <?php if ($statusRaw === 'ACCEPTED'): ?>
                        <div class="action-buttons">
                            <a href="ride_chat.php?room=<?php echo $id; ?>" class="btn-action btn-chat">
                                <i class="fa-regular fa-comments"></i> Chat
                            </a>
                            <form id="form-complete-<?php echo $id; ?>" method="POST" style="margin:0;">
                                <input type="hidden" name="booking_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="complete_ride_action" value="1">
                                <button type="button" onclick="initiatePayment(<?php echo $id; ?>, '<?php echo $fare; ?>', '<?php echo $my_qr_code_path; ?>')" class="btn-action btn-complete">
                                    Complete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div id="noResults">No rides found matching your search.</div>
</div>

<script>
document.getElementById('ridesSearchInput').addEventListener('input', function() {
    let filter = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll('.item-ride');
    let hasVisible = false;
    cards.forEach(card => {
        if (card.getAttribute('data-search').includes(filter)) {
            card.style.display = ""; hasVisible = true;
        } else { card.style.display = "none"; }
    });
    document.getElementById('noResults').style.display = (filter !== "" && !hasVisible) ? "block" : "none";
});

function initiatePayment(bookingId, fareAmount, qrPath) {
    Swal.fire({
        title: 'Select Payment Method',
        html: `Total Amount: <b style='font-size:18px; color:#15803d;'>RM ${fareAmount}</b>`,
        icon: 'info',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#4318ff', 
        denyButtonColor: '#16a34a',    
        cancelButtonColor: '#a3aed0',
        confirmButtonText: '<i class="fa-solid fa-qrcode"></i> Scan QR',
        denyButtonText: '<i class="fa-solid fa-money-bill-wave"></i> Cash',
        reverseButtons: true
    }).then((result) => {
        if (result.isDenied) {
            submitCompletion(bookingId);
        } else if (result.isConfirmed) {
            showQRCode(bookingId, fareAmount, qrPath);
        }
    });
}

function showQRCode(bookingId, fareAmount, qrImagePath) {
    Swal.fire({
        title: 'Scan to Pay',
        imageUrl: qrImagePath, 
        imageWidth: 250,
        imageHeight: 250,
        imageAlt: 'Payment QR Code',
        html: `
            <p>Please show this QR to the passenger.</p>
            <h3 style="color:#15803d; margin-top:10px;">RM ${fareAmount}</h3>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm Payment Received',
        confirmButtonColor: '#16a34a',
        cancelButtonText: 'Back',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            submitCompletion(bookingId);
        }
    });
}

function submitCompletion(bookingId) {
    document.getElementById('form-complete-' + bookingId).submit();
}
</script>

<?php include "footer.php"; ?>