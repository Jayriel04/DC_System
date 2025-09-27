<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Compatible session check used in other user pages
if (strlen($_SESSION['sturecmsstuid'] == 0)) {
    if (strlen($_SESSION['sturecmsnumber']) == 0) {
        header('location:logout.php');
        exit();
    }
}

// Month navigation
$currentMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$currentYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

$firstDayOfMonth = strtotime("{$currentYear}-{$currentMonth}-01");
$daysInMonth = date('t', $firstDayOfMonth);
$startDay = date('w', $firstDayOfMonth);
$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

$weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

// Fetch events for the month
$sql = "SELECT * FROM tblcalendar WHERE MONTH(date) = :month AND YEAR(date) = :year";
$query = $dbh->prepare($sql);
$query->bindParam(':month', $currentMonth, PDO::PARAM_INT);
$query->bindParam(':year', $currentYear, PDO::PARAM_INT);
$query->execute();
$events = $query->fetchAll(PDO::FETCH_OBJ);

$eventDays = [];
foreach ($events as $event) {
    $dayIndex = date('j', strtotime($event->date));
    $eventDays[$dayIndex][] = $event;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Patient Calendar</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/calendar.css">
    <style>
        /* small tweaks for user calendar */
        .calendar-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }
        /* small colored box for events; tooltip shows details on hover */
        .event-box {
            width: 14px;
            height: 14px;
            display: inline-block;
            background: #007bff;
            border-radius: 3px;
            margin: 3px 4px 3px 0;
            vertical-align: middle;
            cursor: default;
        }
        .event-container { display:flex; flex-wrap:wrap; align-items:center; }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
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
                                echo '<h4>' . $day . '</h4>';

                                if (isset($eventDays[$day])) {
                                                    echo '<div class="event-container">';
                                                    foreach ($eventDays[$day] as $event) {
                                                        // prepare tooltip text: time, title, description
                                                        $parts = [];
                                                        if (!empty($event->start_time) || !empty($event->end_time)) {
                                                            $start_formatted = '';
                                                            $end_formatted = '';
                                                            if (!empty($event->start_time) && strtotime($event->start_time) !== false) {
                                                                $start_formatted = date('g:i A', strtotime($event->start_time));
                                                            } elseif (!empty($event->start_time)) {
                                                                $start_formatted = $event->start_time;
                                                            }
                                                            if (!empty($event->end_time) && strtotime($event->end_time) !== false) {
                                                                $end_formatted = date('g:i A', strtotime($event->end_time));
                                                            } elseif (!empty($event->end_time)) {
                                                                $end_formatted = $event->end_time;
                                                            }
                                                            $timePart = trim($start_formatted . ' - ' . $end_formatted);
                                                            if ($timePart !== '-') $parts[] = 'Time: ' . $timePart;
                                                        }

                                                        // title/description
                                                        $title = '';
                                                        if (isset($event->title) && trim($event->title) !== '') {
                                                            $title = $event->title;
                                                        } elseif (isset($event->EventTitle) && trim($event->EventTitle) !== '') {
                                                            $title = $event->EventTitle;
                                                        }
                                                        if (!empty($title)) $parts[] = 'Title: ' . $title;

                                                        $desc = '';
                                                        if (isset($event->description) && trim($event->description) !== '') {
                                                            $desc = $event->description;
                                                        } elseif (isset($event->EventDetails) && trim($event->EventDetails) !== '') {
                                                            $desc = $event->EventDetails;
                                                        }
                                                        if (!empty($desc)) $parts[] = 'Description: ' . $desc;

                                                        $tooltip = htmlentities(implode("\n", $parts));

                                                        echo '<div class="event-box" title="' . $tooltip . '"></div>';
                                                    }
                                                    echo '</div>';
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
    <script src="./vendors/chart.js/Chart.min.js"></script>
    <script src="./vendors/moment/moment.min.js"></script>
    <script src="./vendors/daterangepicker/daterangepicker.js"></script>
    <script src="./vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="./js/dashboard.js"></script>
</body>

</html>