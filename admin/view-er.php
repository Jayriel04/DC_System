<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Get patient ID from URL
$stdid = '';
if (isset($_GET['stid'])) {
    $stdid = intval($_GET['stid']);
} elseif (isset($_GET['number'])) {
    $stdid = intval($_GET['number']);
}

if (empty($stdid)) {
    echo "<p style='color:red'>No patient selected.</p>";
    exit();
}

// Fetch patient info
$sql = "SELECT * FROM tblpatient WHERE number = :num";
$query = $dbh->prepare($sql);
$query->bindParam(':num', $stdid, PDO::PARAM_INT);
$query->execute();
$row = $query->fetch(PDO::FETCH_OBJ);

if (!$row) {
    echo "<p style='color:red'>Patient not found.</p>";
    exit();
}

// Fetch health conditions for this patient
$health_arr = [];
if (!empty($row->health_conditions) && $row->health_conditions !== 'null' && $row->health_conditions !== '[]') {
    $decoded = json_decode($row->health_conditions, true);
    if (is_array($decoded)) {
        $health_arr = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Patient Details</title>
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
<div class="container mt-4">
    <?php if (isset($_SESSION['modal_success'])) { ?>
        <div class="container px-3 mt-3">
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_SESSION['modal_success']); ?></div>
        </div>
        <?php unset($_SESSION['modal_success']);
    } ?> 
    

    <!-- Health Conditions Table -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Health Conditions</div>
        <div class="card-body">
            <?php if (!empty($health_arr)) { ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Values</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($health_arr as $cat => $vals) {
                                if (is_array($vals)) {
                                    $vals_disp = implode(', ', $vals);
                                } else {
                                    $vals_disp = (string) $vals;
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat); ?></td>
                                    <td><?php echo htmlspecialchars($vals_disp); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p>No health information on file for this patient.</p>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>