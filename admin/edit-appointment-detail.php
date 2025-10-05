<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_GET['editid'])) {
    echo "<script>alert('No appointment selected');window.location.href='mac.php';</script>";
    exit();
}

$id = intval($_GET['editid']);

// Fetch appointment data by id
$sql = "SELECT * FROM tblappointment WHERE id = :id";
$query = $dbh->prepare($sql);
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();
$appointment = $query->fetch(PDO::FETCH_OBJ);

if (!$appointment) {
    echo "<script>alert('Appointment not found');window.location.href='mac.php';</script>";
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    // Only update the status from this form
    $status = $_POST['status'];

    $updateSql = "UPDATE tblappointment SET status=:status WHERE id=:id";
    $updateQuery = $dbh->prepare($updateSql);
    $updateQuery->bindParam(':status', $status, PDO::PARAM_STR);
    $updateQuery->bindParam(':id', $id, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo "<script>alert('Appointment updated successfully');window.location.href='mac.php';</script>";
        exit();
    } else {
        echo "<script>alert('Update failed. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Appointment</title>
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
                    <h3 class="page-title">Edit Appointment</h3>
                </div>
                <div class="row">
                    <div class="col-md-8 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="firstname">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlentities($appointment->firstname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="surname">Surname</label>
                                        <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlentities($appointment->surname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="date">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlentities($appointment->date); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="time">Time</label>
                                        <input type="time" class="form-control" id="time" name="time" value="<?php echo htmlentities($appointment->time); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="Pending" <?php if($appointment->status=="Pending") echo "selected"; ?>>Pending</option>
                                            <option value="Approved" <?php if($appointment->status=="Approved") echo "selected"; ?>>Approved</option>
                                            <option value="Declined" <?php if($appointment->status=="Declined") echo "selected"; ?>>Declined</option>
                                        </select>
                                    </div>
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <button type="submit" name="submit" class="btn btn-primary">Update Appointment</button>
                                    <a href="mac.php" class="btn btn-secondary">Cancel</a>
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