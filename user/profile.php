<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
    exit();
} else {
    $patient_number = $_SESSION['sturecmsnumber'];

    // Handle form submission for updating medical history
    if (isset($_POST['update_medical_history'])) {
        $health_conditions_data = isset($_POST['health_conditions']) && is_array($_POST['health_conditions']) ? $_POST['health_conditions'] : [];

        // Ensure all categories are present, even if empty, to overwrite old data
        $all_categories = ['general', 'liver', 'diabetes', 'thyroid', 'urinary', 'nervous', 'blood', 'respiratory', 'liver_specify'];
        foreach ($all_categories as $cat) {
            if (!isset($health_conditions_data[$cat])) {
                $health_conditions_data[$cat] = ($cat === 'liver_specify') ? '' : [];
            }
        }

        $health_json = json_encode($health_conditions_data);

        $sql_update = "UPDATE tblpatient SET health_conditions = :health_json WHERE number = :patient_number";
        $query_update = $dbh->prepare($sql_update);
        $query_update->bindParam(':health_json', $health_json, PDO::PARAM_STR);
        $query_update->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
        $query_update->execute();

        header('Location: profile.php?tab=medical');
        exit();
    }

    // Handle form submission for updating profile from modal
    if (isset($_POST['update_profile'])) {
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $date_of_birth = $_POST['date_of_birth'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $username = $_POST['username'];
        $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $occupation = isset($_POST['occupation']) ? $_POST['occupation'] : '';
        $image = $_FILES['image']['name'];

        $age = '';
        if (!empty($date_of_birth)) {
            $dob = new DateTime($date_of_birth);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
        }

        $sql_get_image = "SELECT Image FROM tblpatient WHERE number = :number";
        $q_get_image = $dbh->prepare($sql_get_image);
        $q_get_image->bindParam(':number', $patient_number, PDO::PARAM_INT);
        $q_get_image->execute();
        $old_image = $q_get_image->fetchColumn();

        if (!empty($image) && isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $target_dir = "../admin/images/";
            $image_basename = basename(preg_replace('/[^A-Za-z0-9._-]/', '_', $image));
            $target_file = $target_dir . $image_basename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_to_update = $image_basename;
            } else {
                $image_to_update = $old_image;
            }
        } else {
            $image_to_update = $old_image;
        }

        $updateSql = "UPDATE tblpatient SET firstname=:firstname, surname=:surname, date_of_birth=:dob, sex=:sex, status=:status, occupation=:occupation, age=:age, contact_number=:contact, address=:address, username=:uname, image=:image WHERE number=:number";
        $updateQuery = $dbh->prepare($updateSql);
        $updateQuery->execute([
            ':firstname' => $firstname,
            ':surname' => $surname,
            ':dob' => $date_of_birth,
            ':sex' => $sex,
            ':status' => $status,
            ':occupation' => $occupation,
            ':age' => $age,
            ':contact' => $contact_number,
            ':address' => $address,
            ':uname' => $username,
            ':image' => $image_to_update,
            ':number' => $patient_number
        ]);

        // Redirect to the profile page to show the updated data
        header('Location: profile.php');
        exit();
    }

    // AJAX endpoint: return calendar times for a given date
    if (isset($_GET['get_calendar_times']) && !empty($_GET['date'])) {
        $reqDate = $_GET['date'];
        try {
            $stmt = $dbh->prepare("SELECT c.id, c.start_time, c.end_time FROM tblcalendar c LEFT JOIN tblappointment a ON a.date = c.date AND a.start_time = c.start_time WHERE c.date = :date AND a.id IS NULL ORDER BY c.start_time");
            $stmt->bindParam(':date', $reqDate, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $start = $r['start_time'];
                $end = $r['end_time'];
                $label = date('g:i A', strtotime($start));
                if (!empty($end)) {
                    $label .= ' - ' . date('g:i A', strtotime($end));
                }
                $out[] = ['id' => $r['id'], 'start' => $start, 'end' => $end, 'label' => $label];
            }
            header('Content-Type: application/json');
            echo json_encode($out);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

    // Handle form submission for booking an appointment from the modal
    if (isset($_POST['book_appointment'])) {
        $appointment_date = trim($_POST['appointment_date']);
        $appointment_time = trim($_POST['appointment_time']);

        // Check for duplicate appointment
        $sqlChk = "SELECT COUNT(*) FROM tblappointment WHERE patient_number = :pn AND `date` = :dt AND `start_time` = :tm";
        $qryChk = $dbh->prepare($sqlChk);
        $qryChk->execute([':pn' => $patient_number, ':dt' => $appointment_date, ':tm' => $appointment_time]);
        if ((int) $qryChk->fetchColumn() > 0) {
            $_SESSION['modal_error'] = 'You already have an appointment at that date and time.';
        } else {
            $firstname = $_SESSION['sturecmsfirstname'] ?? '';
            $surname = $_SESSION['sturecmssurname'] ?? '';
            $status_default = 'Pending';

            $sqlIns = "INSERT INTO tblappointment (firstname, surname, date, start_time, patient_number, status) VALUES (:fn, :sn, :dt, :tm, :pn, :st)";
            $qryIns = $dbh->prepare($sqlIns);
            $qryIns->execute([
                ':fn' => $firstname,
                ':sn' => $surname,
                ':dt' => $appointment_date,
                ':tm' => $appointment_time,
                ':pn' => $patient_number,
                ':st' => $status_default
            ]);

            $_SESSION['modal_success'] = 'Appointment booked successfully.';
            header('Location: profile.php?tab=appointments');
            exit();
        }
        // If there was an error, redirect back to show it
        header('Location: profile.php?tab=appointments&show_booking=1');
        exit();
    }


    $patient_number = $_SESSION['sturecmsnumber'];

    // Fetch patient data
    $sql_patient = "SELECT * FROM tblpatient WHERE number = :patient_number";
    $query_patient = $dbh->prepare($sql_patient);
    $query_patient->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
    $query_patient->execute();
    $patient = $query_patient->fetch(PDO::FETCH_OBJ);

    // Fetch health conditions
    $health_arr = [];
    if ($patient && !empty($patient->health_conditions) && $patient->health_conditions !== 'null') {
        $decoded = json_decode($patient->health_conditions, true);
        if (is_array($decoded)) {
            $health_arr = $decoded;
        }
    }

    // Helper functions to pre-fill the form
    function hc_checked($category, $value, $health_data)
    {
        if (isset($health_data[$category]) && is_array($health_data[$category]) && in_array($value, $health_data[$category])) {
            return 'checked';
        }
        return '';
    }

    function hc_text($key, $health_data)
    {
        return isset($health_data[$key]) ? htmlspecialchars($health_data[$key]) : '';
    }


    // Fetch consultation appointments
    $sql_appts = "SELECT id, `date`, start_time, status FROM tblappointment WHERE patient_number = :patient_number ORDER BY `date` DESC, start_time DESC";
    $query_appts = $dbh->prepare($sql_appts);
    $query_appts->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
    $query_appts->execute();
    $appointments = $query_appts->fetchAll(PDO::FETCH_ASSOC);

    // Fetch service schedules
    $sql_schedules = "SELECT s.id, s.date, s.time, s.duration, s.status AS sched_status, svc.name AS service_name 
                      FROM tblschedule s 
                      LEFT JOIN tblservice svc ON svc.number = s.service_id 
                      WHERE s.patient_number = :patient_number 
                      ORDER BY s.date DESC, s.time DESC";
    $query_schedules = $dbh->prepare($sql_schedules);
    $query_schedules->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
    $query_schedules->execute();
    $service_schedules = $query_schedules->fetchAll(PDO::FETCH_ASSOC);

    // Helper to format time
    function format_time_12hr($time_24hr)
    {
        if (empty($time_24hr))
            return '';
        return date("g:i A", strtotime($time_24hr));
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Patient Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link href="./css/profile.css" rel="stylesheet">
    <link href="./css/header.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <?php include_once(__DIR__ . './includes/header.php'); ?>
    <div class="container">
        <?php if ($patient): ?>
            <!-- Patient Header -->
            <?php
            if (isset($_SESSION['profile_message'])) {
                echo '<div class="alert alert-success" style="margin-bottom: 20px;">' . htmlspecialchars($_SESSION['profile_message']) . '</div>';
                unset($_SESSION['profile_message']);
            }
            ?>
            <div class="patient-header">
                <div class="patient-info">
                    <div class="patient-avatar">
                        <?php
                        $profile_avatar = 'avatar.png'; // Default fallback
                        if (!empty($patient->Image)) {
                            $profile_avatar = $patient->Image;
                        } elseif ($patient->sex === 'Male') {
                            $profile_avatar = 'man-icon.png';
                        } elseif ($patient->sex === 'Female') {
                            $profile_avatar = 'woman-icon.jpg';
                        }
                        ?>
                        <img src="../admin/images/<?php echo htmlentities($profile_avatar); ?>"
                            alt="<?php echo htmlentities($patient->firstname); ?>">
                    </div>
                    <div class="patient-details">
                        <h1 class="patient-name"><?php echo htmlentities($patient->surname . ', ' . $patient->firstname); ?>
                        </h1>
                        <p class="patient-id">Patient ID: <?php echo htmlentities($patient->number); ?></p>
                        <span class="status-badge">Active</span>
                    </div>
                </div>

                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-label">Email</span>
                        <span class="contact-value"><?php echo htmlentities($patient->email ?: 'N/A'); ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Phone</span>
                        <span class="contact-value"><?php echo htmlentities($patient->contact_number ?: 'N/A'); ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Date of Birth</span>
                        <span
                            class="contact-value"><?php echo htmlentities($patient->date_of_birth ? date("F j, Y", strtotime($patient->date_of_birth)) : 'N/A'); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <button id="editProfileBtn" class="btn btn-outline">
                        📝
                        Edit Profile
                    </button>
                    <button id="bookAppointmentBtn" class="btn btn-primary">
                        🗓️
                        Book Appointment
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs" id="profileTabs">

                <div class="tab active" data-tab-target="#medicalContent">
                    🩺
                    Medical History
                </div>
                <div class="tab" data-tab-target="#appointmentsContent">
                    🗓️
                    Appointments
                </div>
            </div>

            <!-- Tab Content Panels -->
            <div class="tab-content-container">

                <div id="medicalContent" class="tab-pane active">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title" style="margin: 0;">Medical History</h2>
                            <button id="editMedicalHistoryBtn" class="btn btn-outline"
                                style="padding: 5px 10px; font-size: 14px;">
                                📝
                                Edit
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($health_arr)): ?>
                                <div class="alert alert-info mb-0">
                                    No health condition records found. You can add this information when booking an appointment.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table medical-history-table">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($health_arr as $category => $conditions):
                                                if (empty($conditions))
                                                    continue;
                                                $display_value = is_array($conditions) ? implode(', ', array_map('htmlspecialchars', $conditions)) : htmlspecialchars($conditions);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $category))); ?>
                                                    </td>
                                                    <td><?php echo $display_value; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="appointmentsContent" class="tab-pane">
                    <div class="content-grid">
                        <!-- Left Card: Consultation Appointments -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Consultations</h2>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <div class="appointment-item">
                                            <div class="appointment-header">
                                                <div class="appointment-status">
                                                    <span
                                                        class="status-tag <?php echo strtolower(htmlentities($appt['status'])); ?>"><?php echo htmlentities($appt['status']); ?></span>
                                                    <div class="appointment-datetime">
                                                        📅
                                                        <?php echo date("F j, Y", strtotime($appt['date'])) . ' at ' . format_time_12hr($appt['start_time']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="appointment-title">Consultation</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No consultation appointments found.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Card: Service Appointments -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Services</h2>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($service_schedules)): ?>
                                    <?php foreach ($service_schedules as $schedule): ?>
                                        <div class="appointment-item">
                                            <div class="appointment-header">
                                                <div class="appointment-status">
                                                    <span
                                                        class="status-tag <?php echo strtolower(htmlentities($schedule['sched_status'])); ?>"><?php echo htmlentities($schedule['sched_status']); ?></span>
                                                    <div class="appointment-datetime">
                                                        📅
                                                        <?php echo date("F j, Y", strtotime($schedule['date'])) . ' at ' . format_time_12hr($schedule['time']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="appointment-title">
                                                <?php echo htmlentities($schedule['service_name'] ?: 'Dental Service'); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No service appointments found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger mt-5">
                Could not load patient profile. Please try logging in again.
            </div>
        <?php endif; ?>
    </div>

    <!-- Book Appointment Modal -->
    <div id="bookAppointmentModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h4 class="modal-title">Book a New Appointment</h4>
                <span class="close" data-dismiss="modal">&times;</span>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <form method="post" action="profile.php">
                    <?php
                    if (isset($_SESSION['modal_error'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['modal_error']) . '</div>';
                        unset($_SESSION['modal_error']);
                    }
                    ?>
                    <div class="form-group">
                        <label for="appointment_date_modal">Preferred Date</label>
                        <input type="date" class="form-control" name="appointment_date" id="appointment_date_modal"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time_modal">Available Times</label>
                        <select class="form-control" name="appointment_time" id="appointment_time_modal" required>
                            <option value="">-- Select a date first --</option>
                        </select>
                    </div>
                    <div class="modal-footer"
                        style="padding: 15px 0 0 0; border-top: 1px solid #e5e5e5; margin-top: 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="book_appointment" class="btn btn-primary">Book Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h4 class="modal-title">Edit Profile</h4>
                <span class="close" data-dismiss="modal">&times;</span>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <?php if ($patient): ?>
                    <form method="post" action="profile.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group"><label>First Name</label><input type="text" class="form-control"
                                        name="firstname" value="<?php echo htmlentities($patient->firstname); ?>" required>
                                </div>
                                <div class="form-group"><label>Surname</label><input type="text" class="form-control"
                                        name="surname" value="<?php echo htmlentities($patient->surname); ?>" required>
                                </div>
                                <div class="form-group"><label>Username</label><input type="text" class="form-control"
                                        name="username" value="<?php echo htmlentities($patient->username); ?>" required>
                                </div>
                                <div class="form-group"><label>Contact Number</label><input type="text" class="form-control"
                                        name="contact_number" value="<?php echo htmlentities($patient->contact_number); ?>"
                                        required></div>
                                <div class="form-group"><label>Address</label><textarea class="form-control" name="address"
                                        required><?php echo htmlentities($patient->address); ?></textarea></div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group"><label>Date of Birth</label><input type="date" class="form-control"
                                        name="date_of_birth" value="<?php echo htmlentities($patient->date_of_birth); ?>"
                                        required></div>
                                <div class="form-group">
                                    <label>Sex</label>
                                    <select class="form-control" name="sex" required>
                                        <option value="">-- Select --</option>
                                        <option value="Male" <?php if ($patient->sex == 'Male')
                                            echo 'selected'; ?>>Male
                                        </option>
                                        <option value="Female" <?php if ($patient->sex == 'Female')
                                            echo 'selected'; ?>>Female
                                        </option>
                                        <option value="Other" <?php if ($patient->sex == 'Other')
                                            echo 'selected'; ?>>Other
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Civil Status</label><input type="text" class="form-control"
                                        name="status" value="<?php echo htmlentities($patient->status); ?>"></div>
                                <div class="form-group"><label>Occupation</label><input type="text" class="form-control"
                                        name="occupation" value="<?php echo htmlentities($patient->occupation); ?>"></div>
                                <div class="form-group">
                                    <label>Profile Picture</label>
                                    <input type="file" class="form-control" name="image">
                                    <small class="form-text text-muted">Leave blank to keep the current image.</small>
                                    <?php
                                    $modal_avatar = 'avatar.png'; // Default fallback
                                    if (!empty($patient->Image)) {
                                        $modal_avatar = $patient->Image;
                                    } elseif ($patient->sex === 'Male') {
                                        $modal_avatar = 'man-icon.png';
                                    } elseif ($patient->sex === 'Female') {
                                        $modal_avatar = 'woman-icon.jpg';
                                    }
                                    ?>
                                    <img src="../admin/images/<?php echo htmlentities($modal_avatar); ?>" width="50"
                                        class="mt-2">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer"
                            style="padding: 15px 0 0 0; border-top: 1px solid #e5e5e5; margin-top: 15px;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p>Could not load profile data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Medical History Edit Modal -->
    <div id="medicalHistoryModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h4 class="modal-title">Edit Medical History</h4>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <form method="post" action="profile.php">
                    <p>Please check all conditions that apply to you.</p>
                    <div class="row section">
                        <div class="col-md-6 ">
                            <h2 class="section-title">GENERAL</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[general][]"
                                        value="Marked weight change" id="hc_modal_general_1" <?php echo hc_checked('general', 'Marked weight change', $health_arr); ?>><label
                                        for="hc_modal_general_1">Marked weight change</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[general][]"
                                        value="Increase frequency of urination" id="hc_modal_general_2" <?php echo hc_checked('general', 'Increase frequency of urination', $health_arr); ?>><label
                                        for="hc_modal_general_2">Increase frequency of urination</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[general][]"
                                        value="Burning sensation on urination" id="hc_modal_general_3" <?php echo hc_checked('general', 'Burning sensation on urination', $health_arr); ?>><label
                                        for="hc_modal_general_3">Burning sensation on urination</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[general][]"
                                        value="Loss of hearing, ringing of ears" id="hc_modal_general_4" <?php echo hc_checked('general', 'Loss of hearing, ringing of ears', $health_arr); ?>><label for="hc_modal_general_4">Loss of hearing, ringing of ears</label>
                                </div>
                            </div>

                            <h2 class="section-title mt-3">LIVER</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[liver][]"
                                        value="History of liver ailment" id="hc_modal_liver_1" <?php echo hc_checked('liver', 'History of liver ailment', $health_arr); ?>><label
                                        for="hc_modal_liver_1">History of liver ailment</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[liver][]"
                                        value="Jaundice" id="hc_modal_liver_2" <?php echo hc_checked('liver', 'Jaundice', $health_arr); ?>><label for="hc_modal_liver_2">Jaundice</label>
                                </div>
                            </div>
                            <div class="form-group mt-2"><label for="liver_specify_modal">Specify:</label><input
                                    type="text" class="form-control" name="health_conditions[liver_specify]"
                                    id="liver_specify_modal"
                                    value="<?php echo hc_text('liver_specify', $health_arr); ?>"></div>

                            <h2 class="section-title mt-3">DIABETES</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[diabetes][]"
                                        value="Delayed healing of wounds" id="hc_modal_diab_1" <?php echo hc_checked('diabetes', 'Delayed healing of wounds', $health_arr); ?>><label
                                        for="hc_modal_diab_1">Delayed healing of wounds</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[diabetes][]"
                                        value="Increase intake of food or water" id="hc_modal_diab_2" <?php echo hc_checked('diabetes', 'Increase intake of food or water', $health_arr); ?>><label for="hc_modal_diab_2">Increase intake of food or water</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[diabetes][]"
                                        value="Family history of diabetes" id="hc_modal_diab_3" <?php echo hc_checked('diabetes', 'Family history of diabetes', $health_arr); ?>><label
                                        for="hc_modal_diab_3">Family history of diabetes</label></div>
                            </div>

                            <h2 class="section-title mt-3">THYROID</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[thyroid][]"
                                        value="Perspire easily" id="hc_modal_thy_1" <?php echo hc_checked('thyroid', 'Perspire easily', $health_arr); ?>><label for="hc_modal_thy_1">Perspire
                                        easily</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[thyroid][]"
                                        value="Apprehension" id="hc_modal_thy_2" <?php echo hc_checked('thyroid', 'Apprehension', $health_arr); ?>><label
                                        for="hc_modal_thy_2">Apprehension</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[thyroid][]"
                                        value="Palpation/rapid heart beat" id="hc_modal_thy_3" <?php echo hc_checked('thyroid', 'Palpation/rapid heart beat', $health_arr); ?>><label
                                        for="hc_modal_thy_3">Palpation/rapid heart beat</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[thyroid][]"
                                        value="Goiter" id="hc_modal_thy_4" <?php echo hc_checked('thyroid', 'Goiter', $health_arr); ?>><label for="hc_modal_thy_4">Goiter</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[thyroid][]"
                                        value="Bulging of eyes" id="hc_modal_thy_5" <?php echo hc_checked('thyroid', 'Bulging of eyes', $health_arr); ?>><label for="hc_modal_thy_5">Bulging of
                                        eyes</label></div>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <h2 class="section-title">URINARY</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[urinary][]"
                                        value="Increase frequency of urination" id="hc_modal_ur_1" <?php echo hc_checked('urinary', 'Increase frequency of urination', $health_arr); ?>><label
                                        for="hc_modal_ur_1">Increase frequency of urination</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[urinary][]"
                                        value="Burning sensation on urination" id="hc_modal_ur_2" <?php echo hc_checked('urinary', 'Burning sensation on urination', $health_arr); ?>><label
                                        for="hc_modal_ur_2">Burning sensation on urination</label></div>
                            </div>

                            <h2 class="section-title mt-3">NERVOUS SYSTEM</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[nervous][]"
                                        value="Headache" id="hc_modal_nerv_1" <?php echo hc_checked('nervous', 'Headache', $health_arr); ?>><label for="hc_modal_nerv_1">Headache</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[nervous][]"
                                        value="Convulsion/epilepsy" id="hc_modal_nerv_2" <?php echo hc_checked('nervous', 'Convulsion/epilepsy', $health_arr); ?>><label
                                        for="hc_modal_nerv_2">Convulsion/epilepsy</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[nervous][]"
                                        value="Numbness/Tingling" id="hc_modal_nerv_3" <?php echo hc_checked('nervous', 'Numbness/Tingling', $health_arr); ?>><label
                                        for="hc_modal_nerv_3">Numbness/Tingling</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[nervous][]"
                                        value="Dizziness/Fainting" id="hc_modal_nerv_4" <?php echo hc_checked('nervous', 'Dizziness/Fainting', $health_arr); ?>><label
                                        for="hc_modal_nerv_4">Dizziness/Fainting</label></div>
                            </div>

                            <h2 class="section-title mt-3">BLOOD</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[blood][]"
                                        value="Bruise easily" id="hc_modal_blood_1" <?php echo hc_checked('blood', 'Bruise easily', $health_arr); ?>><label for="hc_modal_blood_1">Bruise
                                        easily</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[blood][]"
                                        value="Anemia" id="hc_modal_blood_2" <?php echo hc_checked('blood', 'Anemia', $health_arr); ?>><label for="hc_modal_blood_2">Anemia</label></div>
                            </div>

                            <h2 class="section-title mt-3">RESPIRATORY</h2>
                            <div class="options">
                                <div class="option"><input type="checkbox" name="health_conditions[respiratory][]"
                                        value="Persistent cough" id="hc_modal_resp_1" <?php echo hc_checked('respiratory', 'Persistent cough', $health_arr); ?>><label
                                        for="hc_modal_resp_1">Persistent cough</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[respiratory][]"
                                        value="Difficulty in breathing" id="hc_modal_resp_2" <?php echo hc_checked('respiratory', 'Difficulty in breathing', $health_arr); ?>><label
                                        for="hc_modal_resp_2">Difficulty in breathing</label></div>
                                <div class="option"><input type="checkbox" name="health_conditions[respiratory][]"
                                        value="Asthma" id="hc_modal_resp_3" <?php echo hc_checked('respiratory', 'Asthma', $health_arr); ?>><label for="hc_modal_resp_3">Asthma</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"
                        style="padding: 15px 0 0 0; border-top: 1px solid #e5e5e5; margin-top: 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_medical_history" class="btn btn-primary">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script> <!-- For Bootstrap 4 components in template -->
    <script src="../js/jquery-1.11.0.min.js"></script> <!-- For Bootstrap 3 components, if needed -->
    <script src="../js/bootstrap.js"></script> <!-- For Bootstrap 3 components -->
    <style>
        /* Add some basic styling for form-check from Bootstrap 4 since it's not in the main CSS */
        .form-check {
            position: relative;
            display: block;
            padding-left: 1.25rem;
        }

        .form-check-input {
            position: absolute;
            margin-top: 0.3rem;
            margin-left: -1.25rem;
        }

        .form-check-label {
            margin-bottom: 0;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .modal-body .row {
            margin-left: -15px;
            margin-right: -15px;
        }

        .modal-body .col-md-6 {
            padding-left: 15px;
            padding-right: 15px;
        }

        .modal-body .form-group {
            margin-bottom: 1rem;
        }

        .modal-body .form-control {
            width: 100%;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }

        .modal-body label {
            display: inline-block;
            margin-bottom: .5rem;
        }
    </style>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const tabToOpen = urlParams.get('tab');
            const tabsContainer = document.getElementById('profileTabs');
            if (!tabsContainer) return;

            const tabs = tabsContainer.querySelectorAll('.tab');
            const contentPanes = document.querySelectorAll('.tab-content-container > .tab-pane');

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const targetId = this.getAttribute('data-tab-target');
                    contentPanes.forEach(pane => {
                        pane.style.display = (pane.id === targetId.substring(1)) ? 'block' : 'none';
                    });
                });
            });

            // Open a specific tab if requested in URL
            if (tabToOpen) {
                const tab = document.querySelector(`.tab[data-tab-target="#${tabToOpen}Content"]`);
                if (tab) tab.click();
            }

            // Chat button
            const chatButton = document.querySelector('.chat-button');
            if (chatButton) {
                chatButton.addEventListener('click', function () {
                    alert('Opening chat support...');
                });
            }

            // Logic for the booking modal's time slots
            const dateInputModal = document.getElementById('appointment_date_modal');
            const timeSelectModal = document.getElementById('appointment_time_modal');

            function populateTimes(date) {
                if (!timeSelectModal) return;
                timeSelectModal.innerHTML = '<option value="">-- Loading times --</option>';
                if (!date) {
                    timeSelectModal.innerHTML = '<option value="">-- Select a date first --</option>';
                    return;
                }

                const url = `profile.php?get_calendar_times=1&date=${encodeURIComponent(date)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        timeSelectModal.innerHTML = '<option value="">-- Select a time --</option>';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.start;
                                option.textContent = item.label;
                                timeSelectModal.appendChild(option);
                            });
                        } else {
                            timeSelectModal.innerHTML = '<option value="">-- No available times --</option>';
                        }
                    })
                    .catch(err => {
                        timeSelectModal.innerHTML = '<option value="">-- Error loading times --</option>';
                    });
            }

            if (dateInputModal) {
                dateInputModal.addEventListener('change', function () {
                    populateTimes(this.value);
                });
            }

        });
    </script>
    <script src="js/profile-medical-modal.js"></script>
    <script src="js/profile-edit-modal.js"></script>
    <script src="js/profile-booking-modal.js"></script>

</body>

</html>