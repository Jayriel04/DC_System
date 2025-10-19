<?php
session_start();
//error_reporting(0);
include('includes/dbconnection.php');
if (strlen(isset($_SESSION['sturecmsnumber']) ? $_SESSION['sturecmsnumber'] : '') == 0) {
  header('location:logout.php');
  } else{
  // Handle schedule cancellation from action column
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_schedule']) && !empty($_POST['schedule_id'])) {
    $schId = intval($_POST['schedule_id']);
    // Build cancel reason from dropdown choice + optional specify field
    $choice = isset($_POST['cancel_reason_choice']) ? trim($_POST['cancel_reason_choice']) : '';
    $specify = isset($_POST['cancel_reason_specify']) ? trim($_POST['cancel_reason_specify']) : '';
    $cancel_reason = '';
    if ($choice !== '') {
      $cancel_reason = $choice;
      if ($specify !== '') {
        $cancel_reason .= ': ' . $specify;
      }
    } elseif ($specify !== '') {
      $cancel_reason = $specify;
    }
  try {
  // fetch appointment_id and service_id before update
  $sth = $dbh->prepare("SELECT appointment_id, service_id FROM tblschedule WHERE id = :id");
  $sth->bindParam(':id', $schId, PDO::PARAM_INT);
  $sth->execute();
  $row = $sth->fetch(PDO::FETCH_ASSOC);
  $appointmentId = isset($row['appointment_id']) ? intval($row['appointment_id']) : null;
  $serviceId = isset($row['service_id']) && $row['service_id'] !== null ? intval($row['service_id']) : null;

      // Ensure cancel_reason and cancelled_at columns exist in tblschedule (best-effort)
      try {
        $colChk = $dbh->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblschedule' AND COLUMN_NAME = 'cancel_reason'");
        $colChk->execute();
        $hasCol = (int) $colChk->fetchColumn();
        if (!$hasCol) {
          // add cancel_reason (TEXT) and cancelled_at (DATETIME)
          $dbh->exec("ALTER TABLE tblschedule ADD COLUMN cancel_reason TEXT DEFAULT NULL, ADD COLUMN cancelled_at DATETIME DEFAULT NULL");
        }
      } catch (Exception $ex) {
        // ignore if cannot alter
      }

      // mark schedule cancelled and save reason/time where possible
      $u = $dbh->prepare("UPDATE tblschedule SET status = 'Cancelled', cancel_reason = :reason, cancelled_at = NOW() WHERE id = :id");
      $u->bindParam(':reason', $cancel_reason, PDO::PARAM_STR);
      $u->bindParam(':id', $schId, PDO::PARAM_INT);
      $u->execute();

      // Intentionally DO NOT update tblappointment here so cancelling a service schedule
      // does not affect consultation appointment records in tblappointment.
      $_SESSION['flash_msg'] = 'Service schedule cancelled. Consultation appointment (if any) was not modified.' . (!empty($cancel_reason) ? ' Reason: ' . htmlspecialchars($cancel_reason) : '');
      header('Location: vas.php');
      exit();
    } catch (Exception $e) {
      // swallow and continue; could log
      $_SESSION['flash_msg'] = 'Unable to cancel appointment.';
      header('Location: vas.php');
      exit();
    }
  }
   
  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
   
    <title>Service Appointments</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css" />
    
  </head>
  <body>
    <style>
  .main-panel {
    margin-left: auto;
    margin-right: 0;
    margin-top: 71px;   
    width: 81%; 
  }

  
  
