<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else { ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title>Dashboard</title>
        <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
        <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
        <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="./css/style.css">
        <link rel="stylesheet" href="./css/dashboard.css">
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
                            $sql_latest_app = "SELECT a.date, a.start_time, a.status, p.firstname, p.surname, p.Image, s.name as service_name 
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
                            $sql_reviews = "SELECT firstname, surname, Image, rating, feedback, created_at FROM tblpatient WHERE feedback IS NOT NULL AND feedback != '' ORDER BY created_at DESC LIMIT 3";
                            $query_reviews = $dbh->prepare($sql_reviews);
                            $query_reviews->execute();
                            $recent_reviews = $query_reviews->fetchAll(PDO::FETCH_OBJ);
                            ?>
                            <!-- Stats Grid -->
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Patients</h3>
                                        <div class="stat-number"><?php echo number_format($total_patients); ?></div>
                                        <div class="stat-change">↑ +12% vs last month</div>
                                    </div>
                                    <div class="stat-icon icon-blue">👤</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Services Available</h3>
                                        <div class="stat-number"><?php echo number_format($total_services); ?></div>
                                        <div class="stat-change">↑ +3 vs last month</div>
                                    </div>
                                    <div class="stat-icon icon-green">❤️</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Total Appointments</h3>
                                        <div class="stat-number"><?php echo number_format($total_appointments); ?></div>
                                        <div class="stat-change">↑ +8% vs last month</div>
                                    </div>
                                    <div class="stat-icon icon-purple">📅</div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-content">
                                        <h3>Average Rating</h3>
                                        <div class="stat-number" style="font-size: 28px;">
                                            <?php echo number_format($avg_rating, 1); ?><span class="stars"><?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?></span>
                                        </div>
                                        <div class="stat-change">↑ +0.2 vs last month</div>
                                    </div>
                                    <div class="stat-icon icon-yellow">⭐</div>
                                </div>
                            </div>

                            <!-- Chart Section -->
                            <div class="chart-section">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h2 class="card-title" style="margin: 0;">Appointments vs Patients</h2>
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

                            <!-- Metrics Grid -->
                            <div class="metrics-grid">
                                <div class="metric-box">
                                    <div class="metric-label">Avg Appointments per Patient</div>
                                    <div class="metric-value metric-value.blue">
                                        <?php echo $total_patients > 0 ? number_format($total_appointments / $total_patients, 1) : '0.0'; ?>
                                    </div>
                                </div>
                                <div class="metric-box">
                                    <div class="metric-label">Returning Patient Rate</div>
                                    <div class="metric-value metric-value.blue">78%</div>
                                </div>
                                <div class="metric-box">
                                    <div class="metric-label">New Patient Conversion</div>
                                    <div class="metric-value metric-value.green">85%</div>
                                </div>
                            </div>

                            <!-- Content Grid -->
                            <div class="content-grid">
                                <!-- Latest Appointments -->
                                <div class="card">
                                    <div class="card-header">
                                        <h2 class="card-title">Latest Appointments</h2>
                                        <a href="all-appointment.php" class="view-link">View All</a>
                                    </div>

                                    <?php foreach ($latest_appointments as $appointment): ?>
                                        <div class="appointment">
                                            <img class="avatar"
                                                src="../user/images/<?php echo htmlentities(!empty($appointment->Image) ? $appointment->Image : 'default-avatar.png'); ?>"
                                                alt="Avatar">
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
                                        <a href="manage-service.php" class="view-link">View Details</a>
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
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Recent Reviews</h2>
                                    <a href="#" class="view-link">View All Reviews</a>
                                </div>
                                <?php if (!empty($recent_reviews)): ?>
                                    <?php foreach ($recent_reviews as $review): ?>
                                        <div class="review">
                                            <div class="review-header">
                                                <div style="display: flex; gap: 16px; align-items: center;">
                                                    <img class="avatar" src="../user/images/<?php echo htmlentities(!empty($review->Image) ? $review->Image : 'default-avatar.png'); ?>" alt="Avatar">
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
                    <?php include_once('includes/footer.php'); ?>
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
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [
                        {
                            label: 'Appointments',
                            data: [45, 52, 48, 61, 55, 67, 72],
                            borderColor: '#14b8a6',
                            backgroundColor: 'rgba(20, 184, 166, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#14b8a6',
                            pointBorderWidth: 2,
                            pointBorderColor: '#fff'
                        },
                        {
                            label: 'Patients',
                            data: [120, 135, 142, 156, 168, 185, 198],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderWidth: 2,
                            pointBorderColor: '#fff'
                        },
                        {
                            label: 'New Patients',
                            data: [12, 18, 22, 28, 35, 42, 50],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#10b981',
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
