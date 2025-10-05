<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'] == 0)) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $service = $_POST['service']; 
        $status = $_POST['status']; 

        
        $sql = "INSERT INTO tblappointment (firstname, surname, date, time, service, status) VALUES (:firstname, :surname, :date, :time, :service, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $query->bindParam(':surname', $surname, PDO::PARAM_STR);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->bindParam(':time', $time, PDO::PARAM_STR);
        $query->bindParam(':service', $service, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();

        echo "<script>alert('Appointment added successfully');</script>";
        echo "<script>window.location.href = 'manage-appointment.php'</script>";
    }

    // Fetch services for the service dropdown
    $services = ["Consultation", "Follow-up", "Emergency", "Routine Checkup"];
    
    // Status options for the status dropdown
    $statuses = ["Pending", "Approved", "Declined"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Appointment</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Add Appointment</h3>
                        
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="firstname">First Name</label>
                                            <input type="text" name="firstname" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="surname">Surname</label>
                                            <input type="text" name="surname" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="date">Date</label>
                                            <input type="date" name="date" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="time">Time</label>
                                            <input type="time" name="time" class="form-control" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Add Appointment</button>
                                        <a href="manage-appointment.php" class="btn btn-secondary">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>
<?php } ?>