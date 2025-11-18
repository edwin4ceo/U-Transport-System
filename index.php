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
                        <a href="#" class="dropbtn">Login <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="login.php"><i class="fa-solid fa-person-walking-luggage"></i> Passenger</a>
                            
                            <a href="login.php"><i class="fa-solid fa-car"></i> Driver</a>
                            
                            <a href="admin_login.php" style="color: #e74c3c;"><i class="fa-solid fa-user-shield"></i> Admin</a>
                        </div>
                    </li>

                    <li><a href="register.php" class="btn-register">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container content-area" style="text-align: center; padding: 50px 20px;">
            <i class="fa-solid fa-route fa-4x" style="color: #005A9C; margin-bottom: 20px;"></i>
            <h2>Welcome to the U-Transport System!</h2>
            <p style="font-size: 1.2rem; margin-bottom: 30px;">
                Exclusively for MMU Melaka Campus students.<br>
                Your safe, convenient, and affordable solution for campus transportation.
            </p>
            
            <div style="margin-top: 30px;">
                <a href="find_ride.php" style="background-color: #005A9C; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin-right: 10px; font-weight: bold;">
                    <i class="fa-solid fa-magnifying-glass"></i> Find a Ride
                </a>
                <a href="offer_ride.php" style="background-color: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    <i class="fa-solid fa-car-side"></i> Offer a Ride
                </a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> U-Transport System. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>