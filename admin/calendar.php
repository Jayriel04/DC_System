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
    $sql = "DELETE FROM tblcalendar WHERE id=:rid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_STR);
    $query->execute();
    echo "<script>alert('Event deleted');</script>";
    echo "<script>window.location.href = 'manage-calendar.php'</script>";
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
  $sql = "SELECT * FROM tblcalendar WHERE MONTH(date) = :month AND YEAR(date) = :year";
  $query = $dbh->prepare($sql);
  $query->bindParam(':month', $currentMonth, PDO::PARAM_INT);
  $query->bindParam(':year', $currentYear, PDO::PARAM_INT);
  $query->execute();
  $events = $query->fetchAll(PDO::FETCH_OBJ);

  // Create an array to hold events by date
  $eventDays = [];
  foreach ($events as $event) {
    $eventDays[date('j', strtotime($event->date))][] = $event;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <title>Student Management System | Manage Calendar</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/calendar.css">
  </head>

  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php'); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
          <div class="content-wrapper">
            <!-- Add Event Button -->
            <div style="text-align: center; margin: 10px;">
              <a href="add-calendar-entry.php" class="btn"
                style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">Add
                Event</a>
            </div>
            <div class="calendar-wrapper">
              <div class="calendar-header">
                <h3><?php echo $months[$currentMonth - 1] . " " . $currentYear; ?></h3>
                <div>
                  <a href="?month=<?php echo ($currentMonth == 1) ? 12 : $currentMonth - 1; ?>&year=<?php echo ($currentMonth == 1) ? $currentYear - 1 : $currentYear; ?>"
                    class="btn">Prev</a>
                  <a href="?month=<?php echo ($currentMonth == 12) ? 1 : $currentMonth + 1; ?>&year=<?php echo ($currentMonth == 12) ? $currentYear + 1 : $currentYear; ?>"
                    class="btn">Next</a>
                </div>
              </div>

              <div class="calendar">
                <!-- Weekday Headers -->
                <?php foreach ($weekdays as $weekday): ?>
                  <div class="weekday"><?php echo $weekday; ?></div>
                <?php endforeach; ?>

                <?php
                // Empty days before the first day of the month
                for ($i = 0; $i < $startDay; $i++) {
                  echo '<div class="day"></div>';
                }

                // Display days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                  echo '<div class="day">';
                  echo '<h4>' . $day . '</h4>'; // Display day number
              
                  // Display events for the day
                  if (isset($eventDays[$day])) {
                    foreach ($eventDays[$day] as $event) {
                      echo '<div class="event">';
                      echo 'Time: ' . htmlentities($event->start_time) . ' - ' . htmlentities($event->end_time) . '<br>';
                      echo '<div>';
                      echo '<a href="edit-calendar-detail.php?editid=' . htmlentities($event->id) . '" class="btn">Edit</a>';
                      echo '<a href="manage-calendar.php?delid=' . htmlentities($event->id) . '" onclick="return confirm(\'Do you really want to Delete ?\');" class="btn btn-danger">Delete</a>';
                      echo '</div>';
                      echo '</div>';
                    }
                  }

                  echo '</div>';
                }
                ?>
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