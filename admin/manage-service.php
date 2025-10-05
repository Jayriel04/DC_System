<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblservice WHERE number = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        echo "<script>alert('Service deleted successfully');</script>";
        echo "<script>window.location.href = 'manage-service.php'</script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Services</title>
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
                    <div class="page-header">
                        <h3 class="page-title">Manage Services</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                     
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        
                                        <a href="add-service.php" class="btn btn-primary ml-auto">Add Service</a>
                                    </div>
                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">No</th>
                                                    <th class="font-weight-bold">Service Name</th>
                                                    <th class="font-weight-bold">Description</th>
                                                    <th class="font-weight-bold">Image</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (isset($_GET['pageno'])) {
                                                    $pageno = $_GET['pageno'];
                                                } else {
                                                    $pageno = 1;
                                                }
                                                // Formula for pagination
                                                $no_of_records_per_page = 15;
                                                $offset = ($pageno - 1) * $no_of_records_per_page;

                                                $ret = "SELECT number FROM tblservice";
                                                $query1 = $dbh->prepare($ret);
                                                $query1->execute();
                                                $total_rows = $query1->rowCount();
                                                $total_pages = ceil($total_rows / $no_of_records_per_page);
                                                $sql = "SELECT * FROM tblservice LIMIT :offset, :limit";
                                                $query = $dbh->prepare($sql);
                                                $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                                                $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                $cnt = $offset + 1; // Start S.No from the correct offset
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $row) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($row->number); ?></td>
                                                        <td><?php echo htmlentities($row->name); ?></td>
                                                        <td><?php echo htmlentities($row->description); ?></td>
                                                        <td>
                                                            <img src="<?php echo htmlentities($row->image); ?>" alt="<?php echo htmlentities($row->name); ?>" style="width: 100px; height: auto;">
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <a href="edit-service.php?editid=<?php echo htmlentities($row->number); ?>" class="btn btn-info btn-xs"> Edit</a>
                                                                <a href="manage-service.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete?');" class="btn btn-danger btn-xs"> Delete</a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php
                                                    }
                                                } else {
                                                ?>
                                                    <tr>
                                                        <th colspan="5" style="color:red;">No Record Found</th>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div align="left" class="mt-4">
                                        <ul class="pagination">
                                            <li><a href="?pageno=1"><strong>First</strong></a></li>
                                            <li class="<?php if ($pageno <= 1) echo 'disabled'; ?>">
                                                <a href="<?php if ($pageno <= 1) echo '#'; else echo "?pageno=" . ($pageno - 1); ?>"><strong>Prev</strong></a>
                                            </li>
                                            <li class="<?php if ($pageno >= $total_pages) echo 'disabled'; ?>">
                                                <a href="<?php if ($pageno >= $total_pages) echo '#'; else echo "?pageno=" . ($pageno + 1); ?>"><strong>Next</strong></a>
                                            </li>
                                            <li><a href="?pageno=<?php echo $total_pages; ?>"><strong>Last</strong></a></li>
                                        </ul>
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
    <script src="js/dashboard.js"></script></body>
</html>
<?php } ?>