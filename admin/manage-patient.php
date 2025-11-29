<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // AJAX handler for fetching services by category
    if (isset($_GET['get_services_by_category']) && !empty($_GET['category_id'])) {
        header('Content-Type: application/json');
        $category_id = $_GET['category_id']; // Changed to category_id
        $sql = "SELECT number, name FROM tblservice WHERE category_id = :category_id ORDER BY name ASC"; // Filter by category_id
        $query = $dbh->prepare($sql);
        $query->bindParam(':category_id', $category_id, PDO::PARAM_INT); // Bind as INT
        $query->execute();
        $services = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($services);
        exit(); // Stop further execution
    }

    // AJAX handler for fetching month availability from tblcalendar
    if (isset($_GET['get_month_availability']) && !empty($_GET['year']) && !empty($_GET['month'])) {
        header('Content-Type: application/json');
        $year = intval($_GET['year']);
        $month = intval($_GET['month']);

        // Get all dates in the month
        $firstDay = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
        $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

        // Query to get AVAILABLE dates from tblcalendar (dates with defined slots)
        $sql = "SELECT DISTINCT DATE(date) as available_date FROM tblcalendar 
                WHERE YEAR(date) = :year AND MONTH(date) = :month 
                ORDER BY date ASC";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':year', $year, PDO::PARAM_INT);
        $query->bindParam(':month', $month, PDO::PARAM_INT);
        $query->execute();
        
        $available_dates = $query->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['available' => $available_dates]);
        exit();
    }

    // Handle patient update from edit modal
    if (isset($_POST['update_patient'])) {
        $patient_id = $_POST['patient_id'];
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $dob = $_POST['date_of_birth'];
        $sex = $_POST['sex'];
        $civil_status = $_POST['civil_status'];
        $occupation = $_POST['occupation'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $dbh->beginTransaction();

        $age = '';
        if (!empty($dob)) {
            $birthDate = new DateTime($dob);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        try {
            $sql_update = "UPDATE tblpatient SET firstname=:fname, surname=:sname, date_of_birth=:dob, sex=:sex, status=:status, occupation=:occupation, age=:age, contact_number=:contact, address=:address, email=:email WHERE number=:pid";
            $query_update = $dbh->prepare($sql_update);
            $query_update->execute([
                ':fname' => $firstname, ':sname' => $surname, ':dob' => $dob, ':sex' => $sex, ':status' => $civil_status, 
                ':occupation' => $occupation, ':age' => $age, ':contact' => $contact_number, ':address' => $address, 
                ':email' => $email, ':pid' => $patient_id
            ]);

            // Check for and create new appointment if details are provided
            $service_id = $_POST['service_id'];
            $app_date = $_POST['app_date'];
            $start_time = $_POST['start_time'];
            $duration = $_POST['duration'];

            if (!empty($service_id) && !empty($app_date) && !empty($start_time)) {
                $app_status = 'Ongoing'; // Default status for service schedules

                $sql_schedule = "INSERT INTO tblschedule (patient_number, firstname, surname, service_id, date, time, duration, status) VALUES (:pnum, :fname, :sname, :service_id, :app_date, :start_time, :duration, :status)";
                $query_schedule = $dbh->prepare($sql_schedule);
                $query_schedule->execute([
                    ':pnum' => $patient_id, 
                    ':fname' => $firstname, 
                    ':sname' => $surname, 
                    ':service_id' => $service_id,
                    ':app_date' => $app_date, 
                    ':start_time' => $start_time, 
                    ':duration' => $duration,
                    ':status' => $app_status
                ]);
                $alert_message = 'Patient details updated and new service appointment scheduled successfully.';
            } else {
                $alert_message = 'Patient details updated successfully.';
            }
            $dbh->commit();
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => $alert_message];
            header('Location: manage-patient.php');
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . addslashes($e->getMessage())];
            header('Location: manage-patient.php');
        }
        exit();
    }
    // Handle new patient creation from modal
    if (isset($_POST['add_patient'])) {
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

            // 2. Check for and create new appointment if details are provided
            $app_date = $_POST['app_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];

            if (!empty($app_date) && !empty($start_time)) {
                $app_status = 'walkin'; // Default status for appointments created this way

                $sql_appointment = "INSERT INTO tblappointment (patient_number, firstname, surname, date, start_time, end_time, status) VALUES (:pnum, :fname, :sname, :app_date, :start_time, :end_time, :status)";
                $query_appointment = $dbh->prepare($sql_appointment);
                $query_appointment->execute([
                    ':pnum' => $patient_id, 
                    ':fname' => $firstname, 
                    ':sname' => $surname, 
                    ':app_date' => $app_date, 
                    ':start_time' => $start_time, 
                    ':end_time' => $end_time, 
                    ':status' => $app_status
                ]);
                $alert_message = 'New patient and appointment scheduled successfully.';
            } else {
                $alert_message = 'New patient added successfully.';
            }
            
            $dbh->commit();
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => $alert_message];
            header('Location: manage-patient.php');
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . addslashes($e->getMessage())];
            header('Location: manage-patient.php');
        }
        exit();
    }

    // Initialize search variable
    $search = '';

    // Handle the search
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_query'])) {
        $search = trim($_POST['search_query']); // Search is triggered on form submit
    }

    // Code for deletion - this remains the same
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblpatient WHERE number = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Data deleted.'];
        header('Location: manage-patient.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/manage-patient.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
     <link rel="stylesheet" href="css/sidebar.css">
     <link rel="stylesheet" href="css/mas-modal.css">   
    <link rel="stylesheet" href="css/admin-calendar-availability.css">
    <link rel="stylesheet" href="css/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel" style="background-color: #f5f7fa;">
                <div id="toast-container"></div>
                <?php
                if (isset($_SESSION['toast_message'])) {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                    unset($_SESSION['toast_message']);
                }
                ?>
                <div class="content-wrapper" style="background-color: #f5f7fa;">
                    <div class="header">
                        <div class="header-text">
                            <h2>Patients</h2>
                            <p>Manage your patient records and information</p>
                        </div>
                        <a href="#" class="add-btn" id="addPatientBtn">
                            <i class="fas fa-user-plus"></i>
                            Add New Patient
                        </a>
                    </div>

                    <form method="POST">
                        <div class="search-filter-bar">
                            <div class="search-box">
                                <span class="search-icon"></span>
                                <input type="text" class="search-input" name="search_query" placeholder="Search patients by name..." value="<?php echo htmlentities($search); ?>" id="searchInput">
                            </div>

                        </div>
                    </form>

                    <?php
                        // Pagination setup
                        $pageno = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
                        $no_of_records_per_page = 10;
                        $offset = ($pageno - 1) * $no_of_records_per_page;

                        // Build the query based on search
                        $countSql = "SELECT COUNT(*) FROM tblpatient WHERE 1=1";
                        if ($search) {
                            $countSql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                        }
                        $countQuery = $dbh->prepare($countSql);
                        if ($search) {
                            $like_search = "%$search%";
                            $countQuery->bindParam(':search', $like_search, PDO::PARAM_STR);
                        }
                        $countQuery->execute();
                        $total_rows = $countQuery->fetchColumn();
                        $total_pages = ceil($total_rows / $no_of_records_per_page);

                        $sql = "SELECT *, SUBSTRING(firstname, 1, 1) as f_initial, SUBSTRING(surname, 1, 1) as s_initial FROM tblpatient WHERE 1=1";
                        if ($search) {
                            $sql .= " AND (firstname LIKE :search OR surname LIKE :search)";
                        }
                        $sql .= " ORDER BY created_at DESC LIMIT :offset, :limit";

                        $query = $dbh->prepare($sql);
                        if ($search) {
                            $query->bindParam(':search', $like_search, PDO::PARAM_STR);
                        }
                        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                        $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                    ?>

                    <div class="patient-table-container">
                        <div class="table-header">
                            <h2>All Patients (<span><?php echo $total_rows; ?></span>)</h2>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date of Birth</th>
                                    <th>Age</th>
                                    <th>Sex</th>
                                    <th>Status</th>
                                    <th>Occupation</th>
                                    <th>Email</th>
                                    <th>Contact No.</th>
                                    <th>Address</th>
                                    <th>Patient History</th>
                                    <th>Examination Record</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="patientTableBody">
                                <?php if ($query->rowCount() > 0) {
                                    foreach ($results as $row) { ?>
                                <tr>
                                    <td>
                                        <div class="patient-cell">
                                            
                                        <div class="avatar"><?php echo htmlentities($row->f_initial) . htmlentities($row->s_initial); ?></div>
                                            <div class="patient-info">
                                                <div class="patient-name"><?php echo htmlentities($row->firstname) . ' ' . htmlentities($row->surname); ?></div>
                                                <div class="patient-id">ID: <?php echo htmlentities($row->number); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlentities($row->date_of_birth); ?>
                                    </td>
                                    <td><?php echo htmlentities($row->age); ?></td>
                                    <td><?php echo htmlentities($row->sex); ?></td>
                                    <td><?php echo htmlentities($row->status); ?></td>
                                    <td><?php echo htmlentities($row->occupation); ?></td>
                                    <td><?php echo htmlentities($row->email); ?></td>
                                    <td><?php echo htmlentities($row->contact_number); ?></td>
                                    <td><?php echo htmlentities($row->address); ?></td>
                                    <td><a href="view-ph.php?number=<?php echo htmlentities($row->number); ?>" class="action-icon" title="View Patient History"><i class="fas fa-history"></i></a></td>
                                    <td><a href="view-er.php?stid=<?php echo $row->number; ?>" class="action-icon" title="View Examination Record"><i class="fas fa-file-medical"></i></a></td>
                                    <td>
                                        <div class="actions-cell">
                                            <button class="action-icon edit-patient-btn" title="Edit"
                                                data-id="<?php echo htmlentities($row->number); ?>"
                                                data-firstname="<?php echo htmlentities($row->firstname); ?>"
                                                data-surname="<?php echo htmlentities($row->surname); ?>"
                                                data-dob="<?php echo htmlentities($row->date_of_birth); ?>"
                                                data-sex="<?php echo htmlentities($row->sex); ?>"
                                                data-status="<?php echo htmlentities($row->status); ?>"
                                                data-occupation="<?php echo htmlentities($row->occupation); ?>"
                                                data-contact="<?php echo htmlentities($row->contact_number); ?>"
                                                data-address="<?php echo htmlentities($row->address); ?>"
                                                data-email="<?php echo htmlentities($row->email); ?>"
                                                style="background:none; border:none; cursor:pointer; padding:0; font-size: 1rem;"
                                            ><i class="fas fa-edit"></i></button>
                                            <a href="manage-patient.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete?');" class="action-icon" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php }
                                } else { ?>
                                <tr>
                                    <td colspan="12" style="text-align: center; color:red;">No patients found.</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Patient</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="addPatientForm" method="POST">
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
                        <div class="form-group"><label for="occupation">Occupation</label><input type="text" id="occupation" name="occupation"></div>
                    </div>
                <div class="form-row"> 
                        <div class="form-group"><label for="contact_number">Phone Number</label><input type="tel" id="contact_number" name="contact_number" required></div>
                        <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email"></div>
                    </div>
                    <div class="form-group"><label for="address">Address</label><textarea id="address" name="address" rows="2"></textarea></div>

                    <hr class="form-divider" style="margin: 20px 0; border-top: 1px solid #ccc;">

                    <h3 class="form-section-title">Appointment Details (Optional)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="app_date">Date</label>
                            <input type="date" id="app_date" name="app_date">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="add_patient" class="btn btn-schedule">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div id="editPatientModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Patient Details</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editPatientForm" method="POST">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <div class="modal-body">
                    <h3 class="form-section-title">Patient Details</h3>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_firstname">First Name</label><input type="text" id="edit_firstname" name="firstname" readonly></div>
                        <div class="form-group"><label for="edit_surname">Surname</label><input type="text" id="edit_surname" name="surname" readonly></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_date_of_birth">Date of Birth</label><input type="date" id="edit_date_of_birth" name="date_of_birth" readonly></div>
                        <div class="form-group">
                            <label for="edit_sex">Sex</label>
                            <select id="edit_sex" name="sex">
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_civil_status">Civil Status</label><input type="text" id="edit_civil_status" name="civil_status"></div>
                        <div class="form-group"><label for="edit_occupation">Occupation</label><input type="text" id="edit_occupation" name="occupation"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_contact_number">Phone Number</label><input type="tel" id="edit_contact_number" name="contact_number"></div>
                        <div class="form-group"><label for="edit_email">Email Address</label><input type="email" id="edit_email" name="email"></div>
                    </div>
                    <div class="form-group"><label for="edit_address">Address</label><textarea id="edit_address" name="address" rows="2" readonly></textarea></div>

                    <hr class="form-divider" style="margin: 20px 0; border-top: 1px solid #ccc;">
                    <h3 class="form-section-title">Add New Service Schedule (Optional)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_service_category">Service Category</label>
                            <select id="edit_service_category" name="service_category">
                                <option value="">Select a Category</option>
                                <?php
                                $cats = $dbh->query("SELECT id, name FROM tblcategory ORDER BY id ASC")->fetchAll(PDO::FETCH_OBJ);
                                foreach ($cats as $c) {
                                    echo "<option value='" . htmlentities($c->id) . "'>" . htmlentities($c->name) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_service_id">Service</label>
                            <select id="edit_service_id" name="service_id">
                                <option value="">Select a category first</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_app_date">Date</label>
                            <input type="date" id="edit_app_date" name="app_date">
                        </div>
                        <div class="form-group">
                            <label for="edit_start_time">Start Time</label>
                            <input type="time" id="edit_start_time" name="start_time">
                        </div>
                        <div class="form-group">
                            <label for="edit_duration">Duration (minutes)</label>
                            <input type="number" id="edit_duration" name="duration" min="1" placeholder="e.g., 30">
                        </div>
                    </div>
                </div>

                
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_patient" class="btn btn-schedule">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/toast.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Add Patient Modal ---
        const modal = document.getElementById('addPatientModal');
        const openBtn = document.getElementById('addPatientBtn');
        const closeBtn = modal.querySelector('.close-button');
        const cancelBtn = modal.querySelector('.btn-cancel');

        openBtn.addEventListener('click', (e) => { e.preventDefault(); modal.style.display = 'flex'; });
        closeBtn.addEventListener('click', () => { modal.style.display = 'none'; });
        cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; });

        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // --- Edit Patient Modal ---
        const editModal = document.getElementById('editPatientModal');
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

        document.querySelectorAll('.edit-patient-btn').forEach(button => {
            button.addEventListener('click', function() {
                const dataset = this.dataset;
                document.getElementById('edit_patient_id').value = dataset.id;
                document.getElementById('edit_firstname').value = dataset.firstname;
                document.getElementById('edit_surname').value = dataset.surname;
                document.getElementById('edit_date_of_birth').value = dataset.dob;
                document.getElementById('edit_sex').value = dataset.sex;
                document.getElementById('edit_civil_status').value = dataset.status;
                document.getElementById('edit_occupation').value = dataset.occupation;
                document.getElementById('edit_contact_number').value = dataset.contact;
                document.getElementById('edit_address').value = dataset.address;
                document.getElementById('edit_email').value = dataset.email;

                // Clear appointment fields
                document.getElementById('edit_service_id').value = '';
                document.getElementById('edit_app_date').value = '';
                document.getElementById('edit_start_time').value = '';
                document.getElementById('edit_duration').value = '';
                document.getElementById('edit_service_category').value = '';
                document.getElementById('edit_service_id').innerHTML = '<option value="">Select a category first</option>';
                document.getElementById('edit_service_id').disabled = true;

                editModal.style.display = 'flex';
            });
        });

        // Auto-capitalize first letter for new patient fields
        function capitalizeFirstLetter(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                    }
                });
            }
        }
        capitalizeFirstLetter('firstname');
        capitalizeFirstLetter('surname');
        capitalizeFirstLetter('occupation');
        capitalizeFirstLetter('address');

        // --- Dependent Dropdown for Edit Modal ---
        const categorySelect = document.getElementById('edit_service_category');
        const serviceSelect = document.getElementById('edit_service_id');

        categorySelect.addEventListener('change', function() {
            const category = this.value;
            serviceSelect.innerHTML = '<option value="">Loading...</option>';
            serviceSelect.disabled = true;

            if (!category) {
                serviceSelect.innerHTML = '<option value="">Select a category first</option>';
                return;
            }

            // Fetch services for the selected category via AJAX
            fetch(`manage-patient.php?get_services_by_category=1&category_id=${encodeURIComponent(category)}`) // Changed parameter name
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    serviceSelect.innerHTML = '<option value="">Select a Service</option>';
                    if (data.length > 0) {
                        data.forEach(service => {
                            const option = new Option(service.name, service.number);
                            serviceSelect.appendChild(option);
                        });
                        serviceSelect.disabled = false;
                    } else {
                        serviceSelect.innerHTML = '<option value="">No services found</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching services:', error);
                    serviceSelect.innerHTML = '<option value="">Error loading services</option>';
                });
        });
    });
    </script>
    <script src="js/admin-calendar-availability.js"></script>
</body>
</html>
<?php } ?>