</style>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
     <?php include_once('includes/header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
      <?php include_once('includes/sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Service Appointments </h3>
              

            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">Your Service Appointments</h5>
                    <p>&nbsp;</p>

                    <?php
                    // determine patient number from session (primary key used across app is 'sturecmsnumber')
                    $patient_number = null;
                    if (isset($_SESSION['sturecmsnumber']) && $_SESSION['sturecmsnumber'] !== '') {
                        $patient_number = $_SESSION['sturecmsnumber'];
                    } elseif (isset($_SESSION['sturecmsstuid']) && $_SESSION['sturecmsstuid'] !== '') {
                        $patient_number = $_SESSION['sturecmsstuid']; // legacy fallback
                    }

                    $schedules = [];
                    if ($patient_number !== null) {
                        try {
                            $sqlS = "SELECT s.id, s.date, s.time, s.duration, s.status as sched_status, s.appointment_id, s.service_id, svc.name as service_name, a.status as appointment_status
                                     FROM tblschedule s
                                     LEFT JOIN tblservice svc ON svc.number = s.service_id
                                     LEFT JOIN tblappointment a ON a.id = s.appointment_id
                                     WHERE s.patient_number = :pn
                                     ORDER BY s.date DESC, s.time DESC";
                            $qS = $dbh->prepare($sqlS);
                            $qS->bindParam(':pn', $patient_number, PDO::PARAM_INT);
                            $qS->execute();
                            $schedules = $qS->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            // If tblschedule doesn't exist or other DB error, show empty state
                            $schedules = [];
                        }
                    }
                    ?>
                    <?php if (!empty($schedules)) { ?>
                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Date</th>
                              <th>Time</th>
                              <th>Duration (min)</th>
                              <th>Service</th>
                              <th>Schedule Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            function time12_vas($t) {
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
                            $k = 1; foreach ($schedules as $s) {
                              $ss = isset($s['sched_status']) ? $s['sched_status'] : '';
                              $as = isset($s['appointment_status']) ? $s['appointment_status'] : '';
                              $schedBadge = 'badge badge-secondary';
                              if ($ss === 'Ongoing') $schedBadge = 'badge badge-warning text-dark';
                              elseif ($ss === 'Done') $schedBadge = 'badge badge-success';

                              $appBadge = 'badge badge-secondary';
                              if ($as === 'Pending') $appBadge = 'badge badge-warning text-dark';
                              elseif ($as === 'Approved') $appBadge = 'badge badge-success';
                              elseif ($as === 'Declined') $appBadge = 'badge badge-danger';
                            ?>
                              <tr>
                                <td><?php echo $k++; ?></td>
                                <td><?php echo htmlspecialchars($s['date']); ?></td>
                                <td><?php echo htmlspecialchars(time12_vas($s['time'])); ?></td>
                                <td><?php echo htmlspecialchars($s['duration']); ?></td>
                                <td><?php echo htmlspecialchars($s['service_name']); ?></td>
                                <td><span class="<?php echo $schedBadge; ?>"><?php echo htmlspecialchars($ss); ?></span></td>
                                <td>
                                  <?php
                                    // action: cancel if not Done or already Cancelled
                                    $canCancel = true;
                                    if (strtolower($ss) === 'done' || strtolower($ss) === 'cancelled') $canCancel = false;
                                    if (empty($ss) && strtolower($as) === 'done') $canCancel = false;
                                  ?>
                                  <button type="button" class="btn btn-danger btn-sm cancel-btn" data-id="<?php echo intval($s['id']); ?>" <?php echo $canCancel ? '' : 'disabled'; ?>>Cancel</button>
                                </td>
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
         <?php include_once('includes/footer.php');?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <!-- End custom js for this page -->
    <!-- Cancel Reason Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post" id="cancelForm">
            <div class="modal-header">
              <h5 class="modal-title" id="cancelModalLabel">Cancel Service Appointment</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-bs-dismiss="modal">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="schedule_id" id="cancel_schedule_id" value="">
              <div class="form-group">
                <label for="cancel_reason_choice">Reason for cancellation</label>
                <select name="cancel_reason_choice" id="cancel_reason_choice" class="form-control">
                  <option value="">-- Select reason (optional) --</option>
                  <option value="Not available">Not available</option>
                  <option value="Found alternative provider">Found alternative provider</option>
                  <option value="Health issue">Health issue</option>
                  <option value="Scheduling conflict">Scheduling conflict</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label for="cancel_reason_specify">Please specify (optional)</label>
                <input type="text" name="cancel_reason_specify" id="cancel_reason_specify" class="form-control" placeholder="If Other, please specify">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="cancel_schedule" class="btn btn-danger">Confirm Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      (function(){
        // Attach click handlers to cancel buttons
        document.addEventListener('DOMContentLoaded', function(){
          var buttons = document.querySelectorAll('.cancel-btn');
          buttons.forEach(function(btn){
            btn.addEventListener('click', function(){
              var id = this.getAttribute('data-id');
              document.getElementById('cancel_schedule_id').value = id;
              // show modal (Bootstrap 4/5 compatible)
              if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                $('#cancelModal').modal('show');
              } else if (typeof bootstrap !== 'undefined') {
                var m = new bootstrap.Modal(document.getElementById('cancelModal'));
                m.show();
              }
            });
          });
        });
      })();
    </script>
  </body>
</html><?php }  ?>