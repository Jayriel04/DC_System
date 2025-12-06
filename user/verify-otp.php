<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// It's better to configure error reporting in php.ini for a production environment
// and show all errors during development. Avoid error_reporting(0);
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

// Handle OTP resend request
if (isset($_GET['resend']) && $_GET['resend'] == 1) {
    $email = $_SESSION['otp_email'];
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_timestamp'] = time(); // Reset the timestamp

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
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
        $mail->Subject = 'Your New OTP for Password Reset';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <h2 style="margin: 0; color: #092c7a;">JF Dental Care - Password Reset</h2>
                </div>
                <div style="padding: 20px 0; text-align: center;">
                    <p style="font-size: 16px;">Hello,</p>
                    <p style="font-size: 16px;">You requested a password reset. Please use the One-Time Password (OTP) below to proceed.</p>
                    <div style="font-size: 28px; font-weight: bold; color: #0056b3; background-color: #e7f3ff; padding: 15px 25px; border-radius: 8px; display: inline-block; letter-spacing: 4px; margin: 20px 0; border: 1px dashed #007bff;">
                        ' . $otp . '
                    </div>
                    <p style="font-size: 16px;">This OTP is valid for 5 minutes.</p>
                    <p style="font-size: 14px; color: #555;">If you did not request a password reset, please ignore this email or contact our support if you have concerns.</p>
                </div>
                <div style="text-align: center; font-size: 12px; color: #777; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p>Thank you,<br>The JF Dental Care Team</p>
                    <p>&copy; ' . date("Y") . ' JF Dental Care. All rights reserved.</p>
                </div>
            </div>
        ';
        $mail->AltBody = 'Your new One-Time Password (OTP) is: ' . $otp . '. It is valid for 5 minutes. If you did not request this, please ignore this email.';

        $mail->send();
        setToast('A new OTP has been sent to your email.', 'success');
    } catch (Exception $e) {
        // For debugging: error_log("Mailer Error: {$mail->ErrorInfo}");
        setToast('The OTP email could not be sent. Please try again later.', 'danger');
    }
    // Redirect to the same page without the resend parameter to prevent re-sending on refresh
    header('Location: verify-otp.php');
    exit();
}

// Step 1: Handle OTP verification
if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);

    // Check if OTP has expired (e.g., 5 minutes)
    if (time() - $_SESSION['otp_timestamp'] > 300) {
        setToast('OTP has expired. Please request a new one.', 'danger');
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_timestamp']);
        // Redirect to start over
        header('location:forgot-password.php');
        exit();
    } elseif ($_SESSION['otp'] != $otp) {
        setToast('Invalid OTP entered.', 'danger');
    } else {
        // OTP is correct, set session flag to show password form
        $_SESSION['otp_verified'] = true;
        setToast('OTP verified successfully. Please set your new password.', 'success');
    }
}

// Step 2: Handle password reset
if (isset($_POST['reset_password'])) {
    // Double-check that OTP was actually verified
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        setToast('Please verify your OTP first.', 'danger');
        header('Location: verify-otp.php');
        exit();
    }

    $new_password = trim($_POST['newpassword']);
    $confirm_password = trim($_POST['confirmpassword']);

    if ($new_password !== $confirm_password) {
        setToast('New password and confirm password do not match.', 'danger');
    } elseif (strlen($new_password) < 8 || !preg_match("#[0-9]+#", $new_password) || !preg_match("#[a-zA-Z]+#", $new_password)) {
        setToast('Password must be at least 8 characters and include letters and numbers.', 'danger');
    } else {
        // Use modern, secure password hashing
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['otp_email'];

        $update_sql = "UPDATE tblpatient SET password=:password WHERE email=:email";
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
                unset($_SESSION['otp_verified']);
                // Redirect to login after a short delay
                header("refresh:3;url=login.php");
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
    <style>
        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.3s;
        }
        .otp-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
    </style>
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
                <div id="toast-container"></div>
                <script src="../js/toast.js"></script>
                <?php
                    if ($toast_message) {
                        // If a password reset was successful, show a success message and redirect.
                        if ($toast_message['type'] === 'success' && !isset($_POST['verify_otp'])) {
                            echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); setTimeout(function() { window.location.href = 'login.php'; }, 3000); });</script>";
                        } else {
                            echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$toast_message['message']}', '{$toast_message['type']}'); });</script>";
                        }
                    }

                    // Determine which part of the form to show
                    $show_password_form = isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'];
                ?>

                <?php if (!$show_password_form): ?>
                    <h1>Verify Code</h1>
                    <p class="subtitle">Enter the 6-digit OTP sent to your email address.</p>
                    <form method="post" action="">
                        <div class="form-group">
                            
                            <div class="otp-container">
                                <input type="text" id="otp-1" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                                <input type="text" id="otp-2" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                                <input type="text" id="otp-3" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                                <input type="text" id="otp-4" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                                <input type="text" id="otp-5" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                                <input type="text" id="otp-6" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                            </div>
                            <!-- Hidden input to store the combined OTP value -->
                            <input type="hidden" name="otp" id="otp">
                        </div>
                        <button type="submit" name="verify_otp" class="login-btn">Verify Code</button>
                        <a href="verify-otp.php?resend=1" class="resend-btn" style="text-decoration: none; text-align: center; display: block; margin-top: 10px;">Request New Code</a>
                    </form>
                <?php else: ?>
                    <h1>Reset Your Password</h1>
                    <p class="subtitle">Set a new password for your account.</p>
                    <form method="post" action="" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="newpassword">New Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="newpassword" class="form-control" placeholder="Enter new password" required name="newpassword">
                                
                            </div>
                            <div id="password-strength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirmpassword">Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirmpassword" class="form-control" placeholder="Confirm new password" required name="confirmpassword">
                                
                            </div>
                        </div>
                        <button type="submit" name="reset_password" class="login-btn">Reset Password</button>
                    </form>
                <?php endif; ?>

                <script>
                <?php if (!$show_password_form): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    const otpContainer = document.querySelector('.otp-container');
                    if (otpContainer) {
                        const inputs = otpContainer.querySelectorAll('.otp-input');
                        const hiddenOtpInput = document.getElementById('otp');

                        const updateHiddenInput = () => {
                            let otpValue = '';
                            inputs.forEach(input => {
                                otpValue += input.value;
                            });
                            hiddenOtpInput.value = otpValue;
                        };

                        inputs.forEach((input, index) => {
                            input.addEventListener('input', () => {
                                if (input.value.length === 1 && index < inputs.length - 1) {
                                    inputs[index + 1].focus();
                                }
                                updateHiddenInput();
                            });

                            input.addEventListener('keydown', (e) => {
                                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                                    inputs[index - 1].focus();
                                }
                            });

                            input.addEventListener('paste', (e) => {
                                e.preventDefault();
                                const paste = (e.clipboardData || window.clipboardData).getData('text');
                                if (paste.length === 6 && /^\d{6}$/.test(paste)) {
                                    inputs.forEach((input, i) => {
                                        input.value = paste[i];
                                    });
                                    updateHiddenInput();
                                    inputs[5].focus();
                                }
                            });
                        });
                    }
                });
                <?php endif; ?>

                <?php if ($show_password_form): ?>
                // Only run password-related scripts if the password form is visible
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
                <?php endif; ?>
                </script>
            </div>
        </div>
    </div>
</body>
</html>