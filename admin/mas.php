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
    $selected_service = '';
    $selected_calendar = '';

    // Handle the search and status filter
    if (isset($_POST['search'])) {
        $search = $_POST['search'];
    }
    if (isset($_POST['status_filter'])) {
        $status_filter = $_POST['status_filter'];
    }
    if (isset($_POST['service_filter'])) {
        $selected_service = $_POST['service_filter'];
    }
    if (isset($_POST['calendar_filter'])) {
        $selected_calendar = $_POST['calendar_filter'];
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
        header('Location: mas.php');
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
    echo "<script>window.location.href = 'mas.php'</script>";
    }
    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointments (MAS)</title>
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
                        <h3 class="page-title">Manage Appointments (MAS)</h3>
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
                                        <div class="form-group mr-3">
                                            <?php
                                            // Load services for dropdown
                                            $svcStmt = $dbh->prepare("SELECT number, name FROM tblservice ORDER BY name ASC");
                                            $svcStmt->execute();
                                            $services = $svcStmt->fetchAll(PDO::FETCH_OBJ);
                                            ?>
                                            <select class="form-control" name="service_filter" onchange="autoSubmit()">
                                                <option value="">All Services</option>
                                                <?php foreach ($services as $svc) { ?>
                                                    <option value="<?php echo htmlentities($svc->number); ?>" <?php if ($selected_service == $svc->number) echo 'selected'; ?>><?php echo htmlentities($svc->name); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-3">
                                            <?php
                                            // Load calendar slots for dropdown (combine date + start_time + duration)
                                            // Note: removed date >= CURDATE() so existing rows (even past ones) are visible.
                                            $calStmt = $dbh->prepare("SELECT id, date, start_time, duration FROM tblcalendar ORDER BY date, start_time");
                                            $calStmt->execute();
                                            $cals = $calStmt->fetchAll(PDO::FETCH_OBJ);
                                            ?>
                                            <select class="form-control" name="calendar_filter" onchange="autoSubmit()">
                                                <option value="">All Slots</option>
                                                <?php if (count($cals) === 0) { ?>
                                                    <option value="">No slots available</option>
                                                <?php } else {
                                                    foreach ($cals as $cal) {
                                                        $label = $cal->date . ' ' . substr($cal->start_time,0,5) . ' (' . intval($cal->duration) . ' mins)';
                                                ?>
                                                    <option value="<?php echo htmlentities($cal->id); ?>" <?php if ($selected_calendar == $cal->id) echo 'selected'; ?>><?php echo htmlentities($label); ?></option>
                                                <?php }
                                                } ?>
                                            </select>
                                        </div>
                                    </form>

                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">No</th>
                                                    <th class="font-weight-bold">First Name</th>
                                                    <th class="font-weight-bold">Surname</th>
                                                    <th class="font-weight-bold">Date</th>
                                                    <th class="font-weight-bold">Time</th>
                                                    <th class="font-weight-bold">Duration (mins)</th>
                                                    <th class="font-weight-bold">Service</th>
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

                                                // Determine if tblschedule has a service_id column so we can join tblservice
                                                $hasServiceColumn = false;
                                                try {
                                                    $svcColChk = $dbh->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblschedule' AND COLUMN_NAME = 'service_id'");
                                                    $svcColChk->execute();
                                                    if ($svcColChk->rowCount() > 0) $hasServiceColumn = true;
                                                } catch (Exception $e) {
                                                    $hasServiceColumn = false;
                                                }

                                                // Build the query based on search and status filter
                                                // Left join tblschedule to get schedule date/time/duration if present
                                                // If service column exists, left join tblservice to fetch service name
                                                $selectExtra = $hasServiceColumn ? ", svc.name AS svc_name" : "";
                                                $joinService = $hasServiceColumn ? " LEFT JOIN tblservice svc ON svc.number = s.service_id" : "";
                                                $sql = "SELECT tblappointment.*, s.id AS schedule_id, s.date AS sched_date, s.time AS sched_time, s.duration AS sched_duration, s.status AS sched_status" . $selectExtra . " FROM tblappointment LEFT JOIN tblschedule s ON s.appointment_id = tblappointment.id" . $joinService . " WHERE 1=1";
                                                if ($search) {
                                                    $sql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                                                }
                                                if ($status_filter) {
                                                    $sql .= " AND status = :status_filter";
                                                }
                                                // If service filter selected and tblschedule has service_id, filter by it
                                                if (!empty($selected_service) && $hasServiceColumn) {
                                                    $sql .= " AND (s.service_id = :svc_id)";
                                                }
                                                // If calendar slot selected, fetch its date/time and filter appointments to that slot
                                                if (!empty($selected_calendar)) {
                                                    $calQf = $dbh->prepare("SELECT date, start_time FROM tblcalendar WHERE id = :cid LIMIT 1");
                                                    $calQf->bindParam(':cid', $selected_calendar, PDO::PARAM_INT);
                                                    $calQf->execute();
                                                    $calRowf = $calQf->fetch(PDO::FETCH_OBJ);
                                                    if ($calRowf) {
                                                        $sql .= " AND ((s.date = :cal_date AND s.time = :cal_time) OR (tblappointment.date = :cal_date AND tblappointment.time = :cal_time))";
                                                        $bind_cal = true;
                                                    }
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
                                                if (!empty($selected_service) && $hasServiceColumn) {
                                                    $query->bindParam(':svc_id', $selected_service, PDO::PARAM_INT);
                                                }
                                                if (!empty($selected_calendar) && !empty($calRowf)) {
                                                    $query->bindParam(':cal_date', $calRowf->date, PDO::PARAM_STR);
                                                    $query->bindParam(':cal_time', $calRowf->start_time, PDO::PARAM_STR);
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
                                                if (!empty($selected_service) && $hasServiceColumn) {
                                                    $ret .= " AND (id IN (SELECT appointment_id FROM tblschedule WHERE service_id = :svc_id))";
                                                }
                                                if (!empty($selected_calendar) && !empty($calRowf)) {
                                                    // Count appointments that match the selected calendar slot
                                                    $ret .= " AND ((id IN (SELECT appointment_id FROM tblschedule WHERE date = :cal_date AND time = :cal_time)) OR (date = :cal_date AND time = :cal_time))";
                                                }
                                                $query1 = $dbh->prepare($ret);
                                                if ($search) {
                                                    $query1->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                if ($status_filter) {
                                                    $query1->bindParam(':status_filter', $status_filter, PDO::PARAM_STR);
                                                }
                                                if (!empty($selected_service) && $hasServiceColumn) {
                                                    $query1->bindParam(':svc_id', $selected_service, PDO::PARAM_INT);
                                                }
                                                if (!empty($selected_calendar) && !empty($calRowf)) {
                                                    $query1->bindParam(':cal_date', $calRowf->date, PDO::PARAM_STR);
                                                    $query1->bindParam(':cal_time', $calRowf->start_time, PDO::PARAM_STR);
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
                                                            <td><?php echo htmlentities(!empty($row->sched_date) ? $row->sched_date : $row->date); ?></td>
                                                            <td><?php echo htmlentities(!empty($row->sched_time) ? substr($row->sched_time,0,5) : substr($row->time,0,5)); ?></td>
                                                            <td><?php echo htmlentities(!empty($row->sched_duration) ? $row->sched_duration : '-'); ?></td>
                                                            <td><?php
                                                                // Show service name from tblschedule if available
                                                                if (!empty($row->schedule_id) && !empty($row->svc_name)) {
                                                                    echo htmlentities($row->svc_name);
                                                                } else {
                                                                    echo '-';
                                                                }
                                                            ?></td>
                                                            <td>
                                                                <?php
                                                                // Prefer schedule-level status when present
                                                                $badgeClass = 'badge badge-secondary';
                                                                $displayStatus = '';
                                                                if (!empty($row->sched_status)) {
                                                                    $displayStatus = $row->sched_status;
                                                                    if ($row->sched_status === 'Ongoing') $badgeClass = 'badge badge-warning text-dark';
                                                                    elseif ($row->sched_status === 'Done') $badgeClass = 'badge badge-success';
                                                                    else $badgeClass = 'badge badge-secondary';
                                                                } else {
                                                                    $displayStatus = $row->status;
                                                                    if ($row->status === 'Pending') $badgeClass = 'badge badge-warning text-dark';
                                                                    elseif ($row->status === 'Approved') $badgeClass = 'badge badge-success';
                                                                    elseif ($row->status === 'Declined') $badgeClass = 'badge badge-danger';
                                                                    else $badgeClass = 'badge badge-secondary';
                                                                }
                                                                ?>
                                                                <span class="<?php echo $badgeClass; ?>"><?php echo htmlentities($displayStatus); ?></span>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <a href="edit-mas.php?editid=<?php echo htmlentities($row->id); ?>" class="btn btn-info btn-xs">Edit</a>
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
