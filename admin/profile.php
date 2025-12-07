<?php
session_start();
error_reporting(0);
include ('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid'] == 0)) {
  header('location:logout.php');
} else {
  if (isset($_POST['update_profile']))
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
    echo '<script>window.location.href="profile.php"</script>';
    exit();
  }
  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Admin Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/toast.css" />
    <link rel="stylesheet" href="css/mas-modal.css">
    <link rel="stylesheet" href="css/stylev2.css" />
  </head>
  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper" style="padding: 0.75rem 1.5rem 0;">
            <div class="container">
                <div id="toast-container"></div>
                  <?php
                  if (isset($_SESSION['toast_message'])) {
                      echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                      unset($_SESSION['toast_message']);
                  }
                  
                  $adminid = $_SESSION['sturecmsaid'];
                  $sql = "SELECT * from tbladmin where ID=:aid";
                  $query = $dbh->prepare($sql);
                  $query->bindParam(':aid', $adminid, PDO::PARAM_STR);
                  $query->execute();
                  $row = $query->fetch(PDO::FETCH_OBJ);
                  if ($row) {
                    $initials = "";
                    $name_parts = explode(" ", $row->AdminName);
                    if(count($name_parts) > 1) {
                        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
                    } else {
                        $initials = strtoupper(substr($row->AdminName, 0, 2));
                    }
                  ?>
                <!-- Tabs -->
                <div class="tabs">
                    <div class="tab active">
                        <i class="fas fa-user"></i>
                        <span style="color: black;">Personal Information</span>
                    </div>
                </div>

                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="avatar"><?php echo htmlentities($initials); ?></div>
                        <div class="profile-details">
                            <h1><?php echo htmlentities($row->AdminName); ?></h1>
                            <p>Administrator</p>
                            <div class="status">
                                <span class="status-dot"></span>
                                <span>Active</span>
                                <span style="color: #999;">Member since <?php echo date("F Y", strtotime($row->AdminRegdate)); ?></span>
                            </div>
                        </div>
                    </div>
                    <button class="edit-btn" id="editProfileBtn">
                        <span>✏️</span>
                        <span>Edit Profile</span>
                    </button>
                </div>

                <!-- Profile Form -->
                <div class="form-grid">
                    <div class="form-group">
                        <h3>Admin Name</h3>
                        <div class="profile-value"><?php echo htmlentities($row->AdminName); ?></div>
                    </div>
                    <div class="form-group">
                        <h3>User Name</h3>
                        <div class="profile-value"><?php echo htmlentities($row->UserName); ?></div>
                    </div>
                    <div class="form-group">
                        <h3>Email Address</h3>
                        <div class="profile-value"><?php echo htmlentities($row->Email); ?></div>
                    </div>
                    <div class="form-group">
                        <h3>Phone Number</h3>
                        <div class="profile-value"><?php echo htmlentities($row->MobileNumber); ?></div>
                    </div>
                    <div class="form-group full-width">
                        <h3>Admin Registration Date</h3>
                        <div class="profile-value"><?php echo htmlentities($row->AdminRegdate); ?></div>
                    </div>
                </div>
                <?php } ?>
            </div>
          </div>
          <?php include_once('includes/footer.php');?>
        </div>
      </div>
    </div>

    <?php if ($row) { ?>
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editProfileForm" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="adminname">Admin Name</label>
                        <input type="text" id="adminname" name="adminname" value="<?php echo htmlentities($row->AdminName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">User Name</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlentities($row->UserName); ?>" readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlentities($row->Email); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobilenumber">Phone Number</label>
                            <input type="tel" id="mobilenumber" name="mobilenumber" value="<?php echo htmlentities($row->MobileNumber); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-update">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/toast.js"></script>
    <script>
        
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editModal');
        const openBtn = document.getElementById('editProfileBtn');
        const closeBtn = editModal.querySelector('.close-button');
        const cancelBtn = editModal.querySelector('.btn-cancel');

        openBtn.addEventListener('click', () => { editModal.style.display = 'flex'; });
        closeBtn.addEventListener('click', () => { editModal.style.display = 'none'; });
        cancelBtn.addEventListener('click', () => { editModal.style.display = 'none'; });

        window.addEventListener('click', function (event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });
    });
    </script>
  </body>
</html><?php }  ?>