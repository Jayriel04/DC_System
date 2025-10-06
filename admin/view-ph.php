<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
  header('location:logout.php');
  exit();
}

// Determine patient id (accept either stid or number from manage-patient.php)
$stdid = null;
if (isset($_GET['stid'])) {
  $stdid = intval($_GET['stid']);
} elseif (isset($_GET['number'])) {
  $stdid = intval($_GET['number']);
}

// If no patient id supplied, stop early
if (empty($stdid)) {
  echo "<p style='color:red'>No patient selected.</p>";
  exit();
}

// Lookup patient from tblpatient (include number)
$sql = "SELECT number, firstname, surname, contact_number, address, username FROM tblpatient WHERE number = :stdid";
$query = $dbh->prepare($sql);
$query->bindParam(':stdid', $stdid, PDO::PARAM_INT);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

// Fetch consultation appointments for this patient
$appointments = [];
try {
  $sqlApp = "SELECT id, firstname, surname, `date`, start_time AS `time`, created_at, status FROM tblappointment WHERE patient_number = :pnum ORDER BY `date` DESC, `time` DESC";
    $qryApp = $dbh->prepare($sqlApp);
    $qryApp->bindParam(':pnum', $stdid, PDO::PARAM_INT);
    $qryApp->execute();
    $appointments = $qryApp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}

// Fetch service schedules for this patient
$serviceSchedules = [];
try {
    $sqlSch = "SELECT s.id AS schedule_id, s.appointment_id, s.date, s.time, s.duration, s.created_at, s.firstname, s.surname, s.status AS sched_status, svc.name AS service_name FROM tblschedule s LEFT JOIN tblservice svc ON svc.number = s.service_id LEFT JOIN tblappointment a ON a.id = s.appointment_id WHERE s.patient_number = :pnum ORDER BY s.date DESC, s.time DESC";
    $qrySch = $dbh->prepare($sqlSch);
    $qrySch->bindParam(':pnum', $stdid, PDO::PARAM_INT);
    $qrySch->execute();
    $serviceSchedules = $qrySch->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}

// Helper to convert 24-hour time to 12-hour am/pm
function time12_viewph($t) {
    if (empty($t)) return '-';
    $parts = explode(':', $t);
    if (count($parts) < 2) return $t;
    $h = intval($parts[0]);
    $m = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
    $ampm = $h >= 12 ? 'pm' : 'am';
    $h12 = $h % 12; if ($h12 === 0) $h12 = 12;
    return $h12 . ':' . $m . ' ' . $ampm;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Patient History</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> View Patient </h3>
              <nav aria-label="breadcrumb">
                
              </nav>
            </div>

            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">

                    <!-- Consultation Appointments Card -->
                    <div class="card mt-4">
                      <div class="card-header bg-primary text-white">Consultation Appointments</div>
                      <div class="card-body">
                        <?php if (!empty($appointments)) { ?>
                          <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                              <thead>
                                <tr>
                                  <th>#</th>
                                  <th>Appointment ID</th>
                                  <th>Date</th>
                                  <th>Time</th>
                                  <th>Status</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php $i = 1; foreach ($appointments as $app) { ?>
                                  <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlentities($app['id']); ?></td>
                                    <td><?php echo htmlentities($app['date']); ?></td>
                                    <td><?php echo htmlentities(time12_viewph($app['time'])); ?></td>
                                    <td><?php echo htmlentities($app['status']); ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                        <?php } else { ?>
                          <p>No consultation appointments found for this patient.</p>
                        <?php } ?>
                      </div>
                    </div>

                    <!-- Service Appointments Card -->
                    <div class="card mt-4">
                      <div class="card-header bg-success text-white">Service Appointments</div>
                      <div class="card-body">
                        <?php if (!empty($serviceSchedules)) { ?>
                          <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                              <thead>
                                <tr>
                                  <th>#</th>
                                  <th>Schedule ID</th>
                                  <th>Appointment ID</th>
                                  <th>Date</th>
                                  <th>Time</th>
                                  <th>Duration (mins)</th>
                                  <th>Service</th>
                                  <th>Status</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php $j = 1; foreach ($serviceSchedules as $s) { ?>
                                  <tr>
                                    <td><?php echo $j++; ?></td>
                                    <td><?php echo htmlentities($s['schedule_id']); ?></td>
                                    <td><?php echo htmlentities($s['appointment_id']); ?></td>
                                    <td><?php echo htmlentities($s['date']); ?></td>
                                    <td><?php echo htmlentities(time12_viewph($s['time'])); ?></td>
                                    <td><?php echo !empty($s['duration']) ? htmlentities($s['duration']) : '-'; ?></td>
                                    <td><?php echo !empty($s['service_name']) ? htmlentities($s['service_name']) : '-'; ?></td>
                                    <td><?php echo htmlentities($s['sched_status']); ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                        <?php } else { ?>
                          <p>No service appointments found for this patient.</p>
                        <?php } ?>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
          <?php include_once('includes/footer.php');?>
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
  </body>
</html>
