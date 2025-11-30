<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure this path is correct for your project structure
require '../vendor/autoload.php';

$toast_message = null;
function setToast($message, $type) {
    global $toast_message;
    $toast_message = ['message' => $message, 'type' => $type];
}
if(isset($_POST['submit'])) {
  $email = $_POST['email'];
  // Check if email exists in the database
  $sql = "SELECT ID, UserName FROM tbladmin WHERE Email=:email";
  $query = $dbh->prepare($sql);
  $query->bindParam(':email', $email, PDO::PARAM_STR);
  $query->execute();
  if($query->rowCount() > 0) {
    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    // Store OTP, email, and timestamp in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_timestamp'] = time();

    // Send OTP to user's email
    $mail = new PHPMailer(true);
    try {
        //Server settings - Update with your SMTP details
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jezrahconde@gmail.com'; // SMTP username
        $mail->Password   = 'gzht tvxy vxzx awrt'; // SMTP password or App Password
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
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Password Reset';
        $mail->Body    = 'Your One-Time Password (OTP) for resetting your password is: <b>' . $otp . '</b>. It is valid for 5 minutes.';

        $mail->send();
        header('location:verify-otp.php');
        exit(); // Stop script execution after redirect
    } catch (Exception $e) {
        // For debugging: error_log("Mailer Error: {$mail->ErrorInfo}");
        setToast('The OTP email could not be sent. Please try again later.', 'danger');
    }
  } else {
    setToast('Email address not found in our records.', 'warning');
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
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="text-center mb-4">
                    <i class="icon-lock text-dark" style="font-size: 50px;"></i>
                    <h4 class="mt-3">Reset Password</h4>
                    <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                </div>
                <form class="pt-3" name="chngpwd" method="post">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control form-control-lg"style="border-radius: 10px;" placeholder="Enter your email" required="true" name="email">
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="submit" class="btn btn-success btn-block btn-lg font-weight-medium auth-form-btn"style="border-radius: 10px;">Send OTP</button>
                    </div>
                    <div class="text-center mt-4 font-weight-light">
                        Remember your password? <strong><a href="login.php" class="text-black">Back to Login</a></strong>
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