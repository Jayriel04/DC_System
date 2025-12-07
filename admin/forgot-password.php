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
        $mail->Subject = 'Your OTP for Admin Password Reset';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <h2 style="margin: 0; color: #092c7a;">JF Dental Care - Admin Password Reset</h2>
                </div>
                <div style="padding: 20px 0; text-align: center;">
                    <p style="font-size: 16px;">Hello Admin,</p>
                    <p style="font-size: 16px;">A password reset was requested for your account. Please use the One-Time Password (OTP) below to proceed.</p>
                    <div style="font-size: 28px; font-weight: bold; color: #0056b3; background-color: #e7f3ff; padding: 15px 25px; border-radius: 8px; display: inline-block; letter-spacing: 4px; margin: 20px 0; border: 1px dashed #007bff;">
                        ' . $otp . '
                    </div>
                    <p style="font-size: 16px;">This OTP is valid for 5 minutes.</p>
                    <p style="font-size: 14px; color: #555;">If you did not request a password reset, please ignore this email or contact support immediately.</p>
                </div>
                <div style="text-align: center; font-size: 12px; color: #777; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p>Thank you,<br>The JF Dental Care Team</p>
                    <p>&copy; ' . date("Y") . ' JF Dental Care. All rights reserved.</p>
                </div>
            </div>
        ';
        $mail->AltBody = 'Your One-Time Password (OTP) for your admin account is: ' . $otp . '. It is valid for 5 minutes. If you did not request this, please ignore this email.';

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
                    <div class="brand-name">JF DENTAL CARE</div>
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