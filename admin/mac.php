<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Handle appointment update from edit modal
    if (isset($_POST['update_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $status = $_POST['status'];

        $sql_update = "UPDATE tblappointment SET status = :status WHERE id = :id";
        $query_update = $dbh->prepare($sql_update);
        $query_update->bindParam(':status', $status, PDO::PARAM_STR);
        $query_update->bindParam(':id', $appointment_id, PDO::PARAM_INT);

        if ($query_update->execute()) {
            echo "<script>alert('Appointment status updated successfully.'); window.location.href='mac.php';</script>";
        } else {
            echo "<script>alert('An error occurred while updating the status.');</script>";
        }
        exit();
    }

    // Handle new appointment and patient creation from modal
    if (isset($_POST['schedule_appointment'])) {
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
            while($dbh->query("SELECT COUNT(*) FROM tblpatient WHERE username = '$username'")->fetchColumn() > 0) {
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

            // 2. Create new appointment
            $app_date = $_POST['date']; // from the form
            $start_time = $_POST['start_time']; // from the form
            $end_time = $_POST['end_time']; // from the form
            $app_status = 'walkin'; // Set default status to walk-in for appointments created this way

            $sql_appointment = "INSERT INTO tblappointment (patient_number, firstname, surname, date, start_time, end_time, status) VALUES (:pnum, :fname, :sname, :app_date, :start_time, :end_time, :status)";
            $query_appointment = $dbh->prepare($sql_appointment);
            $query_appointment->execute([':pnum' => $patient_id, ':fname' => $firstname, ':sname' => $surname, ':app_date' => $app_date, ':start_time' => $start_time, ':end_time' => $end_time, ':status' => $app_status]);

            $dbh->commit();
            echo "<script>alert('New patient and appointment scheduled successfully.'); window.location.href='mac.php';</script>";
        } catch (Exception $e) {
            $dbh->rollBack();
            echo "<script>alert('An error occurred: " . $e->getMessage() . "');</script>";
        }
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblappointment WHERE id = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        echo "<script>alert('Appointment deleted');</script>";
        echo "<script>window.location.href = 'mac.php'</script>";
    }
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    // --- APPOINTMENT COUNTS ---
    $sql_all = "SELECT COUNT(*) FROM tblappointment";
    $query_all = $dbh->prepare($sql_all);
    $query_all->execute();
    $count_all = $query_all->fetchColumn();

    $sql_today = "SELECT COUNT(*) FROM tblappointment WHERE date = CURDATE()";
    $query_today = $dbh->prepare($sql_today);
    $query_today->execute();
    $count_today = $query_today->fetchColumn();

    $sql_upcoming = "SELECT COUNT(*) FROM tblappointment WHERE date > CURDATE()";
    $query_upcoming = $dbh->prepare($sql_upcoming);
    $query_upcoming->execute();
    $count_upcoming = $query_upcoming->fetchColumn();

    $sql_pending = "SELECT COUNT(*) FROM tblappointment WHERE status = 'Pending'";
    $query_pending = $dbh->prepare($sql_pending);
    $query_pending->execute();
    $count_pending = $query_pending->fetchColumn();

    // 'Completed' is considered as appointments with status 'Approved' or 'walk-in' that are in the past.
    $sql_completed = "SELECT COUNT(*) FROM tblappointment WHERE date < CURDATE() AND status IN ('Approved', 'walk-in')";
    $query_completed = $dbh->prepare($sql_completed);
    $query_completed->execute();
    $count_completed = $query_completed->fetchColumn();

    // --- APPOINTMENT LIST ---
    $sql_appointments = "SELECT * FROM tblappointment";
    $where_clauses = [];

    switch ($filter) {
        case 'today':
            $where_clauses[] = "date = CURDATE()";
            break;
        case 'upcoming':
            $where_clauses[] = "date > CURDATE()";
            break;
        case 'pending':
            $where_clauses[] = "status = 'Pending'";
            break;
        case 'completed':
            $where_clauses[] = "date < CURDATE() AND status IN ('Approved', 'walk-in')";
            break;
    }

    if (!empty($where_clauses)) {
        $sql_appointments .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql_appointments .= " ORDER BY date DESC, start_time DESC";
    $query_appointments = $dbh->prepare($sql_appointments);
    $query_appointments->execute();
    $appointments = $query_appointments->fetchAll(PDO::FETCH_OBJ);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Appointments & Patients</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mac-modal.css">
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
                                <h1>Manage Appointments</h1>
                                <p>Schedule and track patient appointments</p>
                            </div>
                            <button type="button" class="new-appointment-btn" id="newAppointmentBtn">New Appointment</button>
                        </div>

                        <div class="filter-section">
                            <div class="search-box">
                                <input type="text" placeholder="Search appointments by patient name or service..." aria-label="Search appointments">
                            </div>
                            <div class="filter-buttons">
                                <a href="mac.php?filter=all" class="filter-btn <?php if ($filter === 'all') echo 'active'; ?>" data-filter="all">
                                    All Appointments <span class="filter-count"><?php echo $count_all; ?></span>
                                </a>
                                <a href="mac.php?filter=today" class="filter-btn <?php if ($filter === 'today') echo 'active'; ?>" data-filter="today">
                                    Today <span class="filter-count"><?php echo $count_today; ?></span>
                                </a>
                                <a href="mac.php?filter=upcoming" class="filter-btn <?php if ($filter === 'upcoming') echo 'active'; ?>" data-filter="upcoming">
                                    Upcoming <span class="filter-count"><?php echo $count_upcoming; ?></span>
                                </a>
                                <a href="mac.php?filter=pending" class="filter-btn <?php if ($filter === 'pending') echo 'active'; ?>" data-filter="pending">
                                    Pending <span class="filter-count"><?php echo $count_pending; ?></span>
                                </a>
                                <a href="mac.php?filter=completed" class="filter-btn <?php if ($filter === 'completed') echo 'active'; ?>" data-filter="completed">
                                    Completed <span class="filter-count"><?php echo $count_completed; ?></span>
                                </a>
                            </div>
                        </div>

                    </div>

                    <hr style="border: 0; border-top: 1px solid #ccc; margin: 30px 0;">

                    <div class="patient-list-card" id="appointment-table-container">
                        <h2 class="section-title">Consultation Appointments (<?php echo count($appointments); ?>)</h2>
                        <table class="patient-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>First Name</th>
                                    <th>Surname</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($appointments) > 0): ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlentities($appointment->id); ?></td>
                                            <td><?php echo htmlentities($appointment->firstname); ?></td>
                                            <td><?php echo htmlentities($appointment->surname); ?></td>
                                            <td><?php echo htmlentities($appointment->date); ?></td>
                                            <td><?php echo format_time_12hr($appointment->start_time); ?></td>
                                            <td><?php echo format_time_12hr($appointment->end_time); ?></td>
                                            <td><span class="status-badge status-<?php echo strtolower(htmlentities($appointment->status)); ?>"><?php echo htmlentities($appointment->status); ?></span></td>
                                            <td class="actions-icons">
                                                <button class="edit-appointment-btn" title="Edit"
                                                    data-id="<?php echo htmlentities($appointment->id); ?>"
                                                    data-firstname="<?php echo htmlentities($appointment->firstname); ?>"
                                                    data-surname="<?php echo htmlentities($appointment->surname); ?>"
                                                    data-date="<?php echo htmlentities($appointment->date); ?>"
                                                    data-start-time="<?php echo htmlentities($appointment->start_time); ?>"
                                                    data-end-time="<?php echo htmlentities($appointment->end_time); ?>"
                                                    data-status="<?php echo htmlentities($appointment->status); ?>"
                                                    style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;"
                                                >✏️</button>
                                                
                                                <a href="mac.php?delid=<?php echo $appointment->id; ?>" title="Delete" onclick="return confirm('Do you really want to Delete ?');" style="font-size: 1.25rem; color: #a0aec0;">🗑️</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No patients found.</td>
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

<!-- New Appointment Modal -->
<div id="newAppointmentModal" class="modal-container" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>New Appointment</h2>
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

                <h3 class="form-section-title">Appointment Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <div class="input-with-icon">
                            <input type="date" id="date" name="date" required>
                            <i class="fas fa-calendar-alt input-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <div class="input-with-icon">
                            <input type="time" id="start_time" name="start_time" required>
                            <i class="fas fa-clock input-icon"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <div class="input-with-icon">
                            <input type="time" id="end_time" name="end_time" required>
                            <i class="fas fa-clock input-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" name="schedule_appointment" class="btn btn-schedule">Schedule Appointment</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="editAppointmentModal" class="modal-container" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Appointment Status</h2>
            <button class="close-button">&times;</button>
        </div>
        <form id="editAppointmentForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="appointment_id" id="edit_appointment_id">
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_date">Date</label>
                        <input type="date" id="edit_date" name="date" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_time">Start Time</label>
                        <input type="time" id="edit_start_time" name="start_time" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_time">End Time</label>
                        <input type="time" id="edit_end_time" name="end_time" class="form-control" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Declined">Declined</option>
                        <option value="walkin">Walk-in</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" name="update_appointment" class="btn btn-update">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script src="vendors/js/vendor.bundle.base.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
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

    // Close modal if clicking outside of the modal content
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Form validation
    const form = document.getElementById('appointmentForm');
    if(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                // Modern browsers will show their own validation messages.
                // For older ones, you might add custom logic here.
                alert('Please fill out all required fields.');
            }
        });
    }
});
</script>
<script src="js/mac-modal.js"></script>

</body>
</html>
<?php ?>