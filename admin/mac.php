<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Initialize search and filter variables
    $search = '';
    $status_filter = '';

    // Handle the search and status filter
    if (isset($_POST['search'])) {
        $search = $_POST['search'];
    }
    if (isset($_POST['status_filter'])) {
        $status_filter = $_POST['status_filter'];
    }

    // Handle status change from admin
    if (isset($_POST['appt_id']) && isset($_POST['new_status'])) {
        $apptId = intval($_POST['appt_id']);
        $newStatus = trim($_POST['new_status']);
        $allowed = ['Pending', 'Approved', 'Declined'];
        if (in_array($newStatus, $allowed, true)) {
            $sqlUp = "UPDATE tblappointment SET status = :st WHERE id = :id";
            $qUp = $dbh->prepare($sqlUp);
            $qUp->bindParam(':st', $newStatus, PDO::PARAM_STR);
            $qUp->bindParam(':id', $apptId, PDO::PARAM_INT);
            $qUp->execute();
        }
        header('Location: manage-appointment.php');
        exit();
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblappointment WHERE id = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
    echo "<script>alert('Data deleted');</script>";
    echo "<script>window.location.href = 'manage-appointment.php'</script>";
    }
    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointments</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script>
        function autoSubmit() {
            document.getElementById("filterForm").submit();
        }
    </script>
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Appointments</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <!-- <a href="add-appointment.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">Add Appointment</a> -->
                                    </div>
                                    <form method="POST" class="form-inline mb-4" id="filterForm">
                                        <div class="form-group mr-3">
                                            <input type="text" class="form-control" name="search" placeholder="Search by First Name or Surname" value="<?php echo htmlentities($search); ?>">
                                        </div>
                                        <div class="form-group mr-3">
                                            <select class="form-control" name="status_filter" onchange="autoSubmit()">
                                                <option value="">All Statuses</option>
                                                <option value="Pending" <?php if ($status_filter == "Pending") echo 'selected'; ?>>Pending</option>
                                                <option value="Approved" <?php if ($status_filter == "Approved") echo 'selected'; ?>>Approved</option>
                                                <option value="Declined" <?php if ($status_filter == "Declined") echo 'selected'; ?>>Declined</option>
                                            </select>
                                        </div>
                                    </form>

                                    <div class="table-responsive border rounded p-1">
                                        <?php
                                        // small helper to format time to 12-hour with am/pm
                                        function time12($t) {
                                            if (empty($t)) return '-';
                                            $parts = explode(':', $t);
                                            if (count($parts) < 2) return $t;
                                            $h = intval($parts[0]);
                                            $m = str_pad($parts[1],2,'0',STR_PAD_LEFT);
                                            $ampm = $h >= 12 ? 'pm' : 'am';
                                            $h12 = $h % 12;
                                            if ($h12 === 0) $h12 = 12;
                                            return $h12 . ':' . $m . ' ' . $ampm;
                                        }

                                        ?>
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">No</th>
                                                    <th class="font-weight-bold">First Name</th>
                                                    <th class="font-weight-bold">Surname</th>
                                                    <th class="font-weight-bold">Date</th>
                                                    <th class="font-weight-bold">Time</th>
                                                    <th class="font-weight-bold">Status</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (isset($_GET['page_no']) && $_GET['page_no'] != "") {
                                                    $page_no = $_GET['page_no'];
                                                } else {
                                                    $page_no = 1;
                                                }

                                                $total_records_per_page = 10;
                                                $offset = ($page_no - 1) * $total_records_per_page;

                                                // Build the query based on search and status filter
                                                $sql = "SELECT * FROM tblappointment WHERE 1=1";
                                                if ($search) {
                                                    $sql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                                                }
                                                if ($status_filter) {
                                                    $sql .= " AND status = :status_filter";
                                                }
                                                $sql .= " LIMIT $offset, $total_records_per_page";

                                                $query = $dbh->prepare($sql);
                                                if ($search) {
                                                    $like_search = "%$search%";
                                                    $query->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                if ($status_filter) {
                                                    $query->bindParam(':status_filter', $status_filter, PDO::PARAM_STR);
                                                }
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Get total rows for pagination
                                                $ret = "SELECT id FROM tblappointment WHERE 1=1";
                                                if ($search) {
                                                    $ret .= " AND (firstname LIKE :search OR surname LIKE :search)";
                                                }
                                                if ($status_filter) {
                                                    $ret .= " AND status = :status_filter";
                                                }
                                                $query1 = $dbh->prepare($ret);
                                                if ($search) {
                                                    $query1->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                if ($status_filter) {
                                                    $query1->bindParam(':status_filter', $status_filter, PDO::PARAM_STR);
                                                }
                                                $query1->execute();
                                                $total_rows = $query1->rowCount();
                                                $total_pages = ceil($total_rows / $total_records_per_page);

                                                $cnt = 1;
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $row) { ?>
                                                        <tr>
                                                            <td><?php echo htmlentities($row->id); ?></td>
                                                            <td><?php echo htmlentities($row->firstname); ?></td>
                                                            <td><?php echo htmlentities($row->surname); ?></td>
                                                            <td><?php echo htmlentities($row->date); ?></td>
                                                            <td><?php echo htmlentities(time12($row->time)); ?></td>
                                                            <td><?php echo htmlentities($row->status); ?></td>
                                                            <td>
                                                                <div>
                                                                    <a href="edit-appointment-detail.php?editid=<?php echo htmlentities($row->id); ?>" class="btn btn-info btn-xs">Edit</a>
                                                                    <a href="manage-appointment.php?delid=<?php echo ($row->id); ?>" onclick="return confirm('Do you really want to Delete ?');" class="btn btn-danger btn-xs">Delete</a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php $cnt++;
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div align="left" class="mt-4">
                                        <ul class="pagination">
                                            <li><a href="?page_no=1"><strong>First</strong></a></li>
                                            <li class="<?php if ($page_no <= 1) echo 'disabled'; ?>">
                                                <a href="<?php if ($page_no <= 1) echo '#'; else echo "?page_no=" . ($page_no - 1); ?>"><strong>Prev</strong></a>
                                            </li>
                                            <li class="<?php if ($page_no >= $total_pages) echo 'disabled'; ?>">
                                                <a href="<?php if ($page_no >= $total_pages) echo '#'; else echo "?page_no=" . ($page_no + 1); ?>"><strong>Next</strong></a>
                                            </li>
                                            <li><a href="?page_no=<?php echo $total_pages; ?>"><strong>Last</strong></a></li>
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
    <script src="js/dashboard.js"></script>
</body>
</html>
<?php } ?>