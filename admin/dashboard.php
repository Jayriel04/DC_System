<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="d-sm-flex align-items-baseline report-summary-header">
                                                <h5 class="font-weight-semibold">Report Summary</h5>
                                                <span class="ml-auto">Updated Report</span>
                                                <button class="btn btn-icons border-0 p-2"><i class="icon-refresh"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row report-inner-cards-wrapper">
                                        <div class="col-md-6 col-xl report-inner-card">
                                            <div class="inner-card-text">
                                                <?php 
                                                $sql1 = "SELECT * FROM tblinventory";
                                                $query1 = $dbh->prepare($sql1);
                                                $query1->execute();
                                                $totinventory = $query1->rowCount();
                                                ?>
                                                <span class="report-title">Total Inventory Items</span>
                                                <h4><?php echo htmlentities($totinventory); ?></h4>
                                                <a href="manage-inventory.php"><span class="report-count"> View Inventory</span></a>
                                            </div>
                                            <div class="inner-card-icon bg-success">
                                                <i class="icon-book-open"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl report-inner-card">
                                            <div class="inner-card-text">
                                                <?php 
                                                $sql2 = "SELECT * FROM tblappointment";
                                                $query2 = $dbh->prepare($sql2);
                                                $query2->execute();
                                                $totapp = $query2->rowCount();
                                                ?>
                                                <span class="report-title">Total Appointments</span>
                                                <h4><?php echo htmlentities($totapp); ?></h4>
                                                <a href="manage-appointment.php"><span class="report-count"> View Appointments</span></a>
                                            </div>
                                            <div class="inner-card-icon bg-danger">
                                                <i class="icon-user"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl report-inner-card">
                                            <div class="inner-card-text">
                                                <?php 
                                                $sql3 = "SELECT * FROM tblcalendar"; // Modified from tblnotice to tblcalendar
                                                $query3 = $dbh->prepare($sql3);
                                                $query3->execute();
                                                $totcalendar = $query3->rowCount(); // Updated variable name
                                                ?>
                                                <span class="report-title">Total Events</span> <!-- Updated title -->
                                                <h4><?php echo htmlentities($totcalendar); ?></h4>
                                                <a href="calendar.php"><span class="report-count"> View Events</span></a> <!-- Updated link -->
                                            </div>
                                            <div class="inner-card-icon bg-warning">
                                                <i class="icon-doc"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl report-inner-card">
                                            <div class="inner-card-text">
                                                <?php 
                                                $sql4 = "SELECT * FROM tblservice";  
                                                $query4 = $dbh->prepare($sql4);
                                                $query4->execute();
                                                $totservice = $query4->rowCount();  
                                                ?>
                                                <span class="report-title">Total Services</span>
                                                <h4><?php echo htmlentities($totservice); ?></h4>
                                                <a href="manage-service.php"><span class="report-count"> View Services</span></a>
                                            </div>
                                            <div class="inner-card-icon bg-primary">
                                                <i class="icon-doc"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="row report-inner-cards-wrapper">
                                        <div class="col-md-3 col-xs report-inner-card">
                                            <div class="inner-card-text">
                                                <?php 
                                                $sql5 = "SELECT * FROM tblpatient";  
                                                $query5 = $dbh->prepare($sql5);
                                                $query5->execute();
                                                $totalpatients = $query5->rowCount();
                                                ?>
                                                <span class="report-title">Total Patients</span>
                                                <h4><?php echo htmlentities($totalpatients); ?></h4>
                                                <a href="manage-patient.php"><span class="report-count"> View Patients</span></a>
                                            </div>
                                            <div class="inner-card-icon bg-success">
                                                <i class="icon-user"></i>
                                            </div>
                                        </div>
                                    </div>
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
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
<?php } ?>