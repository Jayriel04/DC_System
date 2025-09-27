</div>
<?php
session_start();
//error_reporting(0);
include('includes/dbconnection.php');

// Updated session check for tblpatient
if (strlen($_SESSION['sturecmsnumber']) == 0) {
  header('location:logout.php');
  exit();
}

// Patient number from session
$patient_number = $_SESSION['sturecmsnumber'];

// Handle modal submission: single action 'Book Appointment' which also saves health conditions
if (isset($_POST['book_appointment'])) {
  $health_conditions = isset($_POST['health_conditions']) ? $_POST['health_conditions'] : [];
  $health_conditions_json = json_encode($health_conditions);

  // Appointment fields from modal (required for booking)
  $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
  $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';

  if (empty($appointment_date) || empty($appointment_time)) {
    $modal_error = 'Please select both appointment date and time.';
  } else {
    $sqlUpd = "UPDATE tblpatient SET health_conditions = :hc WHERE number = :num";
    $qryUpd = $dbh->prepare($sqlUpd);
    $qryUpd->bindParam(':hc', $health_conditions_json, PDO::PARAM_STR);
    $qryUpd->bindParam(':num', $patient_number, PDO::PARAM_INT);
    if ($qryUpd->execute()) {
      // Duplicate check: same patient, same date and time
      $sqlChk = "SELECT COUNT(*) FROM tblappointment WHERE patient_number = :pn AND `date` = :dt AND `time` = :tm";
      $qryChk = $dbh->prepare($sqlChk);
      $qryChk->bindParam(':pn', $patient_number, PDO::PARAM_INT);
      $qryChk->bindParam(':dt', $appointment_date, PDO::PARAM_STR);
      $qryChk->bindParam(':tm', $appointment_time, PDO::PARAM_STR);
      $qryChk->execute();
      $exists = (int) $qryChk->fetchColumn();
      if ($exists > 0) {
        $modal_error = 'You already have an appointment at that date and time.';
      } else {
        $firstname = isset($_SESSION['sturecmsfirstname']) ? $_SESSION['sturecmsfirstname'] : '';
        $surname = isset($_SESSION['sturecmssurname']) ? $_SESSION['sturecmssurname'] : '';

        // set default status for new appointments
        $status_default = 'Pending';
        $sqlIns = "INSERT INTO tblappointment (`firstname`, `surname`, `date`, `time`, `patient_number`, `status`) VALUES (:fn, :sn, :dt, :tm, :pn, :st)";
        $qryIns = $dbh->prepare($sqlIns);
        $qryIns->bindParam(':fn', $firstname, PDO::PARAM_STR);
        $qryIns->bindParam(':sn', $surname, PDO::PARAM_STR);
        $qryIns->bindParam(':dt', $appointment_date, PDO::PARAM_STR);
        $qryIns->bindParam(':tm', $appointment_time, PDO::PARAM_STR);
        $qryIns->bindParam(':pn', $patient_number, PDO::PARAM_INT);
        $qryIns->bindParam(':st', $status_default, PDO::PARAM_STR);
        $qryIns->execute();

        // Success - set a session flash and redirect
        $_SESSION['modal_success'] = 'Appointment booked successfully.';
        header('Location: dashboard.php');
        exit();
      }
    } else {
      $modal_error = 'Could not save health conditions. Please try again.';
    }
  }
}

// Check whether the patient already has health conditions saved
$sql = "SELECT health_conditions FROM tblpatient WHERE number = :num";
$query = $dbh->prepare($sql);
$query->bindParam(':num', $patient_number, PDO::PARAM_INT);
$query->execute();
$row = $query->fetch(PDO::FETCH_OBJ);
$existing_health = '';
if ($row && isset($row->health_conditions)) {
  $existing_health = trim($row->health_conditions);
}

// Show modal if no health data exists
$show_health_modal = empty($existing_health) || $existing_health === 'null' || $existing_health === '[]';

