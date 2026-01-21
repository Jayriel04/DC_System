<?php
session_start();
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    
    if (isset($_GET['get_services_by_category']) && !empty($_GET['category_id'])) {
        header('Content-Type: application/json');
        $category_id = $_GET['category_id']; 
        $sql = "SELECT number, name FROM tblservice WHERE category_id = :category_id ORDER BY name ASC"; // Filter by category_id
        $query = $dbh->prepare($sql);
        $query->bindParam(':category_id', $category_id, PDO::PARAM_INT); // Bind as INT
        $query->execute();
        $services = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($services);
        exit(); // Stop further execution
    }


    if (isset($_GET['get_month_availability']) && !empty($_GET['year']) && !empty($_GET['month'])) {
        header('Content-Type: application/json');
        $year = intval($_GET['year']);
        $month = intval($_GET['month']);

        // Get all dates in the month
        $firstDay = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
        $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

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

    // Schedule a new service for an existing patient
    if (isset($_POST['schedule_service_for_patient'])) {
        $patient_id = $_POST['patient_id'];
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];

        $dbh->beginTransaction();

        try {
            $service_id = $_POST['service_id'];
            $app_date = $_POST['app_date'];
            $start_time = $_POST['start_time'];
            $duration = $_POST['duration'];

            $alert_message = 'No service details provided.';
            $alert_type = 'warning';

            if (!empty($service_id) && !empty($app_date) && !empty($start_time)) {
                $app_status = 'Ongoing'; 

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
                $alert_type = 'success';

                // --- Start Notification Logic ---
                // 1. Get patient email and service name
                $sql_details = "SELECT p.email, s.name as service_name 
                                FROM tblpatient p, tblservice s 
                                WHERE p.number = :pnum AND s.number = :snum";
                $query_details = $dbh->prepare($sql_details);
                $query_details->execute([':pnum' => $patient_id, ':snum' => $service_id]);
                $details = $query_details->fetch(PDO::FETCH_ASSOC);

                if ($details) {
                    $patient_email = $details['email'];
                    $service_name = $details['service_name'];
                    $schedule_date_formatted = date('F j, Y', strtotime($app_date));
                    $schedule_time_formatted = date('g:i A', strtotime($start_time));

                    // 2. Insert in-app notification for the patient
                    $notif_message = "A new service appointment for " . htmlentities($service_name) . " has been scheduled for you on " . $schedule_date_formatted . " at " . $schedule_time_formatted . ".";
                    $notif_url = "profile.php?tab=appointments";
                    $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'patient', :msg, :url)";
                    $query_notif = $dbh->prepare($sql_notif);
                    $query_notif->execute([':rid' => $patient_id, ':msg' => $notif_message, ':url' => $notif_url]);

                    // 3. Send email notification
                    if (!empty($patient_email)) {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'canoniokevin@gmail.com'; 
                            $mail->Password   = 'qfkr wesz vhkm tydc'; 
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                            $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                            $mail->addAddress($patient_email, $firstname . ' ' . $surname);

                            $mail->isHTML(true);
                            $mail->Subject = 'New Service Appointment Scheduled';
                            $mail->Body    = "Dear " . htmlentities($firstname) . ",<br><br>A new service appointment for <strong>" . htmlentities($service_name) . "</strong> has been scheduled for you on <strong>" . $schedule_date_formatted . " at " . $schedule_time_formatted . "</strong>.<br><br>Thank you,<br>JF Dental Care";
                            $mail->AltBody = "Dear " . htmlentities($firstname) . ",\n\nA new service appointment for " . htmlentities($service_name) . " has been scheduled for you on " . $schedule_date_formatted . " at " . $schedule_time_formatted . ".\n\nThank you,\nJF Dental Care";

                            $mail->send();
                        } catch (Exception $e) {
                            // Email sending failed, but we don't stop the process. Log it if needed.
                        }
                    }
                }
                // --- End Notification Logic ---
            }
            $dbh->commit();
            $_SESSION['toast_message'] = ['type' => $alert_type, 'message' => $alert_message];
            header('Location: manage-patient.php');
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . addslashes($e->getMessage())];
            header('Location: manage-patient.php');
        }
        exit();
    }
    
    if (isset($_POST['add_patient'])) {
        $dbh->beginTransaction();
        try {
            //  Create new patient
            $firstname = ucfirst(trim($_POST['firstname']));
            $surname = ucfirst(trim($_POST['surname']));
            $dob = $_POST['date_of_birth'];
            $sex = $_POST['sex'];
            $civil_status = $_POST['civil_status'];
            $occupation = ucfirst(trim($_POST['occupation']));
            $contact_number = $_POST['contact_number'];
            $address = ucfirst(trim($_POST['address']));
            $email = $_POST['email'];
            $password = md5('password'); 

            
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

            //  Check for and create new appointment if details are provided
            $app_date = $_POST['app_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];

            if (!empty($app_date) && !empty($start_time)) {
                $app_status = 'walkin'; 

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

            // --- Start Notification Logic (for new patient with appointment) ---
            if (!empty($app_date) && !empty($start_time) && !empty($email)) {
                // 1. Get service name
                $sql_service = "SELECT name FROM tblservice WHERE number = :snum";
                $query_service = $dbh->prepare($sql_service);
                $query_service->execute([':snum' => $service_id ?? 0]); // Assuming service_id might be set if appointment is added
                $service_info = $query_service->fetch(PDO::FETCH_ASSOC);
                $service_name = $service_info ? $service_info['name'] : 'the scheduled service';

                $schedule_date_formatted = date('F j, Y', strtotime($app_date));
                $schedule_time_formatted = date('g:i A', strtotime($start_time));

                // 2. Insert in-app notification for the patient
                $notif_message = "A new service appointment for " . htmlentities($service_name) . " has been scheduled for you on " . $schedule_date_formatted . " at " . $schedule_time_formatted . ".";
                $notif_url = "profile.php?tab=appointments";
                $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'patient', :msg, :url)";
                $query_notif = $dbh->prepare($sql_notif);
                $query_notif->execute([':rid' => $patient_id, ':msg' => $notif_message, ':url' => $notif_url]);

                // 3. Send email notification
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'canoniokevin@gmail.com'; 
                    $mail->Password   = 'qfkr wesz vhkm tydc'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                    $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                    $mail->addAddress($email, $firstname . ' ' . $surname);

                    $mail->isHTML(true);
                    $mail->Subject = 'New Service Appointment Scheduled';
                    $mail->Body    = "Dear " . htmlentities($firstname) . ",<br><br>A new service appointment for <strong>" . htmlentities($service_name) . "</strong> has been scheduled for you on <strong>" . $schedule_date_formatted . " at " . $schedule_time_formatted . "</strong>.<br><br>Thank you,<br>JF Dental Care";
                    $mail->AltBody = "Dear " . htmlentities($firstname) . ",\n\nA new service appointment for " . htmlentities($service_name) . " has been scheduled for you on " . $schedule_date_formatted . " at " . $schedule_time_formatted . ".\n\nThank you,\nJF Dental Care";

                    $mail->send();
                } catch (Exception $e) {
                    // Email sending failed, but we don't stop the process. Log it if needed.
                }
            }
            // --- End Notification Logic ---
            
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

    
    $search = '';

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_query'])) {
        $search = trim($_POST['search_query']); // Search is triggered on form submit
    }

    // Code for deletion 
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
    <link rel="stylesheet" href="css/stylev2.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
     <link rel="stylesheet" href="css/sidebar.css">
     <link rel="stylesheet" href="css/mas-modal.css">   
    <link rel="stylesheet" href="css/calendar-avail.css">
    <link rel="stylesheet" href="css/toast.css">
    <link rel="stylesheet" href="css/admin-calendar-availability.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
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
                        <button class="btn-add" id="addPatientBtn">
                                <i class="fas fa-user"></i>
                                Add New Patient
                        </button>
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
                        
                        $pageno = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
                        $no_of_records_per_page = 5;
                        $offset = ($pageno - 1) * $no_of_records_per_page;

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
                                            <?php
                                            $avatar_image = 'avatar.png'; 
                                            if (!empty($row->Image)) {
                                                $avatar_image = $row->Image;
                                            } elseif ($row->sex === 'Male') {
                                                $avatar_image = 'man-icon.png';
                                            } elseif ($row->sex === 'Female') {
                                                $avatar_image = 'woman-icon.jpg';
                                            }
                                            ?>
                                            <div class="avatar">
                                                <img src="../admin/images/<?php echo htmlentities($avatar_image); ?>" alt="Avatar">
                                            </div>
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
                                            <button class="action-icon edit-patient-btn" title="Add service appointment"
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
                                            ><i class="fas fa-calendar-plus" style="color:#007bffe3 ;"></i></button>
                                            <a href="manage-patient.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete?');" class="action-icon" title="Delete"><i class="fas fa-trash-alt" style="color:red;"></i></a>
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
                        <div align="left" class="mt-4">
                            <?php
                            $query_params = [];
                            if ($search) {
                                $query_params['search_query'] = $search;
                            }
                            ?>
                            <ul class="pagination">
                                <li class="<?php if ($pageno <= 1) { echo 'disabled'; } ?>">
                                    <a
                                        href="<?php if ($pageno <= 1) { echo '#'; } else { echo "?pageno=" . ($pageno - 1) . (!empty($query_params) ? '&' . http_build_query($query_params) : ''); } ?>">Prev</a>
                                </li>
                                <?php
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    if ($i == $pageno) {
                                        echo '<li class="active"><a href="#">' . $i . '</a></li>';
                                    } else {
                                        echo '<li><a href="?pageno=' . $i . (!empty($query_params) ? '&' . http_build_query($query_params) : '') . '">' . $i . '</a></li>';
                                    }
                                }
                                ?>
                                <li class="<?php if ($pageno >= $total_pages) { echo 'disabled'; } ?>">
                                    <a
                                        href="<?php if ($pageno >= $total_pages) { echo '#'; } else { echo "?pageno=" . ($pageno + 1) . (!empty($query_params) ? '&' . http_build_query($query_params) : ''); } ?>">Next</a>
                                </li>
                            </ul>
                        </div>
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
                    <button type="submit" name="add_patient" class="btn btn-schedule" style=" background-color: #008779 !important; color: white;">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Patient Modal with service appointment schedule-->
    <div id="editPatientModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Service Schedule</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editPatientForm" method="POST">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <div class="modal-body">
                    <h3 class="form-section-title">Patient: <span id="patient_name_for_service"></span></h3>
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
                         <div class="form-group admin-calendar-container">
                            <label for="edit_appointment_date">Date</label>
                            <input type="text" id="edit_appointment_date" name="app_date" placeholder="Click to select date" readonly style="cursor: pointer; background-color: #f9f9f9;">
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
                    <button type="submit" name="schedule_service_for_patient" class="btn btn-schedule" style=" background-color: #008779 !important; color: white;">Schedule Service</button>
                </div>
            </form>
        </div>
        <?php include_once('includes/footer.php'); ?>
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
                document.getElementById('edit_surname').value = dataset.surname; // Keep for form submission
                document.getElementById('patient_name_for_service').textContent = dataset.firstname + ' ' + dataset.surname;

                document.getElementById('edit_service_id').value = '';
                document.getElementById('edit_appointment_date').value = '';
                document.getElementById('edit_start_time').value = '';
                document.getElementById('edit_duration').value = '';
                document.getElementById('edit_service_category').value = '';
                document.getElementById('edit_service_id').innerHTML = '<option value="">Select a category first</option>';
                document.getElementById('edit_service_id').disabled = true;

                editModal.style.display = 'flex';
            });
        });

        // Add hidden inputs for firstname and surname to the edit form
        const editForm = document.getElementById('editPatientForm');
        const hiddenFname = document.createElement('input');
        hiddenFname.type = 'hidden';
        hiddenFname.name = 'firstname';
        hiddenFname.id = 'edit_firstname';
        const hiddenSname = document.createElement('input');
        hiddenSname.type = 'hidden';
        hiddenSname.name = 'surname';
        hiddenSname.id = 'edit_surname';
        editForm.prepend(hiddenSname);
        editForm.prepend(hiddenFname);

        
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