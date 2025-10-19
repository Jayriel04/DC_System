<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Message variable for feedback
$message = '';
if(isset($_POST['submit'])) {
  $email = $_POST['email'];
  // Check if email exists in database
  $sql = "SELECT id, username FROM tblpatient WHERE username=:email";
  $query = $dbh->prepare($sql);
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->execute();
  if($query->rowCount() > 0) {
    // Generate temporary password
    $temp_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
    $hashed_password = md5($temp_password);
    // Update password in database
    $update_sql = "UPDATE tblpatient SET password=:password WHERE username=:email";
    $update_query = $dbh->prepare($update_sql);
    $update_query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $update_query->bindParam(':email', $email, PDO::PARAM_STR);
    if($update_query->execute()) {
      $message = '<div class="alert alert-success text-center">A temporary password has been generated: <strong>' . $temp_password . '</strong><br>Please login with this password and change it immediately.</div>';
    } else {
      $message = '<div class="alert alert-danger text-center">Something went wrong. Please try again.</div>';
    }
  } else {
    $message = '<div class="alert alert-warning text-center">Email address not found in our records.</div>';
  }
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
  
  <title>Forgot Password</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css">
   <script type="text/javascript">
function valid()
{
if(document.chngpwd.newpassword.value!= document.chngpwd.confirmpassword.value)
{
alert("New Password and Confirm Password Field do not match  !!");
document.chngpwd.confirmpassword.focus();
return false;
}
return true;
}
</script>
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
          <div class="auth-form-light text-left p-5">
          <?php if(!empty($message)) echo $message; ?>
                <div class="text-center mb-4">
                    <i class="icon-lock text-primary" style="font-size: 50px;"></i>
                    <h4 class="mt-3">Reset Password</h4>
                    <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                </div>
          <form class="pt-3" name="chngpwd" method="post">
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" class="form-control form-control-lg" style="border-radius: 10px;" placeholder="Enter your email" required name="email">
            </div>
            <div class="mt-3">
              <button type="submit" name="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" style="border-radius: 10px;">Send Reset Link</button>
            </div>
            <div class="text-center mt-4 font-weight-light">
              Remember your password? <a href="login.php" class="text-primary">Back to Login</a>
            </div>
          </form>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
  </body>
</html>