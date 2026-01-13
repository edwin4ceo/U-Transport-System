<?php
session_start();
include "db_connect.php";
include "function.php"; // Ensure this includes your redirect function

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit;
}

$driver_id = $_SESSION['driver_id'];
$message = "";

// --- Handle Image Upload ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['qr_image'])) {
    $target_dir = "uploads/qrcodes/";
    
    // Create folder if it doesn't exist
    if (!is_dir($target_dir)) { 
        mkdir($target_dir, 0777, true); 
    }

    $file_ext = pathinfo($_FILES["qr_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = "qr_" . $driver_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $target_file)) {
        // Update filename in database
        $stmt = $conn->prepare("UPDATE drivers SET duitnow_qr = ? WHERE driver_id = ?");
        $stmt->bind_param("si", $new_filename, $driver_id);
        if ($stmt->execute()) {
            $message = "<div class='alert-msg success'>QR Code updated successfully!</div>";
        } else {
            $message = "<div class='alert-msg error'>Database update failed.</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert-msg error'>Failed to upload file to server. Check folder permissions.</div>";
    }
}

// --- Fetch Current QR ---
$stmt = $conn->prepare("SELECT duitnow_qr FROM drivers WHERE driver_id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$res = $stmt->get_result();
$current_qr = ($res && $row = $res->fetch_assoc()) ? $row['duitnow_qr'] : null;
$stmt->close();

include "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root { --primary: #004b82; --text-main: #1a202c; --text-light: #718096; }
    body { background: #f8f9fc; font-family: 'Inter', sans-serif; }
    .settings-container { max-width: 600px; margin: 40px auto; padding: 20px; }
    .modern-card { background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; }
    
    .qr-preview-box { 
        width: 220px; height: 220px; background: #f7fafc; 
        border: 2px dashed #cbd5e0; border-radius: 12px; 
        margin: 20px auto; display: flex; align-items: center; 
        justify-content: center; overflow: hidden;
    }
    .qr-preview-box img { width: 100%; height: 100%; object-fit: cover; }
    .no-qr { color: #a0aec0; font-size: 14px; font-weight: 500; }

    .file-input-group { margin: 25px 0; text-align: left; }
    input[type="file"] { width: 100%; padding: 10px; background: #edf2f7; border-radius: 8px; font-size: 14px; }

    /* The Save Button */
    .btn-save-qr { 
        background: var(--primary); color: white; border: none; 
        padding: 12px 30px; border-radius: 10px; font-weight: 600; 
        cursor: pointer; width: 100%; font-size: 16px; transition: 0.2s;
    }
    .btn-save-qr:hover { background: #003a66; transform: translateY(-2px); }

    .alert-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
    .error { background: #fed7d7; color: #822727; border: 1px solid #feb2b2; }
</style>

<div class="settings-container">
    <div class="modern-card">
        <i class="fa-solid fa-qrcode" style="font-size: 40px; color: var(--primary); margin-bottom: 15px;"></i>
        <h2 style="margin: 0; color: var(--text-main);">Payment Settings</h2>
        <p style="color: var(--text-light); font-size: 14px; margin-top: 10px;">Upload your DuitNow QR so students can pay you directly.</p>

        <?php echo $message; ?>

        <form action="driver_payment_settings.php" method="POST" enctype="multipart/form-data">
            <div class="qr-preview-box">
                <?php if ($current_qr): ?>
                    <img src="uploads/qrcodes/<?php echo htmlspecialchars($current_qr); ?>" alt="My QR Code">
                <?php else: ?>
                    <span class="no-qr">No QR Uploaded</span>
                <?php endif; ?>
            </div>

            <div class="file-input-group">
                <label style="font-size: 14px; font-weight: 600; display: block; margin-bottom: 8px;">Select QR Image:</label>
                <input type="file" name="qr_image" accept="image/*" required>
            </div>

            <button type="submit" class="btn-save-qr">
                <i class="fa-solid fa-cloud-arrow-up"></i> Save QR Code
            </button>
        </form>

        <a href="driver_dashboard.php" style="display: block; margin-top: 20px; color: var(--text-light); text-decoration: none; font-size: 13px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include "footer.php"; ?>