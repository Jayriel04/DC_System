<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Initialize search variable
    $search = '';

    // Handle the search
    if (isset($_POST['search'])) {
        $search = trim($_POST['search']);
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblpatient WHERE number = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        echo "<script>alert('Data deleted');</script>";
        echo "<script>window.location.href = 'manage-patient.php'</script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Patient</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Patients</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <a href="add-patient.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">Add Patient</a>
                                    </div>
                                    <form method="POST" class="form-inline mb-4">
                                        <div class="form-group mr-3">
                                            <input type="text" class="form-control" name="search" placeholder="Search by First Name or Surname" value="<?php echo htmlentities($search); ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>

                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">No</th>
                                                    <th class="font-weight-bold">First Name</th>
                                                    <th class="font-weight-bold">Surname</th>
                                                    <th class="font-weight-bold">Date of Birth</th>
                                                    <th class="font-weight-bold">Age</th>
                                                    <th class="font-weight-bold">Sex</th>
                                                    <th class="font-weight-bold">Status</th>
                                                    <th class="font-weight-bold">Contact Number</th>
                                                    <th class="font-weight-bold">Address</th>
                                                    <th class="font-weight-bold">Occupation</th>
                                                    <th class="font-weight-bold">Patient History</th>
                                                    <th class="font-weight-bold">Medical Record</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Pagination setup
                                                $pageno = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
                                                $no_of_records_per_page = 15;
                                                $offset = ($pageno - 1) * $no_of_records_per_page;

                                                // Build the query based on search
                                                $sql = "SELECT * FROM tblpatient WHERE 1=1";
                                                if ($search) {
                                                    $sql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                                                }
                                                $sql .= " LIMIT :offset, :limit";

                                                $query = $dbh->prepare($sql);
                                                if ($search) {
                                                    $like_search = "%$search%";
                                                    $query->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                                                $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Get total rows for pagination
                                                $countSql = "SELECT COUNT(*) FROM tblpatient WHERE 1=1";
                                                if ($search) {
                                                    $countSql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                                                }
                                                $countQuery = $dbh->prepare($countSql);
                                                if ($search) {
                                                    $countQuery->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                $countQuery->execute();
                                                $total_rows = $countQuery->fetchColumn();
                                                $total_pages = ceil($total_rows / $no_of_records_per_page);

                                                $cnt = $offset + 1; // Adjust for pagination
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $row) { ?>   
                                                        <tr>
                                                            <td><?php echo htmlentities($cnt);?></td>
                                                            <td><?php echo htmlentities($row->firstname);?></td>
                                                            <td><?php echo htmlentities($row->surname);?></td>
                                                            <td><?php echo htmlentities($row->date_of_birth);?></td>
                                                            <td><?php echo htmlentities($row->age);?></td>
                                                            <td><?php echo htmlentities($row->sex);?></td>
                                                            <td><?php echo htmlentities($row->status);?></td>
                                                            <td><?php echo htmlentities($row->contact_number);?></td>
                                                            <td><?php echo htmlentities($row->address);?></td>
                                                            <td><?php echo htmlentities($row->occupation);?></td>
                                                            <td><?php echo htmlentities($row->patient_history);?>
                                                                <div>
                                                                    <a href="patient-history.php?number=<?php echo htmlentities($row->number); ?>" class="btn btn-success btn-sm">View</a>
                                                                </div>
                                                            </td>
                                                            <td><?php echo htmlentities($row->medical_record);?>
                                                        <div>
                                                            <a href="medical-record.php?number=<?php echo htmlentities($row->number); ?>" class="btn btn-success btn-sm">View</a>
                                                        </div></td>
                                                            <td>
                                                                <div>
                                                                    <a href="edit-patient.php?number=<?php echo htmlentities($row->number); ?>" class="btn btn-info btn-sm">Edit</a>
                                                                    <a href="manage-patients.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete?');" class="btn btn-danger btn-sm">Delete</a>
                                                                </div>
                                                            </td> 
                                                        </tr>
                                                        <?php $cnt++;
                                                    }
                                                } else { ?>
                                                    <tr>
                                                        <th colspan="8" style="color:red;">No Record Found</th>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div align="left" class="mt-4">
                                        <ul class="pagination">
                                            <li><a href="?pageno=1"><strong>First</strong></a></li>
                                            <li class="<?php if ($pageno <= 1) { echo 'disabled'; } ?>">
                                                <a href="<?php if ($pageno <= 1) { echo '#'; } else { echo "?pageno=" . ($pageno - 1); } ?>"><strong>Prev</strong></a>
                                            </li>
                                            <li class="<?php if ($pageno >= $total_pages) { echo 'disabled'; } ?>">
                                                <a href="<?php if ($pageno >= $total_pages) { echo '#'; } else { echo "?pageno=" . ($pageno + 1); } ?>"><strong>Next</strong></a>
                                            </li>
                                            <li><a href="?pageno=<?php echo $total_pages; ?>"><strong>Last</strong></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php');?>
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