<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'] == 0)) {
  header('location:logout.php');
} else {
  // Code for deletion 
  if (isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    // Fetch the calendar entries for date and start_time
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
        $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'Cannot delete: one or more appointments exist for this schedule.'];
        header('Location: calendar.php');
        exit();
      }
    }

    // Safe to delete
    $sql = "DELETE FROM tblcalendar WHERE id=:rid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_INT);
    $query->execute();
    $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Event deleted.'];
    header('Location: calendar.php');
  }

  // Handle admin declining an appointment
  if (isset($_GET['decline_date']) && isset($_GET['decline_time'])) {
    $dd = $_GET['decline_date'];
    $tt = $_GET['decline_time'];
    // Update appointments that match the date and start_time
    $sqld = "UPDATE tblappointment SET status = 'Declined' WHERE `date` = :dt AND `start_time` = :tm";
    $queryd = $dbh->prepare($sqld);
    $queryd->bindParam(':dt', $dd, PDO::PARAM_STR);
    $queryd->bindParam(':tm', $tt, PDO::PARAM_STR);
    if ($queryd->execute()) {
      $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment(s) declined.'];
    } else {
      $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'Unable to decline appointment(s).'];
    }
    header('Location: calendar.php');
    exit();
  }

  // calendar edit/update from modal
  if (isset($_POST['update_calendar'])) {
    $eid = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $udate = isset($_POST['date']) ? $_POST['date'] : null;
    $ustart = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $uend = isset($_POST['end_time']) ? $_POST['end_time'] : null;
    if ($eid > 0) {
      // Check if schedule entry already has an appointment (booked)
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
          $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'Cannot edit: one or more appointments exist for this schedule.'];
          header('Location: calendar.php');
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
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Event updated.'];
        header('Location: calendar.php');
        exit();
      } else {
        $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'Could not update event.'];
      }
    }
  }

  // add new schedule creation from modal
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
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'New schedule added successfully.'];
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred. Please try again.'];
        }
        header('Location: calendar.php');
        exit();
    }
  }
  
  $currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
  $currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

  $firstDayOfMonth = strtotime("{$currentYear}-{$currentMonth}-01");
  $daysInMonth = date('t', $firstDayOfMonth);
  $startDay = date('w', $firstDayOfMonth);
  $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

  
  $weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  
  $sql = "SELECT c.*, CASE WHEN a.id IS NULL THEN 0 ELSE 1 END AS booked_flag FROM tblcalendar c LEFT JOIN tblappointment a ON a.date = c.date AND a.start_time = c.start_time AND (a.status IS NULL OR a.status NOT IN ('Declined', 'Cancelled')) WHERE MONTH(c.date) = :month AND YEAR(c.date) = :year";
  $query = $dbh->prepare($sql);
  $query->bindParam(':month', $currentMonth, PDO::PARAM_INT);
  $query->bindParam(':year', $currentYear, PDO::PARAM_INT);
  $query->execute();
  $events = $query->fetchAll(PDO::FETCH_OBJ);

  
  $eventDays = [];
  foreach ($events as $event) {
    $dayIndex = date('j', strtotime($event->date));
    $event->booked = !empty($event->booked_flag);
    $eventDays[$dayIndex][] = $event;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta charset="utf-8">
    <title>Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
     <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/toast.css">
    <link rel="stylesheet" href="css/stylev2.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mas-modal.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  </head>

  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php'); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div id="toast-container"></div>
            <?php
            if (isset($_SESSION['toast_message'])) {
                echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                unset($_SESSION['toast_message']);
            }
            ?>
            <div class="content-wrapper" style="padding: 0.75rem 1.5rem 0;">
                <div class="header">
                    <div class="header-content">
                        <h1>Calendar</h1>
                        <p>View and manage schedule availability</p>
                    </div>
                    <button class="btn-add" id="addScheduleBtn">
                                <i class="fas fa-calendar"></i>
                                Add Schedule
                    </button>
                </div>

                <div class="calendar-container">
                    <div class="calendar-header">
                      <div class="header-controls" style="display: flex; align-items: center; gap: 15px;">
                        <button onclick="window.location.href='?month=<?php echo ($currentMonth == 1) ? 12 : $currentMonth - 1; ?>&year=<?php echo ($currentMonth == 1) ? $currentYear - 1 : $currentYear; ?>'" class="nav-button" title="Previous Month" style="width: 4vh;padding: 2px;background-color: white;border-radius: 5px;">&lt;</button>
                        <h2 class="month-year"><?php echo $months[$currentMonth - 1] . " " . $currentYear; ?></h2>
                        <button onclick="window.location.href='?month=<?php echo ($currentMonth == 12) ? 1 : $currentMonth + 1; ?>&year=<?php echo ($currentMonth == 12) ? $currentYear + 1 : $currentYear; ?>'" class="nav-button" title="Next Month" style="width: 4vh;padding: 2px;background-color: white;border-radius: 5px;">&gt;</button>
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
                                    
                                    echo '<div class="event-actions" style="margin-top: 5px;">';
                                    if (!empty($event->booked)) {
                                        echo '<button class="btn btn-secondary btn-xs" disabled title="This schedule has an appointment and cannot be edited or deleted.">Booked</button>';
                                    } else {
                                        
                                        echo '<button class="btn btn-primary btn-xs edit-event-btn"
                                                data-id="' . htmlentities($event->id) . '" 
                                                data-date="' . htmlentities($event->date) . '" 
                                                data-start="' . htmlentities($event->start_time) . '" 
                                                data-end="' . htmlentities($event->end_time) . '" 
                                                title="Edit" 
                                                style="background:none; border:none; padding:0; font-size: 1rem; color: #007bff; cursor:pointer;"><i class="fa-regular fa-pen-to-square"></i></button>';
                                        echo ' <a href="calendar.php?delid=' . htmlentities($event->id) . '" 
                                                onclick="return confirm(\'Do you really want to delete this schedule?\');" 
                                                class="btn btn-danger-emoji btn-xs" title="Delete" style="background:none; border:none; padding:0 0 0 8px; font-size: 1rem; color: #dc3545; cursor:pointer;"><i class="fa-regular fa-trash-can"></i></a>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        }

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

    <!-- Add Schedule Modal -->
    <div id="addScheduleModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Schedule</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="addScheduleForm" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add_date">Date</label>
                        <input type="date" id="add_date" name="date" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_start_time">Start Time</label>
                            <input type="time" id="add_start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="add_end_time">End Time</label>
                            <input type="time" id="add_end_time" name="end_time">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="add_schedule" class="btn btn-schedule" style="background-color: #008779 !important; color: white; cursor: pointer;">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Schedule</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editScheduleForm" method="POST">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="modal-body">
                    <div class="form-group"><label for="edit_date">Date</label><input type="date" id="edit_date" name="date" required></div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_start_time">Start Time</label><input type="time" id="edit_start_time" name="start_time" required></div>
                        <div class="form-group"><label for="edit_end_time">End Time</label><input type="time" id="edit_end_time" name="end_time"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_calendar" class="btn btn-update">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Add Schedule Modal ---
        const addModal = document.getElementById('addScheduleModal');
        const addOpenBtn = document.getElementById('addScheduleBtn');
        const addCloseBtn = addModal.querySelector('.close-button');
        const addCancelBtn = addModal.querySelector('.btn-cancel');

        addOpenBtn.addEventListener('click', (e) => { e.preventDefault(); addModal.style.display = 'flex'; });
        addCloseBtn.addEventListener('click', () => { addModal.style.display = 'none'; });
        addCancelBtn.addEventListener('click', () => { addModal.style.display = 'none'; });

        window.addEventListener('click', function (event) {
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        });

        // --- Edit Schedule Modal ---
        const editModal = document.getElementById('editScheduleModal');
        const editCloseBtn = editModal.querySelector('.close-button');
        const editCancelBtn = editModal.querySelector('.btn-cancel');

        function closeEditModal() {
            editModal.style.display = 'none';
        }

        editCloseBtn.addEventListener('click', closeEditModal);
        editCancelBtn.addEventListener('click', closeEditModal);

        window.addEventListener('click', function (event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });

        document.querySelectorAll('.edit-event-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_event_id').value = this.dataset.id;
                document.getElementById('edit_date').value = this.dataset.date;
                document.getElementById('edit_start_time').value = this.dataset.start;
                document.getElementById('edit_end_time').value = this.dataset.end;
                editModal.style.display = 'flex';
            });
        });
    });
    </script> 
  </body>
  </html>
<?php } ?>