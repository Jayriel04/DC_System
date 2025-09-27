<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'] == 0)) {
    header('location:logout.php');
}

if (isset($_POST['add_calendar'])) {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $sql = "INSERT INTO tblcalendar (date, start_time, end_time) VALUES (:date, :start_time, :end_time)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':date', $date, PDO::PARAM_STR);
    $query->bindParam(':start_time', $start_time, PDO::PARAM_STR);
    $query->bindParam(':end_time', $end_time, PDO::PARAM_STR);
    $query->execute();
    echo "<script>alert('Calendar entry added');</script>";
    echo "<script>window.location.href = 'calendar.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Patient Management System | Add Calendar Entry</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-scroller">
    <?php include_once('includes/header.php'); ?>
    <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"> Add Calendar Entry </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page"> Add Calendar Entry</li>
                        </ol>
                    </nav>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <button type="submit" name="add_calendar" class="btn btn-primary">Add Entry</button>
                    <a href="calendar.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/misc.js"></script>
</body>
</html>