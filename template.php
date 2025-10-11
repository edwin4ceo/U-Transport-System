<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U-Transport | Welcome</title>
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
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn-register">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container content-area">
            <h2>Welcome to the U-Transport System!</h2>
            <p>Your convenient and safe solution for campus transportation. Find a ride or offer one today.</p>
            
            <h3>Sample Form</h3>
            <form action="#" method="POST">
                <label for="destination">Where to?</label>
                <input type="text" id="destination" name="destination" placeholder="e.g., University Library">
                <button type="submit">Search Rides</button>
            </form>

        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> U-Transport System. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>