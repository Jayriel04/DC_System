<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure this path is correct for your project structure
require '../vendor/autoload.php';

// Message variable for feedback
$message = '';
if(isset($_POST['submit'])) {
  $email = $_POST['email'];
  // Check if email exists in the database
  $sql = "SELECT number, username FROM tblpatient WHERE email=:email";
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
        $mail->Username   = 'canonio.jezrahfaith.mcc@gmail.com'; // SMTP username
        $mail->Password   = 'hevm yhzs rnbh shqj'; // SMTP password or App Password
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
        $message = "<div class='alert alert-danger text-center'>The OTP email could not be sent. Please check your mail server configuration. Mailer Error: {$mail->ErrorInfo}</div>";
    }
  } else {
    $message = '<div class="alert alert-warning text-center">Email address not found in our records.</div>';
  }
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      /* Style for the message box */
      .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
      }
      .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
      }
      .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
      }
      .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
      }
    </style>
    <link rel="stylesheet" href="css/login.css">
  </head>
  <body>
    <div class="container-login">
        <div class="left-section">
            <div class="floating-shape shape1"></div>
            <div class="floating-shape shape2"></div>
            <div class="floating-shape shape3"></div>
            
            <div class="illustration">
                <div class="tooth">
                    🦷
                    <div class="tooth-icon">
                        <i class="fas fa-key"></i>
                    </div>
                </div>
                <div class="toothbrush-emoji">🪥</div>
                <div class="syringe-emoji">💉</div>
                <div class="wrench-emoji">🔧</div>
                <div class="magnifier-emoji">🔎</div>
            </div>
        </div>

        <div class="right-section">
            <a href="login.php" class="close-btn" title="Close">&times;</a>
            <div class="header">
                <a href="../index.php" class="logo">
                    <img src="../images/Jf logo.png" alt="JF Dental Care Logo">
                    <span>JF DENTAL CARE</span>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="color: #999; font-size: 14px;">Remember your password?</span>
                    <a href="login.php" class="create-account-link" style="background: #3498db; color: white;">SIGN IN</a>
                </div>
            </div>

            <div class="form-container">
                <h1>Reset Password</h1>
                <p class="subtitle">Enter your email to receive a One-Time Password (OTP).</p>
                <?php if(!empty($message)) echo $message; ?>
                <form method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" class="form-control" placeholder="Enter your email" required name="email">
                </div>
                <button type="submit" name="submit" class="login-btn">Send OTP</button>
                </form> 
            </div>
        </div>
    </div>
  </body>
</html>