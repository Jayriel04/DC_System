<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function getAdminNotificationEmailBody(string $title, string $message, ?string $ctaUrl = null, string $ctaText = 'View in Admin Panel'): string
{
    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
            <h2 style="margin: 0; color: #092c7a;">JF Dental Care - Admin Notification</h2>
        </div>
        <div style="padding: 20px 0; text-align: left;">
            <p>Dear Admin,</p>
            <h3 style="color: #333; margin-top: 0;"><?php echo htmlspecialchars($title); ?></h3>
            <p><?php echo $message;  ?></p>
            <?php if ($ctaUrl): ?><p style="text-align: center; margin-top: 30px;"><a href="<?php echo htmlspecialchars($ctaUrl); ?>" style="background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;"><?php echo htmlspecialchars($ctaText); ?></a></p><?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
    exit();
} else {
    $patient_number = $_SESSION['sturecmsnumber'];

    
    if (isset($_POST['update_medical_history'])) {
        $health_conditions_data = isset($_POST['health_conditions']) && is_array($_POST['health_conditions']) ? $_POST['health_conditions'] : [];

        
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
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Examination record updated successfully.'];
        header('Location: profile.php?tab=medical');
        exit();
    }

    
    if (isset($_POST['update_profile'])) {
        $firstname = ucfirst(trim($_POST['firstname']));
        $surname = ucfirst(trim($_POST['surname']));
        $date_of_birth = $_POST['date_of_birth'];
        $contact_number = $_POST['contact_number'];
        $address = ucfirst(trim($_POST['address']));
        $username = $_POST['username'];
        $email = $_POST['email'];
        $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $occupation = isset($_POST['occupation']) ? ucfirst(trim($_POST['occupation'])) : '';
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

        $updateSql = "UPDATE tblpatient SET firstname=:firstname, surname=:surname, date_of_birth=:dob, sex=:sex, status=:status, occupation=:occupation, age=:age, contact_number=:contact, address=:address, email=:email, username=:uname, image=:image WHERE number=:number";
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
            ':email' => $email,
            ':uname' => $username,
            ':image' => $image_to_update,
            ':number' => $patient_number
        ]);

        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
        header('Location: profile.php');
        exit();
    }

    
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

    if (isset($_GET['get_month_availability']) && !empty($_GET['month']) && !empty($_GET['year'])) {
        $month = $_GET['month'];
        $year = $_GET['year'];

        try {
            
            $all_slots_sql = "SELECT `date`, `start_time` FROM `tblcalendar` WHERE YEAR(`date`) = :year1 AND MONTH(`date`) = :month1";

            
            $booked_slots_sql = "SELECT `date`, `start_time` FROM `tblappointment` WHERE YEAR(`date`) = :year2 AND MONTH(`date`) = :month2 AND `status` != 'Declined' AND `status` != 'Cancelled'";

            
            $sql = "SELECT DISTINCT T1.`date`
                    FROM ($all_slots_sql) AS T1
                    LEFT JOIN ($booked_slots_sql) AS T2 
                    ON T1.`date` = T2.`date` AND T1.`start_time` = T2.`start_time`
                    WHERE T2.`start_time` IS NULL";

            $query = $dbh->prepare($sql);
            $query->execute([':year1' => $year, ':month1' => $month, ':year2' => $year, ':month2' => $month]);
            $available_dates = $query->fetchAll(PDO::FETCH_COLUMN, 0);

            header('Content-Type: application/json');
            echo json_encode(['available' => $available_dates]);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

    
    if (isset($_POST['book_appointment'])) {
        $appointment_date = trim($_POST['appointment_date']);
        $appointment_time = trim($_POST['appointment_time']);

        
        $sqlChk = "SELECT COUNT(*) FROM tblappointment WHERE patient_number = :pn AND `date` = :dt AND `start_time` = :tm";
        $qryChk = $dbh->prepare($sqlChk);
        $qryChk->execute([':pn' => $patient_number, ':dt' => $appointment_date, ':tm' => $appointment_time]);
        if ((int) $qryChk->fetchColumn() > 0) {
            $_SESSION['toast_message'] = ['type' => 'warning', 'message' => 'You already have an appointment at that date and time.'];
        } else {
            // Fetch the end_time from tblcalendar
            $sql_get_end_time = "SELECT end_time FROM tblcalendar WHERE `date` = :dt AND `start_time` = :tm LIMIT 1";
            $query_get_end_time = $dbh->prepare($sql_get_end_time);
            $query_get_end_time->execute([':dt' => $appointment_date, ':tm' => $appointment_time]);
            $end_time_result = $query_get_end_time->fetch(PDO::FETCH_ASSOC);
            $appointment_end_time = ($end_time_result && !empty($end_time_result['end_time'])) ? $end_time_result['end_time'] : null;

            $firstname = $_SESSION['sturecmsfirstname'] ?? '';
            $surname = $_SESSION['sturecmssurname'] ?? '';
            $status_default = 'Pending';

            $sqlIns = "INSERT INTO tblappointment (firstname, surname, date, start_time, end_time, patient_number, status) VALUES (:fn, :sn, :dt, :tm, :et, :pn, :st)";
            $qryIns = $dbh->prepare($sqlIns);
            $qryIns->execute([
                ':fn' => $firstname,
                ':sn' => $surname,
                ':dt' => $appointment_date,
                ':tm' => $appointment_time,
                ':et' => $appointment_end_time,
                ':pn' => $patient_number,
                ':st' => $status_default
            ]);

            
            $admin_id = 1;
            $notif_message = "New consultation request from " . htmlentities($firstname . ' ' . $surname) . ".";
            $notif_url = "mac.php?filter=pending";
            $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
            $query_notif = $dbh->prepare($sql_notif);
            $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);

            
            try {
                
                $sql_admin_email = "SELECT Email FROM tbladmin WHERE ID = :admin_id";
                $query_admin_email = $dbh->prepare($sql_admin_email);
                $query_admin_email->execute([':admin_id' => $admin_id]);
                $admin_email = $query_admin_email->fetchColumn();

                if ($admin_email) {
                    $mail = new PHPMailer(true);
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'canoniokevin@gmail.com';
                    $mail->Password   = 'qfkr wesz vhkm tydc'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    
                    $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                    $mail->addAddress($admin_email);

                    
                    $mail->isHTML(true);
                    $mail->Subject = 'New Consultation Request';
                    $emailTitle = 'New Consultation Request';
                    $emailMessage = "A new consultation has been booked by <strong>" . htmlentities($firstname . ' ' . $surname) . "</strong> for <strong>" . date('F j, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($appointment_time)) . "</strong>.<br><br>Please review it in the admin panel.";
                    $emailCtaUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/DC_System/admin/mac.php?filter=pending';

                    $mail->Body = getAdminNotificationEmailBody($emailTitle, $emailMessage, $emailCtaUrl);
                    $mail->AltBody = "Dear Admin,\n\n" . strip_tags(str_replace('<br>', "\n", $emailMessage));

                    $mail->send(); 
                }
            } catch (Exception $e) {  }

            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Appointment booked successfully.'];
            header('Location: profile.php?tab=appointments');
            exit();
        }
        exit();
    }

    
    if (isset($_POST['cancel_appointment'])) {
        $appointment_id_to_cancel = $_POST['cancel_appointment_id'];
        $cancel_reason = trim($_POST['cancel_reason']);
        
        
        $sql_cancel = "UPDATE tblappointment SET status = 'Cancelled', cancel_reason = :cancel_reason, cancelled_at = NOW() WHERE id = :appointment_id AND patient_number = :patient_number";
        $query_cancel = $dbh->prepare($sql_cancel);
        $query_cancel->bindParam(':appointment_id', $appointment_id_to_cancel, PDO::PARAM_INT);
        $query_cancel->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
        $query_cancel->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
        $query_cancel->execute();

       
        $admin_id = 1; 
        $notif_message = htmlentities($_SESSION['sturecmsfirstname'] . ' ' . $_SESSION['sturecmssurname']) . " cancelled a consultation.";
        $notif_url = "mac.php?filter=cancelled";
        $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
        $query_notif = $dbh->prepare($sql_notif);
        $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);

        
        try {
            // Fetch admin email
            $sql_admin_email = "SELECT Email FROM tbladmin WHERE ID = :admin_id";
            $query_admin_email = $dbh->prepare($sql_admin_email);
            $query_admin_email->execute([':admin_id' => $admin_id]);
            $admin_email = $query_admin_email->fetchColumn();

            if ($admin_email) {
                $mail = new PHPMailer(true);
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'canoniokevin@gmail.com'; 
                $mail->Password   = 'qfkr wesz vhkm tydc'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                
                $mail->setFrom('JFDentalCare.mcc@gmail.com', 'JF Dental Care');
                $mail->addAddress($admin_email);

                
                $mail->isHTML(true);
                $mail->Subject = 'Consultation Cancellation';
                $emailTitle = 'Consultation Cancellation';
                $emailMessage = "The consultation for <strong>" . htmlentities($_SESSION['sturecmsfirstname'] . ' ' . $_SESSION['sturecmssurname']) . "</strong> has been cancelled by the patient.<br><strong>Reason:</strong> " . htmlentities($cancel_reason);
                $emailCtaUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/DC_System/admin/mac.php?filter=cancelled';

                $mail->Body = getAdminNotificationEmailBody($emailTitle, $emailMessage, $emailCtaUrl);
                $mail->AltBody = "Dear Admin,\n\n" . strip_tags(str_replace('<br>', "\n", $emailMessage));

                $mail->send(); 
            }
        } catch (Exception $e) {  }
        
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Your appointment has been successfully cancelled.'];
        header('Location: profile.php?tab=appointments');
        exit();
    }

    if (isset($_POST['request_service_cancellation'])) {
        $schedule_id_to_cancel = $_POST['schedule_id_to_cancel'];
        $cancel_reason = trim($_POST['cancel_reason']);

        $sql_cancel_service = "UPDATE tblschedule SET status = 'For Cancellation', cancel_reason = :cancel_reason, cancelled_at = NOW() WHERE id = :schedule_id AND patient_number = :patient_number";
        $query_cancel_service = $dbh->prepare($sql_cancel_service);
        $query_cancel_service->bindParam(':schedule_id', $schedule_id_to_cancel, PDO::PARAM_INT);
        $query_cancel_service->bindParam(':cancel_reason', $cancel_reason, PDO::PARAM_STR);
        $query_cancel_service->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
        $query_cancel_service->execute();

       
        $admin_id = 1; 
        $notif_message = htmlentities($_SESSION['sturecmsfirstname'] . ' ' . $_SESSION['sturecmssurname']) . " requested to cancel a service.";
        $notif_url = "mas.php?filter=for_cancellation";
        $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, :url)";
        $query_notif = $dbh->prepare($sql_notif);
        $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message, ':url' => $notif_url]);

        // Send email to admin
        try {
            // Fetch admin email
            $sql_admin_email = "SELECT Email FROM tbladmin WHERE ID = :admin_id";
            $query_admin_email = $dbh->prepare($sql_admin_email);
            $query_admin_email->execute([':admin_id' => $admin_id]);
            $admin_email = $query_admin_email->fetchColumn();

            if ($admin_email) {
                $mail = new PHPMailer(true);
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'canoniokevin@gmail.com'; 
                $mail->Password   = 'qfkr wesz vhkm tydc'; 
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
                $mail->addAddress($admin_email);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Service Cancellation Request';
                $emailTitle = 'Service Cancellation Request';
                $emailMessage = "<strong>" . htmlentities($_SESSION['sturecmsfirstname'] . ' ' . $_SESSION['sturecmssurname']) . "</strong> has requested to cancel a service appointment.<br><strong>Reason:</strong> " . htmlentities($cancel_reason) . "<br><br>Please review this request in the admin panel.";
                $emailCtaUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/DC_System/admin/mas.php?filter=for_cancellation';

                $mail->Body = getAdminNotificationEmailBody($emailTitle, $emailMessage, $emailCtaUrl);
                $mail->AltBody = "Dear Admin,\n\n" . strip_tags(str_replace('<br>', "\n", $emailMessage));

                $mail->send(); 
            }
        } catch (Exception $e) { }

        $_SESSION['toast_message'] = ['type' => 'info', 'message' => 'Your cancellation request has been submitted for review.'];
        header('Location: profile.php?tab=appointments');
        exit();
    }

    $patient_number = $_SESSION['sturecmsnumber'];

    
    $sql_patient = "SELECT * FROM tblpatient WHERE number = :patient_number";
    $query_patient = $dbh->prepare($sql_patient);
    $query_patient->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
    $query_patient->execute();
    $patient = $query_patient->fetch(PDO::FETCH_OBJ);

    $health_arr = [];
    if ($patient && !empty($patient->health_conditions) && $patient->health_conditions !== 'null') {
        $decoded = json_decode($patient->health_conditions, true);
        if (is_array($decoded)) {
            $health_arr = $decoded;
        }
    }

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


    $sql_appts = "SELECT id, `date`, start_time, status FROM tblappointment WHERE patient_number = :patient_number ORDER BY `date` DESC, start_time DESC";
    $query_appts = $dbh->prepare($sql_appts);
    $query_appts->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);
    $query_appts->execute();
    $appointments = $query_appts->fetchAll(PDO::FETCH_ASSOC);

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
    <link href="css/custom-calendar.css" rel="stylesheet">
    <link href="./css/edit.css" rel="stylesheet">
    <link href="css/profile.css" rel="stylesheet">
    <link href="../css/style.v2.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/css/mas-modal.css">
    <link rel="stylesheet" href="../css/toast.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
 <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta charset="utf-8">

