<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
  header('location:logout.php');
  exit(); // It's good practice to exit after a header redirect
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
$sql = "SELECT number, firstname, surname FROM tblpatient WHERE number = :stdid";
$query = $dbh->prepare($sql);
$query->bindParam(':stdid', $stdid, PDO::PARAM_INT);
$query->execute();
$patient = $query->fetch(PDO::FETCH_OBJ);

// Fetch consultation appointments for this patient
$appointments = [];
try {
  $sqlApp = "SELECT id, firstname, surname, `date`, start_time AS `time`, created_at, status FROM tblappointment WHERE patient_number = :pnum ORDER BY `date` DESC, `time` DESC";
    $qryApp = $dbh->prepare($sqlApp);
    $qryApp->bindParam(':pnum', $stdid, PDO::PARAM_INT);
    $qryApp->execute();
    $appointments = $qryApp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // You might want to log this error in a real application
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
    // You might want to log this error in a real application
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
    <link rel="stylesheet" href="css/view-ph.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/stylev2.css">
    <link rel="stylesheet" href="css/responsive.css">
    
  </head>
  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <?php if ($patient): ?>
              <h2 class="patient-name-header">Patient History:           <?php echo htmlentities($patient->firstname . ' ' . $patient->surname); ?></h2>
            <?php else: ?>
              <h2 class="patient-name-header">Patient History</h2>
              <div class="alert alert-danger">Patient not found.</div>
            <?php endif; ?>

            <div class="history-container">
              <!-- Consultation Card -->
              <div class="history-card consultation">
                <div class="history-header">
                  <div class="history-icon consultation-icon" style="font-size: 24px;">
                    <i class="fas fa-notes-medical"></i>
                  </div>
                  <div class="history-title-section">
                    <h3 class="history-title">Consultation History</h3>
                    <p class="history-count"><?php echo count($appointments); ?> records</p>
                  </div>
                </div>
                <div class="history-content">
                  <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                      <table class="history-table">
                        <thead>
                          <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($appointments as $app): ?>
                            <tr>
                              <td>#<?php echo htmlentities($app['id']); ?></td>
                              <td><?php echo htmlentities(date('M d, Y', strtotime($app['date']))); ?></td>
                              <td><?php echo htmlentities(time12_viewph($app['time'])); ?></td>
                              <td><span class="status-badge status-<?php echo strtolower(htmlentities($app['status'])); ?>"><?php echo htmlentities($app['status']); ?></span></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <p class="no-history">No consultation history found.</p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Service Card -->
              <div class="history-card service">
                <div class="history-header">
                  <div class="history-icon service-icon" style="font-size: 24px;">
                    <i class="fas fa-briefcase-medical"></i>
                  </div>
                  <div class="history-title-section">
                    <h3 class="history-title">Service History</h3>
                    <p class="history-count"><?php echo count($serviceSchedules); ?> records</p>
                  </div>
                </div>
                <div class="history-content">
                  <?php if (!empty($serviceSchedules)): ?>
                    <div class="table-responsive">
                      <table class="history-table">
                        <thead>
                          <tr>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($serviceSchedules as $s): ?>
                            <tr>
                              <td><?php echo !empty($s['service_name']) ? htmlentities($s['service_name']) : 'N/A'; ?></td>
                              <td><?php echo htmlentities(date('M d, Y', strtotime($s['date']))); ?></td>
                              <td><?php echo htmlentities(time12_viewph($s['time'])); ?></td>
                              <td><span class="status-badge status-<?php echo strtolower(htmlentities($s['sched_status'])); ?>"><?php echo htmlentities($s['sched_status']); ?></span></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <p class="no-history">No service history found.</p>
                  <?php endif; ?>
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
    <script src="js/view-ph.js"></script>
  </body>
</html>
