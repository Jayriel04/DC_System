<?php session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsstuid'] == 0)) {
  // The session variable seems to be 'sturecmsnumber' from login.php, let's use that for consistency.
  if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
  }
} else {
  if (isset($_POST['save_health_conditions'])) {
    // Use the correct session variable for the patient's number
    $patient_number = $_SESSION['sturecmsnumber'];

    // Collect and serialize health conditions
    $health_conditions = isset($_POST['health_conditions']) ? $_POST['health_conditions'] : [];
    $health_conditions_json = json_encode($health_conditions);

    // Update the tblpatient record
    $sql = "UPDATE tblpatient SET health_conditions = :health_conditions WHERE number = :patient_number";
    $query = $dbh->prepare($sql);
    $query->bindParam(':health_conditions', $health_conditions_json, PDO::PARAM_STR);
    $query->bindParam(':patient_number', $patient_number, PDO::PARAM_INT);

    if ($query->execute()) {
      echo "<script>alert('Health conditions saved successfully.');</script>";
      echo "<script>window.location.href ='dashboard.php'</script>";
    } else {
      echo "<script>alert('Could not save health conditions. Please try again.');</script>";
    }
  }
  ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>

    <title>Dental Clinic || Health History Form</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <style>
      .health-section {
        margin-bottom: 1.5rem;
      }

      .health-section h5 {
        font-weight: bold;
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
        margin-bottom: 10px;
      }

      .form-check-label {
        margin-left: 5px;
      }
    </style>
    <link rel="stylesheet" href="css/style.css" />

  </head>

  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <?php include_once('includes/header.php'); ?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include_once('includes/sidebar.php'); ?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Health History Form </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page"> Health History Form</li>
                </ol>
              </nav>
            </div>
            <div class="row">

              <div class="col-12 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title text-center">Please check all conditions that apply to you.</h4>
                    <hr>
                    <form method="post">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="health-section">
                            <h5>GENERAL</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[general][]" value="Marked weight change"> Marked weight
                                change</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[general][]" value="Increase frequency of urination"> Increase
                                frequency of urination</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[general][]" value="Burning sensation on urination"> Burning
                                sensation on urination</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[general][]" value="Loss of hearing, ringing of ears"> Loss of
                                hearing, ringing of ears</label></div>
                          </div>

                          <div class="health-section">
                            <h5>LIVER</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[liver][]" value="History of liver ailment"> History of liver
                                ailment</label></div>
                            <div class="form-group">
                              <label for="liver_specify">Specify:</label>
                              <input type="text" class="form-control" name="health_conditions[liver_specify]"
                                id="liver_specify">
                            </div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[liver][]" value="Jaundice"> Jaundice</label></div>
                          </div>

                          <div class="health-section">
                            <h5>DIABETES</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[diabetes][]" value="Delayed healing of wounds"> Delayed healing
                                of wounds</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[diabetes][]" value="Increase intake of food or water"> Increase
                                intake of food or water</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[diabetes][]" value="Family history of diabetes"> Family history
                                of diabetes</label></div>
                          </div>

                          <div class="health-section">
                            <h5>THYROID</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[thyroid][]" value="Perspire easily"> Perspire easily</label>
                            </div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[thyroid][]" value="Apprehension"> Apprehension</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[thyroid][]" value="Palpation/rapid heart beat"> Palpation/rapid
                                heart beat</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[thyroid][]" value="Goiter"> Goiter</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[thyroid][]" value="Bulging of eyes"> Bulging of eyes</label>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="health-section">
                            <h5>URINARY</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[urinary][]" value="Increase frequency of urination"> Increase
                                frequency of urination</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[urinary][]" value="Burning sensation on urination"> Burning
                                sensation on urination</label></div>
                          </div>

                          <div class="health-section">
                            <h5>NERVOUS SYSTEM</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[nervous][]" value="Headache"> Headache</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[nervous][]" value="Convulsion/epilepsy">
                                Convulsion/epilepsy</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[nervous][]" value="Numbness/Tingling"> Numbness/Tingling</label>
                            </div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[nervous][]" value="Dizziness/Fainting">
                                Dizziness/Fainting</label></div>
                          </div>

                          <div class="health-section">
                            <h5>BLOOD</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[blood][]" value="Bruise easily"> Bruise easily</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[blood][]" value="Anemia"> Anemia</label></div>
                          </div>

                          <div class="health-section">
                            <h5>RESPIRATORY</h5>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[respiratory][]" value="Persistent cough"> Persistent
                                cough</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[respiratory][]" value="Difficulty in breathing"> Difficulty in
                                breathing</label></div>
                            <div class="form-check"><label class="form-check-label"><input type="checkbox"
                                  name="health_conditions[respiratory][]" value="Asthma"> Asthma</label></div>
                          </div>
                        </div>
                      </div>
                      <div class="mt-3">
                        <button class="btn btn-success btn-block" name="save_health_conditions" type="submit">Save Health
                          Conditions</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <?php include_once('includes/footer.php'); ?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <!-- End custom js for this page -->
  </body>

  </html><?php } ?>