<body>
    <?php include_once(__DIR__ . '../includes/header.php'); ?>
    <div id="toast-container" class="toast-container top-center"></div>
    <div class="container">
        <?php if ($patient): ?>
            
            <?php
            if (isset($_SESSION['toast_message'])) {
                echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                unset($_SESSION['toast_message']);
            }
            ?>
            <div class="patient-header">
                <div class="patient-info">
                    <div class="patient-avatar">
                        <?php
                        $profile_avatar = 'avatar.png'; 
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

            
            <div class="tabs" id="profileTabs">
                <div class="tab active" data-tab-target="#aboutContent">
                    👤
                    About
                </div>
                <div class="tab" data-tab-target="#medicalContent">
                    🩺
                    Examination Record
                </div>
                <div class="tab" data-tab-target="#appointmentsContent">
                    🗓️
                    Appointments
                </div>
            </div>

            
            <div class="tab-content-container">

                <div id="aboutContent" class="tab-pane active">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title" style="margin: 0;">Personal Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="profile-details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">First Name</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->firstname); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Last Name</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->surname); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Username</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->username); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Gender</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->sex ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Civil Status</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->status ?: 'N/A'); ?></span>
                               </div>
                                <div class="detail-item">
                                    <span class="detail-label">Occupation</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->occupation ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Age</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->age ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-label">Address</span>
                                    <span class="detail-value"><?php echo htmlentities($patient->address ?: 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="medicalContent" class="tab-pane">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title" style="margin: 0;">Examination Records</h2>
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
                                            <div class="appointment-title">Consultation
                                                <?php if (in_array($appt['status'], ['Pending', 'Approved'])): ?>
                                                    <button type="button" class="btn btn-danger btn-sm cancel-consultation-btn"
                                                        data-appointment-id="<?php echo $appt['id']; ?>"
                                                        style="margin-left: auto; padding: 5px 14px; font-size: 12px; background-color: red; color: white;">
                                                        Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No consultation appointments found.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    
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
                                            
                                            <div class="appointment-actions">
                                                    <?php if ($schedule['sched_status'] === 'Ongoing'): ?>
                                                        <button type="button" class="btn btn-danger btn-sm request-cancel-btn"
                                                            data-schedule-id="<?php echo $schedule['id']; ?>"
                                                            style="margin-left: auto; width: 119px; padding: 5px 14px; font-size: 12px; background-color: red; color: white;">Request Cancel</button>
                                                    <?php endif; ?>
                                                </div>
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

    <!-- Cancel Consultation Modal -->
    <div id="cancelConsultationModal" class="modal" tabindex="-1" role="dialog" style="display: none !important; display: flex; align-items: center; justify-content: center;">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style=" width: 450px; left: 231px;">
                <div class="modal-header">
                    <h4 class="modal-title">Cancel Appointment</h4>
                    <span class="close" data-dismiss="modal">&times;</span>
                </div>
                <div class="modal-body" style="padding: 12px;">
                    <form method="post" action="profile.php">
                        <input type="hidden" name="cancel_appointment_id" id="cancel_appointment_id">
                        <div class="form-group">
                            <label for="consultation_cancel_reason" style="font-weight: bold; margin-bottom: 8px;">Reason for cancellation:</label>
                            <textarea name="cancel_reason" id="consultation_cancel_reason" class="form-control" rows="4" required placeholder="Please provide a reason for cancelling..."></textarea>
                        </div>
                        <div class="modal-footer" style="padding: 15px 0 0 0; border-top: 1px solid #e5e5e5; margin-top: 15px;">
                            <button type="submit" name="cancel_appointment" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Book Appointment Modal -->
    <div id="bookAppointmentModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width: 400px;">
            <div class="modal-content" style="max-width: 500px; left: 50px;">
                <div class="modal-header">
                    <h4 class="modal-title">Book New Appointment</h4>
                    <span class="close" data-dismiss="modal">&times;</span>
                </div>
                <div class="modal-body" style="padding: 15px; overflow-y: auto; max-height: 80vh;">
                    <form method="post" action="profile.php">
                        <div class="form-group">
                            <label for="appointment_date">Preferred Date</label>
                            
                            <input type="text" class="form-control" name="appointment_date" id="appointment_date"
                                placeholder="Select a date" required readonly>
                            
                            <div class="calendar-wrapper" id="calendarWrapper">
                                <div class="calendar-header">
                                    <button type="button" class="nav-btn" id="prevBtn">‹</button>
                                    <h2 id="monthYear"></h2>
                                    <button type="button" class="nav-btn" id="nextBtn">›</button>
                                </div>
                                <div class="weekdays">
                                    <div class="weekday">Mon</div>
                                    <div class="weekday">Tue</div>
                                    <div class="weekday">Wed</div>
                                    <div class="weekday">Thu</div>
                                    <div class="weekday">Fri</div>
                                    <div class="weekday">Sat</div>
                                    <div class="weekday">Sun</div>
                                </div>
                                <div class="days" id="daysContainer"></div>
                            </div>
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
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="edit-profile-modal-content">
            <?php if ($patient): ?>
                <form method="post" action="profile.php" enctype="multipart/form-data">
                    <div class="header">
                        <div class="breadcrumb">My profile › <span>Edit Profile</span></div>
                       <span class="close" data-dismiss="modal">&times;</span>
                    </div>

                    <div class="profile-section">
                        <div class="left-column">
                            <div class="profile-pic-container">
                                <img src="../admin/images/<?php echo htmlentities($profile_avatar); ?>" alt="Profile"
                                    class="profile-pic" id="profilePic">
                                <button type="button" class="edit-pic-btn"
                                    onclick="document.getElementById('fileInput').click()">✎</button>
                                <input type="file" name="image" id="fileInput" style="display:none" accept="image/*"
                                    onchange="changeProfilePic(event)">
                            </div>

                            <div class="name-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="edit_firstname" name="firstname"
                                        value="<?php echo htmlentities($patient->firstname); ?>" required>
                                </div>
                                <br>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" id="edit_surname" name="surname" value="<?php echo htmlentities($patient->surname); ?>"
                                        required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="edit_email" name="email" value="<?php echo htmlentities($patient->email); ?>">
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" id="edit_contact_number" name="contact_number"
                                    value="<?php echo htmlentities($patient->contact_number); ?>">
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <textarea id="edit_address" name="address" rows="2"><?php echo htmlentities($patient->address); ?></textarea>
                            </div>
                        </div>

                        <div class="right-column">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo htmlentities($patient->username); ?>">
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth"
                                    value="<?php echo htmlentities($patient->date_of_birth); ?>">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="sex">
                                    <option value="Male" <?php if ($patient->sex == 'Male')
                                        echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if ($patient->sex == 'Female')
                                        echo 'selected'; ?>>Female
                                    </option>
                                    <option value="Other" <?php if ($patient->sex == 'Other')
                                        echo 'selected'; ?>>Other
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Civil Status</label>
                                <select name="status">
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
                            <div class="form-group">
                                <label>Occupation</label>
                                <input type="text" id="edit_occupation" name="occupation"
                                    value="<?php echo htmlentities($patient->occupation); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="save-btn">Save →</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <p>Could not load profile data.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Medical History Edit Modal -->
    <div id="medicalHistoryModal" class="modal">
        <div class="modal-content health-questionnaire-modal" style="max-width: 1200px; margin: 2rem auto;">
            <div class="modal-header">
                <h4 class="modal-title">Edit Examination Records</h4>
                <span class="close" data-dismiss="modal">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" action="profile.php" id="medicalHistoryForm">
                    <p>Please check all conditions that apply to you.</p>
                    <div class="two-column">
                        <div>
                            <div class="section">
                                <div class="section-title">General</div>
                                <div class="form-group"><label>Marked weight change</label><input type="checkbox"
                                        name="health_conditions[general][]" value="Marked weight change" <?php echo hc_checked('general', 'Marked weight change', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Ear</div>
                                <div class="form-group"><label>Loss of hearing, ringing of ears</label><input
                                        type="checkbox" name="health_conditions[ear][]"
                                        value="Loss of hearing, ringing of ears" <?php echo hc_checked('ear', 'Loss of hearing, ringing of ears', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Nervous System</div>
                                <div class="form-group"><label>Headache</label><input type="checkbox"
                                        name="health_conditions[nervous][]" value="Headache" <?php echo hc_checked('nervous', 'Headache', $health_arr); ?>></div>
                                <div class="form-group"><label>Convulsion / epilepsy</label><input type="checkbox"
                                        name="health_conditions[nervous][]" value="Convulsion/epilepsy" <?php echo hc_checked('nervous', 'Convulsion/epilepsy', $health_arr); ?>></div>
                                <div class="form-group"><label>Numbness / Tingling</label><input type="checkbox"
                                        name="health_conditions[nervous][]" value="Numbness/Tingling" <?php echo hc_checked('nervous', 'Numbness/Tingling', $health_arr); ?>></div>
                                <div class="form-group"><label>Dizziness / Fainting</label><input type="checkbox"
                                        name="health_conditions[nervous][]" value="Dizziness/Fainting" <?php echo hc_checked('nervous', 'Dizziness/Fainting', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Blood</div>
                                <div class="form-group"><label>Bruise easily</label><input type="checkbox"
                                        name="health_conditions[blood][]" value="Bruise easily" <?php echo hc_checked('blood', 'Bruise easily', $health_arr); ?>></div>
                                <div class="form-group"><label>Anemia</label><input type="checkbox"
                                        name="health_conditions[blood][]" value="Anemia" <?php echo hc_checked('blood', 'Anemia', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Respiratory</div>
                                <div class="form-group"><label>Persistent cough</label><input type="checkbox"
                                        name="health_conditions[respiratory][]" value="Persistent cough" <?php echo hc_checked('respiratory', 'Persistent cough', $health_arr); ?>></div>
                                <div class="form-group"><label>Difficulty in breathing</label><input type="checkbox"
                                        name="health_conditions[respiratory][]" value="Difficulty in breathing" <?php echo hc_checked('respiratory', 'Difficulty in breathing', $health_arr); ?>>
                                </div>
                                <div class="form-group"><label>Asthma</label><input type="checkbox"
                                        name="health_conditions[respiratory][]" value="Asthma" <?php echo hc_checked('respiratory', 'Asthma', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Heart</div>
                                <div class="form-group"><label>Chest pain/discomfort</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="Chest pain/discomfort" <?php echo hc_checked('heart', 'Chest pain/discomfort', $health_arr); ?>></div>
                                <div class="form-group"><label>Shortness of breath</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="Shortness of breath" <?php echo hc_checked('heart', 'Shortness of breath', $health_arr); ?>></div>
                                <div class="form-group"><label>Hypertension</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="Hypertension" <?php echo hc_checked('heart', 'Hypertension', $health_arr); ?>></div>
                                <div class="form-group"><label>Ankle edema</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="Ankle edema" <?php echo hc_checked('heart', 'Ankle edema', $health_arr); ?>></div>
                                <div class="form-group"><label>Rheumatic fever (age)</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="Rheumatic fever" <?php echo hc_checked('heart', 'Rheumatic fever', $health_arr); ?>></div>
                                <div class="input-group"><input type="text" placeholder="Specify age"
                                        name="health_conditions[rheumatic_age]"
                                        value="<?php echo hc_text('rheumatic_age', $health_arr); ?>"></div>
                                <div class="form-group"><label>History of stroke (When)</label><input type="checkbox"
                                        name="health_conditions[heart][]" value="History of stroke" <?php echo hc_checked('heart', 'History of stroke', $health_arr); ?>></div>
                                <div class="input-group"><input type="text" placeholder="When"
                                        name="health_conditions[stroke_when]"
                                        value="<?php echo hc_text('stroke_when', $health_arr); ?>">
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="section">
                                <div class="section-title">Urinary</div>
                                <div class="form-group"><label>Increase frequency of urination</label><input
                                        type="checkbox" name="health_conditions[urinary][]"
                                        value="Increase frequency of urination" <?php echo hc_checked('urinary', 'Increase frequency of urination', $health_arr); ?>></div>
                                <div class="form-group"><label>Burning sensation on urination</label><input
                                        type="checkbox" name="health_conditions[urinary][]"
                                        value="Burning sensation on urination" <?php echo hc_checked('urinary', 'Burning sensation on urination', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Liver</div>
                                <div class="form-group"><label>History of liver ailment</label><input type="checkbox"
                                        name="health_conditions[liver][]" value="History of liver ailment" <?php echo hc_checked('liver', 'History of liver ailment', $health_arr); ?>></div>
                                <div class="input-group"><input type="text" placeholder="Specify"
                                        name="health_conditions[liver_specify]"
                                        value="<?php echo hc_text('liver_specify', $health_arr); ?>"></div>
                                <div class="form-group"><label>Jaundice</label><input type="checkbox"
                                        name="health_conditions[liver][]" value="Jaundice" <?php echo hc_checked('liver', 'Jaundice', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Diabetes</div>
                                <div class="form-group"><label>Delayed healing of wounds</label><input type="checkbox"
                                        name="health_conditions[diabetes][]" value="Delayed healing of wounds" <?php echo hc_checked('diabetes', 'Delayed healing of wounds', $health_arr); ?>></div>
                                <div class="form-group"><label>Increase intake of food or water</label><input
                                        type="checkbox" name="health_conditions[diabetes][]"
                                        value="Increase intake of food or water" <?php echo hc_checked('diabetes', 'Increase intake of food or water', $health_arr); ?>></div>
                                <div class="form-group"><label>Family history of diabetes</label><input type="checkbox"
                                        name="health_conditions[diabetes][]" value="Family history of diabetes" <?php echo hc_checked('diabetes', 'Family history of diabetes', $health_arr); ?>>
                                </div>
                            </div>
                            <div class="section">
                                <div class="section-title">Thyroid</div>
                                <div class="form-group"><label>Perspire easily</label><input type="checkbox"
                                        name="health_conditions[thyroid][]" value="Perspire easily" <?php echo hc_checked('thyroid', 'Perspire easily', $health_arr); ?>></div>
                                <div class="form-group"><label>Apprehension</label><input type="checkbox"
                                        name="health_conditions[thyroid][]" value="Apprehension" <?php echo hc_checked('thyroid', 'Apprehension', $health_arr); ?>></div>
                                <div class="form-group"><label>Palpitation/rapid heart beat</label><input
                                        type="checkbox" name="health_conditions[thyroid][]"
                                        value="Palpation/rapid heart beat" <?php echo hc_checked('thyroid', 'Palpation/rapid heart beat', $health_arr); ?>></div>
                                <div class="form-group"><label>Goiter</label><input type="checkbox"
                                        name="health_conditions[thyroid][]" value="Goiter" <?php echo hc_checked('thyroid', 'Goiter', $health_arr); ?>></div>
                                <div class="form-group"><label>Bulging of eyes</label><input type="checkbox"
                                        name="health_conditions[thyroid][]" value="Bulging of eyes" <?php echo hc_checked('thyroid', 'Bulging of eyes', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Arthritis</div>
                                <div class="form-group"><label>Joint pain</label><input type="checkbox"
                                        name="health_conditions[arthritis][]" value="Joint pain" <?php echo hc_checked('arthritis', 'Joint pain', $health_arr); ?>></div>
                                <div class="form-group"><label>Joint Swelling</label><input type="checkbox"
                                        name="health_conditions[arthritis][]" value="Joint Swelling" <?php echo hc_checked('arthritis', 'Joint Swelling', $health_arr); ?>></div>
                            </div>
                            <div class="section">
                                <div class="section-title">Radiograph</div>
                                <div class="form-group"><label>Undergo radiation therapy</label><input type="checkbox"
                                        name="health_conditions[radiograph][]" value="Undergo radiation therapy" <?php echo hc_checked('radiograph', 'Undergo radiation therapy', $health_arr); ?>>
                                </div>
                            </div>
                            <div class="section">
                                <div class="section-title">Women</div>
                                <div class="form-group"><label>Pregnancy (No. of month)</label><input type="checkbox"
                                        name="health_conditions[women][]" value="Pregnancy" <?php echo hc_checked('women', 'Pregnancy', $health_arr); ?>></div>
                                <div class="input-group"><input type="number" placeholder="Number of months"
                                        name="health_conditions[pregnancy_months]" min="1" max="9"
                                        value="<?php echo hc_text('pregnancy_months', $health_arr); ?>"></div>
                                <div class="form-group"><label>Breast feed</label><input type="checkbox"
                                        name="health_conditions[women][]" value="Breast feed" <?php echo hc_checked('women', 'Breast feed', $health_arr); ?>></div>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Hospitalization</div>
                        <div class="inline-group"><label>Have you been hospitalized</label><input type="checkbox"
                                name="health_conditions[hospitalization][]" value="Hospitalized" <?php echo hc_checked('hospitalization', 'Hospitalized', $health_arr); ?>></div>
                        <div class="input-group"><label>Date:</label><input type="date"
                                name="health_conditions[hospitalization_date]"
                                value="<?php echo hc_text('hospitalization_date', $health_arr); ?>"></div>
                        <div class="input-group"><label>Specify:</label><input type="text"
                                name="health_conditions[hospitalization_specify]" placeholder="Please specify reason"
                                value="<?php echo hc_text('hospitalization_specify', $health_arr); ?>"></div>
                    </div>

                    <div class="allergy-section">
                        <div class="allergy-title">Are you allergic or have ever experienced any reaction to the ff?
                        </div>
                        <div class="inline-group">
                            <label>Sleeping pills</label><input type="checkbox" name="health_conditions[allergies][]"
                                value="Sleeping pills" <?php echo hc_checked('allergies', 'Sleeping pills', $health_arr); ?>>
                            <label>Aspirin</label><input type="checkbox" name="health_conditions[allergies][]"
                                value="Aspirin" <?php echo hc_checked('allergies', 'Aspirin', $health_arr); ?>>
                            <label>Food</label><input type="checkbox" name="health_conditions[allergies][]" value="Food"
                                <?php echo hc_checked('allergies', 'Food', $health_arr); ?>>
                        </div>
                        <div class="inline-group">
                            <label>Penicillin/other antibiotics</label><input type="checkbox"
                                name="health_conditions[allergies][]" value="Penicillin/other antibiotics" <?php echo hc_checked('allergies', 'Penicillin/other antibiotics', $health_arr); ?>>
                            <label>Sulfa Drugs</label><input type="checkbox" name="health_conditions[allergies][]"
                                value="Sulfa Drugs" <?php echo hc_checked('allergies', 'Sulfa Drugs', $health_arr); ?>>
                            <label>Others</label><input type="checkbox" name="health_conditions[allergies][]"
                                value="Others" <?php echo hc_checked('allergies', 'Others', $health_arr); ?>>
                        </div>
                        <div class="input-group"><label>Specify:</label><input type="text"
                                name="health_conditions[allergy_specify]" placeholder="Please specify allergies"
                                value="<?php echo hc_text('allergy_specify', $health_arr); ?>"></div>
                    </div><br>

                    <div class="section">
                        <div class="section-title">Previous Extraction History</div>
                        <div class="form-group"><label>Have you had any previous extraction</label><input
                                type="checkbox" name="health_conditions[extraction][]" value="Previous extraction" <?php echo hc_checked('extraction', 'Previous extraction', $health_arr); ?>></div>
                        <div class="input-group"><label>Date of last extraction:</label><input type="date"
                                name="health_conditions[extraction_date]"
                                value="<?php echo hc_text('extraction_date', $health_arr); ?>"></div>
                        <div class="input-group"><label>Specify:</label><textarea
                                name="health_conditions[extraction_specify]" rows="2"
                                placeholder="Please provide details"><?php echo hc_text('extraction_specify', $health_arr); ?></textarea>
                        </div>
                        <div class="form-group"><label>Untoward reaction to extraction</label><input type="checkbox"
                                name="health_conditions[extraction][]" value="Untoward reaction to extraction" <?php echo hc_checked('extraction', 'Untoward reaction to extraction', $health_arr); ?>></div>
                        <div class="input-group"><label>Specify:</label><input type="text"
                                name="health_conditions[extraction_reaction_specify]"
                                placeholder="Please specify reaction"
                                value="<?php echo hc_text('extraction_reaction_specify', $health_arr); ?>"></div>
                        <div class="form-group"><label>Were you under local anesthesia</label><input type="checkbox"
                                name="health_conditions[extraction][]" value="Under local anesthesia" <?php echo hc_checked('extraction', 'Under local anesthesia', $health_arr); ?>></div>
                        <div class="form-group"><label>Allergic reaction to local anesthesia</label><input
                                type="checkbox" name="health_conditions[extraction][]"
                                value="Allergic reaction to local anesthesia" <?php echo hc_checked('extraction', 'Allergic reaction to local anesthesia', $health_arr); ?>></div>
                    </div>
                    <button type="submit" name="update_medical_history" class="submit-btn">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Service Appointment Modal -->
    <div id="cancelServiceModal" class="modal" tabindex="-1" role="dialog" style="display: none !important; display: flex; align-items: center; justify-content: center;">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style=" width: 450px; left: 231px;">
                <div class="modal-header">
                    <h4 class="modal-title">Request Cancellation</h4>
                    <span class="close" data-dismiss="modal">&times;</span>
                </div>
                <div class="modal-body" style="padding: 12px;">
                    <form method="post" action="profile.php">
                        <input type="hidden" name="schedule_id_to_cancel" id="schedule_id_to_cancel">
                        <div class="form-group">
                            <label for="cancel_reason" style="font-weight: bold; margin-bottom: 8px;">Reason for cancellation:</label>
                            <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="4" required placeholder="Please provide a reason for cancelling..."></textarea>
                        </div>
                        <div class="modal-footer" style="padding: 15px 0 0 0; border-top: 1px solid #e5e5e5; margin-top: 15px;">
                            <button type="submit" name="request_service_cancellation" class="btn btn-danger">Submit Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="../js/jquery-1.11.0.min.js"></script>
    <script src="../js/toast.js"></script>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="../js/bootstrap.js"></script>
    <link rel="stylesheet" href="../css/interactive-calendar.css">
    <style>
        .form-check {
            position: relative;
            display: block;
            padding-left: 1.25rem;
        }

        .profile-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 1rem;
            font-weight: 500;
        }
        .full-width {
            grid-column: 1 / -1;
        }

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
            padding: -0.625rem .75rem;
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
                    contentPanes.forEach(pane => { // Use classList to toggle visibility
                        pane.classList.toggle('active', pane.id === targetId.substring(1));
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
        });
    </script>
    <script src="../js/interactive-calendar.js"></script>
    <script src="js/calendar-availability.js"></script>
    <script src="js/profile-booking-modal.js"></script>
    <script src="js/profile-medical-modal.js"></script>
    <script src="js/profile-edit-modal.js"></script>
    <script src="../js/health-questionnaire.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Page loaded, checking elements:');
            console.log('Book Appointment Button:', !!document.getElementById('bookAppointmentBtn'));
            console.log('Booking Modal:', !!document.getElementById('bookAppointmentModal'));
            console.log('Date Input:', !!document.getElementById('appointment_date'));
            console.log('Time Select:', !!document.getElementById('appointment_time_modal'));
            console.log('Calendar Wrapper:', !!document.getElementById('calendarWrapper'));
        });
    </script>

    <script>
        function changeProfilePic(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profilePic').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

    <script>
        // Service Cancellation Modal
        document.addEventListener('DOMContentLoaded', function () {
            const cancelServiceModal = document.getElementById('cancelServiceModal');
            const cancelScheduleIdInput = document.getElementById('schedule_id_to_cancel');
            
            document.querySelectorAll('.request-cancel-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const scheduleId = this.getAttribute('data-schedule-id');
                    cancelScheduleIdInput.value = scheduleId;
                    cancelServiceModal.style.display = 'flex';
                });
            });

            cancelServiceModal.querySelector('[data-dismiss="modal"]').addEventListener('click', function() {
                cancelServiceModal.style.display = 'none';
            });

            // Auto-capitalize first letter for profile edit fields
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
            capitalizeFirstLetter('edit_firstname');
            capitalizeFirstLetter('edit_surname');
            capitalizeFirstLetter('edit_occupation');

            // Consultation Cancellation Modal
            const cancelConsultationModal = document.getElementById('cancelConsultationModal');
            const cancelAppointmentIdInput = document.getElementById('cancel_appointment_id');

            document.querySelectorAll('.cancel-consultation-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    cancelAppointmentIdInput.value = appointmentId;
                    cancelConsultationModal.style.display = 'flex';
                });
            });

            cancelConsultationModal.querySelector('[data-dismiss="modal"]').addEventListener('click', function() {
                cancelConsultationModal.style.display = 'none';
            });
        });
    </script>

</body>

</html>