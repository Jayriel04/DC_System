<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

/**
 * Renders a professional HTML email template for service appointment status updates.
 *
 * @param string $patientName The name of the patient.
 * @param string $appointmentDate The formatted date of the appointment.
 * @param string $serviceName The name of the service.
 * @param string $status The new status (e.g., 'Done', 'Cancelled').
 * @param string|null $reason The reason for cancellation, if any.
 * @return string The full HTML body of the email.
 */
function getServiceAppointmentEmailBody(string $patientName, string $appointmentDate, string $serviceName, string $status, ?string $reason = null): string
{
    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
            <h2 style="margin: 0; color: #092c7a;">JF Dental Care - Service Appointment Update</h2>
        </div>
        <div style="padding: 20px 0; text-align: left;">
            <p>Dear <?php echo htmlspecialchars($patientName); ?>,</p>
            <p>This is to inform you that your service appointment for <strong><?php echo htmlspecialchars($serviceName); ?></strong> on <strong><?php echo htmlspecialchars($appointmentDate); ?></strong> has been marked as <strong><?php echo htmlspecialchars(strtolower($status)); ?></strong>.</p>
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
    // Handle schedule update from edit modal
    if (isset($_POST['update_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        $status = $_POST['status'];
        $cancel_reason = ($status === 'Cancelled') ? $_POST['cancel_reason'] : NULL;
        
        // Fetch schedule details before updating for notification purposes
        $sql_fetch_schedule = "SELECT s.patient_number, s.date, svc.name as service_name, p.email, p.firstname, p.surname 
                               FROM tblschedule s 
                               LEFT JOIN tblservice svc ON s.service_id = svc.number 
                               LEFT JOIN tblpatient p ON s.patient_number = p.number
                               WHERE s.id = :id";
        $query_fetch_schedule = $dbh->prepare($sql_fetch_schedule);
        $query_fetch_schedule->bindParam(':id', $schedule_id, PDO::PARAM_INT);
        $query_fetch_schedule->execute();
        $schedule_data = $query_fetch_schedule->fetch(PDO::FETCH_ASSOC);

        if ($schedule_data) {
            $sql_update = "UPDATE tblschedule SET status = :status, cancel_reason = :cancel_reason WHERE id = :id";
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':status', $status, PDO::PARAM_STR);
            $query_update->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
            $query_update->bindParam(':id', $schedule_id, PDO::PARAM_INT);

            if ($query_update->execute()) {
                // If status is Done or Cancelled, send a notification to the patient
                if ($status == 'Done' || $status == 'Cancelled') {
                    $patient_id = $schedule_data['patient_number'];
                    $service_name = $schedule_data['service_name'] ?: 'your service';
                    $schedule_date = date('F j, Y', strtotime($schedule_data['date']));
                    $message = "Your appointment for " . $service_name . " on " . $schedule_date . " has been marked as " . strtolower($status) . ".";
                    $url = "profile.php?tab=appointments";

                    $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:recipient_id, 'patient', :message, :url)";
                    $query_notif = $dbh->prepare($sql_notif);
                    $query_notif->execute([':recipient_id' => $patient_id, ':message' => $message, ':url' => $url]);

                    // Send email notification
                    if (!empty($schedule_data['email'])) {
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
                            $mail->addAddress($schedule_data['email'], $schedule_data['firstname'] . ' ' . $schedule_data['surname']);

                            //Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Update on your Service Appointment';
                            
                            $patientFullName = htmlentities($schedule_data['firstname'] . ' ' . $schedule_data['surname']);
                            $mail->Body = getServiceAppointmentEmailBody($patientFullName, $schedule_date, $service_name, $status, $cancel_reason);

                            $altBody = "Dear " . $patientFullName . ",\n\nThis is to inform you that your service appointment for " . htmlentities($service_name) . " on " . $schedule_date . " has been marked as " . strtolower($status) . ".";
                            if ($status === 'Cancelled' && !empty($cancel_reason)) { $altBody .= "\nReason: " . htmlentities($cancel_reason); }
                            $altBody .= "\n\nThank you,\nJF Dental Care";
                            $mail->AltBody = $altBody;
                            $mail->send();
                        } catch (Exception $e) {
                            // Optional: Log mail error
                        }
                    }
                }
                $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Schedule status updated successfully.'];
            } else {
                $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the status.'];
            }
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the status.'];
        }
        header('Location: mas.php');
        exit();
    }

    // Handle service schedule cancellation from admin modal
    if (isset($_POST['confirm_cancel_service'])) {
        $schedule_id = $_POST['schedule_id'];
        $cancel_reason = $_POST['cancel_reason'];
        $status = 'Cancelled'; // Set status to Cancelled

        // Fetch schedule details for notification
        $sql_fetch_schedule = "SELECT s.patient_number, s.date, svc.name as service_name, p.email, p.firstname, p.surname FROM tblschedule s LEFT JOIN tblservice svc ON s.service_id = svc.number LEFT JOIN tblpatient p ON s.patient_number = p.number WHERE s.id = :id";
        $query_fetch_schedule = $dbh->prepare($sql_fetch_schedule);
        $query_fetch_schedule->bindParam(':id', $schedule_id, PDO::PARAM_INT);
        $query_fetch_schedule->execute();
        $schedule_data = $query_fetch_schedule->fetch(PDO::FETCH_ASSOC);

        $sql_cancel = "UPDATE tblschedule SET status = :status, cancel_reason = :cancel_reason WHERE id = :id";
        $query_cancel = $dbh->prepare($sql_cancel);
        $query_cancel->bindParam(':status', $status, PDO::PARAM_STR);
        $query_cancel->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
        $query_cancel->bindParam(':id', $schedule_id, PDO::PARAM_INT);

        if ($query_cancel->execute()) {
            // Send email notification to patient
            if ($schedule_data && !empty($schedule_data['email'])) {
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
                    $mail->addAddress($schedule_data['email'], $schedule_data['firstname'] . ' ' . $schedule_data['surname']);

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Service Appointment has been Cancelled';
                    
                    $patientFullName = htmlentities($schedule_data['firstname'] . ' ' . $schedule_data['surname']);
                    $appointmentDate = date('F j, Y', strtotime($schedule_data['date']));
                    $serviceName = htmlentities($schedule_data['service_name']);
                    $mail->Body = getServiceAppointmentEmailBody($patientFullName, $appointmentDate, $serviceName, 'Cancelled', $cancel_reason);

                    $altBody = "Dear " . $patientFullName . ",\n\nThis is to inform you that your service appointment for " . $serviceName . " on " . $appointmentDate . " has been cancelled by the admin.";
                    if (!empty($cancel_reason)) { $altBody .= "\nReason: " . htmlentities($cancel_reason); }
                    $altBody .= "\n\nThank you,\nJF Dental Care";
                    $mail->AltBody = $altBody;
                    $mail->send();
                } catch (Exception $e) { /* Optional: Log mail error */ }
            }
            // Insert notification for admin
            $admin_id = 1; // Assuming admin ID is 1
            $patient_name = $_POST['patient_name_for_notif']; // Hidden input needed in the form
            $notif_message = "A service for " . htmlentities($patient_name) . " was cancelled by an admin.";
            $notif_url = "mas.php?filter=cancelled";
            $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
            $query_notif = $dbh->prepare($sql_notif);
            $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Service schedule cancelled successfully.'];
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while cancelling the service schedule.'];
        }
        header('Location: mas.php');
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
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'New patient and service appointment scheduled successfully.'];
            header('Location: mas.php');
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . $e->getMessage()];
            header('Location: mas.php');
        }
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblschedule WHERE id = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Service schedule deleted.'];
        header('Location: mas.php');
        exit();
    }

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
    // --- SERVICE APPOINTMENT COUNTS ---
    $sql_all = "SELECT COUNT(*) FROM tblschedule";
    $query_all = $dbh->prepare($sql_all);
    $query_all->execute();
    $count_all = $query_all->fetchColumn();

    $sql_today = "SELECT COUNT(*) FROM tblschedule WHERE date = CURDATE() AND status != 'Cancelled'";
    $query_today = $dbh->prepare($sql_today);
    $query_today->execute();
    $count_today = $query_today->fetchColumn();

    $sql_upcoming = "SELECT COUNT(*) FROM tblschedule WHERE date > CURDATE() AND status != 'Cancelled'";
    $query_upcoming = $dbh->prepare($sql_upcoming);
    $query_upcoming->execute();
    $count_upcoming = $query_upcoming->fetchColumn();

    $sql_pending = "SELECT COUNT(*) FROM tblschedule WHERE status = 'Ongoing' AND date >= CURDATE()"; // 'Ongoing' is like 'Pending' for services
    $query_pending = $dbh->prepare($sql_pending);
    $query_pending->execute();
    $count_pending = $query_pending->fetchColumn();

    $sql_completed = "SELECT COUNT(*) FROM tblschedule WHERE status = 'Done'";
    $query_completed = $dbh->prepare($sql_completed);
    $query_completed->execute();
    $count_completed = $query_completed->fetchColumn();

    $sql_cancelled = "SELECT COUNT(*) FROM tblschedule WHERE status = 'Cancelled'";
    $query_cancelled = $dbh->prepare($sql_cancelled);
    $query_cancelled->execute();
    $count_cancelled = $query_cancelled->fetchColumn();

    $sql_for_cancellation = "SELECT COUNT(*) FROM tblschedule WHERE status = 'For Cancellation'";
    $query_for_cancellation = $dbh->prepare($sql_for_cancellation);
    $query_for_cancellation->execute();
    $count_for_cancellation = $query_for_cancellation->fetchColumn();

    // --- SERVICE APPOINTMENT LIST ---
    $sql_schedules = "SELECT s.*, svc.name as service_name, cat.name as category_name FROM tblschedule s LEFT JOIN tblservice svc ON s.service_id = svc.number LEFT JOIN tblcategory cat ON svc.category_id = cat.id";
    $where_clauses = [];
    $params = [];

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
        case 'cancelled':
            $where_clauses[] = "s.status = 'Cancelled'";
            break;
        case 'for_cancellation':
            $where_clauses[] = "s.status = 'For Cancellation'";
            break;
    }

    if (!empty($search)) {
        $where_clauses[] = "(s.firstname LIKE :search OR s.surname LIKE :search OR svc.name LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($where_clauses)) {
        $sql_schedules .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql_schedules .= " ORDER BY s.date DESC, s.time DESC";
    $query_schedules = $dbh->prepare($sql_schedules);
    if (!empty($params)) {
        $query_schedules->execute($params);
    } else {
        $query_schedules->execute();
    }
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Appointments</title>
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
    <style>
        
    </style>
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
                                    <h1>Manage Service Appointments</h1>
                                    <p>Schedule and track patient appointments for services</p>
                                </div>
                                
                            </div>
                        <div class="appointment-management-card">
                            <div class="filter-section"> 
                                <form method="GET" class="search-form" style="flex-grow: 1;">
                                    <div class="search-box"> 
                                        <input type="hidden" name="filter" value="<?php echo htmlentities($filter); ?>">
                                        <input type="text" name="search_query" placeholder="Search by patient or service name..." value="<?php echo htmlentities($search); ?>" aria-label="Search appointments">
                                    </div>
                                </form>
                                <div class="filter-dropdown">
                                    <button class="filter-dropdown-toggle" id="filterDropdownToggle">
                                        <i class="fas fa-sort-amount-down-alt"></i>
                                        <span>Sort</span>
                                    </button>
                                    <div class="filter-buttons" id="filterButtons">
                                        <a href="mas.php?filter=all&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'all') echo 'active'; ?>" data-filter="all">
                                            All Appointments <span class="filter-count"><?php echo $count_all; ?></span>
                                        </a>
                                        <a href="mas.php?filter=today&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'today') echo 'active'; ?>" data-filter="today">
                                            Today <span class="filter-count"><?php echo $count_today; ?></span>
                                        </a>
                                        <a href="mas.php?filter=upcoming&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'upcoming') echo 'active'; ?>" data-filter="upcoming">
                                            Upcoming <span class="filter-count"><?php echo $count_upcoming; ?></span>
                                        </a>
                                        <a href="mas.php?filter=pending&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'pending') echo 'active'; ?>" data-filter="pending">
                                            Ongoing <span class="filter-count"><?php echo $count_pending; ?></span>
                                        </a>
                                        <a href="mas.php?filter=completed&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'completed') echo 'active'; ?>" data-filter="completed">
                                            Completed <span class="filter-count"><?php echo $count_completed; ?></span>
                                        </a>
                                        <a href="mas.php?filter=cancelled&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'cancelled') echo 'active'; ?>" data-filter="cancelled">
                                            Cancelled <span class="filter-count"><?php echo $count_cancelled; ?></span>
                                        </a>
                                        <a href="mas.php?filter=for_cancellation&search_query=<?php echo urlencode($search); ?>" class="filter-btn <?php if ($filter === 'for_cancellation') echo 'active'; ?>" data-filter="for_cancellation">
                                            Request Cancellation <span class="filter-count"><?php echo $count_for_cancellation; ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        <hr style="border: 0; border-top: 1px solid #ccc; margin: 30px 0;">

                        <div class="patient-list-card" id="appointment-table-container">
                            <h2 class="section-title">Service Appointments (<?php echo count($schedules); ?>)</h2>
                            <table class="patient-table" style="overflow-x: auto;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>First Name</th>
                                        <th>Surname</th>
                                        <th style="width: 150px;">Category</th>
                                        <th style="width: 315px;">Service</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th style="width: 75px;">Reason</th>
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
                                                <td style="width: 150px;"><?php echo htmlentities($schedule->category_name ?: 'N/A'); ?></td>
                                                <td style="width: 315px;"><?php echo htmlentities($schedule->service_name ?: 'N/A'); ?></td>
                                                <td><?php echo htmlentities($schedule->date); ?></td>
                                                <td><?php echo format_time_12hr($schedule->time); ?></td>
                                                <td><?php echo htmlentities($schedule->duration ? $schedule->duration . ' mins' : 'N/A'); ?></td>
                                                <td class="cancel-reason-cell" style="width: 50px;">
                                                    <?php if (!empty($schedule->cancel_reason)): ?>
                                                    <button class="view-reason-btn" title="View Reason"
                                                        data-reason="<?php echo htmlentities($schedule->cancel_reason); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <span style="color: #ccc;">-</span>
                                                    <?php endif; ?>
                                                </td>
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
                                                        data-cancel-reason="<?php echo htmlentities($schedule->cancel_reason); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0;">
                                                        <i class="fas fa-edit" style="color: #007bffe3;"></i>
                                                    </button>
                                                    <button class="cancel-schedule-btn" title="Cancel"
                                                        data-id="<?php echo htmlentities($schedule->id); ?>"
                                                        data-firstname="<?php echo htmlentities($schedule->firstname); ?>"
                                                        data-surname="<?php echo htmlentities($schedule->surname); ?>"
                                                        data-service="<?php echo htmlentities($schedule->service_name ?: 'N/A'); ?>"
                                                        data-date="<?php echo htmlentities($schedule->date); ?>"
                                                        data-time="<?php echo htmlentities($schedule->time); ?>"
                                                        style="background:none; border:none; cursor:pointer; font-size: 1.25rem; color: #a0aec0; padding: 0 8px;">
                                                        <i class="fas fa-times-circle" style="color: #ff000078;"></i>
                                                    </button>
                                                    <a href="mas.php?delid=<?php echo $schedule->id; ?>" title="Delete" onclick="return confirm('Do you really want to Delete this service schedule?');" style="font-size: 1.25rem; color: #a0aec0; text-decoration: none;">
                                                        <i class="fas fa-trash-alt" style="color: red"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="11" style="text-align: center;">No service appointments found.</td>
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
                            <option value="For Cancellation">For Cancellation</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                    <div class="form-group" id="cancel_reason_group" style="display: none;">
                        <label for="edit_cancel_reason">Reason for Cancellation</label>
                        <textarea id="edit_cancel_reason" name="cancel_reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_schedule" class="btn btn-update">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Service Schedule Admin Modal -->
    <div id="cancelScheduleAdminModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cancel Service Schedule</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="cancelScheduleAdminForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="cancel_admin_schedule_id">
                    <input type="hidden" name="patient_name_for_notif" id="cancel_admin_patient_name_hidden">
                    <p>Are you sure you want to cancel the service schedule for <strong
                            id="cancel_admin_patient_name"></strong> for the service <strong
                            id="cancel_admin_service_name"></strong> on <strong
                            id="cancel_admin_schedule_date_time"></strong>?</p>
                    <div class="form-group">
                        <label for="cancel_admin_reason">Reason for Cancellation (Optional)</label>
                        <textarea id="cancel_admin_reason" name="cancel_reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Close</button>
                    <button type="submit" name="confirm_cancel_service" class="btn btn-danger">Confirm Cancellation</button>
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

            // Show/hide cancellation reason field in edit modal
            const editStatusSelect = document.getElementById('edit_status');
            const cancelReasonGroup = document.getElementById('cancel_reason_group');

            if(editStatusSelect) {
                editStatusSelect.addEventListener('change', function() {
                    if (this.value === 'Cancelled') {
                        cancelReasonGroup.style.display = 'block';
                    } else {
                        cancelReasonGroup.style.display = 'none';
                    }
                });
            }

            // --- Cancel Service Schedule Admin Modal ---
            const cancelAdminModal = document.getElementById('cancelScheduleAdminModal');
            if (cancelAdminModal) {
                const cancelAdminCloseBtn = cancelAdminModal.querySelector('.close-button');
                const cancelAdminCancelBtn = cancelAdminModal.querySelector('.btn-cancel');

                function closeCancelAdminModal() {
                    cancelAdminModal.style.display = 'none';
                }

                cancelAdminCloseBtn.addEventListener('click', closeCancelAdminModal);
                cancelAdminCancelBtn.addEventListener('click', closeCancelAdminModal);

                window.addEventListener('click', function(event) {
                    if (event.target === cancelAdminModal) {
                        closeCancelAdminModal();
                    }
                });

                document.querySelectorAll('.cancel-schedule-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const dataset = this.dataset;
                        document.getElementById('cancel_admin_schedule_id').value = dataset.id;
                        document.getElementById('cancel_admin_patient_name').textContent = dataset.firstname + ' ' + dataset.surname;
                        document.getElementById('cancel_admin_patient_name_hidden').value = dataset.firstname + ' ' + dataset.surname;
                        document.getElementById('cancel_admin_service_name').textContent = dataset.service;
                        document.getElementById('cancel_admin_schedule_date_time').textContent = dataset.date + ' at ' + dataset.time;
                        document.getElementById('cancel_admin_reason').value = dataset.cancelReason || '';
                        cancelAdminModal.style.display = 'flex';
                    });
                });
            }

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

                window.addEventListener('click', function(event) {
                    if (event.target === viewReasonModal) {
                        closeViewReasonModal();
                    }
                });

                document.querySelectorAll('.view-reason-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const reason = this.dataset.reason;
                        document.getElementById('reasonText').textContent = reason;
                        viewReasonModal.style.display = 'flex';
                    });
                });
            }
        });
    </script>
    <script src="js/mas-modal.js"></script>
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
</body>

</html>
<?php ?>
