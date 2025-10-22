<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Handle schedule update from edit modal
    if (isset($_POST['update_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        $status = $_POST['status'];

        $sql_update = "UPDATE tblschedule SET status = :status WHERE id = :id";
        $query_update = $dbh->prepare($sql_update);
        $query_update->bindParam(':status', $status, PDO::PARAM_STR);
        $query_update->bindParam(':id', $schedule_id, PDO::PARAM_INT);

        if ($query_update->execute()) {
            echo "<script>alert('Schedule status updated successfully.'); window.location.href='mas.php';</script>";
        } else {
            echo "<script>alert('An error occurred while updating the status.');</script>";
        }
        exit();
    }

    // Handle new service appointment and patient creation from modal
    if (isset($_POST['schedule_service_appointment'])) {
        $dbh->beginTransaction();
        try {
            // 1. Create new patient
            $firstname = $_POST['firstname'];
            $surname = $_POST['surname'];
            $dob = $_POST['date_of_birth'];
            $sex = $_POST['sex'];
            $civil_status = $_POST['civil_status'];
            $occupation = $_POST['occupation'];
            $contact_number = $_POST['contact_number'];
            $address = $_POST['address'];
            $email = $_POST['email'];
            $password = md5('password'); // Default password

            // Auto-generate a unique username
            $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname . $surname));
            $username = $base_username;
            $counter = 1;
            while ($dbh->query("SELECT COUNT(*) FROM tblpatient WHERE username = '$username'")->fetchColumn() > 0) {
                $username = $base_username . $counter++;
            }

            $age = '';
            if (!empty($dob)) {
                $birthDate = new DateTime($dob);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
            }

            $sql_patient = "INSERT INTO tblpatient (firstname, surname, date_of_birth, sex, status, occupation, age, contact_number, address, email, username, password, created_at) VALUES (:fname, :sname, :dob, :sex, :status, :occupation, :age, :contact, :address, :email, :uname, :password, NOW())";
            $query_patient = $dbh->prepare($sql_patient);
            $query_patient->execute([':fname' => $firstname, ':sname' => $surname, ':dob' => $dob, ':sex' => $sex, ':status' => $civil_status, ':occupation' => $occupation, ':age' => $age, ':contact' => $contact_number, ':address' => $address, ':email' => $email, ':uname' => $username, ':password' => $password]);
            $patient_id = $dbh->lastInsertId();

            // 2. Create new service schedule
            $service_id = $_POST['service_id'];
            $app_date = $_POST['date'];
            $start_time = $_POST['start_time'];
            $duration = $_POST['duration'];
            $app_status = 'Ongoing'; // Set default status to Ongoing for services

            $sql_schedule = "INSERT INTO tblschedule (patient_number, firstname, surname, service_id, date, time, duration, status) VALUES (:pnum, :fname, :sname, :service_id, :app_date, :start_time, :duration, :status)";
            $query_schedule = $dbh->prepare($sql_schedule);
            $query_schedule->execute([':pnum' => $patient_id, ':fname' => $firstname, ':sname' => $surname, ':service_id' => $service_id, ':app_date' => $app_date, ':start_time' => $start_time, ':duration' => $duration, ':status' => $app_status]);

            $dbh->commit();
            echo "<script>alert('New patient and service appointment scheduled successfully.'); window.location.href='mas.php';</script>";
        } catch (Exception $e) {
            $dbh->rollBack();
            echo "<script>alert('An error occurred: " . $e->getMessage() . "');</script>";
        }
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblschedule WHERE id = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        echo "<script>alert('Service schedule deleted');</script>";
        echo "<script>window.location.href = 'mas.php'</script>";
    }

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    // --- SERVICE APPOINTMENT COUNTS ---
    $sql_all = "SELECT COUNT(*) FROM tblschedule";
    $query_all = $dbh->prepare($sql_all);
    $query_all->execute();
    $count_all = $query_all->fetchColumn();

    $sql_today = "SELECT COUNT(*) FROM tblschedule WHERE date = CURDATE()";
    $query_today = $dbh->prepare($sql_today);
    $query_today->execute();
    $count_today = $query_today->fetchColumn();

    $sql_upcoming = "SELECT COUNT(*) FROM tblschedule WHERE date > CURDATE()";
    $query_upcoming = $dbh->prepare($sql_upcoming);
    $query_upcoming->execute();
    $count_upcoming = $query_upcoming->fetchColumn();

    $sql_pending = "SELECT COUNT(*) FROM tblschedule WHERE status = 'Ongoing'"; // 'Ongoing' is like 'Pending' for services
    $query_pending = $dbh->prepare($sql_pending);
    $query_pending->execute();
    $count_pending = $query_pending->fetchColumn();

    $sql_completed = "SELECT COUNT(*) FROM tblschedule WHERE status = 'Done'";
    $query_completed = $dbh->prepare($sql_completed);
    $query_completed->execute();
    $count_completed = $query_completed->fetchColumn();

    // --- SERVICE APPOINTMENT LIST ---
    $sql_schedules = "SELECT s.*, svc.name as service_name FROM tblschedule s LEFT JOIN tblservice svc ON s.service_id = svc.number";
    $where_clauses = [];

    switch ($filter) {
        case 'today':
            $where_clauses[] = "s.date = CURDATE()";
            break;
        case 'upcoming':
            $where_clauses[] = "s.date > CURDATE()";
            break;
        case 'pending':
            $where_clauses[] = "s.status = 'Ongoing'";
            break;
        case 'completed':
            $where_clauses[] = "s.status = 'Done'";
            break;
    }

    if (!empty($where_clauses)) {
        $sql_schedules .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql_schedules .= " ORDER BY s.date DESC, s.time DESC";
    $query_schedules = $dbh->prepare($sql_schedules);
    $query_schedules->execute();
    $schedules = $query_schedules->fetchAll(PDO::FETCH_OBJ);

    // Helper to format time
    function format_time_12hr($time_24hr)
    {
        if (empty($time_24hr))
            return 'N/A';
        return date("g:i A", strtotime($time_24hr));
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard | Service Appointments</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mas-modal.css">
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    
                    <div class="container">

                        <div class="appointment-management-card">
                            <div class="header-section">
                                <div class="header-text">
                                    <h1>Manage Service Appointments</h1>
                                    <p>Schedule and track patient appointments for services</p>
                                </div>
                                <button type="button" class="new-appointment-btn" id="newAppointmentBtn">New Service Appointment</button>
                            </div>

                            <div class="filter-section">
                                <div class="search-box">
                                    <input type="text" placeholder="Search appointments by patient name or service..." aria-label="Search appointments">
                                </div>
                                <div class="filter-buttons">
                                    <a href="mas.php?filter=all" class="filter-btn <?php if ($filter === 'all') echo 'active'; ?>" data-filter="all">
                                        All Appointments <span class="filter-count"><?php echo $count_all; ?></span>
                                    </a>
                                    <a href="mas.php?filter=today" class="filter-btn <?php if ($filter === 'today') echo 'active'; ?>" data-filter="today">
                                        Today <span class="filter-count"><?php echo $count_today; ?></span>
                                    </a>
                                    <a href="mas.php?filter=upcoming" class="filter-btn <?php if ($filter === 'upcoming') echo 'active'; ?>" data-filter="upcoming">
                                        Upcoming <span class="filter-count"><?php echo $count_upcoming; ?></span>
                                    </a>
                                    <a href="mas.php?filter=pending" class="filter-btn <?php if ($filter === 'pending') echo 'active'; ?>" data-filter="pending">
                                        Ongoing <span class="filter-count"><?php echo $count_pending; ?></span>
                                    </a>
                                    <a href="mas.php?filter=completed" class="filter-btn <?php if ($filter === 'completed') echo 'active'; ?>" data-filter="completed">
                                        Completed <span class="filter-count"><?php echo $count_completed; ?></span>
                                    </a>
                                </div>
                            </div>

                        </div>

                        <hr style="border: 0; border-top: 1px solid #ccc; margin: 30px 0;">

                        <div class="patient-list-card" id="appointment-table-container">
                            <h2 class="section-title">Service Appointments (<?php echo count($schedules); ?>)</h2>
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>First Name</th>
                                        <th>Surname</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($schedules) > 0) : ?>
                                        <?php foreach ($schedules as $schedule) : ?>
                                            <tr>
                                                <td><?php echo htmlentities($schedule->id); ?></td>
                                                <td><?php echo htmlentities($schedule->firstname); ?></td>
                                                <td><?php echo htmlentities($schedule->surname); ?></td>
                                                <td><?php echo htmlentities($schedule->service_name ?: 'N/A'); ?></td>
                                                <td><?php echo htmlentities($schedule->date); ?></td>
                                                <td><?php echo format_time_12hr($schedule->time); ?></td>
                                                <td><?php echo htmlentities($schedule->duration ? $schedule->duration . ' mins' : 'N/A'); ?></td>
                                                <td><span class="status-badge status-<?php echo strtolower(htmlentities($schedule->status)); ?>"><?php echo htmlentities($schedule->status); ?></span></td>
                                                <td class="actions-icons">
                                                    <button class="edit-schedule-btn" title="Edit"
                                                        data-id="<?php echo htmlentities($schedule->id); ?>"
                                                        data-firstname="<?php echo htmlentities($schedule->firstname); ?>"
                                                        data-surname="<?php echo htmlentities($schedule->surname); ?>"
                                                        data-service="<?php echo htmlentities($schedule->service_name ?: 'N/A'); ?>"
                                                        data-date="<?php echo htmlentities($schedule->date); ?>"
                                                        data-time="<?php echo htmlentities($schedule->time); ?>"
                                                        data-duration="<?php echo htmlentities($schedule->duration ? $schedule->duration . ' mins' : 'N/A'); ?>"
                                                        data-status="<?php echo htmlentities($schedule->status); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">✏️</button>
                                                    <a href="mas.php?delid=<?php echo $schedule->id; ?>" title="Delete" onclick="return confirm('Do you really want to Delete this service schedule?');" style="font-size: 1.25rem; color: #a0aec0; text-decoration: none;">🗑️</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center;">No service appointments found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- New Service Appointment Modal -->
    <div id="newAppointmentModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Service Appointment</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="appointmentForm" method="POST">
                <div class="modal-body">
                    <h3 class="form-section-title">Patient Details</h3>
                    <div class="form-row">
                        <div class="form-group"><label for="firstname">First Name</label><input type="text" id="firstname" name="firstname" required></div>
                        <div class="form-group"><label for="surname">Surname</label><input type="text" id="surname" name="surname" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="date_of_birth">Date of Birth</label><input type="date" id="date_of_birth" name="date_of_birth" required></div>
                        <div class="form-group">
                            <label for="sex">Sex</label>
                            <select id="sex" name="sex" required>
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="civil_status">Civil Status</label><input type="text" id="civil_status" name="civil_status"></div>
                        <div class="form-group"><label for="occupation">Occupation</label><input type="text" id="occupation" name="occupation"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="contact_number">Phone Number</label><input type="tel" id="contact_number" name="contact_number" required></div>
                        <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email"></div>
                    </div>
                    <div class="form-group"><label for="address">Address</label><textarea id="address" name="address" rows="2"></textarea></div>

                    <hr class="form-divider">

                    <h3 class="form-section-title">Service Appointment Details</h3>
                    <div class="form-group">
                        <label for="service_id">Service</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Select Service</option>
                            <?php
                            $svcs = $dbh->query("SELECT number, name FROM tblservice ORDER BY name")->fetchAll(PDO::FETCH_OBJ);
                            foreach ($svcs as $s) {
                                echo "<option value='{$s->number}'>" . htmlentities($s->name) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Time</label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="1" placeholder="e.g., 30" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="schedule_service_appointment" class="btn btn-schedule">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Service Schedule Modal -->
    <div id="editScheduleModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Service Schedule Status</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editScheduleForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_firstname">First Name</label>
                            <input type="text" id="edit_firstname" name="firstname" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_surname">Surname</label>
                            <input type="text" id="edit_surname" name="surname" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_service">Service</label>
                        <input type="text" id="edit_service" name="service" class="form-control" readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_date">Date</label>
                            <input type="date" id="edit_date" name="date" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_time">Time</label>
                            <input type="time" id="edit_time" name="time" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_duration">Duration</label>
                            <input type="text" id="edit_duration" name="duration" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_schedule" class="btn btn-update">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('newAppointmentModal');
            const openBtn = document.getElementById('newAppointmentBtn');
            const closeBtn = modal.querySelector('.close-button');
            const cancelBtn = modal.querySelector('.btn-cancel');

            function openModal() {
                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            if (openBtn) {
                openBtn.addEventListener('click', openModal);
            }
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            const form = document.getElementById('appointmentForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        alert('Please fill out all required fields.');
                    }
                });
            }
        });
    </script>
    <script src="js/mas-modal.js"></script>
</body>

</html>
<?php ?>
