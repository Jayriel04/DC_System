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
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment status updated successfully.'];
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the status.'];
        }
        header('Location: mac.php');
        exit();
    }

    // Handle appointment cancellation from admin modal
    if (isset($_POST['confirm_cancel_admin'])) {
        $appointment_id = $_POST['appointment_id'];
        $cancel_reason = $_POST['cancel_reason'];
        $status = 'Cancelled'; // Set status to Cancelled

        $sql_cancel = "UPDATE tblappointment SET status = :status, cancel_reason = :cancel_reason WHERE id = :id";
        $query_cancel = $dbh->prepare($sql_cancel);
        $query_cancel->bindParam(':status', $status, PDO::PARAM_STR);
        $query_cancel->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
        $query_cancel->bindParam(':id', $appointment_id, PDO::PARAM_INT);

        if ($query_cancel->execute()) {
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment cancelled successfully.'];
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while cancelling the appointment.'];
        }
        header('Location: mac.php');
        exit();
    }
    // Handle new appointment and patient creation from modal
    if (isset($_POST['schedule_appointment'])) {
        $dbh->beginTransaction();
        try {
            // 1. Create new patient
            $firstname = ucfirst(trim($_POST['firstname']));
            $surname = ucfirst(trim($_POST['surname']));
            $dob = $_POST['date_of_birth'];
            $sex = $_POST['sex'];
            $civil_status = $_POST['civil_status'];
            $occupation = ucfirst(trim($_POST['occupation']));
            $contact_number = $_POST['contact_number'];
            $address = ucfirst(trim($_POST['address']));
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

            // 2. Create new appointment
            $app_date = $_POST['date']; // from the form
            $start_time = $_POST['start_time']; // from the form
            $end_time = $_POST['end_time']; // from the form
            $app_status = 'walkin'; // Set default status to walk-in for appointments created this way

            $sql_appointment = "INSERT INTO tblappointment (patient_number, firstname, surname, date, start_time, end_time, status) VALUES (:pnum, :fname, :sname, :app_date, :start_time, :end_time, :status)";
            $query_appointment = $dbh->prepare($sql_appointment);
            $query_appointment->execute([':pnum' => $patient_id, ':fname' => $firstname, ':sname' => $surname, ':app_date' => $app_date, ':start_time' => $start_time, ':end_time' => $end_time, ':status' => $app_status]);

            $dbh->commit();
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'New patient and appointment scheduled successfully.'];
            header('Location: mac.php');
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: mac.php');
        }
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblappointment WHERE id = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment deleted.'];
        header('Location: mac.php');
        exit();
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

    // Count for 'Cancelled' appointments
    $sql_cancelled = "SELECT COUNT(*) FROM tblappointment WHERE status = 'Cancelled'";
    $query_cancelled = $dbh->prepare($sql_cancelled);
    $query_cancelled->execute();
    $count_cancelled = $query_cancelled->fetchColumn();

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
        case 'cancelled':
            $where_clauses[] = "status = 'Cancelled'";
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
    <title>Consultation Appointment</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mac-modal.css">
    <link rel="stylesheet" href="css/toast.css">
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
                <div class="content-wrapper">
                    <div class="container">

                        
                            <div class="header-section">
                                <div class="header-text">
                                    <h1>Manage Appointments</h1>
                                    <p>Schedule and track patient appointments</p>
                                </div>
                                <button type="button" class="new-appointment-btn" id="newAppointmentBtn">New
                                    Appointment</button>
                            </div>
                        <div class="appointment-management-card">
                            <div class="filter-section">
                                <div class="search-box">
                                    <input type="text" placeholder="Search appointments by patient name"
                                        aria-label="Search appointments">
                                </div>
                                <div class="filter-buttons">
                                    <a href="mac.php?filter=all"
                                        class="filter-btn <?php if ($filter === 'all')
                                            echo 'active'; ?>"
                                        data-filter="all">
                                        All Appointments <span class="filter-count"><?php echo $count_all; ?></span>
                                    </a>
                                    <a href="mac.php?filter=today"
                                        class="filter-btn <?php if ($filter === 'today')
                                            echo 'active'; ?>"
                                        data-filter="today">
                                        Today <span class="filter-count"><?php echo $count_today; ?></span>
                                    </a>
                                    <a href="mac.php?filter=upcoming"
                                        class="filter-btn <?php if ($filter === 'upcoming')
                                            echo 'active'; ?>"
                                        data-filter="upcoming">
                                        Upcoming <span class="filter-count"><?php echo $count_upcoming; ?></span>
                                    </a>
                                    <a href="mac.php?filter=pending"
                                        class="filter-btn <?php if ($filter === 'pending')
                                            echo 'active'; ?>"
                                        data-filter="pending">
                                        Pending <span class="filter-count"><?php echo $count_pending; ?></span>
                                    </a>
                                    <a href="mac.php?filter=completed"
                                        class="filter-btn <?php if ($filter === 'completed')
                                            echo 'active'; ?>"
                                        data-filter="completed">
                                        Completed <span class="filter-count"><?php echo $count_completed; ?></span>
                                    </a>
                                    <a href="mac.php?filter=cancelled"
                                        class="filter-btn <?php if ($filter === 'cancelled') echo 'active'; ?>"
                                        data-filter="cancelled">
                                        Cancelled <span class="filter-count"><?php echo $count_cancelled; ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #ccc; margin: 30px 0;">

                        <div class="patient-list-card" id="appointment-table-container">
                            <h2 class="section-title">Consultation Appointments (<?php echo count($appointments); ?>)
                            </h2>
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>First Name</th>
                                        <th>Surname</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Cancel Reason</th>
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
                                                <td><?php echo htmlentities($appointment->cancel_reason); ?></td>
                                                <td><span
                                                        class="status-badge status-<?php echo strtolower(htmlentities($appointment->status)); ?>"><?php echo htmlentities($appointment->status); ?></span>
                                                </td>
                                                <td class="actions-icons">
                                                    <button class="edit-appointment-btn" title="Edit"
                                                        data-id="<?php echo htmlentities($appointment->id); ?>"
                                                        data-firstname="<?php echo htmlentities($appointment->firstname); ?>"
                                                        data-surname="<?php echo htmlentities($appointment->surname); ?>"
                                                        data-date="<?php echo htmlentities($appointment->date); ?>"
                                                        data-start-time="<?php echo htmlentities($appointment->start_time); ?>"
                                                        data-end-time="<?php echo htmlentities($appointment->end_time); ?>"
                                                        data-status="<?php echo htmlentities($appointment->status); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">✏️</button>

                                                    <button class="cancel-appointment-admin-btn" title="Cancel"
                                                        data-id="<?php echo htmlentities($appointment->id); ?>"
                                                        data-firstname="<?php echo htmlentities($appointment->firstname); ?>"
                                                        data-surname="<?php echo htmlentities($appointment->surname); ?>"
                                                        data-date="<?php echo htmlentities($appointment->date); ?>"
                                                        data-start-time="<?php echo htmlentities($appointment->start_time); ?>"
                                                        data-end-time="<?php echo htmlentities($appointment->end_time); ?>"
                                                        data-cancel-reason="<?php echo htmlentities($appointment->cancel_reason); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0 8px;">🚫</button>

                                                    <a href="mac.php?delid=<?php echo $appointment->id; ?>" title="Delete"
                                                        onclick="return confirm('Do you really want to Delete ?');"
                                                        style="font-size: 1.25rem; color: #a0aec0;">🗑️</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center;">No patients found.</td>
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
                        <div class="form-group"><label for="firstname">First Name</label><input type="text"
                                id="firstname" name="firstname" required></div>
                        <div class="form-group"><label for="surname">Surname</label><input type="text" id="surname"
                                name="surname" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="date_of_birth">Date of Birth</label><input type="date"
                                id="date_of_birth" name="date_of_birth" required></div>
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
                        <div class="form-group">
                            <label>Civil Status</label>
                            <select name="status">
                                <option value="">Select Status</option>
                                <option value="Single" <?php if ($patient->status == 'Single')
                                    echo 'selected'; ?>>Single
                                </option>
                                <option value="Married" <?php if ($patient->status == 'Married')
                                    echo 'selected'; ?>>
                                    Married</option>
                                <option value="Widowed" <?php if ($patient->status == 'Widowed')
                                    echo 'selected'; ?>>
                                    Widowed</option>
                                <option value="Separated" <?php if ($patient->status == 'Separated')
                                    echo 'selected'; ?>>
                                    Separated</option>
                            </select>
                        </div>
                        <div class="form-group"><label for="occupation">Occupation</label><input type="text"
                                id="occupation" name="occupation"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="contact_number">Phone Number</label><input type="tel"
                                id="contact_number" name="contact_number" required></div>
                        <div class="form-group"><label for="email">Email Address</label><input type="email" id="email"
                                name="email"></div>
                    </div>
                    <div class="form-group"><label for="address">Address</label><textarea id="address" name="address"
                            rows="2"></textarea></div>

                    <hr class="form-divider">

                    <h3 class="form-section-title">Appointment Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <div class="input-with-icon">
                                <input type="date" id="date" name="date" required>

                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <div class="input-with-icon">
                                <input type="time" id="start_time" name="start_time" required>

                            </div>
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <div class="input-with-icon">
                                <input type="time" id="end_time" name="end_time" required>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="schedule_appointment" class="btn btn-schedule">Schedule
                        Appointment</button>
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

    <!-- Cancel Appointment Admin Modal -->
    <div id="cancelAppointmentAdminModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cancel Appointment</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="cancelAppointmentAdminForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="appointment_id" id="cancel_admin_appointment_id">
                    <p>Are you sure you want to cancel the appointment for <strong
                            id="cancel_admin_patient_name"></strong> on <strong
                            id="cancel_admin_appointment_date_time"></strong>?</p>
                    <div class="form-group">
                        <label for="cancel_admin_reason">Reason for Cancellation (Optional)</label>
                        <textarea id="cancel_admin_reason" name="cancel_reason" class="form-control"
                            rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Close</button>
                    <button type="submit" name="confirm_cancel_admin" class="btn btn-danger">Confirm
                        Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/toast.js"></script>
    <script>
        // Helper function to format time for display, now in a scope accessible to all listeners
        function formatTime12hr(time24hr) {
            if (!time24hr) return 'N/A';
            const [hours, minutes] = time24hr.split(':');
            const date = new Date();
            date.setHours(hours, minutes);
            return date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        }

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
            if (form) {
                form.addEventListener('submit', function (e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        // Modern browsers will show their own validation messages.
                        // For older ones, you might add custom logic here.
                        alert('Please fill out all required fields.');
                    }
                });
            }

            // Auto-capitalize first letter for new appointment fields
            function capitalizeFirstLetter(inputId) {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', function () {
                        if (this.value.length > 0) {
                            this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                        }
                    });
                }
            }
            capitalizeFirstLetter('firstname');
            capitalizeFirstLetter('surname');
            capitalizeFirstLetter('occupation');

            // --- Cancel Appointment Admin Modal ---
            const cancelAdminModal = document.getElementById('cancelAppointmentAdminModal');
            const cancelAdminCloseBtn = cancelAdminModal.querySelector('.close-button');
            const cancelAdminCancelBtn = cancelAdminModal.querySelector('.btn-cancel');

            function closeCancelAdminModal() {
                cancelAdminModal.style.display = 'none';
            }

            cancelAdminCloseBtn.addEventListener('click', closeCancelAdminModal);
            cancelAdminCancelBtn.addEventListener('click', closeCancelAdminModal);

            window.addEventListener('click', function (event) {
                if (event.target === cancelAdminModal) {
                    closeCancelAdminModal();
                }
            });

            document.querySelectorAll('.cancel-appointment-admin-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const dataset = this.dataset;
                    document.getElementById('cancel_admin_appointment_id').value = dataset.id;
                    document.getElementById('cancel_admin_patient_name').textContent = dataset.firstname + ' ' + dataset.surname;
                    document.getElementById('cancel_admin_appointment_date_time').textContent = dataset.date + ' at ' + formatTime12hr(dataset.startTime) + ' - ' + formatTime12hr(dataset.endTime); // This line was causing the error
                    document.getElementById('cancel_admin_reason').value = dataset.cancelReason; // Pre-fill if already cancelled with a reason

                    cancelAdminModal.style.display = 'flex';
                });
            });
        });
    </script>
    <script src="js/mac-modal.js"></script>

</body>

</html>
<?php ?>