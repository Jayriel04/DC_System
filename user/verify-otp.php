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
        
        $update_sql = "UPDATE tblpatient SET password=:password WHERE email=:email";
        $update_query = $dbh->prepare($update_sql);
        $update_query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $update_query->bindParam(':email', $email, PDO::PARAM_STR);

        try {
            if ($update_query->execute()) {
                setToast('Password has been reset successfully. You can now login.', 'success');
                // Clear session variables
                unset($_SESSION['otp']);
                unset($_SESSION['otp_email']);
                unset($_SESSION['otp_timestamp']);
            } else {
                setToast('Failed to update password. Please try again.', 'danger');
            }
        } catch (PDOException $e) {
            setToast('A database error occurred. Please try again later.', 'danger');
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
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="../css/toast.css">
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
                
                <div id="toast-container"></div>
                <script src="../js/toast.js"></script>
                <?php
                    if ($toast_message) {
                        echo "<script>showToast('{$toast_message['message']}', '{$toast_message['type']}');</script>";
                    }
                ?>

                <form method="post" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="otp">OTP</label>
                        <input type="text" id="otp" class="form-control" placeholder="Enter OTP" required name="otp" pattern="[0-9]{6}" title="Please enter the 6-digit OTP">
                    </div>
                    <div class="form-group">
                        <label for="newpassword">New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="newpassword" class="form-control" placeholder="Enter new password" required name="newpassword" pattern="(?=.*\d)(?=.*[a-zA-Z]).{8,}" title="Must contain at least 8 characters with both letters and numbers">
                            <i class="fas fa-eye toggle-password" id="toggleNewPassword"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmpassword">Confirm New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirmpassword" class="form-control" placeholder="Confirm new password" required name="confirmpassword">
                            <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    <div id="password-strength" class="mb-3"></div>
                    <button type="submit" name="submit" class="login-btn">Reset Password</button>
                </form>
                
                <script>
                function validateForm() {
                    var newPassword = document.getElementById('newpassword').value;
                    var confirmPassword = document.getElementById('confirmpassword').value;
                    
                    if (newPassword !== confirmPassword) {
                        showToast('Passwords do not match!', 'danger');
                        return false;
                    }
                    
                    if (newPassword.length < 8) {
                        showToast('Password must be at least 8 characters long', 'danger');
                        return false;
                    }
                    
                    if (!/\d/.test(newPassword)) {
                        showToast('Password must contain at least one number', 'danger');
                        return false;
                    }
                    
                    if (!/[a-zA-Z]/.test(newPassword)) {
                        showToast('Password must contain at least one letter', 'danger');
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

                // Password toggle functionality
                function setupPasswordToggle(toggleId, inputId) {
                    const toggle = document.getElementById(toggleId);
                    const input = document.getElementById(inputId);

                    if (toggle && input) {
                        toggle.addEventListener('click', function() {
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.classList.toggle('fa-eye');
                            this.classList.toggle('fa-eye-slash');
                        });
                    }
                }

                setupPasswordToggle('toggleNewPassword', 'newpassword');
                setupPasswordToggle('toggleConfirmPassword', 'confirmpassword');
                </script>
            </div>
        </div>
    </div>
</body>
</html>