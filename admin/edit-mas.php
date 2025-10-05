<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_GET['editid'])) {
    echo "<script>alert('No appointment selected');window.location.href='mas.php';</script>";
    exit();
}

$appointmentId = intval($_GET['editid']);

// Fetch appointment and any existing schedule
$sql = "SELECT a.*, s.id AS schedule_id, s.date AS sched_date, s.time AS sched_time FROM tblappointment a LEFT JOIN tblschedule s ON s.appointment_id = a.id WHERE a.id = :id";
$q = $dbh->prepare($sql);
$q->bindParam(':id', $appointmentId, PDO::PARAM_INT);
$q->execute();
$record = $q->fetch(PDO::FETCH_OBJ);
if (!$record) {
    echo "<script>alert('Appointment not found');window.location.href='mas.php';</script>";
    exit();
}

// Handle deletion of the existing schedule (from the edit page)
if (isset($_GET['delsid'])) {
    $delsid = intval($_GET['delsid']);
    try {
        // Only delete if the schedule belongs to this appointment
        $delStmt = $dbh->prepare("DELETE FROM tblschedule WHERE id = :sid AND appointment_id = :aid");
        $delStmt->bindParam(':sid', $delsid, PDO::PARAM_INT);
        $delStmt->bindParam(':aid', $appointmentId, PDO::PARAM_INT);
        $delStmt->execute();
        if ($delStmt->rowCount() > 0) {
            echo "<script>alert('Schedule deleted');</script>";
        } else {
            echo "<script>alert('No matching schedule found or already deleted');</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Failed to delete schedule');</script>";
    }
    echo "<script>window.location.href = 'mas.php'</script>";
    exit();
}

// Ensure we correctly detect any existing schedule for this appointment.
// Relying on the JOIN above can sometimes miss or return unexpected rows if data is inconsistent.
$existingScheduleId = null;
try {
    $chkSch = $dbh->prepare("SELECT id FROM tblschedule WHERE appointment_id = :aid LIMIT 1");
    $chkSch->bindParam(':aid', $appointmentId, PDO::PARAM_INT);
    $chkSch->execute();
    $sch = $chkSch->fetch(PDO::FETCH_OBJ);
    if ($sch && isset($sch->id)) {
        $existingScheduleId = (int)$sch->id;
        // overwrite record->schedule_id so the rest of the code treats this appointment as scheduled
        $record->schedule_id = $existingScheduleId;
    }
} catch (Exception $e) {
    // ignore - we'll fall back to whatever the original join returned
}

// If this appointment already has a schedule, fetch its service_id, duration and status to preselect
$current_service_id = null;
$current_duration = null;
$current_schedule_status = null;
if (!empty($record->schedule_id)) {
    try {
        $svcQ = $dbh->prepare("SELECT service_id, duration, status FROM tblschedule WHERE id = :sid LIMIT 1");
        $svcQ->bindParam(':sid', $record->schedule_id, PDO::PARAM_INT);
        $svcQ->execute();
        $svcRow = $svcQ->fetch(PDO::FETCH_OBJ);
        if ($svcRow) {
            if (isset($svcRow->service_id)) $current_service_id = $svcRow->service_id;
            if (isset($svcRow->duration)) $current_duration = $svcRow->duration;
            if (isset($svcRow->status)) $current_schedule_status = $svcRow->status;
            // If schedule status is null or empty, default to 'Ongoing' for display
            if (empty($current_schedule_status)) {
                $current_schedule_status = 'Ongoing';
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}

// Handle save to tblschedule
if (isset($_POST['save_schedule'])) {
    $sched_date = '';
    $sched_time = '';
    $sched_duration = null;
    $selected_calendar_slot = isset($_POST['calendar_slot']) ? intval($_POST['calendar_slot']) : 0;
    $selected_service = isset($_POST['service_id']) ? intval($_POST['service_id']) : null;
    $posted_duration = isset($_POST['duration']) && $_POST['duration'] !== '' ? intval($_POST['duration']) : null;
    $posted_schedule_status = isset($_POST['schedule_status']) ? trim($_POST['schedule_status']) : null;

    // Use posted fields only (manual entry). Calendar slots are no longer used.
    $sched_date = isset($_POST['sched_date']) ? $_POST['sched_date'] : '';
    $sched_time = isset($_POST['sched_time']) ? $_POST['sched_time'] : '';
    // Determine duration: prefer posted value, fallback to existing schedule's duration
    $sched_duration = $posted_duration !== null ? $posted_duration : $current_duration;

    // Basic validation (after resolving calendar slot)
    if (empty($sched_date) || empty($sched_time)) {
        $error = 'Please provide both date and time for the schedule.';
    } else {
        try {
            // prepare values from appointment
            $sched_firstname = isset($record->firstname) ? $record->firstname : '';
            $sched_surname = isset($record->surname) ? $record->surname : '';
            $sched_patient_number = isset($record->patient_number) ? $record->patient_number : null;

            // Using posted sched_date/sched_time only; duration already set above
            $sched_date = isset($_POST['sched_date']) ? $_POST['sched_date'] : '';
            $sched_time = isset($_POST['sched_time']) ? $_POST['sched_time'] : '';
            if ($sched_duration === null) $sched_duration = $current_duration;

            // Ensure tblschedule has service_id, duration and status columns. If missing, attempt to add them (best-effort)
            $hasServiceColumn = false;
            $hasDurationColumn = false;
            $hasStatusColumn = false;
            try {
                $colChk = $dbh->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblschedule' AND COLUMN_NAME IN ('service_id','duration','status')");
                $colChk->execute();
                $foundCols = [];
                while ($r = $colChk->fetch(PDO::FETCH_OBJ)) {
                    $foundCols[] = $r->COLUMN_NAME;
                }
                $hasServiceColumn = in_array('service_id', $foundCols);
                $hasDurationColumn = in_array('duration', $foundCols);
                $hasStatusColumn = in_array('status', $foundCols);
                // attempt to add any missing columns
                if (!$hasServiceColumn) {
                    try {
                        $dbh->exec("ALTER TABLE tblschedule ADD COLUMN service_id INT(11) DEFAULT NULL");
                        $hasServiceColumn = true;
                    } catch (Exception $e) {
                        $hasServiceColumn = false;
                    }
                }
                if (!$hasDurationColumn) {
                    try {
                        $dbh->exec("ALTER TABLE tblschedule ADD COLUMN duration INT(11) DEFAULT NULL");
                        $hasDurationColumn = true;
                    } catch (Exception $e) {
                        $hasDurationColumn = false;
                    }
                }
                if (!$hasStatusColumn) {
                    try {
                        $dbh->exec("ALTER TABLE tblschedule ADD COLUMN status VARCHAR(50) DEFAULT NULL");
                        $hasStatusColumn = true;
                    } catch (Exception $e) {
                        $hasStatusColumn = false;
                    }
                }
            } catch (Exception $e) {
                $hasServiceColumn = false;
                $hasDurationColumn = false;
            }

            // determine final status to save: prefer posted value, then existing schedule status, otherwise default to 'Ongoing'
            $statusToSave = null;
            if (!empty($posted_schedule_status)) {
                $statusToSave = $posted_schedule_status;
            } elseif (!empty($current_schedule_status)) {
                $statusToSave = $current_schedule_status;
            } else {
                $statusToSave = 'Ongoing';
            }

            // now upsert into tblschedule (include service_id when available)
                if (!empty($record->schedule_id)) {
                // update existing schedule (also update name, patient_number and optional service)
                $sqlUp = "UPDATE tblschedule SET date = :d, time = :t, firstname = :fn, surname = :sn, patient_number = :pn";
                if ($hasServiceColumn) {
                    $sqlUp .= ", service_id = :svc";
                }
                if ($hasDurationColumn) {
                    $sqlUp .= ", duration = :dur";
                }
                if ($hasStatusColumn) {
                    $sqlUp .= ", status = :status";
                }
                $sqlUp .= " WHERE id = :sid";
                $qUp = $dbh->prepare($sqlUp);
                $qUp->bindParam(':d', $sched_date, PDO::PARAM_STR);
                $qUp->bindParam(':t', $sched_time, PDO::PARAM_STR);
                $qUp->bindParam(':fn', $sched_firstname, PDO::PARAM_STR);
                $qUp->bindParam(':sn', $sched_surname, PDO::PARAM_STR);
                $qUp->bindParam(':pn', $sched_patient_number, PDO::PARAM_INT);
                if ($hasServiceColumn) {
                    $qUp->bindParam(':svc', $selected_service, PDO::PARAM_INT);
                }
                if ($hasDurationColumn) {
                    // bind duration as int or null
                    if ($sched_duration !== null) $qUp->bindParam(':dur', $sched_duration, PDO::PARAM_INT);
                    else $qUp->bindValue(':dur', null, PDO::PARAM_NULL);
                }
                if ($hasStatusColumn) {
                    // always bind a status string (defaulting to 'Ongoing' if nothing provided)
                    $qUp->bindParam(':status', $statusToSave, PDO::PARAM_STR);
                }
                $qUp->bindParam(':sid', $record->schedule_id, PDO::PARAM_INT);
                $qUp->execute();
            } else {
                // Before inserting, re-check for an existing schedule record for this appointment to avoid duplicate entries
                try {
                    $chk = $dbh->prepare("SELECT id FROM tblschedule WHERE appointment_id = :aid LIMIT 1");
                    $chk->bindParam(':aid', $appointmentId, PDO::PARAM_INT);
                    $chk->execute();
                    $existing = $chk->fetch(PDO::FETCH_OBJ);
                    if ($existing && isset($existing->id)) {
                        // convert to update flow to prevent duplicate
                        $record->schedule_id = (int)$existing->id;
                        // rebuild the update query used above
                        $sqlUp2 = "UPDATE tblschedule SET date = :d, time = :t, firstname = :fn, surname = :sn, patient_number = :pn";
                        if ($hasServiceColumn) $sqlUp2 .= ", service_id = :svc";
                        if ($hasDurationColumn) $sqlUp2 .= ", duration = :dur";
                        if ($hasStatusColumn) $sqlUp2 .= ", status = :status";
                        $sqlUp2 .= " WHERE id = :sid";
                        $qUp2 = $dbh->prepare($sqlUp2);
                        $qUp2->bindParam(':d', $sched_date, PDO::PARAM_STR);
                        $qUp2->bindParam(':t', $sched_time, PDO::PARAM_STR);
                        $qUp2->bindParam(':fn', $sched_firstname, PDO::PARAM_STR);
                        $qUp2->bindParam(':sn', $sched_surname, PDO::PARAM_STR);
                        $qUp2->bindParam(':pn', $sched_patient_number, PDO::PARAM_INT);
                        if ($hasServiceColumn) $qUp2->bindParam(':svc', $selected_service, PDO::PARAM_INT);
                        if ($hasDurationColumn) {
                            if ($sched_duration !== null) $qUp2->bindParam(':dur', $sched_duration, PDO::PARAM_INT);
                            else $qUp2->bindValue(':dur', null, PDO::PARAM_NULL);
                        }
                        if ($hasStatusColumn) $qUp2->bindParam(':status', $statusToSave, PDO::PARAM_STR);
                        $qUp2->bindParam(':sid', $record->schedule_id, PDO::PARAM_INT);
                        $qUp2->execute();
                        // redirect after update
                        header('Location: mas.php');
                        exit();
                    }
                } catch (Exception $e) {
                    // proceed with insert if check fails
                }

                // insert new schedule including patient info and optional service
                $sqlIns = "INSERT INTO tblschedule (appointment_id, patient_number, firstname, surname, date, time, created_at";
                if ($hasServiceColumn) $sqlIns .= ", service_id";
                if ($hasDurationColumn) $sqlIns .= ", duration";
                if ($hasStatusColumn) $sqlIns .= ", status";
                $sqlIns .= ") VALUES (:aid, :pn, :fn, :sn, :d, :t, NOW()";
                if ($hasServiceColumn) $sqlIns .= ", :svc";
                if ($hasDurationColumn) $sqlIns .= ", :dur";
                if ($hasStatusColumn) $sqlIns .= ", :status";
                $sqlIns .= ")";
                $qIns = $dbh->prepare($sqlIns);
                $qIns->bindParam(':aid', $appointmentId, PDO::PARAM_INT);
                $qIns->bindParam(':pn', $sched_patient_number, PDO::PARAM_INT);
                $qIns->bindParam(':fn', $sched_firstname, PDO::PARAM_STR);
                $qIns->bindParam(':sn', $sched_surname, PDO::PARAM_STR);
                $qIns->bindParam(':d', $sched_date, PDO::PARAM_STR);
                $qIns->bindParam(':t', $sched_time, PDO::PARAM_STR);
                if ($hasServiceColumn) {
                    $qIns->bindParam(':svc', $selected_service, PDO::PARAM_INT);
                }
                if ($hasDurationColumn) {
                    if ($sched_duration !== null) $qIns->bindParam(':dur', $sched_duration, PDO::PARAM_INT);
                    else $qIns->bindValue(':dur', null, PDO::PARAM_NULL);
                }
                if ($hasStatusColumn) {
                    // always bind a status string (defaulting to 'Ongoing' if nothing provided)
                    $qIns->bindParam(':status', $statusToSave, PDO::PARAM_STR);
                }
                $qIns->execute();
            }
            // After saving schedule: DO NOT change the appointment's consultation status here.
            // Consultation statuses should only be edited in the appointment detail editor (edit-appointment-detail.php).
            // Redirect back to MAS list after save.
            header('Location: mas.php');
            exit();
        } catch (Exception $e) {
            $error = 'Failed to save schedule.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit MAS Schedule</title>
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-scroller">
    <?php include_once('includes/header.php'); ?>
    <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">Edit MAS Schedule</h3>
                </div>
                <div class="row">
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($error)) echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>'; ?>
                                <?php
                                // Fetch services and calendar slots for form selects
                                $svcStmtForm = $dbh->prepare("SELECT number, name FROM tblservice ORDER BY name ASC");
                                $svcStmtForm->execute();
                                $formServices = $svcStmtForm->fetchAll(PDO::FETCH_OBJ);

                                // Prepare separated date, time and duration prefills (prefer POSTed values)
                                $sched_date_value = isset($_POST['sched_date']) ? $_POST['sched_date'] : (!empty($record->sched_date) ? $record->sched_date : '');
                                $sched_time_value = isset($_POST['sched_time']) ? $_POST['sched_time'] : (!empty($record->sched_time) ? substr($record->sched_time,0,5) : '');
                                // duration in minutes (if available)
                                $duration_value = isset($_POST['duration']) ? intval($_POST['duration']) : ($current_duration !== null ? $current_duration : '');
                                ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input class="form-control" value="<?php echo htmlentities($record->firstname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Surname</label>
                                        <input class="form-control" value="<?php echo htmlentities($record->surname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Service</label>
                                        <select name="service_id" class="form-control">
                                            <option value="">-- Select Service (optional) --</option>
                                            <?php
                                            // Determine selected service: prefer POSTed value (after submit), then existing schedule value
                                            $selected_service_value = isset($_POST['service_id']) ? intval($_POST['service_id']) : $current_service_id;
                                            foreach ($formServices as $fs) { ?>
                                                <option value="<?php echo htmlentities($fs->number); ?>" <?php if ($selected_service_value && $selected_service_value == $fs->number) echo 'selected'; ?>><?php echo htmlentities($fs->name); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Duration (minutes)</label>
                                        <input type="number" name="duration" min="1" step="1" class="form-control" value="<?php echo htmlentities($duration_value); ?>" placeholder="Duration in minutes">
                                    </div>
                                    <div class="form-group">
                                        <label>Schedule Status</label>
                                        <?php $status_value = isset($_POST['schedule_status']) ? $_POST['schedule_status'] : $current_schedule_status; ?>
                                        <select name="schedule_status" class="form-control">
                                            <option value="">-- Select Status --</option>
                                            <option value="Ongoing" <?php if ($status_value == 'Ongoing') echo 'selected'; ?>>Ongoing</option>
                                            <option value="Done" <?php if ($status_value == 'Done') echo 'selected'; ?>>Done</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Scheduled Date</label>
                                        <input type="date" name="sched_date" class="form-control" value="<?php echo htmlentities($sched_date_value); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Scheduled Time</label>
                                        <input type="time" name="sched_time" class="form-control" value="<?php echo htmlentities($sched_time_value); ?>">
                                    </div>
                                    <!-- calendar slot removed: schedules are entered manually using Scheduled Date/Time -->
                                    <button type="submit" name="save_schedule" class="btn btn-primary">Save Schedule</button>
                                    <a href="mas.php" class="btn btn-secondary">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>