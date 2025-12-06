<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else { ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
        <meta charset="utf-8">
        <title>Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
        <meta charset="utf-8">
        <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
        <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
        <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="./css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="./css/dashboard.css">
        <link rel="stylesheet" href="./css/sidebar.css">
        <link rel="stylesheet" href="./css/responsive.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    </head>
    <body>
        <div class="container-scroller">
            <?php include_once('includes/header.php'); ?>
            <div class="container-fluid page-body-wrapper">
                <?php include_once('includes/sidebar.php'); ?>
                <div class="main-panel">
                    <div class="content-wrapper">
                        <div class="container">
                            <?php
                            // Fetch data for stat cards
                            $sql_patients = "SELECT COUNT(*) FROM tblpatient";
                            $query_patients = $dbh->prepare($sql_patients);
                            $query_patients->execute();
                            $total_patients = $query_patients->fetchColumn();

                            $sql_services = "SELECT COUNT(*) FROM tblservice";
                            $query_services = $dbh->prepare($sql_services);
                            $query_services->execute();
                            $total_services = $query_services->fetchColumn();

                            $sql_appointments = "SELECT COUNT(*) FROM tblappointment";
                            $query_appointments = $dbh->prepare($sql_appointments);
                            $query_appointments->execute();
                            $total_appointments = $query_appointments->fetchColumn();

                            // Fetch average rating from tblpatient
                            $sql_rating = "SELECT AVG(rating) FROM tblpatient WHERE rating > 0";
                            $query_rating = $dbh->prepare($sql_rating);
                            $query_rating->execute();
                            $avg_rating = $query_rating->fetchColumn() ?: 0;

                            // Fetch latest appointments
                            $sql_latest_app = "SELECT a.date, a.start_time, a.status, p.firstname, p.surname, p.Image, p.sex, s.name as service_name 
                                               FROM tblappointment a 
                                               JOIN tblpatient p ON a.patient_number = p.number 
                                               LEFT JOIN tblschedule sch ON sch.appointment_id = a.id
                                               LEFT JOIN tblservice s ON sch.service_id = s.number
                                               ORDER BY a.date DESC, a.start_time DESC 
                                               LIMIT 5";
                            $query_latest_app = $dbh->prepare($sql_latest_app);
                            $query_latest_app->execute();
                            $latest_appointments = $query_latest_app->fetchAll(PDO::FETCH_OBJ);

                            // Fetch popular services
                            $sql_popular_svc = "SELECT s.name, COUNT(sch.id) as appointment_count
                                                FROM tblschedule sch
                                                JOIN tblservice s ON sch.service_id = s.number
                                                GROUP BY s.name
                                                ORDER BY appointment_count DESC
                                                LIMIT 6";
                            $query_popular_svc = $dbh->prepare($sql_popular_svc);
                            $query_popular_svc->execute();
                            $popular_services = $query_popular_svc->fetchAll(PDO::FETCH_OBJ);
                            $max_appointments = 0;
                            if (!empty($popular_services)) {
                                $max_appointments = $popular_services[0]->appointment_count;
                            }
                            
                            // Fetch recent reviews from tblpatient
                            $sql_reviews = "SELECT firstname, surname, Image, sex, rating, feedback, created_at FROM tblpatient WHERE feedback IS NOT NULL AND feedback != '' ORDER BY created_at DESC LIMIT 3";
                            $query_reviews = $dbh->prepare($sql_reviews);
                            $query_reviews->execute();
                            $recent_reviews = $query_reviews->fetchAll(PDO::FETCH_OBJ);

                            // --- Chart Data Logic ---
                            $period = isset($_GET['period']) ? $_GET['period'] : 'monthly'; // Default to monthly

                            $months = [];
                            $appointments_data = [];
                            $new_patients_data = [];
                            $total_patients_data = [];

                            $date_format_sql = '';
                            $group_by_sql = '';
                            $interval_sql = '';
                            $patient_group_by_sql = ''; // This will be for the date column
                            $php_date_format = '';
                            $php_interval_unit = '';
                            $interval_count = 0;

                            switch ($period) {
                                case 'daily':
                                    $date_format_sql = '%Y-%m-%d';
                                    $group_by_sql = "DATE(a.date)";
                                    $patient_group_by_sql = "DATE(p.created_at)";
                                    $interval_sql = 'INTERVAL 7 DAY';
                                    $php_date_format = 'D';
                                    $php_interval_unit = 'day';
                                    $interval_count = 6;
                                    break;
                                case 'weekly':
                                    $date_format_sql = '%x%v';
                                    $group_by_sql = "YEARWEEK(a.date, 1)";
                                    $patient_group_by_sql = "YEARWEEK(p.created_at, 1)";
                                    $interval_sql = 'INTERVAL 7 WEEK';
                                    // The label will be custom-generated below
                                    $php_interval_unit = 'week';
                                    $interval_count = 6;
                                    break;
                                case 'yearly':
                                    $date_format_sql = '%Y'; // Not used in final query, but kept for consistency
                                    $group_by_sql = "YEAR(a.date)";
                                    $patient_group_by_sql = "YEAR(p.created_at)";
                                    $interval_sql = 'INTERVAL 5 YEAR';
                                    $php_date_format = 'Y';
                                    $php_interval_unit = 'year';
                                    $interval_count = 4;
                                    break;
                                case 'monthly':
                                default:
                                    $date_format_sql = '%Y-%m'; // Not used in final query, but kept for consistency
                                    $group_by_sql = "DATE_FORMAT(a.date, '%Y-%m')";
                                    $patient_group_by_sql = "DATE_FORMAT(p.created_at, '%Y-%m')";
                                    $interval_sql = 'INTERVAL 7 MONTH';
                                    $php_date_format = 'M';
                                    $php_interval_unit = 'month';
                                    $interval_count = 6;
                                    break;
                            }

                            for ($i = $interval_count; $i >= 0; $i--) {
                                $current_date = strtotime("-$i $php_interval_unit");
                                
                                if ($period === 'weekly') {
                                    $date_key = date('oW', $current_date); // ISO-8601 year and week
                                    $week_start_ts = strtotime("-$i week Monday this week");
                                    $week_end_ts = strtotime("-$i week Sunday this week");
                                    $label = date('M d', $week_start_ts) . '-' . date('d', $week_end_ts);
                                    $months[$label] = $date_key;
                                } else {
                                    $label = date($php_date_format, strtotime("-$i $php_interval_unit"));
                                    $date_key = date(($period === 'yearly' ? 'Y' : 'Y-m-d'), $current_date);
                                    if ($period === 'monthly') $date_key = date('Y-m', $current_date);
                                    $months[$label] = $date_key;
                                }
                                $appointments_data[$date_key] = 0;
                                $new_patients_data[$date_key] = 0;
                            }

                            // Fetch appointments count
                            $sql_chart_app = "SELECT COUNT(a.id) as count, $group_by_sql as period_key FROM tblappointment a GROUP BY period_key";
                            $query_chart_app = $dbh->prepare($sql_chart_app);
                            $query_chart_app->execute();
                            $app_results = $query_chart_app->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($app_results as $row) {
                                if (isset($appointments_data[$row['period_key']])) {
                                    $appointments_data[$row['period_key']] = (int)$row['count'];
                                }
                            }

                            // Fetch new patients count
                            // Use COALESCE to treat NULL created_at as a very old date so it's grouped but likely outside our display range.
                            // This ensures all patients are considered in the cumulative count.
                            $sql_chart_pat = "SELECT COUNT(p.number) as count, $patient_group_by_sql as period_key FROM tblpatient p GROUP BY period_key";
                            $query_chart_pat = $dbh->prepare($sql_chart_pat);
                            $query_chart_pat->execute();
                            $pat_results = $query_chart_pat->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($pat_results as $row) {
                                if (isset($new_patients_data[$row['period_key']])) {
                                    $new_patients_data[$row['period_key']] = (int)$row['count'];
                                }
                            }

                            // Calculate cumulative total patients
                            // For each period, count all patients created up to the end of that period.
                            foreach ($months as $label => $date_key) {
                                $end_date_for_period = date('Y-m-d', strtotime("last day of " . $label));
                                if ($period === 'daily') {
                                    $end_date_for_period = $date_key;
                                } else if ($period === 'weekly') {
                                    // Custom logic to get end of week for weekly period key
                                    $year = substr($date_key, 0, 4);
                                    $week = substr($date_key, 4, 2);
                                    $end_date_for_period = date('Y-m-d', strtotime($year."W".$week."7"));
                                }
                                $sql_cumulative = "SELECT COUNT(number) FROM tblpatient WHERE created_at IS NULL OR DATE(created_at) <= :end_date";
                                $query_cumulative = $dbh->prepare($sql_cumulative);
                                $query_cumulative->bindParam(':end_date', $end_date_for_period, PDO::PARAM_STR);
                                $query_cumulative->execute();
                                $total_patients_data[$date_key] = (int)$query_cumulative->fetchColumn();
                            }

                            ?>
                            <!-- Stats Grid -->
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Patients</h3>
                                        <div class="stat-number"><?php echo number_format($total_patients); ?></div>
                                        
                                    </div>
                                    <div class="stat-icon icon-blue">👤</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Services Available</h3>
                                        <div class="stat-number"><?php echo number_format($total_services); ?></div>
                                        
                                    </div>
                                    <div class="stat-icon icon-green">❤️</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Appointments</h3>
                                        <div class="stat-number"><?php echo number_format($total_appointments); ?></div>
                                        
                                    </div>
                                    <div class="stat-icon icon-purple">📅</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Average Rating</h3>
                                        <div class="stat-number" style="font-size: 28px;">
                                            <?php echo number_format($avg_rating, 1); ?><span class="stars"><?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?></span>
                                        </div>
                                        
                                    </div>
                                    <div class="stat-icon icon-yellow">⭐</div>
                                </div>
                            </div>

                            <!-- Chart Section -->
                            <div class="chart-section">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 280px;">
                                        <h2 class="card-title" style="margin: 0; white-space: nowrap;">Clinic Statistics</h2>
                                        <div class="btn-group" role="group" style="gap: 8px;">
                                            <a href="dashboard.php?period=daily" class="btn btn-sm <?php echo ($period === 'daily' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 8px; border: 1px solid;">Daily</a>
                                            <a href="dashboard.php?period=weekly" class="btn btn-sm <?php echo ($period === 'weekly' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 8px; border: 1px solid;">Weekly</a>
                                            <a href="dashboard.php?period=monthly" class="btn btn-sm <?php echo ($period === 'monthly' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 8px; border: 1px solid;">Monthly</a>
                                            <a href="dashboard.php?period=yearly" class="btn btn-sm <?php echo ($period === 'yearly' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 8px; border: 1px solid;">Yearly</a>
                                        </div>
                                    </div>
                                    <div class="legend">
                                        <div class="legend-item">
                                            <div class="legend-dot dot-teal"></div>
                                            <span>Appointments</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-dot dot-blue"></div>
                                            <span>Patients</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-dot dot-green"></div>
                                            <span>New Patients</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="appointmentChart"></canvas>
                                </div>
                            </div>

                            <!-- Content Grid -->
                            <div class="content-grid">
                                <!-- Latest Appointments -->
                                <div class="card">
                                    <div class="card-header">
                                        <h2 class="card-title">Latest Appointments</h2>
                                        <a href="mac.php" class="view-link">View All</a>
                                    </div>

                                    <?php foreach ($latest_appointments as $appointment): ?>
                                        <?php
                                        $avatar_image = 'avatar.png'; // Default fallback
                                        if (!empty($appointment->Image)) {
                                            $avatar_image = $appointment->Image;
                                        } elseif ($appointment->sex === 'Male') {
                                            $avatar_image = 'man-icon.png';
                                        } elseif ($appointment->sex === 'Female') {
                                            $avatar_image = 'woman-icon.jpg';
                                        }
                                        ?>
                                        <div class="appointment">
                                            <img class="avatar" src="../admin/images/<?php echo htmlentities($avatar_image); ?>" alt="Avatar">
                                            <div class="appointment-info">
                                                <div class="patient-name">
                                                    <?php echo htmlentities($appointment->firstname . ' ' . $appointment->surname); ?>
                                                </div>
                                                <div class="service-type">
                                                    <?php echo htmlentities($appointment->service_name ?: 'Consultation'); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="appointment-time">
                                                    <?php echo date("Y-m-d g:i A", strtotime($appointment->date . ' ' . $appointment->start_time)); ?>
                                                </div>
                                                <span class="status status-<?php echo strtolower(str_replace(' ', '-', htmlentities($appointment->status))); ?>">
                                                    <?php echo htmlentities($appointment->status); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($latest_appointments)): ?>
                                        <p>No recent appointments.</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Popular Services -->
                                <div class="card">
                                    <div class="card-header">
                                        <h2 class="card-title">Popular Services</h2>
                                        <a href="mas.php" class="view-link">View Details</a>
                                    </div>

                                    <?php foreach ($popular_services as $service):
                                        $bar_width = $max_appointments > 0 ? ($service->appointment_count / $max_appointments) * 100 : 0;
                                        ?>
                                        <div class="service-item">
                                            <div class="service-name">
                                                <?php echo htmlentities($service->name); ?>
                                                <span class="service-count">
                                                    <?php echo $service->appointment_count; ?> appointments
                                                </span>
                                            </div>
                                            <div class="service-bar">
                                                <div class="bar-fill" style="width: <?php echo $bar_width; ?>%;"></div>
                                            </div>
                                            <div class="service-meta">
                                                <span class="revenue"></span>
                                                <span><?php echo round($bar_width); ?>%</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($popular_services)): ?>
                                        <p>No service data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Reviews -->
                            <div class="card-tall">
                                <div class="card-header">
                                    <h2 class="card-title">Recent Reviews</h2>
                                    <a href="manage-reviews.php" class="view-link">View All Reviews</a>
                                </div>
                                <?php if (!empty($recent_reviews)): ?>
                                    <?php foreach ($recent_reviews as $review): ?>
                                        <?php
                                        $review_avatar = 'avatar.png'; // Default fallback
                                        if (!empty($review->Image)) {
                                            $review_avatar = $review->Image;
                                        } elseif ($review->sex === 'Male') {
                                            $review_avatar = 'man-icon.png';
                                        } elseif ($review->sex === 'Female') {
                                            $review_avatar = 'woman-icon.jpg';
                                        }
                                        ?>
                                        <div class="review">
                                            <div class="review-header">
                                                <div style="display: flex; gap: 16px; align-items: center;">
                                                    <img class="avatar" src="../admin/images/<?php echo htmlentities($review_avatar); ?>" alt="Avatar">
                                                    <div class="review-info">
                                                        <div class="review-name">
                                                            <?php echo htmlentities($review->firstname . ' ' . $review->surname); ?>
                                                        </div>
                                                        <div class="review-service">Patient Review</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="review-stars">
                                                        <?php echo str_repeat('★', (int) $review->rating) . str_repeat('☆', 5 - (int) $review->rating); ?>
                                                    </div>
                                                    <div class="review-date">
                                                        <?php echo date("Y-m-d", strtotime($review->created_at)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="review-text">"<?php echo htmlentities($review->feedback); ?>"</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No recent reviews.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
        <script src="vendors/js/vendor.bundle.base.js"></script>
        <script src="js/off-canvas.js"></script>
        <script src="js/misc.js"></script>
        <script>
            const ctx = document.getElementById('appointmentChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($months)); ?>,
                    datasets: [
                        {
                            label: 'Appointments',
                            data: <?php echo json_encode(array_values($appointments_data)); ?>,
                            borderColor: '#14b8a6',
                            backgroundColor: 'rgba(20, 184, 166, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#14b8a6', // Teal
                            pointBorderWidth: 2,
                            pointBorderColor: '#fff'
                        },
                        {
                            label: 'Patients',
                            data: <?php echo json_encode(array_values($total_patients_data)); ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#3b82f6', // Blue
                            pointBorderWidth: 2,
                            pointBorderColor: '#fff'
                        },
                        {
                            label: 'New Patients',
                            data: <?php echo json_encode(array_values($new_patients_data)); ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#10b981', // Green
                            pointBorderWidth: 2,
                            pointBorderColor: '#fff'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        </script>
    </body>

    </html>
<?php } ?>
