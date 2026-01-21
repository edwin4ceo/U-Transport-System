<?php
session_start();

include "db_connect.php";
include "function.php";

// Only logged-in driver can access
if (!isset($_SESSION['driver_id'])) {
    redirect("driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];
$history = [];

// Fetch bookings
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id, b.pickup_point, b.destination, b.date_time, b.passengers, b.remark, b.status, 
        b.fare, 
        s.name AS passenger_name, s.phone AS passenger_phone
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.student_id
    WHERE b.driver_id = ?
    ORDER BY b.date_time DESC, b.id DESC
");

if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) { while ($row = $result->fetch_assoc()) { $history[] = $row; } }
    $stmt->close();
}

include "header.php";
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Global Styles */
    body { background-color: #f4f7fe; font-family: 'Poppins', sans-serif; color: #2b3674; }
    
    .history-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px 20px 80px; 
    }

    /* Header Section */
    .page-header { margin-bottom: 25px; text-align: center; }
    .page-header h1 { font-size: 26px; font-weight: 700; color: #004b82; margin: 0; }
    .page-header p { color: #a3aed0; font-size: 14px; margin-top: 5px; }

    /* Search Bar */
    .search-box-wrapper {
        position: relative;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(112, 144, 176, 0.08);
        border-radius: 30px;
        background: white;
    }
    
    .search-input {
        width: 100%;
        padding: 16px 25px; 
        border: none;
        border-radius: 30px;
        font-size: 15px;
        color: #2b3674;
        background: transparent;
        outline: none;
        transition: all 0.2s;
        text-align: center; 
    }
    .search-input:focus { box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1); }

    /* Card Styling */
    .history-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid transparent;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .history-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-color: #eef2f6;
    }

    /* Status Strip */
    .status-strip { position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
    .strip-completed { background: #05cd99; } 
    .strip-cancelled { background: #ee5d50; } 
    .strip-pending { background: #ffce20; }   

    /* Card Layout */
    .card-top { display: flex; justify-content: space-between; margin-bottom: 12px; }
    .trip-date { font-size: 13px; color: #a3aed0; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    
    .route-display { margin-bottom: 15px; padding-left: 10px; border-left: 2px solid #eef2f6; }
    .route-text { font-size: 15px; font-weight: 600; line-height: 1.4; color: #1b2559; }
    
    /* [新增] Remark 样式 */
    .remark-box {
        background-color: #f8fafc;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .remark-box i { color: #004b82; margin-top: 3px; }

    .card-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 12px; border-top: 1px dashed #eef2f6;
    }
    .passenger-info { font-size: 13px; font-weight: 500; color: #707eae; display: flex; align-items: center; gap: 6px; }
    
    /* Badges */
    .status-badge {
        padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-completed { background: #e6fdf6; color: #05cd99; }
    .badge-cancelled { background: #fff5f5; color: #ee5d50; }
    .badge-pending { background: #fffbf0; color: #ffce20; }

    /* Price Tag Style */
    .price-tag {
        font-size: 16px;
        font-weight: 700;
        color: #05cd99; /* Green for money */
        text-align: right;
    }
    .price-tag.cancelled { color: #a3aed0; text-decoration: line-through; font-size: 14px; } 

    /* Empty State */
    .empty-state { text-align: center; padding: 50px 20px; color: #a3aed0; }
    .empty-state i { font-size: 40px; margin-bottom: 15px; display: block; opacity: 0.5; }
    
    /* No Results State */
    #noResults { display: none; text-align: center; padding: 40px; color: #a3aed0; }
</style>

<div class="history-wrapper">
    <div class="page-header">
        <h1>Rides History</h1>
        <p>Review your past journeys and earnings</p>
    </div>

    <div class="search-box-wrapper">
        <input type="text" id="historySearchInput" class="search-input" placeholder="Type passenger name, location or ID...">
    </div>

    <div id="historyList">
        <?php if (count($history) === 0): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                No history found. Time to hit the road!
            </div>
        <?php else: ?>
            <?php foreach ($history as $row): ?>
                <?php
                    // Data Processing
                    $id = (int)$row['booking_id'];
                    $datetime = $row['date_time'] ? date("d M, h:i A", strtotime($row['date_time'])) : "-";
                    $statusRaw = strtoupper(trim($row['status'] ?? 'PENDING'));
                    
                    // 读取 'fare' 和 'remark'
                    $priceVal = (float)($row['fare'] ?? 0);
                    $priceDisplay = number_format($priceVal, 2);
                    $remark = trim($row['remark'] ?? ''); // 获取备注

                    // Style Logic
                    if (in_array($statusRaw, ['COMPLETED', 'FINISHED'])) {
                        $stripClass = "strip-completed"; $badgeClass = "badge-completed";
                        $priceClass = ""; 
                    } elseif (in_array($statusRaw, ['CANCELLED', 'REJECTED'])) {
                        $stripClass = "strip-cancelled"; $badgeClass = "badge-cancelled";
                        $priceClass = "cancelled"; 
                    } else {
                        $stripClass = "strip-pending"; $badgeClass = "badge-pending";
                        $priceClass = "";
                    }

                    $pickup = htmlspecialchars($row['pickup_point']);
                    $dest   = htmlspecialchars($row['destination']);
                    $pName  = htmlspecialchars($row['passenger_name'] ?? 'Guest');
                    
                    $routeText = ($row['pickup_point'] && $row['destination']) 
                                 ? $pickup . ' <i class="fa-solid fa-arrow-right-long" style="color:#a3aed0; font-size:12px; margin:0 5px;"></i> ' . $dest
                                 : "Trip #$id";
                                 
                    $searchData = strtolower("$id $pName $pickup $dest $remark"); // 把备注也加入搜索关键词
                ?>
                
                <div class="history-card" data-search="<?php echo $searchData; ?>">
                    <div class="status-strip <?php echo $stripClass; ?>"></div>
                    
                    <div class="card-top">
                        <div class="trip-date"><i class="fa-regular fa-calendar-alt"></i> <?php echo $datetime; ?></div>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusRaw; ?></span>
                    </div>

                    <div class="route-display">
                        <div class="route-text"><?php echo $routeText; ?></div>
                    </div>

                    <?php if (!empty($remark) && $remark !== '-'): ?>
                        <div class="remark-box">
                            <i class="fa-regular fa-comment-dots"></i>
                            <span><?php echo htmlspecialchars($remark); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="card-footer">
                        <div class="passenger-info">
                            <i class="fa-solid fa-user-circle"></i>
                            <?php echo $pName; ?>
                            <?php if($row['passengers'] > 1): ?>
                                <span style="background:#f4f7fe; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:5px;">+<?php echo $row['passengers']-1; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: right;">
                            <div class="price-tag <?php echo $priceClass; ?>">
                                RM <?php echo $priceDisplay; ?>
                            </div>
                            <div style="font-size:10px; color:#a3aed0; margin-top:2px;">ID: #<?php echo $id; ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div id="noResults">
                <i class="fa-solid fa-magnifying-glass" style="font-size:30px; margin-bottom:15px; opacity:0.5;"></i><br>
                No results found matching "<span id="searchQueryText" style="font-weight:600;"></span>"
            </div>
            
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time Smart Search Logic
document.getElementById('historySearchInput').addEventListener('input', function() {
    let filter = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll('.history-card');
    let hasVisible = false;
    
    cards.forEach(card => {
        let searchData = card.getAttribute('data-search');
        if (searchData.includes(filter)) {
            card.style.display = "";
            hasVisible = true;
        } else {
            card.style.display = "none";
        }
    });

    const noRes = document.getElementById('noResults');
    if (noRes) {
        if (!hasVisible && filter !== "") {
            noRes.style.display = "block";
            document.getElementById('searchQueryText').innerText = this.value;
        } else {
            noRes.style.display = "none";
        }
    }
});
</script>

<?php include "footer.php"; ?>