// Decode existing health JSON into array for display
$health_arr = [];
if (!empty($existing_health) && $existing_health !== 'null' && $existing_health !== '[]') {
  $decoded = json_decode($existing_health, true);
  if (is_array($decoded)) {
    $health_arr = $decoded;
  }
}

// Fetch this patient's appointments for dashboard display
$appointments = [];
try {
  $sqlApp = "SELECT id, firstname, surname, `date`, `time`, created_at, status FROM tblappointment WHERE patient_number = :pn ORDER BY `date` DESC, `time` DESC";
  $qryApp = $dbh->prepare($sqlApp);
  $qryApp->bindParam(':pn', $patient_number, PDO::PARAM_INT);
  $qryApp->execute();
  $appointments = $qryApp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // If table doesn't exist or query fails, keep $appointments empty and optionally log the error
}

// Fetch this patient's service schedules from tblschedule
$serviceSchedules = [];
try {
  $sqlSch = "SELECT s.id AS schedule_id, s.appointment_id, s.date, s.time, s.duration, s.created_at, s.firstname, s.surname, s.status AS sched_status, svc.name AS service_name, a.status AS appointment_status FROM tblschedule s LEFT JOIN tblservice svc ON svc.number = s.service_id LEFT JOIN tblappointment a ON a.id = s.appointment_id WHERE s.patient_number = :pn ORDER BY s.date DESC, s.time DESC";
  $qrySch = $dbh->prepare($sqlSch);
  $qrySch->bindParam(':pn', $patient_number, PDO::PARAM_INT);
  $qrySch->execute();
  $serviceSchedules = $qrySch->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // keep empty if no table or error
}

