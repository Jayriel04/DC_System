<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

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


$sql = "SELECT * FROM tblpatient WHERE number = :num";
$query = $dbh->prepare($sql);
$query->bindParam(':num', $stdid, PDO::PARAM_INT);
$query->execute();
$row = $query->fetch(PDO::FETCH_OBJ);

if (!$row) {
    echo "<p style='color:red'>Patient not found.</p>";
    exit();
}

$patient_fullname = trim((isset($row->firstname) ? $row->firstname : '') . ' ' . (isset($row->surname) ? $row->surname : ''));
if (empty($patient_fullname)) {
    if (!empty($row->username)) {
        $patient_fullname = $row->username;
    } elseif (!empty($row->email)) {
        $patient_fullname = $row->email;
    } else {
        $patient_fullname = 'Unknown Patient';
    }
}

$health_arr = [];
if (!empty($row->health_conditions) && $row->health_conditions !== 'null' && $row->health_conditions !== '[]') {
    $decoded = json_decode($row->health_conditions, true);
    if (is_array($decoded)) {
        $health_arr = $decoded;
    }
}

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

// save health conditions 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_health'])) {
    $post_health = isset($_POST['health_conditions']) ? $_POST['health_conditions'] : [];
    $hc_json = json_encode($post_health);

    $sqlUpd = "UPDATE tblpatient SET health_conditions = :hc WHERE number = :num";
    $qryUpd = $dbh->prepare($sqlUpd);
    $qryUpd->bindParam(':hc', $hc_json, PDO::PARAM_STR);
    $qryUpd->bindParam(':num', $stdid, PDO::PARAM_INT);
    if ($qryUpd->execute()) {
        $_SESSION['modal_success'] = 'Health info saved.';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/view-er-modal.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/stylev2.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="header">
                        <div class="header-text">
                            <h2>Examination Records</h2>
                            <p>Manage your medical records</p>
                        </div>
                        <a href="manage-patient.php" class="btn-add btn-back-small" style="text-decoration: none;"><i
                                class="fas fa-reply"></i> Back</a>
                    </div>
                    <div class="er-container">
                        <div class="er-header">
                            <h1>Patient <span class="patient-name" style="font-size: 25px">:
                                    <?php echo htmlspecialchars($patient_fullname); ?></span></h1>
                            <button class="edit-btn" id="btnEditHealth" aria-label="Edit Examination Records"
                                style="padding: 8px 20px;background-color: white;border: 1px solid #e2e8f0;border-radius: 6px;cursor: pointer;display: flex;align-items: center;gap: 8px;color: #64748b;font-size: 14px;transition: all 0.3s;background-color: #f8fafc;border-color: #cbd5e1;">
                                <i class="fas fa-edit edit-icon" aria-hidden="true"></i>
                                Edit
                            </button>
                        </div>

                        <?php if (isset($_SESSION['modal_success'])) { ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($_SESSION['modal_success']); ?>
                            </div>
                            <?php unset($_SESSION['modal_success']);
                        } ?>

                        <table class="er-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($health_arr)) { ?>
                                    <?php foreach ($health_arr as $cat => $vals) {
                                        if (is_array($vals)) {
                                            $vals_disp = implode(', ', $vals);
                                        } else {
                                            $vals_disp = (string) $vals;
                                        }
                                        // Skip empty values
                                        if (empty(trim($vals_disp)))
                                            continue;
                                        ?>
                                        <tr>
                                            <td class="category-cell">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $cat)); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($vals_disp); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center;">No health information on file for this
                                            patient.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>

                <!-- Admin Health Modal -->
                <div id="medicalHistoryModal" class="modal">
                    <div class="modal-content health-questionnaire-modal">
                        <div class="modal-header">
                            <h2 class="modal-title">Edit Examination Records</h2>
                            <span class="close" data-dismiss="modal">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="view-er.php?stid=<?php echo $stdid; ?>" id="medicalHistoryForm">
                                <p class="instruction">Please check all conditions that apply to the patient.</p>
                                <div class="form-container">
                                    
                                    <div>
                                        <div class="section general">
                                            <div class="section-title">General</div>
                                            <div class="checkbox-item"><label for="hc-general-1">Marked weight
                                                    change</label><input type="checkbox" id="hc-general-1"
                                                    name="health_conditions[general][]" value="Marked weight change"
                                                    <?php echo hc_checked('general', 'Marked weight change'); ?>></div>
                                        </div>
                                        <div class="section ear">
                                            <div class="section-title">Ear</div>
                                            <div class="checkbox-item"><label for="hc-ear-1">Loss of hearing, ringing of
                                                    ears</label><input type="checkbox" id="hc-ear-1"
                                                    name="health_conditions[ear][]"
                                                    value="Loss of hearing, ringing of ears" <?php echo hc_checked('ear', 'Loss of hearing, ringing of ears'); ?>></div>
                                        </div>
                                        <div class="section nervous">
                                            <div class="section-title">Nervous System</div>
                                            <div class="checkbox-item"><label for="hc-nervous-1">Headache</label><input
                                                    type="checkbox" id="hc-nervous-1"
                                                    name="health_conditions[nervous][]" value="Headache" <?php echo hc_checked('nervous', 'Headache'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-nervous-2">Convulsion /
                                                    epilepsy</label><input type="checkbox" id="hc-nervous-2"
                                                    name="health_conditions[nervous][]" value="Convulsion/epilepsy"
                                                    <?php echo hc_checked('nervous', 'Convulsion/epilepsy'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-nervous-3">Numbness /
                                                    Tingling</label><input type="checkbox" id="hc-nervous-3"
                                                    name="health_conditions[nervous][]" value="Numbness/Tingling" <?php echo hc_checked('nervous', 'Numbness/Tingling'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-nervous-4">Dizziness /
                                                    Fainting</label><input type="checkbox" id="hc-nervous-4"
                                                    name="health_conditions[nervous][]" value="Dizziness/Fainting" <?php echo hc_checked('nervous', 'Dizziness/Fainting'); ?>></div>
                                        </div>
                                        <div class="section blood">
                                            <div class="section-title">Blood</div>
                                            <div class="checkbox-item"><label for="hc-blood-1">Bruise
                                                    easily</label><input type="checkbox" id="hc-blood-1"
                                                    name="health_conditions[blood][]" value="Bruise easily" <?php echo hc_checked('blood', 'Bruise easily'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-blood-2">Anemia</label><input
                                                    type="checkbox" id="hc-blood-2" name="health_conditions[blood][]"
                                                    value="Anemia" <?php echo hc_checked('blood', 'Anemia'); ?>></div>
                                        </div>
                                        <div class="section respiratory">
                                            <div class="section-title">Respiratory</div>
                                            <div class="checkbox-item"><label for="hc-respiratory-1">Persistent
                                                    cough</label><input type="checkbox" id="hc-respiratory-1"
                                                    name="health_conditions[respiratory][]" value="Persistent cough"
                                                    <?php echo hc_checked('respiratory', 'Persistent cough'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-respiratory-2">Difficulty in
                                                    breathing</label><input type="checkbox" id="hc-respiratory-2"
                                                    name="health_conditions[respiratory][]"
                                                    value="Difficulty in breathing" <?php echo hc_checked('respiratory', 'Difficulty in breathing'); ?>>
                                            </div>
                                            <div class="checkbox-item"><label
                                                    for="hc-respiratory-3">Asthma</label><input type="checkbox"
                                                    id="hc-respiratory-3" name="health_conditions[respiratory][]"
                                                    value="Asthma" <?php echo hc_checked('respiratory', 'Asthma'); ?>>
                                            </div>
                                        </div>
                                        <div class="section heart">
                                            <div class="section-title">Heart</div>
                                            <div class="checkbox-item"><label for="hc-heart-1">Chest
                                                    pain/discomfort</label><input type="checkbox" id="hc-heart-1"
                                                    name="health_conditions[heart][]" value="Chest pain/discomfort"
                                                    <?php echo hc_checked('heart', 'Chest pain/discomfort'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-heart-2">Shortness of
                                                    breath</label><input type="checkbox" id="hc-heart-2"
                                                    name="health_conditions[heart][]" value="Shortness of breath" <?php echo hc_checked('heart', 'Shortness of breath'); ?>></div>
                                            <div class="checkbox-item"><label
                                                    for="hc-heart-3">Hypertension</label><input type="checkbox"
                                                    id="hc-heart-3" name="health_conditions[heart][]"
                                                    value="Hypertension" <?php echo hc_checked('heart', 'Hypertension'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-heart-4">Ankle edema</label><input
                                                    type="checkbox" id="hc-heart-4" name="health_conditions[heart][]"
                                                    value="Ankle edema" <?php echo hc_checked('heart', 'Ankle edema'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-heart-5">Rheumatic fever
                                                    (age)</label><input type="checkbox" id="hc-heart-5"
                                                    name="health_conditions[heart][]" value="Rheumatic fever" <?php echo hc_checked('heart', 'Rheumatic fever'); ?>></div>
                                            <input type="text" class="specify-input" placeholder="Specify age"
                                                name="health_conditions[rheumatic_age]"
                                                value="<?php echo hc_text('rheumatic_age'); ?>">
                                            <div class="checkbox-item"><label for="hc-heart-6">History of stroke
                                                    (When)</label><input type="checkbox" id="hc-heart-6"
                                                    name="health_conditions[heart][]" value="History of stroke" <?php echo hc_checked('heart', 'History of stroke'); ?>></div>
                                            <input type="text" class="specify-input" placeholder="When"
                                                name="health_conditions[stroke_when]"
                                                value="<?php echo hc_text('stroke_when'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div class="section urinary">
                                            <div class="section-title">Urinary</div>
                                            <div class="checkbox-item"><label for="hc-urinary-1">Increase frequency of
                                                    urination</label><input type="checkbox" id="hc-urinary-1"
                                                    name="health_conditions[urinary][]"
                                                    value="Increase frequency of urination" <?php echo hc_checked('urinary', 'Increase frequency of urination'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-urinary-2">Burning sensation on
                                                    urination</label><input type="checkbox" id="hc-urinary-2"
                                                    name="health_conditions[urinary][]"
                                                    value="Burning sensation on urination" <?php echo hc_checked('urinary', 'Burning sensation on urination'); ?>></div>
                                        </div>
                                        <div class="section liver">
                                            <div class="section-title">Liver</div>
                                            <div class="checkbox-item"><label for="hc-liver-1">History of liver
                                                    ailment</label><input type="checkbox" id="hc-liver-1"
                                                    name="health_conditions[liver][]" value="History of liver ailment"
                                                    <?php echo hc_checked('liver', 'History of liver ailment'); ?>>
                                            </div>
                                            <input type="text" class="specify-input" placeholder="Specify"
                                                name="health_conditions[liver_specify]"
                                                value="<?php echo hc_text('liver_specify'); ?>">
                                            <div class="checkbox-item"><label for="hc-liver-2">Jaundice</label><input
                                                    type="checkbox" id="hc-liver-2" name="health_conditions[liver][]"
                                                    value="Jaundice" <?php echo hc_checked('liver', 'Jaundice'); ?>>
                                            </div>
                                        </div>
                                        <div class="section diabetes">
                                            <div class="section-title">Diabetes</div>
                                            <div class="checkbox-item"><label for="hc-diabetes-1">Delayed healing of
                                                    wounds</label><input type="checkbox" id="hc-diabetes-1"
                                                    name="health_conditions[diabetes][]"
                                                    value="Delayed healing of wounds" <?php echo hc_checked('diabetes', 'Delayed healing of wounds'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-diabetes-2">Increase intake of
                                                    food or water</label><input type="checkbox" id="hc-diabetes-2"
                                                    name="health_conditions[diabetes][]"
                                                    value="Increase intake of food or water" <?php echo hc_checked('diabetes', 'Increase intake of food or water'); ?>>
                                            </div>
                                            <div class="checkbox-item"><label for="hc-diabetes-3">Family history of
                                                    diabetes</label><input type="checkbox" id="hc-diabetes-3"
                                                    name="health_conditions[diabetes][]"
                                                    value="Family history of diabetes" <?php echo hc_checked('diabetes', 'Family history of diabetes'); ?>>
                                            </div>
                                        </div>
                                        <div class="section thyroid">
                                            <div class="section-title">Thyroid</div>
                                            <div class="checkbox-item"><label for="hc-thyroid-1">Perspire
                                                    easily</label><input type="checkbox" id="hc-thyroid-1"
                                                    name="health_conditions[thyroid][]" value="Perspire easily" <?php echo hc_checked('thyroid', 'Perspire easily'); ?>></div>
                                            <div class="checkbox-item"><label
                                                    for="hc-thyroid-2">Apprehension</label><input type="checkbox"
                                                    id="hc-thyroid-2" name="health_conditions[thyroid][]"
                                                    value="Apprehension" <?php echo hc_checked('thyroid', 'Apprehension'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-thyroid-3">Palpitation/rapid heart
                                                    beat</label><input type="checkbox" id="hc-thyroid-3"
                                                    name="health_conditions[thyroid][]"
                                                    value="Palpation/rapid heart beat" <?php echo hc_checked('thyroid', 'Palpation/rapid heart beat'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-thyroid-4">Goiter</label><input
                                                    type="checkbox" id="hc-thyroid-4"
                                                    name="health_conditions[thyroid][]" value="Goiter" <?php echo hc_checked('thyroid', 'Goiter'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-thyroid-5">Bulging of
                                                    eyes</label><input type="checkbox" id="hc-thyroid-5"
                                                    name="health_conditions[thyroid][]" value="Bulging of eyes" <?php echo hc_checked('thyroid', 'Bulging of eyes'); ?>></div>
                                        </div>
                                        <div class="section arthritis">
                                            <div class="section-title">Arthritis</div>
                                            <div class="checkbox-item"><label for="hc-arthritis-1">Joint
                                                    pain</label><input type="checkbox" id="hc-arthritis-1"
                                                    name="health_conditions[arthritis][]" value="Joint pain" <?php echo hc_checked('arthritis', 'Joint pain'); ?>></div>
                                            <div class="checkbox-item"><label for="hc-arthritis-2">Joint
                                                    Swelling</label><input type="checkbox" id="hc-arthritis-2"
                                                    name="health_conditions[arthritis][]" value="Joint Swelling" <?php echo hc_checked('arthritis', 'Joint Swelling'); ?>></div>
                                        </div>
                                        <div class="section radiograph">
                                            <div class="section-title">Radiograph</div>
                                            <div class="checkbox-item"><label for="hc-radiograph-1">Undergo radiation
                                                    therapy</label><input type="checkbox" id="hc-radiograph-1"
                                                    name="health_conditions[radiograph][]"
                                                    value="Undergo radiation therapy" <?php echo hc_checked('radiograph', 'Undergo radiation therapy'); ?>>
                                            </div>
                                        </div>
                                        <div class="section women">
                                            <div class="section-title">Women</div>
                                            <div class="checkbox-item"><label for="hc-women-1">Pregnancy (No. of
                                                    month)</label><input type="checkbox" id="hc-women-1"
                                                    name="health_conditions[women][]" value="Pregnancy" <?php echo hc_checked('women', 'Pregnancy'); ?>></div>
                                            <input type="number" class="specify-input" placeholder="Number of months"
                                                name="health_conditions[pregnancy_months]" min="1" max="9"
                                                value="<?php echo hc_text('pregnancy_months'); ?>">
                                            <div class="checkbox-item"><label for="hc-women-2">Breast feed</label><input
                                                    type="checkbox" id="hc-women-2" name="health_conditions[women][]"
                                                    value="Breast feed" <?php echo hc_checked('women', 'Breast feed'); ?>></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="section hospitalization">
                                    <div class="section-title">Hospitalization</div>
                                    <div class="checkbox-item"><label for="hc-hosp-1">Have you been
                                            hospitalized</label><input type="checkbox" id="hc-hosp-1"
                                            name="health_conditions[hospitalization][]" value="Hospitalized" <?php echo hc_checked('hospitalization', 'Hospitalized'); ?>></div>
                                    <label>Date:</label><input type="date" class="specify-input"
                                        name="health_conditions[hospitalization_date]"
                                        value="<?php echo hc_text('hospitalization_date'); ?>">
                                    <label>Specify:</label><input type="text" class="specify-input"
                                        name="health_conditions[hospitalization_specify]"
                                        placeholder="Please specify reason"
                                        value="<?php echo hc_text('hospitalization_specify'); ?>">
                                </div>

                                <div class="section allergy">
                                    <div class="allergy-title">Are you allergic or have ever experienced any reaction to
                                        the ff?
                                    </div>
                                    <div class="checkbox-item"><label for="hc-allergy-1">Sleeping pills</label><input
                                            type="checkbox" id="hc-allergy-1" name="health_conditions[allergies][]"
                                            value="Sleeping pills" <?php echo hc_checked('allergies', 'Sleeping pills'); ?>></div>
                                    <div class="checkbox-item"><label for="hc-allergy-2">Aspirin</label><input
                                            type="checkbox" id="hc-allergy-2" name="health_conditions[allergies][]"
                                            value="Aspirin" <?php echo hc_checked('allergies', 'Aspirin'); ?>></div>
                                    <div class="checkbox-item"><label for="hc-allergy-3">Food</label><input
                                            type="checkbox" id="hc-allergy-3" name="health_conditions[allergies][]"
                                            value="Food" <?php echo hc_checked('allergies', 'Food'); ?>></div>
                                    <div class="checkbox-item"><label for="hc-allergy-4">Penicillin/other
                                            antibiotics</label><input type="checkbox" id="hc-allergy-4"
                                            name="health_conditions[allergies][]" value="Penicillin/other antibiotics"
                                            <?php echo hc_checked('allergies', 'Penicillin/other antibiotics'); ?>>
                                    </div>
                                    <div class="checkbox-item"><label for="hc-allergy-5">Sulfa Drugs</label><input
                                            type="checkbox" id="hc-allergy-5" name="health_conditions[allergies][]"
                                            value="Sulfa Drugs" <?php echo hc_checked('allergies', 'Sulfa Drugs'); ?>>
                                    </div>
                                    <div class="checkbox-item"><label for="hc-allergy-6">Others</label><input
                                            type="checkbox" id="hc-allergy-6" name="health_conditions[allergies][]"
                                            value="Others" <?php echo hc_checked('allergies', 'Others'); ?>></div>
                                    <input type="text" class="specify-input" name="health_conditions[allergy_specify]"
                                        placeholder="Please specify other allergies"
                                        value="<?php echo hc_text('allergy_specify'); ?>">
                                </div>

                                <div class="section extraction">
                                    <div class="section-title">Previous Extraction History</div>
                                    <div class="checkbox-item"><label for="hc-ext-1">Have you had any previous
                                            extraction</label><input type="checkbox" id="hc-ext-1"
                                            name="health_conditions[extraction][]" value="Previous extraction" <?php echo hc_checked('extraction', 'Previous extraction'); ?>></div>
                                    <label>Date of last extraction:</label><input type="date" class="specify-input"
                                        name="health_conditions[extraction_date]"
                                        value="<?php echo hc_text('extraction_date'); ?>">
                                    <label>Specify:</label><textarea class="specify-input"
                                        name="health_conditions[extraction_specify]" rows="2"
                                        placeholder="Please provide details"><?php echo hc_text('extraction_specify'); ?></textarea>
                                    <div class="checkbox-item"><label for="hc-ext-2">Untoward reaction to
                                            extraction</label><input type="checkbox" id="hc-ext-2"
                                            name="health_conditions[extraction][]"
                                            value="Untoward reaction to extraction" <?php echo hc_checked('extraction', 'Untoward reaction to extraction'); ?>></div>
                                    <input type="text" class="specify-input"
                                        name="health_conditions[extraction_reaction_specify]"
                                        placeholder="Please specify reaction"
                                        value="<?php echo hc_text('extraction_reaction_specify'); ?>">
                                    <div class="checkbox-item"><label for="hc-ext-3">Were you under local
                                            anesthesia</label><input type="checkbox" id="hc-ext-3"
                                            name="health_conditions[extraction][]" value="Under local anesthesia" <?php echo hc_checked('extraction', 'Under local anesthesia'); ?>></div>
                                    <div class="checkbox-item"><label for="hc-ext-4">Allergic reaction to local
                                            anesthesia</label><input type="checkbox" id="hc-ext-4"
                                            name="health_conditions[extraction][]"
                                            value="Allergic reaction to local anesthesia" <?php echo hc_checked('extraction', 'Allergic reaction to local anesthesia'); ?>></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="save_health" class="btn btn-primary"
                                        style="background-color: #008779 !important;color: white;">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <script src="vendors/js/vendor.bundle.base.js"></script>
            <script src="js/off-canvas.js"></script>
            <script src="js/misc.js"></script>
            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                    const modal = document.getElementById('medicalHistoryModal');
                    if (!modal) return;

                    const openBtn = document.getElementById('btnEditHealth');
                    const closeBtn = modal.querySelector('.close');

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
                    window.addEventListener('click', function (event) {
                        if (event.target === modal) closeModal();
                    });
                    });
            </script>
</body>

</html>