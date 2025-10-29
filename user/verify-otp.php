<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

$message = '';

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
        $message = '<div class="alert alert-danger text-center">OTP has expired. Please request a new one.</div>';
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_timestamp']);
    } elseif ($_SESSION['otp'] != $otp) {
        $message = '<div class="alert alert-danger text-center">Invalid OTP entered.</div>';
    } elseif ($new_password != $confirm_password) {
        $message = '<div class="alert alert-danger text-center">New password and confirm password do not match.</div>';
    } elseif (strlen($new_password) < 8) {
        $message = '<div class="alert alert-danger text-center">Password must be at least 8 characters long.</div>';
    } elseif (!preg_match("#[0-9]+#", $new_password)) {
        $message = '<div class="alert alert-danger text-center">Password must include at least one number.</div>';
    } elseif (!preg_match("#[a-zA-Z]+#", $new_password)) {
        $message = '<div class="alert alert-danger text-center">Password must include at least one letter.</div>';
    } else {
        $hashed_password = md5($new_password);
        $email = $_SESSION['otp_email'];

        // Store error info
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $update_sql = "UPDATE tblpatient SET password=:password WHERE email=:email";
        $update_query = $dbh->prepare($update_sql);
        $update_query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $update_query->bindParam(':email', $email, PDO::PARAM_STR);

        try {
            if ($update_query->execute()) {
                $message = '<div class="alert alert-success text-center">Password has been reset successfully. You can now <a href="login.php">login</a>.</div>';
                // Clear session variables
                unset($_SESSION['otp']);
                unset($_SESSION['otp_email']);
                unset($_SESSION['otp_timestamp']);
            } else {
                $message = '<div class="alert alert-danger text-center">Failed to update password. Please try again.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger text-center">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP & Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
      .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
      .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
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
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-section">
            <a href="login.php" class="close-btn" title="Close">&times;</a>
            <div class="header">
                <a href="../index.php" class="logo">
                    <img src="../images/Jf logo.png" alt="JF Dental Care Logo">
                    <span>JF DENTAL CARE</span>
                </a>
            </div>

            <div class="form-container">
                <h1>Reset Password</h1>
                <p class="subtitle">An OTP has been sent to your email. Enter it below to reset your password.</p>
                <?php if (!empty($message)) echo $message; ?>
                <form method="post" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="otp">OTP</label>
                        <input type="text" id="otp" class="form-control" placeholder="Enter OTP" required name="otp" pattern="[0-9]{6}" title="Please enter the 6-digit OTP">
                    </div>
                    <div class="form-group">
                        <label for="newpassword">New Password</label>
                        <input type="password" id="newpassword" class="form-control" placeholder="Enter new password" required name="newpassword" pattern="(?=.*\d)(?=.*[a-zA-Z]).{8,}" title="Must contain at least 8 characters with both letters and numbers">
                    </div>
                    <div class="form-group">
                        <label for="confirmpassword">Confirm New Password</label>
                        <input type="password" id="confirmpassword" class="form-control" placeholder="Confirm new password" required name="confirmpassword">
                    </div>
                    <div id="password-strength" class="mb-3"></div>
                    <button type="submit" name="submit" class="login-btn">Reset Password</button>
                </form>
                
                <script>
                function validateForm() {
                    var newPassword = document.getElementById('newpassword').value;
                    var confirmPassword = document.getElementById('confirmpassword').value;
                    
                    if (newPassword !== confirmPassword) {
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (newPassword.length < 8) {
                        alert('Password must be at least 8 characters long');
                        return false;
                    }
                    
                    if (!/\d/.test(newPassword)) {
                        alert('Password must contain at least one number');
                        return false;
                    }
                    
                    if (!/[a-zA-Z]/.test(newPassword)) {
                        alert('Password must contain at least one letter');
                        return false;
                    }
                    
                    return true;
                }
                
                // Password strength indicator
                document.getElementById('newpassword').addEventListener('input', function() {
                    var password = this.value;
                    var strength = 0;
                    
                    if (password.length >= 8) strength++;
                    if (/\d/.test(password)) strength++;
                    if (/[a-z]/.test(password)) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    
                    var strengthDiv = document.getElementById('password-strength');
                    var strengthText = '';
                    var strengthColor = '';
                    
                    switch(strength) {
                        case 0:
                        case 1:
                            strengthText = 'Weak';
                            strengthColor = '#ff4444';
                            break;
                        case 2:
                        case 3:
                            strengthText = 'Medium';
                            strengthColor = '#ffbb33';
                            break;
                        case 4:
                        case 5:
                            strengthText = 'Strong';
                            strengthColor = '#00C851';
                            break;
                    }
                    
                    strengthDiv.innerHTML = 'Password Strength: ' + strengthText;
                    strengthDiv.style.color = strengthColor;
                });
                </script>
            </div>
        </div>
    </div>
</body>
</html>