// Helper: convert 24-hour time string to 12-hour with am/pm for display
function time12_dashboard($t) {
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
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Patient Management System ||| Dashboard</title>
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
  <link rel="stylesheet" href="./css/style.css">
</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <?php include_once('includes/header.php'); ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
      <?php include_once('includes/sidebar.php'); ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row purchace-popup">
            <div class="col-12 stretch-card grid-margin">
              <div class="card card-secondary">
                <span class="card-body d-lg-flex align-items-center">
                  <p class="mb-lg-0">Notices from the school kindly check!</p>
                  <a href="view-notice.php" target="_blank"
                    class="btn btn-warning purchase-button btn-sm my-1 my-sm-0 ml-auto">View Notice</a>
                </span>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['modal_success'])) { ?>
            <div class="container px-3 mt-3">
              <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_SESSION['modal_success']); ?>
              </div>
            </div>
            <?php unset($_SESSION['modal_success']);
          } ?>
          <!-- Dashboard tables: Health Conditions & Appointments -->
          <div class="row mt-4">
            <div class="col-md-6">
              <div class="card">
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
                    <p>No health information on file. Please complete the health form in the modal.</p>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card">
                <div class="card-header bg-primary text-white">Consultation Appointment</div>
                <div class="card-body">
                  <?php if (!empty($appointments)) { ?>
                    <div class="table-responsive">
                      <table class="table table-bordered table-striped">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            
                          </tr>
                        </thead>
                        <tbody>
                          <?php $i = 1;
                          foreach ($appointments as $a) { 
                            $status = isset($a['status']) ? $a['status'] : '';
                            $badgeClass = 'badge badge-secondary';
                            if ($status === 'Pending') $badgeClass = 'badge badge-warning text-dark';
                            elseif ($status === 'Approved') $badgeClass = 'badge badge-success';
                            elseif ($status === 'Declined') $badgeClass = 'badge badge-danger';
                          ?>
                            <tr>
                              <td><?php echo $i++; ?></td>
                              <td><?php echo htmlspecialchars($a['date']); ?></td>
                              <td><?php echo htmlspecialchars(time12_dashboard($a['time'])); ?></td>
                              <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                              
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  <?php } else { ?>
                    <p>No appointments booked yet.</p>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Appointments -->
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header bg-success text-white">Service Appointments</div>
                <div class="card-body">
                  <?php if (!empty($serviceSchedules)) { ?>
                    <div class="table-responsive">
                      <table class="table table-bordered table-striped">
                        <thead>
                          <tr>
                            <th>#</th>
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
                              <td><?php echo htmlspecialchars($s['appointment_id']); ?></td>
                              <td><?php echo htmlspecialchars($s['date']); ?></td>
                              <td><?php echo htmlspecialchars(time12_dashboard($s['time'])); ?></td>
                              <td><?php echo htmlspecialchars(!empty($s['duration']) ? $s['duration'] : '-'); ?></td>
                              <td><?php echo htmlspecialchars(!empty($s['service_name']) ? $s['service_name'] : '-'); ?></td>
                              <td><?php
                                $sched_status = '';
                                if (!empty($s['sched_status'])) $sched_status = $s['sched_status'];
                                elseif (!empty($s['appointment_status'])) $sched_status = $s['appointment_status'];
                                $badgeClass = 'badge badge-secondary';
                                if ($sched_status === 'Ongoing') $badgeClass = 'badge badge-warning text-dark';
                                elseif ($sched_status === 'Done') $badgeClass = 'badge badge-success';
                                elseif ($sched_status === 'Pending') $badgeClass = 'badge badge-warning text-dark';
                                elseif ($sched_status === 'Approved') $badgeClass = 'badge badge-success';
                                elseif ($sched_status === 'Declined') $badgeClass = 'badge badge-danger';
                                if (!empty($sched_status)) echo '<span class="' . $badgeClass . '">' . htmlspecialchars($sched_status) . '</span>'; else echo '-';
                              ?></td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  <?php } else { ?>
                    <p>No service appointments found.</p>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>

        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        <?php include_once('includes/footer.php'); ?>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <script src="./vendors/chart.js/Chart.min.js"></script>
  <script src="./vendors/moment/moment.min.js"></script>
  <script src="./vendors/daterangepicker/daterangepicker.js"></script>
  <script src="./vendors/chartist/chartist.min.js"></script>
  <script src="js/off-canvas.js"></script>
  <script src="js/misc.js"></script>
  <script src="./js/dashboard.js"></script>

  <!-- Health Conditions Modal (inserted so it's available when dashboard renders) -->
  <?php if (isset($show_health_modal) && $show_health_modal) { ?>
    <!-- Modal markup -->
    <div class="modal fade" id="healthModal" tabindex="-1" role="dialog" aria-labelledby="healthModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="healthModalLabel">Health Condition Form</h5>
              <button type="button" class="close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <!-- Appointment fields inside modal -->
              <div class="row mt-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="appointment_date">Preferred Appointment Date</label>
                    <input type="date" class="form-control" name="appointment_date" id="appointment_date">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="appointment_time">Preferred Appointment Time</label>
                    <input type="time" class="form-control" name="appointment_time" id="appointment_time">
                  </div>
                </div>
              </div>

              <?php if (!empty($modal_error)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($modal_error) . '</div>';
              } ?>
              <p>Please check all conditions that apply to you.</p>
              <div class="row">
                <div class="col-md-6">
                  <h6>GENERAL</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[general][]" value="Marked weight change" id="hc_general_1"><label
                      class="form-check-label" for="hc_general_1">Marked weight change</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[general][]" value="Increase frequency of urination" id="hc_general_2"><label
                      class="form-check-label" for="hc_general_2">Increase frequency of urination</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[general][]" value="Burning sensation on urination" id="hc_general_3"><label
                      class="form-check-label" for="hc_general_3">Burning sensation on urination</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[general][]" value="Loss of hearing, ringing of ears"
                      id="hc_general_4"><label class="form-check-label" for="hc_general_4">Loss of hearing, ringing of
                      ears</label></div>

                  <h6 class="mt-3">LIVER</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[liver][]" value="History of liver ailment" id="hc_liver_1"><label
                      class="form-check-label" for="hc_liver_1">History of liver ailment</label></div>
                  <div class="form-group mt-2"><label for="liver_specify">Specify:</label><input type="text"
                      class="form-control" name="health_conditions[liver_specify]" id="liver_specify"></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[liver][]" value="Jaundice" id="hc_liver_2"><label class="form-check-label"
                      for="hc_liver_2">Jaundice</label></div>

                  <h6 class="mt-3">DIABETES</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[diabetes][]" value="Delayed healing of wounds" id="hc_diab_1"><label
                      class="form-check-label" for="hc_diab_1">Delayed healing of wounds</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[diabetes][]" value="Increase intake of food or water" id="hc_diab_2"><label
                      class="form-check-label" for="hc_diab_2">Increase intake of food or water</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[diabetes][]" value="Family history of diabetes" id="hc_diab_3"><label
                      class="form-check-label" for="hc_diab_3">Family history of diabetes</label></div>

                  <h6 class="mt-3">THYROID</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[thyroid][]" value="Perspire easily" id="hc_thy_1"><label
                      class="form-check-label" for="hc_thy_1">Perspire easily</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[thyroid][]" value="Apprehension" id="hc_thy_2"><label
                      class="form-check-label" for="hc_thy_2">Apprehension</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[thyroid][]" value="Palpation/rapid heart beat" id="hc_thy_3"><label
                      class="form-check-label" for="hc_thy_3">Palpation/rapid heart beat</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[thyroid][]" value="Goiter" id="hc_thy_4"><label class="form-check-label"
                      for="hc_thy_4">Goiter</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[thyroid][]" value="Bulging of eyes" id="hc_thy_5"><label
                      class="form-check-label" for="hc_thy_5">Bulging of eyes</label></div>
                </div>
                <div class="col-md-6">
                  <h6>URINARY</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[urinary][]" value="Increase frequency of urination" id="hc_ur_1"><label
                      class="form-check-label" for="hc_ur_1">Increase frequency of urination</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[urinary][]" value="Burning sensation on urination" id="hc_ur_2"><label
                      class="form-check-label" for="hc_ur_2">Burning sensation on urination</label></div>

                  <h6 class="mt-3">NERVOUS SYSTEM</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[nervous][]" value="Headache" id="hc_nerv_1"><label class="form-check-label"
                      for="hc_nerv_1">Headache</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[nervous][]" value="Convulsion/epilepsy" id="hc_nerv_2"><label
                      class="form-check-label" for="hc_nerv_2">Convulsion/epilepsy</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[nervous][]" value="Numbness/Tingling" id="hc_nerv_3"><label
                      class="form-check-label" for="hc_nerv_3">Numbness/Tingling</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[nervous][]" value="Dizziness/Fainting" id="hc_nerv_4"><label
                      class="form-check-label" for="hc_nerv_4">Dizziness/Fainting</label></div>

                  <h6 class="mt-3">BLOOD</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[blood][]" value="Bruise easily" id="hc_blood_1"><label
                      class="form-check-label" for="hc_blood_1">Bruise easily</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[blood][]" value="Anemia" id="hc_blood_2"><label class="form-check-label"
                      for="hc_blood_2">Anemia</label></div>

                  <h6 class="mt-3">RESPIRATORY</h6>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[respiratory][]" value="Persistent cough" id="hc_resp_1"><label
                      class="form-check-label" for="hc_resp_1">Persistent cough</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[respiratory][]" value="Difficulty in breathing" id="hc_resp_2"><label
                      class="form-check-label" for="hc_resp_2">Difficulty in breathing</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox"
                      name="health_conditions[respiratory][]" value="Asthma" id="hc_resp_3"><label
                      class="form-check-label" for="hc_resp_3">Asthma</label></div>
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php } ?>

  <script>
    (function () {
      if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
        $(document).ready(function () {
          $('#healthModal').modal({ backdrop: 'static', keyboard: false });
          $('#healthModal').modal('show');
        });
      } else if (typeof bootstrap !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function () {
          var myModal = new bootstrap.Modal(document.getElementById('healthModal'), { backdrop: 'static', keyboard: false });
          myModal.show();
        });
      }
    })();
  </script>

</body>

</html>