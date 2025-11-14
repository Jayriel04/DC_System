<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Get patient ID from URL
$stdid = '';
if (isset($_GET['stid'])) {
    $stdid = intval($_GET['stid']);
} elseif (isset($_GET['number'])) {
    $stdid = intval($_GET['number']);
}

if (empty($stdid)) {
    echo "<p style='color:red'>No patient selected.</p>";
    exit();
}

// Fetch patient info
$sql = "SELECT * FROM tblpatient WHERE number = :num";
$query = $dbh->prepare($sql);
$query->bindParam(':num', $stdid, PDO::PARAM_INT);
$query->execute();
$row = $query->fetch(PDO::FETCH_OBJ);

if (!$row) {
    echo "<p style='color:red'>Patient not found.</p>";
    exit();
}

// Fetch health conditions for this patient
$health_arr = [];
if (!empty($row->health_conditions) && $row->health_conditions !== 'null' && $row->health_conditions !== '[]') {
    $decoded = json_decode($row->health_conditions, true);
    if (is_array($decoded)) {
        $health_arr = $decoded;
    }
}

// helper to mark checkbox checked and to get text values
function hc_checked($cat, $val)
{
    global $health_arr;
    if (isset($health_arr[$cat]) && is_array($health_arr[$cat]) && in_array($val, $health_arr[$cat]))
        return 'checked';
    return '';
}
function hc_text($key)
{
    global $health_arr;
    if (isset($health_arr[$key]) && !is_array($health_arr[$key]))
        return htmlspecialchars($health_arr[$key]);
    return '';
}

