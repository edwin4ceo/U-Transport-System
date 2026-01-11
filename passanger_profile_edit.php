<?php
session_start();
include "db_connect.php";
include "function.php";

// Check login status
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// Handle Form Update
if(isset($_POST['update'])){
    $name  = $_POST['name'];
    $phone = $_POST['phone'];
    
    // [NEW] Get Gender from form
    $gender = $_POST['gender']; 
    
    // Update database (Including gender)
    $sql = "UPDATE students SET name=?, phone=?, gender=? WHERE student_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $phone, $gender, $student_id);
    
    if($stmt->execute()){
        // Update session name
        $_SESSION['student_name'] = $name;
        
        // Success Alert
        $_SESSION['swal_title'] = "Profile Updated";
        $_SESSION['swal_msg'] = "Your details have been saved.";
        $_SESSION['swal_type'] = "success";
        // Redirect back to profile
        redirect("passanger_profile.php");
    } else {
        alert("Error updating profile.");
    }
}

// Fetch current details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

include "header.php"; 
?>

<style>
    /* Container styling for the edit form */
    .edit-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    /* Styling for the Back button */
    .btn-back {
        display: inline-block;
        padding: 12px 30px;
        background-color: #f1f3f5; /* Light grey background */
        color: #495057;           /* Dark grey text */
        border: 1px solid #dee2e6;
        border-radius: 50px;      /* Pill shape */
        text-decoration: none;
        font-weight: bold;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .btn-back:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        color: #212529;
    }

    .btn-back i {
        margin-right: 8px;
    }

    /* Style for dropdown to match inputs */
    select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
</style>

<div class="edit-container">
    <h2 style="text-align: center; margin-bottom: 25px; color: #333;">Edit Profile</h2>

    <form action="" method="POST">
        <label>Full Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>

        <label>Student ID (Cannot be changed)</label>
        <input type="text" value="<?php echo htmlspecialchars($row['student_id']); ?>" disabled style="background: #f8f9fa; color: #6c757d; cursor: not-allowed;">

        <label>Email (Cannot be changed)</label>
        <input type="email" value="<?php echo htmlspecialchars($row['email']); ?>" disabled style="background: #f8f9fa; color: #6c757d; cursor: not-allowed;">

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars(!empty($row['phone']) ? $row['phone'] : '+60'); ?>" placeholder="+60123456789">

        <label>Gender</label>
        <select name="gender" required>
            <option value="Male" <?php if($row['gender'] == 'Male') echo 'selected'; ?>>Male</option>
            <option value="Female" <?php if($row['gender'] == 'Female') echo 'selected'; ?>>Female</option>
        </select>

        <button type="submit" name="update">Save Changes</button>
    </form>

    <div style="margin-top: 30px; text-align: center;">
        <a href="passanger_profile.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<?php include "footer.php"; ?>