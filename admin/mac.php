<?php
session_start();
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

include('includes/dbconnection.php');

/**
 * Renders a professional HTML email template for appointment status updates.
 *
 * @param string $patientName The name of the patient.
 * @param string $appointmentDate The formatted date of the appointment.
 * @param string $status The new status (e.g., 'Approved', 'Cancelled').
 * @param string|null $reason The reason for cancellation, if any.
 * @return string The full HTML body of the email.
 */
function getAppointmentEmailBody(string $patientName, string $appointmentDate, string $status, ?string $reason = null): string
{
    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
            <h2 style="margin: 0; color: #092c7a;">JF Dental Care - Appointment Update</h2>
        </div>
        <div style="padding: 20px 0; text-align: left;">
            <p>Dear <?php echo htmlspecialchars($patientName); ?>,</p>
            <p>This is to inform you that your consultation appointment on <strong><?php echo htmlspecialchars($appointmentDate); ?></strong> has been <strong><?php echo htmlspecialchars(strtolower($status)); ?></strong>.</p>
            <?php if (!empty($reason)): ?><p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p><?php endif; ?>
            <p>If you have any questions, please feel free to contact us.</p>
        </div>
        <div style="text-align: center; font-size: 12px; color: #777; padding-top: 20px; border-top: 1px solid #ddd;">
            <p>Thank you,<br>The JF Dental Care Team</p>
            <p>&copy; <?php echo date("Y"); ?> JF Dental Care. All rights reserved.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Handle appointment update from edit modal
    if (isset($_POST['update_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $status = $_POST['status'];

        // Fetch appointment details before updating for notification purposes
        $sql_fetch_appt = "SELECT a.patient_number, a.date, p.email, p.firstname, p.surname FROM tblappointment a JOIN tblpatient p ON a.patient_number = p.number WHERE a.id = :id";
        $query_fetch_appt = $dbh->prepare($sql_fetch_appt);
        $query_fetch_appt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
        $query_fetch_appt->execute();
        $appointment_data = $query_fetch_appt->fetch(PDO::FETCH_ASSOC);

        if ($appointment_data) {
            $sql_update = "UPDATE tblappointment SET status = :status WHERE id = :id";
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':status', $status, PDO::PARAM_STR);
            $query_update->bindParam(':id', $appointment_id, PDO::PARAM_INT);

            if ($query_update->execute()) {
                // If status is Approved or Declined, send notification to the patient
                if ($status == 'Approved' || $status == 'Declined') {
                    $patient_id = $appointment_data['patient_number'];
                    $appointment_date = date('F j, Y', strtotime($appointment_data['date']));
                    $message = "Your consultation on " . $appointment_date . " has been " . strtolower($status) . ".";
                    $url = "profile.php?tab=appointments";

                    $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:recipient_id, 'patient', :message, :url)";
                    $query_notif = $dbh->prepare($sql_notif);
                    $query_notif->execute([':recipient_id' => $patient_id, ':message' => $message, ':url' => $url]);

                    // Send email notification
                    if (!empty($appointment_data['email'])) {
                        $mail = new PHPMailer(true);
                        try {
                            //Server settings
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'jezrahconde@gmail.com'; // Your Gmail address
                            $mail->Password   = 'gzht tvxy vxzx awrt'; // Your Gmail App Password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->SMTPOptions = array(
                                'ssl' => array(
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                )
                            );

                            //Recipients
                            $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                            $mail->addAddress($appointment_data['email'], $appointment_data['firstname'] . ' ' . $appointment_data['surname']);

                            //Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Update on your Consultation Appointment';
                            
                            $patientFullName = htmlentities($appointment_data['firstname'] . ' ' . $appointment_data['surname']);
                            $mail->Body = getAppointmentEmailBody($patientFullName, $appointment_date, $status);

                            // Plain text version for non-HTML email clients
                            $altBody = "Dear " . $patientFullName . ",\n\n";
                            $altBody .= "This is to inform you that your consultation appointment scheduled for " . $appointment_date . " has been " . strtolower($status) . ".\n\n";
                            $altBody .= "Thank you,\nJF Dental Care";
                            $mail->AltBody = $altBody;
                            $mail->send();
                        } catch (Exception $e) {
                            // Optional: Log mail error
                        }
                    }
                }
                $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment status updated successfully.'];
            } else {
                $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the status.'];
            }
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

        // Fetch appointment details for notification
        $sql_fetch_appt = "SELECT a.patient_number, a.date, p.email, p.firstname, p.surname FROM tblappointment a JOIN tblpatient p ON a.patient_number = p.number WHERE a.id = :id";
        $query_fetch_appt = $dbh->prepare($sql_fetch_appt);
        $query_fetch_appt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
        $query_fetch_appt->execute();
        $appointment_data = $query_fetch_appt->fetch(PDO::FETCH_ASSOC);

        $sql_cancel = "UPDATE tblappointment SET status = :status, cancel_reason = :cancel_reason WHERE id = :id";
        $query_cancel = $dbh->prepare($sql_cancel);
        $query_cancel->bindParam(':status', $status, PDO::PARAM_STR);
        $query_cancel->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
        $query_cancel->bindParam(':id', $appointment_id, PDO::PARAM_INT);

        if ($query_cancel->execute()) { 
            // Insert notification for patient
            if ($appointment_data) {
                $patient_id = $appointment_data['patient_number'];
                $appointment_date = date('F j, Y', strtotime($appointment_data['date']));
                $message = "Your consultation on " . $appointment_date . " has been cancelled by the admin.";
                if (!empty($cancel_reason)) { $message .= " Reason: " . $cancel_reason; }
                $url = "profile.php?tab=appointments";
                $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:recipient_id, 'patient', :message, :url)";
                $dbh->prepare($sql_notif)->execute([':recipient_id' => $patient_id, ':message' => $message, ':url' => $url]);
            }
            if ($appointment_data && !empty($appointment_data['email'])) {
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'jezrahconde@gmail.com'; // Your Gmail address
                    $mail->Password   = 'gzht tvxy vxzx awrt'; // Your Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    //Recipients
                    $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                    $mail->addAddress($appointment_data['email'], $appointment_data['firstname'] . ' ' . $appointment_data['surname']);

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Consultation Appointment has been Cancelled';
                    
                    $patientFullName = htmlentities($appointment_data['firstname'] . ' ' . $appointment_data['surname']);
                    $appointmentDate = date('F j, Y', strtotime($appointment_data['date']));
                    $mail->Body = getAppointmentEmailBody($patientFullName, $appointmentDate, 'Cancelled', $cancel_reason);

                    $altBody = "Dear " . $patientFullName . ",\n\nThis is to inform you that your consultation appointment on " . $appointmentDate . " has been cancelled by the admin.";
                    if (!empty($cancel_reason)) { $altBody .= "\nReason: " . htmlentities($cancel_reason); }
                    $altBody .= "\n\nThank you,\nJF Dental Care";
                    $mail->AltBody = $altBody;
                    $mail->send();
                } catch (Exception $e) { /* Optional: Log mail error */ }
            }
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

            // Insert notification for admin
            $admin_id = 1; // Assuming admin ID is 1
            $notif_message = "A new walk-in appointment was created for " . htmlentities($firstname . ' ' . $surname) . ".";
            $notif_url = "mac.php?filter=today";
            $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
            $query_notif = $dbh->prepare($sql_notif);
            $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);

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

    // Initialize search and filter variables
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

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
    $params = [];

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

    if (!empty($search)) {
        $where_clauses[] = "(firstname LIKE :search OR surname LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($where_clauses)) {
        $sql_appointments .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql_appointments .= " ORDER BY date DESC, start_time DESC";
    $query_appointments = $dbh->prepare($sql_appointments);
    if (!empty($params)) {
        $query_appointments->execute($params);
    } else {
        $query_appointments->execute();
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mas-modal.css">
    <link rel="stylesheet" href="css/toast.css">
    <link rel="stylesheet" href="css/responsive.css">
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
                                <form method="GET" class="search-form" style="flex-grow: 1;">
                                <div class="search-box"> 
                                    <input type="hidden" name="filter" value="<?php echo htmlentities($filter); ?>">
                                        <input type="text" name="search_query" placeholder="Search by name..." value="<?php echo htmlentities($search); ?>" aria-label="Search appointments">
                                </div>
                                </form> 
                                <div class="filter-dropdown">
                                    <button class="filter-dropdown-toggle" id="filterDropdownToggle">
                                        <i class="fas fa-sort-amount-down-alt"></i>
                                        <span>Sort</span>
                                    </button>
                                    <div class="filter-buttons" id="filterButtons">
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
                                        <th>Reason</th>
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
                                                <td class="cancel-reason-cell">
                                                    <?php if (!empty($appointment->cancel_reason)): ?>
                                                    <button class="view-reason-btn" title="View Reason"
                                                        data-reason="<?php echo htmlentities($appointment->cancel_reason); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <span style="color: #ccc;">-</span>
                                                    <?php endif; ?>
                                                </td>
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
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">
                                                        <i class="fas fa-edit" style="color: #007bffe3;"></i>
                                                    </button>
                                                    <button class="cancel-appointment-admin-btn" title="Cancel"
                                                        data-id="<?php echo htmlentities($appointment->id); ?>"
                                                        data-firstname="<?php echo htmlentities($appointment->firstname); ?>"
                                                        data-surname="<?php echo htmlentities($appointment->surname); ?>"
                                                        data-date="<?php echo htmlentities($appointment->date); ?>"
                                                        data-start-time="<?php echo htmlentities($appointment->start_time); ?>"
                                                        data-end-time="<?php echo htmlentities($appointment->end_time); ?>"
                                                        data-cancel-reason="<?php echo htmlentities($appointment->cancel_reason); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0 8px;">
                                                        <i class="fas fa-times-circle" style="color:#ff000078"></i>
                                                    </button>
                                                    <a href="mac.php?delid=<?php echo $appointment->id; ?>" title="Delete"
                                                        onclick="return confirm('Do you really want to Delete ?');"
                                                        style="font-size: 1.25rem; color: #a0aec0; text-decoration: none;">
                                                        <i class="fas fa-trash-alt" style="color: red;"></i>
                                                    </a>
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

    <!-- View Cancellation Reason Modal -->
    <div id="viewReasonModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cancellation Reason</h2>
                <button class="close-button">&times;</button>
            </div>
            <div class="modal-body">
                <p id="reasonText" style="font-size: 16px; line-height: 1.6; color: #333;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Close</button>
            </div>
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

            // --- View Cancellation Reason Modal ---
            const viewReasonModal = document.getElementById('viewReasonModal');
            if (viewReasonModal) {
                const viewReasonCloseBtn = viewReasonModal.querySelector('.close-button');
                const viewReasonCancelBtn = viewReasonModal.querySelector('.btn-cancel');

                function closeViewReasonModal() {
                    viewReasonModal.style.display = 'none';
                }

                viewReasonCloseBtn.addEventListener('click', closeViewReasonModal);
                viewReasonCancelBtn.addEventListener('click', closeViewReasonModal);

                window.addEventListener('click', function (event) {
                    if (event.target === viewReasonModal) {
                        closeViewReasonModal();
                    }
                });

                document.querySelectorAll('.view-reason-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const reason = this.dataset.reason;
                        document.getElementById('reasonText').textContent = reason;
                        viewReasonModal.style.display = 'flex';
                    });
                });
            }
        });

        // --- Edit Appointment Modal ---
        const editModal = document.getElementById('editAppointmentModal');
        if (editModal) {
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

            document.querySelectorAll('.edit-appointment-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const dataset = this.dataset;
                    document.getElementById('edit_appointment_id').value = dataset.id;
                    document.getElementById('edit_firstname').value = dataset.firstname;
                    document.getElementById('edit_surname').value = dataset.surname;
                    document.getElementById('edit_date').value = dataset.date;
                    document.getElementById('edit_start_time').value = dataset.startTime;
                    document.getElementById('edit_end_time').value = dataset.endTime;
                    document.getElementById('edit_status').value = dataset.status;
                    editModal.style.display = 'flex';
                });
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownToggle = document.getElementById('filterDropdownToggle');
            const filterButtons = document.getElementById('filterButtons');

            if (dropdownToggle && filterButtons) {
                dropdownToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    filterButtons.classList.toggle('show');
                });

                // Close the dropdown if the user clicks outside of it
                window.addEventListener('click', function (event) {
                    if (!dropdownToggle.contains(event.target) && !filterButtons.contains(event.target)) {
                        filterButtons.classList.remove('show');
                    }
                });

                // Close on escape key
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        filterButtons.classList.remove('show');
                    }
                });
            }
        });
    </script>
    <script src="js/mac-modal.js"></script>

</body>

</html>
<?php ?>