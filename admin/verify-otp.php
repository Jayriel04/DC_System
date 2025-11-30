<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

$toast_message = null;
function setToast($message, $type) {
    global $toast_message;
    $toast_message = ['message' => $message, 'type' => $type];
}

// Redirect if email is not in session
if (empty($_SESSION['otp_email'])) {
    header('location:forgot-password.php');
    exit();
}

if (isset($_POST['submit'])) {
    $otp = trim($_POST['otp']);
    $new_password = trim($_POST['newpassword']);
    $confirm_password = trim($_POST['confirmpassword']);
    
    // Check if OTP has expired (e.g., 5 minutes)
    if (time() - $_SESSION['otp_timestamp'] > 300) {
        setToast('OTP has expired. Please request a new one.', 'danger');
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_timestamp']);
    } elseif ($_SESSION['otp'] != $otp) {
        setToast('Invalid OTP entered.', 'danger');
    } elseif ($new_password != $confirm_password) {
        setToast('New password and confirm password do not match.', 'danger');
    } elseif (strlen($new_password) < 8) {
        setToast('Password must be at least 8 characters long.', 'danger');
    } elseif (!preg_match("#[0-9]+#", $new_password)) {
        setToast('Password must include at least one number.', 'danger');
    } elseif (!preg_match("#[a-zA-Z]+#", $new_password)) {
        setToast('Password must include at least one letter.', 'danger');
    } else {
        $hashed_password = md5($new_password);
        $email = $_SESSION['otp_email'];

        // Store error info
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $update_sql = "UPDATE tbladmin SET Password=:password WHERE Email=:email";
        $update_query = $dbh->prepare($update_sql);
        $update_query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $update_query->bindParam(':email', $email, PDO::PARAM_STR);

        try {
            if ($update_query->execute() && $update_query->rowCount() > 0) {
                setToast('Password has been reset successfully. You can now login.', 'success');
                // Clear session variables
                unset($_SESSION['otp']);
                unset($_SESSION['otp_email']);
                unset($_SESSION['otp_timestamp']);
            } else {
                setToast('Failed to update password. Please try again.', 'danger');
            }
        } catch (PDOException $e) {
            // For debugging: error_log($e->getMessage());
            setToast('A database error occurred. Please try again.', 'danger');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - JF Dental Care</title>
    <link rel="stylesheet" href="css/verify-otp.css">
    <link rel="stylesheet" href="css/toast.css">
</head>
<body>
    <div class="container">
        <a href="login.php" class="close-btn">&times;</a>
        
        <div class="header">
            <div class="logo"><img src="../images/Jf logo.png" alt="JF Dental Care Logo"></div>
            <div class="brand-name">JF DENTAL CARE</div>
        </div>

        <h1>Reset Password</h1>
        <p class="description">An OTP has been sent to your email. Enter it below to reset your password.</p>

        <div id="toast-container"></div>
        <?php
            if ($toast_message) {
                // If a password reset was successful, show a success message and redirect.
                if ($toast_message['type'] === 'success') {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showToast('{$toast_message['message']}', '{$toast_message['type']}');
                            setTimeout(function() { window.location.href = 'login.php'; }, 3000);
                        });
                    </script>";
                } else {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
                }
            }
        ?>

        <form id="resetForm" method="post">
            <div class="form-group">
                <label for="otp">OTP</label>
                <input type="text" id="otp" name="otp" placeholder="Enter OTP" maxlength="6" required>
                <div class="error-message" id="otpError">Please enter a valid 6-digit OTP</div>
            </div>

            <div class="form-group">
                <label for="newPassword">New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="newPassword" name="newpassword" placeholder="Enter new password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <div class="error-message" id="passwordError">Password must be at least 8 characters</div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="confirmPassword" name="confirmpassword" placeholder="Confirm new password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <div class="error-message" id="confirmError">Passwords do not match</div>
            </div>

            <button type="submit" name="submit" class="submit-btn">Reset Password</button>
        </form>
    </div>

    <script src="js/toast.js"></script>
    <script src="js/verify-otp.js"></script>
</body>
</html>