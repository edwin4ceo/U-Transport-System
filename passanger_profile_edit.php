<?php
session_start();
include "db_connect.php";
include "function.php";

// 1. Check login status
if(!isset($_SESSION['student_id'])){
    redirect("passanger_login.php");
}

$student_id = $_SESSION['student_id'];

// 2. Handle Form Update
if(isset($_POST['update'])){
    $name   = $_POST['name'];
    
    // [LOGIC] Combine prefix + input
    // User types "123456789", we add "+60" for database
    $phone_input = $_POST['phone'];
    $full_phone  = "+60" . $phone_input; 
    
    $gender = $_POST['gender']; 
    
    // Update database
    $sql = "UPDATE students SET name=?, phone=?, gender=? WHERE student_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $full_phone, $gender, $student_id);
    
    if($stmt->execute()){
        $_SESSION['student_name'] = $name;
        $_SESSION['swal_title'] = "Profile Updated";
        $_SESSION['swal_msg'] = "Your details have been saved.";
        $_SESSION['swal_type'] = "success";
        redirect("passanger_profile.php");
    } else {
        alert("Error updating profile.");
    }
}

// 3. Fetch current details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

// [DISPLAY LOGIC] Remove '+60' for display in the input box
$display_phone = $row['phone'];
if (substr($display_phone, 0, 3) == '+60') {
    $display_phone = substr($display_phone, 3);
}

include "header.php"; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Main Container */
    .edit-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        font-family: sans-serif;
    }

    /* Back Button */
    .btn-back {
        display: inline-block;
        padding: 12px 30px;
        background-color: #f1f3f5;
        color: #495057;
        border: 1px solid #dee2e6;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.2s ease;
    }
    .btn-back:hover {
        background-color: #e9ecef;
        color: #212529;
    }
    .btn-back i { margin-right: 8px; }
    
    /* Labels */
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    /* Normal Inputs (Name, Email, etc) */
    .form-control {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        box-sizing: border-box; 
        font-size: 16px; /* Set font size to match */
        height: 45px;    /* Fixed height for consistency */
    }

    /* Disabled Input Style */
    input:disabled {
        background: #f8f9fa; 
        color: #6c757d; 
        cursor: not-allowed;
    }

    /* [FIXED] Phone Input Group Styling */
    .phone-input-group {
        display: flex;       /* This aligns the +60 and Input side-by-side */
        margin-bottom: 15px;
        width: 100%;
    }

    /* The +60 Box */
    .input-group-prefix {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 15px;
        background-color: #e9ecef; /* Grey background */
        border: 1px solid #ced4da;
        border-right: none;        /* REMOVE Right border so it merges with input */
        border-radius: 4px 0 0 4px; /* Round only left corners */
        color: #495057;
        font-weight: bold;
        font-size: 16px;
        height: 45px;              /* Same height as input */
    }

    /* The Phone Input Field */
    .phone-input-field {
        flex: 1;                   /* Takes up all remaining space */
        border: 1px solid #ced4da;
        border-radius: 0 4px 4px 0; /* Round only right corners */
        padding: 10px;
        font-size: 16px;
        height: 45px;              /* Same height as prefix */
        outline: none;
        box-sizing: border-box;
    }

    .phone-input-field:focus {
        border-color: #004b82;
        z-index: 1; /* Brings border on top if focused */
    }

    /* Save Button */
    .btn-save {
        width: 100%; 
        padding: 12px; 
        background: #004b82; 
        color: white; 
        border: none; 
        border-radius: 4px; 
        font-size: 16px; 
        cursor: pointer;
        font-weight: bold;
    }
    .btn-save:hover {
        background: #00365e;
    }
</style>

<div class="edit-container">
    <h2 style="text-align: center; margin-bottom: 25px; color: #333;">Edit Profile</h2>

    <form action="" method="POST" id="editProfileForm">
        
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>

        <label>Student ID (Cannot be changed)</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['student_id']); ?>" disabled>

        <label>Email (Cannot be changed)</label>
        <input type="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" disabled>

        <label>Phone Number</label>
        <div class="phone-input-group">
            <span class="input-group-prefix">+60</span>
            
            <input 
                type="text" 
                name="phone" 
                id="phoneInput" 
                class="phone-input-field"
                value="<?php echo htmlspecialchars($display_phone); ?>" 
                placeholder="123456789"
                maxlength="10" 
                inputmode="numeric"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            >
        </div>

        <label>Gender</label>
        <select name="gender" class="form-control" required>
            <option value="Male" <?php if($row['gender'] == 'Male') echo 'selected'; ?>>Male</option>
            <option value="Female" <?php if($row['gender'] == 'Female') echo 'selected'; ?>>Female</option>
        </select>

        <button type="submit" name="update" class="btn-save">Save Changes</button>
    </form>

    <div style="margin-top: 30px; text-align: center;">
        <a href="passanger_profile.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<script>
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
        const phoneInput = document.getElementById('phoneInput');
        let phone = phoneInput.value.trim();

        // 1. Check if Empty
        if (phone === "") {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Field',
                text: 'Please enter your phone number.'
            });
            return;
        }

        // 2. Check Valid Length
        if (phone.length < 9) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Number Too Short',
                text: 'Please enter a valid phone number (at least 9 digits).'
            });
            return;
        }
    });
</script>

<?php include "footer.php"; ?>