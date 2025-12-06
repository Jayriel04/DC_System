<?php
session_start();
error_reporting(0);
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
        // $mail->SMTPDebug = 2; // Enable for verbose debug output
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/forgot-password-v2.css">
    <link rel="stylesheet" href="css/toast.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="floating-shape shape1"></div>
            <div class="floating-shape shape2"></div>
            <div class="floating-shape shape3"></div>
            
            <div class="illustration">
                <div class="tooth">
                    🦷
                    <div class="tooth-icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="toothbrush-emoji">🪥</div>
                <div class="syringe-emoji">💉</div>
                <div class="wrench-emoji">🔧</div>
                <div class="magnifier-emoji">🔎</div>
            </div>
        </div>

        <div class="right-panel">
            <a href="login.php" class="close-btn">&times;</a>
            
            <div class="header">
                <div class="logo-section">
                    <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo">
                    <div class="brand-name">JF DENTAL<br>CARE</div>
                </div>
                
            </div>

            <div class="form-content">
                <h1>Forgot Password</h1>
                <p class="subtitle">Enter your email to receive a One-Time Password (OTP).</p>

                <div id="toast-container"></div>
                <?php
                    if ($toast_message) {
                        echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
                    }
                ?>

                <form method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <button type="submit" name="submit" class="send-btn">Send OTP</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/toast.js"></script>
</body>
</html>