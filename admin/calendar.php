<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'] == 0)) {
  header('location:logout.php');
} else {
  // Code for deletion: prevent deleting a calendar entry that already has an appointment
  if (isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    // Fetch the calendar entry's date and start_time
    $sqlFetch = "SELECT `date`, `start_time` FROM tblcalendar WHERE id = :rid";
    $queryFetch = $dbh->prepare($sqlFetch);
    $queryFetch->bindParam(':rid', $rid, PDO::PARAM_INT);
    $queryFetch->execute();
    $calEntry = $queryFetch->fetch(PDO::FETCH_OBJ);

    if ($calEntry) {
      // Check if there exists any appointment for this date/time that is not Declined
      $sqlCheck = "SELECT COUNT(*) FROM tblappointment WHERE `date` = :dt AND `start_time` = :tm AND (status IS NULL OR status != 'Declined')";
      $queryCheck = $dbh->prepare($sqlCheck);
      $queryCheck->bindParam(':dt', $calEntry->date, PDO::PARAM_STR);
      $queryCheck->bindParam(':tm', $calEntry->start_time, PDO::PARAM_STR);
      $queryCheck->execute();
      $count = $queryCheck->fetchColumn();

      if ($count > 0) {
        echo "<script>alert('Cannot delete: one or more appointments exist for this schedule.');</script>";
        echo "<script>window.location.href = 'calendar.php'</script>";
        exit();
      }
    }

    // Safe to delete
    $sql = "DELETE FROM tblcalendar WHERE id=:rid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_INT);
    $query->execute();
    echo "<script>alert('Event deleted');</script>";
    echo "<script>window.location.href = 'calendar.php'</script>";
  }

  // Handle admin declining an appointment: mark matching appointments as Declined
  if (isset($_GET['decline_date']) && isset($_GET['decline_time'])) {
    $dd = $_GET['decline_date'];
    $tt = $_GET['decline_time'];
    // Update appointment(s) that match the date and start_time
    $sqld = "UPDATE tblappointment SET status = 'Declined' WHERE `date` = :dt AND `start_time` = :tm";
    $queryd = $dbh->prepare($sqld);
    $queryd->bindParam(':dt', $dd, PDO::PARAM_STR);
    $queryd->bindParam(':tm', $tt, PDO::PARAM_STR);
    if ($queryd->execute()) {
      echo "<script>alert('Appointment(s) declined');</script>";
    } else {
      echo "<script>alert('Unable to decline appointment(s)');</script>";
    }
    echo "<script>window.location.href = 'calendar.php'</script>";
    exit();
  }

  // Handle calendar edit/update from modal
  if (isset($_POST['update_calendar'])) {
    $eid = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $udate = isset($_POST['date']) ? $_POST['date'] : null;
    $ustart = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $uend = isset($_POST['end_time']) ? $_POST['end_time'] : null;
    if ($eid > 0) {
      // Check if this calendar entry already has an appointment (booked)
      $sqlFetch = "SELECT `date`, `start_time` FROM tblcalendar WHERE id = :eid";
      $queryFetch = $dbh->prepare($sqlFetch);
      $queryFetch->bindParam(':eid', $eid, PDO::PARAM_INT);
      $queryFetch->execute();
      $calEntry = $queryFetch->fetch(PDO::FETCH_OBJ);

      if ($calEntry) {
        $sqlCheck = "SELECT COUNT(*) FROM tblappointment WHERE `date` = :dt AND `start_time` = :tm AND (status IS NULL OR status != 'Declined')";
        $queryCheck = $dbh->prepare($sqlCheck);
        $queryCheck->bindParam(':dt', $calEntry->date, PDO::PARAM_STR);
        $queryCheck->bindParam(':tm', $calEntry->start_time, PDO::PARAM_STR);
        $queryCheck->execute();
        $count = $queryCheck->fetchColumn();

        if ($count > 0) {
          echo "<script>alert('Cannot edit: one or more appointments exist for this schedule.');</script>";
          echo "<script>window.location.href = 'calendar.php'</script>";
          exit();
        }
      }

      $sqlu = "UPDATE tblcalendar SET date = :date, start_time = :start_time, end_time = :end_time WHERE id = :id";
      $queryu = $dbh->prepare($sqlu);
      $queryu->bindParam(':date', $udate, PDO::PARAM_STR);
      $queryu->bindParam(':start_time', $ustart, PDO::PARAM_STR);
      $queryu->bindParam(':end_time', $uend, PDO::PARAM_STR);
      $queryu->bindParam(':id', $eid, PDO::PARAM_INT);
      if ($queryu->execute()) {
        echo "<script>alert('Event updated');</script>";
        echo "<script>window.location.href = 'calendar.php'</script>";
        exit();
      } else {
        echo "<script>alert('Could not update event.');</script>";
      }
    }
  }

  // Handle new schedule creation from modal
  if (isset($_POST['add_schedule'])) {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (!empty($date) && !empty($start_time)) {
        $sql = "INSERT INTO tblcalendar (date, start_time, end_time) VALUES (:date, :start_time, :end_time)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $query->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        if ($query->execute()) {
            echo "<script>alert('New schedule added successfully.'); window.location.href='calendar.php';</script>";
        } else {
            echo "<script>alert('An error occurred. Please try again.');</script>";
        }
    }
  }
  // Month navigation
  $currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
  $currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

  $firstDayOfMonth = strtotime("{$currentYear}-{$currentMonth}-01");
  $daysInMonth = date('t', $firstDayOfMonth);
  $startDay = date('w', $firstDayOfMonth);
  $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

  // Get names of the days of the week
  $weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  // Fetch events
  // Fetch calendar events and mark as booked when a matching appointment exists (join on date and time)
  // Only consider appointments that are not Declined when calculating booked flag
  $sql = "SELECT c.*, CASE WHEN a.id IS NULL THEN 0 ELSE 1 END AS booked_flag FROM tblcalendar c LEFT JOIN tblappointment a ON a.date = c.date AND a.start_time = c.start_time AND (a.status IS NULL OR a.status != 'Declined') WHERE MONTH(c.date) = :month AND YEAR(c.date) = :year";
  $query = $dbh->prepare($sql);
  $query->bindParam(':month', $currentMonth, PDO::PARAM_INT);
  $query->bindParam(':year', $currentYear, PDO::PARAM_INT);
  $query->execute();
  $events = $query->fetchAll(PDO::FETCH_OBJ);

  // Create an array to hold events by date and flag booked events based strictly on tblappointment
  $eventDays = [];
  foreach ($events as $event) {
    $dayIndex = date('j', strtotime($event->date));
    // booked_flag comes from SQL join; coerce to boolean
    $event->booked = !empty($event->booked_flag);
    $eventDays[$dayIndex][] = $event;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <title>Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
     <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="./css/sidebar.css">
    <!-- endinject -->
    <!-- Custom CSS for new calendar UI -->
    <link rel="stylesheet" href="css/new-calendar.css">
  </head>

  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php'); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="header">
                        <div class="header-text">
                            <h2>Calendar</h2>
                            <p>View and manage schedule </p>
                        </div>
                            <a href="#" class="add-btn" id="addScheduleBtn">
                                <i class="fas fa-calendar"></i>
                             Add Schedule
                            </a>
                    </div>
                <div class="calendar-container">
                    <div class="calendar-header">
                      <div class="header-controls">
                        <a href="?month=<?php echo ($currentMonth == 1) ? 12 : $currentMonth - 1; ?>&year=<?php echo ($currentMonth == 1) ? $currentYear - 1 : $currentYear; ?>" class="btn btn-outline-dark btn-sm">&gt;</a>
                        <h2><?php echo $months[$currentMonth - 1] . " " . $currentYear; ?></h2>
                            <a href="?month=<?php echo ($currentMonth == 12) ? 1 : $currentMonth + 1; ?>&year=<?php echo ($currentMonth == 12) ? $currentYear + 1 : $currentYear; ?>" class="btn btn-outline-dark btn-sm">&gt;</a>
                        </div>
                    </div>

                    <div class="calendar">
                        <?php foreach ($weekdays as $weekday): ?>
                            <div class="day-header"><?php echo substr($weekday, 0, 3); ?></div>
                        <?php endforeach; ?>

                        <?php
                        // Previous month's days
                        $prevMonth = ($currentMonth == 1) ? 12 : $currentMonth - 1;
                        $prevYear = ($currentMonth == 1) ? $currentYear - 1 : $currentYear;
                        $daysInPrevMonth = date('t', strtotime("{$prevYear}-{$prevMonth}-01"));
                        for ($i = $startDay - 1; $i >= 0; $i--) {
                            echo '<div class="day-cell other-month"><div class="day-number">' . ($daysInPrevMonth - $i) . '</div></div>';
                        }

                        // Current month's days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            echo '<div class="day-cell">';
                            echo '<div class="day-number">' . $day . '</div>';

                            if (isset($eventDays[$day])) {
                                foreach ($eventDays[$day] as $event) {
                                    $eventClass = !empty($event->booked) ? 'booked' : 'available';
                                    
                                    $start_formatted = !empty($event->start_time) ? date('g:i A', strtotime($event->start_time)) : '';
                                    $end_formatted = !empty($event->end_time) ? date('g:i A', strtotime($event->end_time)) : '';
                                    $time_display = trim($start_formatted . ' - ' . $end_formatted, ' -');

                                    echo '<div class="event ' . $eventClass . '" title="Click to edit">';
                                    echo '<div>' . htmlentities($time_display) . '</div>';
                                    
                                    // Action buttons
                                    echo '<div class="event-actions" style="margin-top: 5px;">';
                                    if (!empty($event->booked)) {
                                        echo '<button class="btn btn-secondary btn-xs" disabled title="This schedule has an appointment and cannot be edited or deleted.">Booked</button>';
                                    } else {
                                        // Use emojis for edit and delete actions
                                        echo '<button class="btn btn-primary btn-xs edit-event-btn"
                                                data-id="' . htmlentities($event->id) . '" 
                                                data-date="' . htmlentities($event->date) . '" 
                                                data-start="' . htmlentities($event->start_time) . '" 
                                                data-end="' . htmlentities($event->end_time) . '" 
                                                title="Edit" 
                                                style="background:none; border:none; padding:0; font-size: 1.2rem; color: #007bff; cursor:pointer;">✏️</button>';
                                        echo ' <a href="calendar.php?delid=' . htmlentities($event->id) . '" 
                                                onclick="return confirm(\'Do you really want to delete this schedule?\');" 
                                                class="btn btn-danger-emoji btn-xs" title="Delete" style="background:none; border:none; padding:0 0 0 8px; font-size: 1.2rem; color: #dc3545; cursor:pointer;">🗑️</a>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        }

                        // Next month's days
                        $totalCells = $startDay + $daysInMonth;
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                        for ($i = 1; $i <= $remainingCells; $i++) {
                            echo '<div class="day-cell other-month"><div class="day-number">' . $i . '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
      </div>
    </div>
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/new-calendar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('addScheduleBtn').addEventListener('click', function(e) {
                e.preventDefault();
                const addModal = new bootstrap.Modal(document.getElementById('addScheduleModal'));
                addModal.show();
            });
        });
    </script>
    <!-- End custom js for this page -->

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1" role="dialog" aria-labelledby="editEventModalLabel"
      aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="editEventModalLabel">Edit Calendar Schedule</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="event_id" id="modal_event_id" value="">
              <div class="form-group">
                <label for="modal_date">Date</label>
                <input type="date" class="form-control" name="date" id="modal_date" required>
              </div>
              <div class="form-group">
                <label for="modal_start">Start Time</label>
                <input type="time" class="form-control" name="start_time" id="modal_start" required>
              </div>
              <div class="form-group">
                <label for="modal_end">End Time</label>
                <input type="time" class="form-control" name="end_time" id="modal_end" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="update_calendar" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addScheduleModalLabel"
      aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="addScheduleModalLabel">Add New Schedule</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="add_date">Date</label>
                <input type="date" class="form-control" name="date" id="add_date" required>
              </div>
              <div class="form-group">
                <label for="add_start">Start Time</label>
                <input type="time" class="form-control" name="start_time" id="add_start" required>
              </div>
              <div class="form-group">
                <label for="add_end">End Time</label>
                <input type="time" class="form-control" name="end_time" id="add_end">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
  </html>
<?php } ?>