<?php
session_start();

error_reporting(0);
include ('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid'] == 0)) {
  header('location:logout.php');
} else {
  if (isset($_POST['submit']))
  {
    $adminid=$_SESSION['sturecmsaid'];
    $AName=$_POST['adminname'];
  $mobno=$_POST['mobilenumber'];
  $email=$_POST['email'];
  $sql="update tbladmin set AdminName=:adminname,MobileNumber=:mobilenumber,Email=:email where ID=:aid";
     $query = $dbh->prepare($sql);
    $query->bindParam(':adminname', $AName, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobilenumber', $mobno, PDO::PARAM_STR);
    $query->bindParam(':aid', $adminid, PDO::PARAM_STR);
    $query->execute();

    $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Your profile has been updated.'];
    header('Location: profile.php');

  }
  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
   
    <title>Admin Profile</title>
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
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/profile.css" />
    <link rel="stylesheet" href="css/modal.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/toast.css" />
    
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
     <?php include_once('includes/header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
      <?php include_once('includes/sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
          <div class="main-content">
              <div id="toast-container"></div>
                <?php
                if (isset($_SESSION['toast_message'])) {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                    unset($_SESSION['toast_message']);
                }
                ?>
              <h1 class="page-title">My Profile</h1>
              <?php
              $adminid = $_SESSION['sturecmsaid'];
              $sql = "SELECT * from tbladmin where ID=:aid";
              $query = $dbh->prepare($sql);
              $query->bindParam(':aid', $adminid, PDO::PARAM_STR);
              $query->execute();
              $row = $query->fetch(PDO::FETCH_OBJ);
              if ($row) {
                $initials = strtoupper(substr($row->AdminName, 0, 2));
                ?>
              <div class="profile-header">
                  <div class="profile-info">
                      <div class="profile-avatar">
                          <span><?php echo htmlentities($initials); ?></span>
                      </div>
                      <div class="profile-details">
                          <h2><?php echo htmlentities($row->AdminName); ?></h2>
                          <p>Admin</p>
                          <p><?php echo htmlentities($row->Email); ?></p>
                      </div>
                  </div>
                  <button class="edit-btn" id="editProfileBtn">
                      <span>Edit</span>
                      <svg class="edit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                      </svg>
                  </button>
              </div>

              <div class="section profile-display">
                  <div class="section-header">
                      <h3 class="section-title">Personal Information</h3>
                  </div>
                  <div class="form-row">
                      <div class="form-group">
                          <label>Admin Name</label>
                          <div class="value"><?php echo htmlentities($row->AdminName); ?></div>
                      </div>
                       <div class="form-group">
                          <label>User Name</label>
                          <div class="value"><?php echo htmlentities($row->UserName); ?></div>
                      </div>
                  </div>
                  <div class="form-row">
                      <div class="form-group">
                          <label>Email address</label>
                          <div class="value"><?php echo htmlentities($row->Email); ?></div>
                      </div>
                      <div class="form-group">
                          <label>Contact Number</label>
                          <div class="value"><?php echo htmlentities($row->MobileNumber); ?></div>
                      </div>
                  </div>
                   <div class="form-row">
                      <div class="form-group">
                          <label>Admin Registration Date</label>
                          <div class="value"><?php echo htmlentities($row->AdminRegdate); ?></div>
                      </div>
                  </div>
              </div>

              <?php } ?>
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
         <?php include_once('includes/footer.php');?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <?php if ($row) { ?>
    <div id="editProfileModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-button">&times;</button>
            </div>
            <form class="forms-sample" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="adminname">Admin Name</label>
                        <input type="text" name="adminname" value="<?php echo htmlentities($row->AdminName); ?>" class="form-control" required='true'>
                    </div>
                    <div class="form-group">
                        <label for="username">User Name</label>
                        <input type="text" name="username" value="<?php echo htmlentities($row->UserName); ?>" class="form-control" readonly="">
                    </div>
                    <div class="form-group">
                        <label for="mobilenumber">Contact Number</label>
                        <input type="text" name="mobilenumber" value="<?php echo htmlentities($row->MobileNumber); ?>" class="form-control" maxlength='11' required='true' pattern="[0-9]+">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" value="<?php echo htmlentities($row->Email); ?>" class="form-control" required='true'>
                    </div>
                    <div class="form-group">
                        <label for="regdate">Admin Registration Date</label>
                        <input type="text" name="regdate" value="<?php echo htmlentities($row->AdminRegdate); ?>" readonly="" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" class="btn btn-update" name="submit"style=" background-color: #008779 !important; color: white;">Update</button>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>
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
    <script src="js/toast.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editProfileBtn = document.getElementById('editProfileBtn');
            const modal = document.getElementById('editProfileModal');
            const closeBtn = modal.querySelector('.close-button');
            const cancelBtn = modal.querySelector('.btn-cancel');

            function openModal() {
                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            editProfileBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
    <!-- End custom js for this page -->
  </body>
</html><?php }  ?>