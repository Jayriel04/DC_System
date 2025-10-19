<?php
session_start();
//error_reporting(0);
include('includes/dbconnection.php');
if (strlen(isset($_SESSION['sturecmsnumber']) ? $_SESSION['sturecmsnumber'] : '') == 0) {
  header('location:logout.php');
} else {

  // Patient number from session (primary session key)
  $patient_number = isset($_SESSION['sturecmsnumber']) ? $_SESSION['sturecmsnumber'] : (isset($_SESSION['sturecmsstuid']) ? $_SESSION['sturecmsstuid'] : null);

  // AJAX endpoint: return calendar times for a given date (used to populate time dropdown)
  if (isset($_GET['get_calendar_times']) && !empty($_GET['date'])) {
    $reqDate = $_GET['date'];
    try {
      // Only return calendar slots for the date that are not already booked
      $stmt = $dbh->prepare("SELECT c.id, c.start_time, c.end_time
        FROM tblcalendar c
        LEFT JOIN tblappointment a ON a.date = c.date AND a.start_time = c.start_time
        WHERE c.date = :date AND a.id IS NULL
        ORDER BY c.start_time");
      $stmt->bindParam(':date', $reqDate, PDO::PARAM_STR);
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $out = [];
      foreach ($rows as $r) {
        $start = $r['start_time'];
        $end = $r['end_time'];
        $label = $start;
        if (strtotime($start) !== false) $label = date('g:i A', strtotime($start));
        if (!empty($end)) {
          $label .= ' - ' . (strtotime($end) !== false ? date('g:i A', strtotime($end)) : $end);
        }
        $out[] = ['id' => $r['id'], 'start' => $start, 'end' => $end, 'label' => $label];
      }
      header('Content-Type: application/json');
      echo json_encode($out);
    } catch (Exception $e) {
      header('Content-Type: application/json', true, 500);
      echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
  }

  // Handle modal submission: Book Appointment and save health conditions
  $modal_error = '';
  if (isset($_POST['book_appointment'])) {
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';

    if (empty($appointment_date) || empty($appointment_time)) {
      $modal_error = 'Please select both appointment date and time.';
      $show_health_modal = true;
    } else {
      // check duplicate appointment for same patient/date/time
      try {
  $sqlChk = "SELECT COUNT(*) FROM tblappointment WHERE patient_number = :pn AND `date` = :dt AND `start_time` = :tm";
        $qryChk = $dbh->prepare($sqlChk);
        $qryChk->bindParam(':pn', $patient_number, PDO::PARAM_INT);
        $qryChk->bindParam(':dt', $appointment_date, PDO::PARAM_STR);
        $qryChk->bindParam(':tm', $appointment_time, PDO::PARAM_STR);
        $qryChk->execute();
        $exists = (int) $qryChk->fetchColumn();
        if ($exists > 0) {
          $modal_error = 'You already have an appointment at that date and time.';
          $show_health_modal = true;
        } else {
          $firstname = isset($_SESSION['sturecmsfirstname']) ? $_SESSION['sturecmsfirstname'] : '';
          $surname = isset($_SESSION['sturecmssurname']) ? $_SESSION['sturecmssurname'] : '';
          $status_default = 'Pending';
          $sqlIns = "INSERT INTO tblappointment (`firstname`, `surname`, `date`, `start_time`, `patient_number`, `status`) VALUES (:fn, :sn, :dt, :tm, :pn, :st)";
          $qryIns = $dbh->prepare($sqlIns);
          $qryIns->bindParam(':fn', $firstname, PDO::PARAM_STR);
          $qryIns->bindParam(':sn', $surname, PDO::PARAM_STR);
          $qryIns->bindParam(':dt', $appointment_date, PDO::PARAM_STR);
          $qryIns->bindParam(':tm', $appointment_time, PDO::PARAM_STR);
          $qryIns->bindParam(':pn', $patient_number, PDO::PARAM_INT);
          $qryIns->bindParam(':st', $status_default, PDO::PARAM_STR);
          $qryIns->execute();

          $_SESSION['modal_success'] = 'Appointment booked successfully.';
          header('Location: vac.php');
          exit();
        }
      } catch (Exception $e) {
        $modal_error = 'Database error: ' . $e->getMessage();
        $show_health_modal = true;
      }
    }
  }

  // Handle cancellation from modal
  if (isset($_POST['confirm_cancel'])) {
    $cancelId = isset($_POST['cancel_appointment_id']) ? intval($_POST['cancel_appointment_id']) : 0;
    $cancelReason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
    if ($cancelId > 0) {
      try {
        // Update appointment status
        $upd = $dbh->prepare("UPDATE tblappointment SET status = 'Cancelled' WHERE id = :id");
        $upd->bindParam(':id', $cancelId, PDO::PARAM_INT);
        $upd->execute();

        // If there is a service schedule attached, mark it cancelled and record reason
        $chk = $dbh->prepare("SELECT id FROM tblschedule WHERE appointment_id = :aid LIMIT 1");
        $chk->bindParam(':aid', $cancelId, PDO::PARAM_INT);
        $chk->execute();
        $sch = $chk->fetch(PDO::FETCH_ASSOC);
        if ($sch && isset($sch['id'])) {
          $updSch = $dbh->prepare("UPDATE tblschedule SET status = 'Cancelled', cancel_reason = :reason, cancelled_at = NOW() WHERE appointment_id = :aid");
          $updSch->bindParam(':reason', $cancelReason, PDO::PARAM_STR);
          $updSch->bindParam(':aid', $cancelId, PDO::PARAM_INT);
          $updSch->execute();
        }

        $_SESSION['modal_success'] = 'Appointment cancelled.' . (!empty($cancelReason) ? ' Reason: ' . htmlspecialchars($cancelReason) : '');
        header('Location: vac.php');
        exit();
      } catch (Exception $e) {
        $modal_error = 'Unable to cancel appointment: ' . $e->getMessage();
        $show_health_modal = true;
      }
    }
  }

  // Fetch existing health conditions to decide whether to show modal automatically
  $existing_health = '';
  try {
    $sql = "SELECT health_conditions FROM tblpatient WHERE number = :num";
    $query = $dbh->prepare($sql);
    $query->bindParam(':num', $patient_number, PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_OBJ);
    if ($row && isset($row->health_conditions)) $existing_health = trim($row->health_conditions);
  } catch (Exception $e) {
    $existing_health = '';
  }
  $show_health_modal = isset($show_health_modal) ? $show_health_modal : (empty($existing_health) || $existing_health === 'null' || $existing_health === '[]');

  ?> 
  <!DOCTYPE html>
  <html lang="en">
 
  <head>   

    <title>Consultation Appointments</title>
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
      <?php include_once('includes/header.php'); ?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include_once('includes/sidebar.php'); ?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Consultation Appointments </h3>
             
              <!-- Book Consultation button -->
              <div class="mt-2">
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#healthModal" data-bs-toggle="modal" data-bs-target="#healthModal">Book Consultation</button>
              </div>
            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">Your Consultation Appointments</h5>
                    <p>&nbsp;</p>

                    <?php
                    // determine patient number from session (try multiple keys used across app)
                    $patient_number = null;
                    // determine patient number from session (primary key used across app is 'sturecmsnumber')
                    if (isset($_SESSION['sturecmsnumber']) && $_SESSION['sturecmsnumber'] !== '') {
                      $patient_number = $_SESSION['sturecmsnumber'];
                    } elseif (isset($_SESSION['sturecmsstuid']) && $_SESSION['sturecmsstuid'] !== '') {
                      // legacy fallback
                      $patient_number = $_SESSION['sturecmsstuid'];
                    }

                    $appointments = [];
                    if ($patient_number !== null) {
                      try {
                        $sqlA = "SELECT id, `date`, start_time AS `time`, status FROM tblappointment WHERE patient_number = :pn ORDER BY `date` DESC, `time` DESC";
                        $qA = $dbh->prepare($sqlA);
                        $qA->bindParam(':pn', $patient_number, PDO::PARAM_INT);
                        $qA->execute();
                        $appointments = $qA->fetchAll(PDO::FETCH_ASSOC);
                      } catch (Exception $e) {
                        // ignore and show empty state
                        $appointments = [];
                      }
                    }
                    ?>
                    <?php if (!empty($appointments)) { ?>
                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Date</th>
                              <th>Time</th>
                              <th>Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php $k = 1;
                            function time12_vac($t) {
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
                            foreach ($appointments as $ap) {
                              $status = isset($ap['status']) ? $ap['status'] : '';
                              $badgeClass = 'badge badge-secondary';
                              if ($status === 'Pending')
                                $badgeClass = 'badge badge-warning text-dark';
                              elseif ($status === 'Approved')
                                $badgeClass = 'badge badge-success';
                              elseif ($status === 'Declined')
                                $badgeClass = 'badge badge-danger';
                              ?>
                              <tr>
                                <td><?php echo $k++; ?></td>
                                <td><?php echo htmlspecialchars($ap['date']); ?></td>
                                <td><?php echo htmlspecialchars(time12_vac($ap['time'])); ?></td>
                                <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                  <?php if ($status !== 'Cancelled' && $status !== 'Declined') { ?>
                                    <button class="btn btn-danger btn-sm btn-cancel" data-id="<?php echo intval($ap['id']); ?>" data-date="<?php echo htmlspecialchars($ap['date']); ?>" data-time="<?php echo htmlspecialchars($ap['time']); ?>">Cancel</button>
                                  <?php } else { echo '-'; } ?>
                                </td>
                              </tr>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                    <?php } else { ?>
                      <p>No consultation appointments found.</p>
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
    <!-- Health Conditions Modal (copied from dashboard) -->
    <?php if (isset($show_health_modal)) { ?>
      <div class="modal fade" id="healthModal" tabindex="-1" role="dialog" aria-labelledby="healthModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <form method="post">
              <div class="modal-header">
                <h5 class="modal-title" id="healthModalLabel">Book Consultation</h5>
                <button type="button" class="close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
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
                      <select class="form-control" name="appointment_time" id="appointment_time">
                        <option value="">-- Select a time --</option>
                      </select>
                    </div>
                  </div>
                </div>

                <?php if (!empty($modal_error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($modal_error) . '</div>'; } ?>

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
            if (<?php echo $show_health_modal ? 'true' : 'false'; ?>) {
              $('#healthModal').modal({ backdrop: 'static', keyboard: false });
              $('#healthModal').modal('show');
            }
          });
        } else if (typeof bootstrap !== 'undefined') {
          document.addEventListener('DOMContentLoaded', function () {
            if (<?php echo $show_health_modal ? 'true' : 'false'; ?>) {
              var myModal = new bootstrap.Modal(document.getElementById('healthModal'), { backdrop: 'static', keyboard: false });
              myModal.show();
            }
          });
        }
      })();
    </script>
    <script>
      // Populate times dropdown from tblcalendar via AJAX endpoint
      (function () {
        function populateTimes(date) {
          var sel = document.getElementById('appointment_time');
          if (!sel) return;
          sel.innerHTML = '<option value="">-- Select a time --</option>';
          if (!date) return;
          var url = 'vac.php?get_calendar_times=1&date=' + encodeURIComponent(date);
          if (typeof $ !== 'undefined' && typeof $.get === 'function') {
            $.get(url).done(function (data) {
              try {
                data.forEach(function (item) {
                  var opt = document.createElement('option');
                  opt.value = item.start; // post start time
                  opt.textContent = item.label;
                  sel.appendChild(opt);
                });
              } catch (e) {}
            });
          } else {
            fetch(url).then(function (r) { return r.json(); }).then(function (data) {
              data.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item.start;
                opt.textContent = item.label;
                sel.appendChild(opt);
              });
            }).catch(function (err) {
              // ignore
            });
          }
        }

        // Populate when date changes
        var dateInput = document.getElementById('appointment_date');
        if (dateInput) {
          dateInput.addEventListener('change', function () {
            populateTimes(this.value);
          });
        }

        // When modal opens, populate times for current date value
        var healthModal = document.getElementById('healthModal');
        if (healthModal) {
          healthModal.addEventListener('shown.bs.modal', function () {
            var d = dateInput ? dateInput.value : '';
            populateTimes(d);
          });
          // also for bootstrap 4 jQuery event
          if (typeof $ !== 'undefined') {
            $(healthModal).on('shown.bs.modal', function () {
              var d = dateInput ? dateInput.value : '';
              populateTimes(d);
            });
          }
        }
      })();
    </script>
    <!-- Cancel Appointment Modal -->
    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="cancelAppointmentLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="cancelAppointmentLabel">Cancel Appointment</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="cancel_appointment_id" id="cancel_appointment_id" value="">
              <div class="form-group">
                <label for="cancel_reason">Reason for cancellation (optional)</label>
                <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="confirm_cancel" class="btn btn-danger">Confirm Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      (function () {
        function openCancelModal(id) {
          var hid = document.getElementById('cancel_appointment_id');
          if (hid) hid.value = id;
          var m = document.getElementById('cancelAppointmentModal');
          if (!m) return;
          if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
            $('#cancelAppointmentModal').modal('show');
          } else if (typeof bootstrap !== 'undefined') {
            var bs = new bootstrap.Modal(m);
            bs.show();
          }
        }

        // attach click handlers
        document.addEventListener('DOMContentLoaded', function () {
          var els = document.querySelectorAll('.btn-cancel');
          els.forEach(function (btn) {
            btn.addEventListener('click', function () {
              var id = this.getAttribute('data-id');
              openCancelModal(id);
            });
          });
        });
      })();
    </script>
  </body>

  </html><?php } ?>