// Handle admin POST from modal: save health conditions and optionally book appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_health'])) {
    $post_health = isset($_POST['health_conditions']) ? $_POST['health_conditions'] : [];
    // normalize any string fields (like liver_specify) into the array shape
    $hc_json = json_encode($post_health);

    $sqlUpd = "UPDATE tblpatient SET health_conditions = :hc WHERE number = :num";
    $qryUpd = $dbh->prepare($sqlUpd);
    $qryUpd->bindParam(':hc', $hc_json, PDO::PARAM_STR);
    $qryUpd->bindParam(':num', $stdid, PDO::PARAM_INT);
    if ($qryUpd->execute()) {
        $_SESSION['modal_success'] = 'Health info saved.';
        // Refresh to show updated values
        header('Location: view-er.php?stid=' . intval($stdid));
        exit();
    } else {
        $modal_error = 'Could not save health conditions. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Patient Details</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
 <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Examination Record</h3>
              <nav aria-label="breadcrumb">
                
              </nav>
            </div>
 
<div class="container mt-4">
    <?php if (isset($_SESSION['modal_success'])) { ?>
        <div class="container px-3 mt-3">
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_SESSION['modal_success']); ?></div>
        </div>
        <?php unset($_SESSION['modal_success']);
    } ?> 
    


                <!-- Health Conditions Table -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <span>Health Conditions</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($health_arr)) { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Values</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($health_arr as $cat => $vals) {
                                            if (is_array($vals)) {
                                                $vals_disp = implode(', ', $vals);
                                            } else {
                                                $vals_disp = (string) $vals;
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cat); ?></td>
                                                <td><?php echo htmlspecialchars($vals_disp); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <p>No health information on file for this patient.</p>
                        <?php } ?>
                    </div>
                </div>
                <button id="btnEditHealth" class="btn btn-primary btn-sm" type="button">Edit</button>


                <!-- Admin Health Modal -->
                <div class="modal fade" id="adminHealthModal" tabindex="-1" role="dialog"
                    aria-labelledby="adminHealthModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <form method="post" action="view-er.php?stid=<?php echo intval($stdid); ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="adminHealthModalLabel">Health Condition Form -
                                        <?php echo htmlspecialchars($row->firstname . ' ' . $row->surname); ?></h5>
                                    <button type="button" class="close" aria-label="Close" data-dismiss="modal"
                                        data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <!-- appointment fields removed: admin modal only edits health conditions -->

                                    <?php if (!empty($modal_error)) {
                                        echo '<div class="alert alert-danger">' . htmlspecialchars($modal_error) . '</div>';
                                    } ?>
                                    <p>Please check all conditions that apply to the patient.</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>GENERAL</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[general][]" value="Marked weight change"
                                                    id="hc_general_1" <?php echo hc_checked('general', 'Marked weight change'); ?>><label class="form-check-label"
                                                    for="hc_general_1">Marked weight change</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[general][]"
                                                    value="Increase frequency of urination" id="hc_general_2" <?php echo hc_checked('general', 'Increase frequency of urination'); ?>><label
                                                    class="form-check-label" for="hc_general_2">Increase frequency of
                                                    urination</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[general][]"
                                                    value="Burning sensation on urination" id="hc_general_3" <?php echo hc_checked('general', 'Burning sensation on urination'); ?>><label
                                                    class="form-check-label" for="hc_general_3">Burning sensation on
                                                    urination</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[general][]"
                                                    value="Loss of hearing, ringing of ears" id="hc_general_4" <?php echo hc_checked('general', 'Loss of hearing, ringing of ears'); ?>><label class="form-check-label" for="hc_general_4">Loss of
                                                    hearing, ringing of ears</label></div>

                                            <h6 class="mt-3">LIVER</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[liver][]" value="History of liver ailment"
                                                    id="hc_liver_1" <?php echo hc_checked('liver', 'History of liver ailment'); ?>><label class="form-check-label"
                                                    for="hc_liver_1">History of liver ailment</label></div>
                                            <div class="form-group mt-2"><label
                                                    for="liver_specify">Specify:</label><input type="text"
                                                    class="form-control" name="health_conditions[liver_specify]"
                                                    id="liver_specify" value="<?php echo hc_text('liver_specify'); ?>">
                                            </div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[liver][]" value="Jaundice" id="hc_liver_2"
                                                    <?php echo hc_checked('liver', 'Jaundice'); ?>><label
                                                    class="form-check-label" for="hc_liver_2">Jaundice</label></div>

                                            <h6 class="mt-3">DIABETES</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[diabetes][]"
                                                    value="Delayed healing of wounds" id="hc_diab_1" <?php echo hc_checked('diabetes', 'Delayed healing of wounds'); ?>><label
                                                    class="form-check-label" for="hc_diab_1">Delayed healing of
                                                    wounds</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[diabetes][]"
                                                    value="Increase intake of food or water" id="hc_diab_2" <?php echo hc_checked('diabetes', 'Increase intake of food or water'); ?>><label
                                                    class="form-check-label" for="hc_diab_2">Increase intake of food or
                                                    water</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[diabetes][]"
                                                    value="Family history of diabetes" id="hc_diab_3" <?php echo hc_checked('diabetes', 'Family history of diabetes'); ?>><label
                                                    class="form-check-label" for="hc_diab_3">Family history of
                                                    diabetes</label></div>

                                            <h6 class="mt-3">THYROID</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[thyroid][]" value="Perspire easily"
                                                    id="hc_thy_1" <?php echo hc_checked('thyroid', 'Perspire easily'); ?>><label class="form-check-label" for="hc_thy_1">Perspire
                                                    easily</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[thyroid][]" value="Apprehension"
                                                    id="hc_thy_2" <?php echo hc_checked('thyroid', 'Apprehension'); ?>><label class="form-check-label"
                                                    for="hc_thy_2">Apprehension</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[thyroid][]"
                                                    value="Palpation/rapid heart beat" id="hc_thy_3" <?php echo hc_checked('thyroid', 'Palpation/rapid heart beat'); ?>><label
                                                    class="form-check-label" for="hc_thy_3">Palpation/rapid heart
                                                    beat</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[thyroid][]" value="Goiter" id="hc_thy_4"
                                                    <?php echo hc_checked('thyroid', 'Goiter'); ?>><label
                                                    class="form-check-label" for="hc_thy_4">Goiter</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[thyroid][]" value="Bulging of eyes"
                                                    id="hc_thy_5" <?php echo hc_checked('thyroid', 'Bulging of eyes'); ?>><label class="form-check-label" for="hc_thy_5">Bulging of
                                                    eyes</label></div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>URINARY</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[urinary][]"
                                                    value="Increase frequency of urination" id="hc_ur_1" <?php echo hc_checked('urinary', 'Increase frequency of urination'); ?>><label
                                                    class="form-check-label" for="hc_ur_1">Increase frequency of
                                                    urination</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[urinary][]"
                                                    value="Burning sensation on urination" id="hc_ur_2" <?php echo hc_checked('urinary', 'Burning sensation on urination'); ?>><label
                                                    class="form-check-label" for="hc_ur_2">Burning sensation on
                                                    urination</label></div>

                                            <h6 class="mt-3">NERVOUS SYSTEM</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[nervous][]" value="Headache" id="hc_nerv_1"
                                                    <?php echo hc_checked('nervous', 'Headache'); ?>><label
                                                    class="form-check-label" for="hc_nerv_1">Headache</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[nervous][]" value="Convulsion/epilepsy"
                                                    id="hc_nerv_2" <?php echo hc_checked('nervous', 'Convulsion/epilepsy'); ?>><label
                                                    class="form-check-label" for="hc_nerv_2">Convulsion/epilepsy</label>
                                            </div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[nervous][]" value="Numbness/Tingling"
                                                    id="hc_nerv_3" <?php echo hc_checked('nervous', 'Numbness/Tingling'); ?>><label class="form-check-label"
                                                    for="hc_nerv_3">Numbness/Tingling</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[nervous][]" value="Dizziness/Fainting"
                                                    id="hc_nerv_4" <?php echo hc_checked('nervous', 'Dizziness/Fainting'); ?>><label
                                                    class="form-check-label" for="hc_nerv_4">Dizziness/Fainting</label>
                                            </div>

                                            <h6 class="mt-3">BLOOD</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[blood][]" value="Bruise easily"
                                                    id="hc_blood_1" <?php echo hc_checked('blood', 'Bruise easily'); ?>><label class="form-check-label" for="hc_blood_1">Bruise
                                                    easily</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[blood][]" value="Anemia" id="hc_blood_2"
                                                    <?php echo hc_checked('blood', 'Anemia'); ?>><label
                                                    class="form-check-label" for="hc_blood_2">Anemia</label></div>

                                            <h6 class="mt-3">RESPIRATORY</h6>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[respiratory][]" value="Persistent cough"
                                                    id="hc_resp_1" <?php echo hc_checked('respiratory', 'Persistent cough'); ?>><label class="form-check-label"
                                                    for="hc_resp_1">Persistent cough</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[respiratory][]"
                                                    value="Difficulty in breathing" id="hc_resp_2" <?php echo hc_checked('respiratory', 'Difficulty in breathing'); ?>><label
                                                    class="form-check-label" for="hc_resp_2">Difficulty in
                                                    breathing</label></div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox"
                                                    name="health_conditions[respiratory][]" value="Asthma"
                                                    id="hc_resp_3" <?php echo hc_checked('respiratory', 'Asthma'); ?>><label class="form-check-label" for="hc_resp_3">Asthma</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="save_health" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script src="vendors/js/vendor.bundle.base.js"></script>
                <script src="js/off-canvas.js"></script>
                <script src="js/misc.js"></script>
                <script>
                    (function () {
                        var btn = document.getElementById('btnEditHealth');
                        if (!btn) return;
                        btn.addEventListener('click', function () {
                            if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                                $('#adminHealthModal').modal('show');
                            } else if (typeof bootstrap !== 'undefined') {
                                var myModal = new bootstrap.Modal(document.getElementById('adminHealthModal'));
                                myModal.show();
                            } else {
                                // fallback: toggle class to show (minimal)
                                var el = document.getElementById('adminHealthModal');
                                if (el) el.style.display = 'block';
                            }
                        });
                    })();
                </script>
            </div>
</body>

</html>