<?php
session_start();

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit;
}

$driverName = $_SESSION['driver_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">U-Transport Driver</a>

        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                Welcome, <?php echo htmlspecialchars($driverName); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">

    <!-- Dashboard Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h6 class="card-title">Pending Booking Requests</h6>
                    <h3>0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h6 class="card-title">Average Rating</h6>
                    <h3>4.8 ★</h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h6 class="card-title">Total Trips</h6>
                    <h3>12</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Tabs -->
    <ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">

        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button">
                Edit Profile
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transport-pane" type="button">
                Add Transport
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#booking-pane" type="button">
                Booking Requests
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#rating-pane" type="button">
                Ratings & Reviews
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#qa-pane" type="button">
                Q & A Forum
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#contact-pane" type="button">
                Contact Us
            </button>
        </li>

    </ul>

    <div class="tab-content">

        <!-- 1. Edit Profile -->
        <div class="tab-pane fade show active" id="profile-pane">
            <div class="card">
                <div class="card-header">Edit Profile (Driving License Included)</div>

                <div class="card-body">
                    <form action="update_profile.php" method="post" enctype="multipart/form-data">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (Read-Only)</label>
                            <input type="email" name="email" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Driving License Number</label>
                            <input type="text" name="license_number" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Upload Driving License (Image or PDF)</label>
                            <input type="file" name="license_file" class="form-control" accept=".jpg,.png,.jpeg,.pdf">
                        </div>

                        <button class="btn btn-primary">Save Profile</button>

                    </form>
                </div>
            </div>
        </div>

<!-- 2. Add Transport -->
<div class="tab-pane fade" id="transport-pane">
    <div class="card">
        <div class="card-header">Add / Update Transport</div>

        <div class="card-body">
            <form action="save_transport.php" method="post">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Category</label>
                        <select name="vehicle_category" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="MPV">MPV</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Vehicle Model</label>
                        <input type="text" name="vehicle_model" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Vehicle Plate Number</label>
                        <input type="text" name="vehicle_plate" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Destination / Area Covered</label>
                    <input type="text" name="destination" class="form-control"
                           placeholder="Example: Penang Island, USM Area" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Day Availability</label>
                        <input type="text" name="day_available" class="form-control"
                               placeholder="Example: Mon–Fri, Weekends Only" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Time Availability</label>
                        <input type="text" name="time_available" class="form-control"
                               placeholder="Example: 7AM–10AM / 5PM–9PM" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="Online Banking">Online Banking</option>
                            <option value="E-Wallet">E-Wallet (TNG / GrabPay)</option>
                        </select>
                    </div>
                </div>

                <button class="btn btn-success">Save Transport</button>

            </form>
        </div>
    </div>
</div>

        <!-- 3. Booking Requests -->
        <div class="tab-pane fade" id="booking-pane">
            <div class="card">
                <div class="card-header">Transport Booking Requests</div>
                <div class="card-body">

                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Pickup</th>
                            <th>Drop-off</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr>
                            <td>1</td>
                            <td>Student A</td>
                            <td>Hostel A</td>
                            <td>Campus</td>
                            <td>2025-11-28 08:00</td>
                            <td>RM10</td>
                            <td>
                                <form action="update_booking_status.php" method="post" class="d-inline">
                                    <input type="hidden" name="booking_id" value="1">
                                    <button name="status" value="accepted" class="btn btn-sm btn-success">Accept</button>
                                </form>

                                <form action="update_booking_status.php" method="post" class="d-inline">
                                    <input type="hidden" name="booking_id" value="1">
                                    <button name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        </tbody>

                    </table>

                </div>
            </div>
        </div>

        <!-- 4. Ratings & Reviews -->
        <div class="tab-pane fade" id="rating-pane">
            <div class="card">
                <div class="card-header">Ratings & Reviews</div>

                <div class="card-body">

                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <h6>Student A</h6>
                                <small>★★★★★</small>
                            </div>

                            <p>Very punctual and friendly driver.</p>
                            <small class="text-muted">2025-11-25</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- 5. Forum -->
        <div class="tab-pane fade" id="qa-pane">
            <div class="card">
                <div class="card-header">Q & A Forum</div>

                <div class="card-body">

                    <form action="post_forum_message.php" method="post">
                        <label class="form-label">Ask a Question or Share Information</label>
                        <textarea name="message" class="form-control mb-3" rows="3" required></textarea>
                        <button class="btn btn-primary">Post Message</button>
                    </form>

                    <hr>

                    <div class="border p-3 rounded bg-white">
                        <strong>Student B:</strong> What time is your morning pickup?<br>
                        <small class="text-muted">2025-11-23 10:30 AM</small>

                        <hr>

                        <strong><?php echo htmlspecialchars($driverName); ?>:</strong> I normally pick up at 7:15 AM.<br>
                        <small class="text-muted">2025-11-23 11:00 AM</small>
                    </div>

                </div>
            </div>
        </div>

        <!-- 6. Contact Us -->
        <div class="tab-pane fade" id="contact-pane">
            <div class="card">
                <div class="card-header">Contact Us / Feedback</div>

                <div class="card-body">
                    <form action="send_feedback.php" method="post">

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message / Feedback</label>
                            <textarea name="message" rows="4" class="form-control" required></textarea>
                        </div>

                        <button class="btn btn-primary">Submit Feedback</button>

                    </form>
                </div>
            </div>
        </div>

    </div><!-- End Tab Content -->

</div><!-- End Container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
