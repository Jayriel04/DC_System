<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {

    // JSON endpoint to provide chart data for appointments and patients
    if (isset($_GET['chart_data']) && $_GET['chart_data'] == '1') {
        header('Content-Type: application/json');
        $metric = isset($_GET['metric']) ? $_GET['metric'] : 'appointments';
        $period = isset($_GET['period']) ? $_GET['period'] : 'week'; // day, week, month

        $labels = [];
        $data = [];

        try {
            if ($metric === 'appointments') {
                if ($period === 'day') {
                    // last 24 hours grouped by hour (0-23)
                    $stmt = $dbh->prepare("SELECT HOUR(created_at) AS h, COUNT(*) AS c FROM tblappointment WHERE created_at >= (NOW() - INTERVAL 1 DAY) GROUP BY h ORDER BY h");
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    for ($i = 0; $i < 24; $i++) {
                        $labels[] = sprintf('%02d:00', $i);
                        $data[] = isset($rows[$i]) ? intval($rows[$i]) : 0;
                    }
                } elseif ($period === 'week') {
                    // last 7 days (including today)
                    $stmt = $dbh->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM tblappointment WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) GROUP BY d ORDER BY d");
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    for ($i = 6; $i >= 0; $i--) {
                        $day = date('Y-m-d', strtotime("-{$i} days"));
                        $labels[] = date('D', strtotime($day));
                        $data[] = isset($rows[$day]) ? intval($rows[$day]) : 0;
                    }
                } else {
                    // month -> last 12 months
                    $stmt = $dbh->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COUNT(*) AS c FROM tblappointment WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY m ORDER BY m");
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    for ($i = 11; $i >= 0; $i--) {
                        $m = date('Y-m', strtotime("-{$i} months"));
                        $labels[] = date('M Y', strtotime($m . '-01'));
                        $data[] = isset($rows[$m]) ? intval($rows[$m]) : 0;
                    }
                }
            } else {
                // patients: prefer created_at on tblpatient if present, otherwise use first appointment as proxy
                $colChk = $dbh->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpatient' AND COLUMN_NAME = 'created_at'");
                $colChk->execute();
                $hasPatientCreated = $colChk->fetch() ? true : false;

                if ($hasPatientCreated) {
                    if ($period === 'day') {
                        $stmt = $dbh->prepare("SELECT HOUR(created_at) AS h, COUNT(*) AS c FROM tblpatient WHERE created_at >= (NOW() - INTERVAL 1 DAY) GROUP BY h ORDER BY h");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 0; $i < 24; $i++) {
                            $labels[] = sprintf('%02d:00', $i);
                            $data[] = isset($rows[$i]) ? intval($rows[$i]) : 0;
                        }
                    } elseif ($period === 'week') {
                        $stmt = $dbh->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM tblpatient WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) GROUP BY d ORDER BY d");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 6; $i >= 0; $i--) {
                            $day = date('Y-m-d', strtotime("-{$i} days"));
                            $labels[] = date('D', strtotime($day));
                            $data[] = isset($rows[$day]) ? intval($rows[$day]) : 0;
                        }
                    } else {
                        $stmt = $dbh->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COUNT(*) AS c FROM tblpatient WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY m ORDER BY m");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 11; $i >= 0; $i--) {
                            $m = date('Y-m', strtotime("-{$i} months"));
                            $labels[] = date('M Y', strtotime($m . '-01'));
                            $data[] = isset($rows[$m]) ? intval($rows[$m]) : 0;
                        }
                    }
                } else {
                    // fallback: use first appointment per patient as proxy for patient creation
                    if ($period === 'day') {
                        // derive first_created only for patients that exist in tblpatient
                        $stmt = $dbh->prepare("SELECT HOUR(t.first_created) AS h, COUNT(*) AS c FROM (SELECT a.patient_number, MIN(a.created_at) AS first_created FROM tblappointment a JOIN tblpatient p ON p.number = a.patient_number GROUP BY a.patient_number) t WHERE t.first_created >= (NOW() - INTERVAL 1 DAY) GROUP BY h ORDER BY h");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 0; $i < 24; $i++) {
                            $labels[] = sprintf('%02d:00', $i);
                            $data[] = isset($rows[$i]) ? intval($rows[$i]) : 0;
                        }
                    } elseif ($period === 'week') {
                        $stmt = $dbh->prepare("SELECT DATE(t.first_created) AS d, COUNT(*) AS c FROM (SELECT a.patient_number, MIN(a.created_at) AS first_created FROM tblappointment a JOIN tblpatient p ON p.number = a.patient_number GROUP BY a.patient_number) t WHERE t.first_created >= (CURDATE() - INTERVAL 6 DAY) GROUP BY d ORDER BY d");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 6; $i >= 0; $i--) {
                            $day = date('Y-m-d', strtotime("-{$i} days"));
                            $labels[] = date('D', strtotime($day));
                            $data[] = isset($rows[$day]) ? intval($rows[$day]) : 0;
                        }
                    } else {
                        $stmt = $dbh->prepare("SELECT DATE_FORMAT(t.first_created,'%Y-%m') AS m, COUNT(*) AS c FROM (SELECT a.patient_number, MIN(a.created_at) AS first_created FROM tblappointment a JOIN tblpatient p ON p.number = a.patient_number GROUP BY a.patient_number) t WHERE t.first_created >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY m ORDER BY m");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        for ($i = 11; $i >= 0; $i--) {
                            $m = date('Y-m', strtotime("-{$i} months"));
                            $labels[] = date('M Y', strtotime($m . '-01'));
                            $data[] = isset($rows[$m]) ? intval($rows[$m]) : 0;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // on error return empty arrays
        }

        echo json_encode(['labels' => $labels, 'data' => $data]);
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title>Dashboard</title>
        <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
        <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
        <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
        <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
        <link rel="stylesheet" href="css/style.css">
    </head>

    <body>
        <div class="container-scroller">
            <?php include_once('includes/header.php'); ?>
            <div class="container-fluid page-body-wrapper">
                <?php include_once('includes/sidebar.php'); ?>
                <div class="main-panel">
                    <div class="content-wrapper">
                        <div class="row">
                            <div class="col-md-12 grid-margin">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="d-sm-flex align-items-baseline report-summary-header">
                                                    <h5 class="font-weight-semibold">Report Summary</h5>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        // Prepare counts for charts: appointments and patients
                                        $sql2 = "SELECT * FROM tblappointment";
                                        $query2 = $dbh->prepare($sql2);
                                        $query2->execute();
                                        $totapp = $query2->rowCount();

                                        $sql5 = "SELECT * FROM tblpatient";
                                        $query5 = $dbh->prepare($sql5);
                                        $query5->execute();
                                        $totalpatients = $query5->rowCount();
                                        ?>
                                        <div class="d-flex justify-content-end mb-2">
                                            <label class="mr-2" style="margin-top: 35px;">Period:</label>
                                            <select id="chartPeriod" class="form-control" style="width:100px; height: auto; margin-top: 30px; border-radius: 5%;; margin-right:auto; margin-left: 0;">
                                                <option value="day">Daily</option>
                                                <option value="week" selected>Weekly</option>
                                                <option value="month">Monthly</option>
                                                <option value="year">Yearly</option>
                                            </select>
                                            <label class="mr-2" style="margin-top: 35px; margin-left: -500px;">Mode:</label>
                                            <select id="chartMode" class="form-control" style="width:180px; height: auto; margin-top: 30px; border-radius: 5%;; margin-right:auto; margin-left: 0;">
                                                <option value="appointments">Appointments</option>
                                                <option value="patients">Patients</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                            <div class="row justify-content-center report-inner-cards-wrapper">
                                                <div class="col-12">
                                                    <div class="report-inner-card p-3" style="min-height:420px;">
                                                        <div class="inner-card-text">
                                                            <span class="report-title" >Appointments & Patients</span>
                                                            <div style="width:120%;margin:50px 0;">
                                                                <canvas id="mainChart" height="420" style="width:100%"></canvas>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <div><a href="mac.php"><span class="report-count"> View Appointments</span></a></div>
                                                                <div><a href="manage-patient.php"><span class="report-count"> View Patients</span></a></div>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                    <script>
                                        (function () {
                                            var mainChart = null;

                                            function fetchSeries(metric) {
                                                var period = document.getElementById('chartPeriod').value;
                                                var url = window.location.pathname + '?chart_data=1&metric=' + encodeURIComponent(metric) + '&period=' + encodeURIComponent(period);
                                                return fetch(url).then(function(res){ return res.json(); });
                                            }

                                            function buildGradient(ctx, color) {
                                                var grad = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height || 300);
                                                grad.addColorStop(0, color.replace(/rgba\(([^)]+)\)/, function(_, inner){ return 'rgba(' + inner.replace(/,\s*0?\.?\d+\)$/, ',0.9)'); }));
                                                grad.addColorStop(1, color.replace(/rgba\(([^)]+)\)/, function(_, inner){ return 'rgba(' + inner.replace(/,\s*0?\.?\d+\)$/, ',0.06)'); }));
                                                return grad;
                                            }

                                            function renderMainChart(mode) {
                                                var ctx = document.getElementById('mainChart');
                                                if (!ctx || typeof Chart === 'undefined') return;
                                                ctx = ctx.getContext('2d');

                                                var promises = [];
                                                if (mode === 'appointments' || mode === 'both') promises.push(fetchSeries('appointments'));
                                                if (mode === 'patients' || mode === 'both') promises.push(fetchSeries('patients'));

                                                Promise.all(promises).then(function(results){
                                                    var labels = [];
                                                    var datasets = [];

                                                    // map results to datasets; results order corresponds to promises order
                                                    var idx = 0;
                                                    if (mode === 'appointments' || mode === 'both') {
                                                        var app = results[idx++];
                                                        labels = app.labels.slice();
                                                        datasets.push({
                                                            label: 'Appointments',
                                                            data: app.data,
                                                            borderColor: 'rgba(220,53,69,0.9)',
                                                            backgroundColor: buildGradient(ctx, 'rgba(220,53,69,0.8)'),
                                                            tension: 0.35,
                                                            fill: true,
                                                            pointRadius: 3
                                                        });
                                                    }
                                                    if (mode === 'patients' || mode === 'both') {
                                                        var pat = results[idx++];
                                                        // if labels empty (patients only), use patients labels
                                                        if (labels.length === 0) labels = pat.labels.slice();
                                                        datasets.push({
                                                            label: 'Patients',
                                                            data: pat.data,
                                                            borderColor: 'rgba(40,167,69,0.9)',
                                                            backgroundColor: buildGradient(ctx, 'rgba(40,167,69,0.8)'),
                                                            tension: 0.35,
                                                            fill: true,
                                                            pointRadius: 3
                                                        });
                                                    }

                                                    var cfg = {
                                                        type: 'line',
                                                        data: { labels: labels, datasets: datasets },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            interaction: { mode: 'index', intersect: false },
                                                            plugins: { legend: { display: true }, title: { display: false } },
                                                            scales: { x: { display: true, ticks: { autoSkip: true } }, y: { beginAtZero: true } }
                                                        }
                                                    };

                                                    if (mainChart) mainChart.destroy();
                                                    mainChart = new Chart(ctx, cfg);
                                                }).catch(function(err){ console.error('Main chart fetch error', err); });
                                            }

                                            function refreshMain() {
                                                var mode = document.getElementById('chartMode').value;
                                                renderMainChart(mode);
                                            }

                                            document.getElementById('chartPeriod').addEventListener('change', refreshMain);
                                            document.getElementById('chartMode').addEventListener('change', refreshMain);
                                            // initial load
                                            refreshMain();
                                        })();
                                    </script> 
    </body>

    </html>
<?php } ?>