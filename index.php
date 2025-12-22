<?php 
// Start session to check login status
session_start();
include "function.php"; // For redirect function

// 1. If Passenger is logged in, send them to Passenger Home
if(isset($_SESSION['student_id'])){
    redirect("passenger_home.php");
}

// 2. If Driver is logged in, send them to Driver Dashboard
if(isset($_SESSION['driver_id'])){
    redirect("driver_dashboard.php");
}

// Include the Header (Header will start session too, but that's fine)
include "header.php"; 
?>

<style>
    /* Force Footer to bottom for this short page */
    footer {
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    /* Style for the selection cards */
    .selection-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-top: 50px;
        gap: 20px;
    }

    .role-card {
        background-color: #fff;
        border: 2px solid #ddd;
        border-radius: 12px;
        padding: 30px;
        width: 100%;
        max-width: 400px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        text-decoration: none; /* Remove underline from links */
        color: inherit;
    }

    .role-card:hover {
        border-color: #005A9C; /* Primary Blue */
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,90,156,0.2);
    }

    .role-card h3 {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .role-card i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #005A9C;
    }
    
    .role-card.driver:hover {
        border-color: #27ae60; /* Green for Driver */
    }
    .role-card.driver i {
        color: #27ae60;
    }
</style>

<div style="text-align: center; margin-bottom: 30px;">
    <h2>Are you a Passenger or a Driver?</h2>
    <p style="color: #666;">Choose your mode to continue.</p>
</div>

<div class="selection-container">
    
    <a href="passanger_login.php" class="role-card">
        <i class="fa-solid fa-user"></i>
        <h3>Passenger</h3>
    </a>

    <a href="driver_login.php" class="role-card driver">
        <i class="fa-solid fa-car"></i>
        <h3>Driver</h3>
    </a>

</div>

<?php 
// Include the Footer
include "footer.php"; 
?>