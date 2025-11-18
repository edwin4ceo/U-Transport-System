<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U-Transport | Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php"><i class="fa-solid fa-car"></i> U-Transport</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="find_ride.php">Find a Ride</a></li>
                    <li><a href="offer_ride.php">Offer a Ride</a></li>
                    
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropbtn" onclick="toggleLoginMenu()">
                            Login <i class="fa-solid fa-caret-down"></i>
                        </a>
                        
                        <div id="loginDropdown" class="dropdown-content">
                            <a href="login.php"><i class="fa-solid fa-person-walking-luggage"></i> Passenger</a>
                            <a href="login.php"><i class="fa-solid fa-car"></i> Driver</a>
                            <a href="admin_login.php" style="color: #e74c3c; border-top: 1px solid #eee;"><i class="fa-solid fa-user-shield"></i> Admin</a>
                        </div>
                    </li>

                    <li><a href="register.php" class="btn-register">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="content-area">
                <i class="fa-solid fa-route fa-4x" style="color: #005A9C; margin-bottom: 20px;"></i>
                
                <h2>Welcome to U-Transport</h2>
                <p>
                    Exclusively for MMU Melaka Campus students.<br>
                    The safest, most convenient way to share rides and save costs.
                </p>
                
                <div style="margin-top: 30px;">
                    <a href="find_ride.php" class="action-btn btn-find">
                        <i class="fa-solid fa-magnifying-glass"></i> Find a Ride
                    </a>
                    <a href="offer_ride.php" class="action-btn btn-offer">
                        <i class="fa-solid fa-car-side"></i> Offer a Ride
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> U-Transport System. All Rights Reserved.</p>
            <small>Faculty of Information Science and Technology, MMU</small>
        </div>
    </footer>

    <script>
        /* Function to toggle the menu open/close */
        function toggleLoginMenu() {
            document.getElementById("loginDropdown").classList.toggle("show");
        }

        /* Close the dropdown if the user clicks outside of it */
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.fa-caret-down')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

</body>
</html>