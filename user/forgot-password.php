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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
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
    <link rel="stylesheet" href="css/profile.css">
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
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="toothbrush-emoji">🪥</div>
                <div class="syringe-emoji">💉</div>
                <div class="wrench-emoji">🔧</div>
                <div class="magnifier-emoji">🔎</div>
            </div>
    <div class="auth-container">
        <div class="left-panel">
            <a href="../index.php" class="logo">
                <img src="../images/Jf logo.png" alt="JF Dental Care Logo" class="logo-img">
                <span class="logo-text">JF DENTAL CARE</span>
            </a>
            <div class="tagline">Smile with Confidence</div>
            <h1 class="hero-title">
                Forgot Your Password?
            </h1>
             <p class="tagline" style="margin-bottom: 60px;">No worries, we'll help you get back on track.</p>
            <div class="illustration">🔑</div>
        </div>

        <div class="right-section">
        <div class="right-panel">
            <a href="login.php" class="close-btn" title="Close">&times;</a>
            <div class="header">
                <a href="../index.php" class="logo">
                    <img src="../images/Jf logo.png" alt="JF Dental Care Logo">
                    <span>JF DENTAL CARE</span>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
            <div class="form-header">
                <div class="link-container">
                    <span style="color: #999; font-size: 14px;">Remember your password?</span>
                    <a href="login.php" class="create-account-link">SIGN IN</a>
                    <a href="login.php">SIGN IN</a>
                </div>
            </div>

            <div class="form-container">
            <div>
                <?php if(!empty($message)) echo $message; ?>
                <h1>Forgot Password?</h1>
                <h2 class="form-title">Forgot Password?</h2>
                <p class="subtitle">Enter your email to reset your password.</p>

                <form class="pt-3" method="post">
                <form method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" placeholder="Enter your email" required name="email">
                        <input type="email" id="email" placeholder="Enter your email" required name="email">
                    </div>
                    <button type="submit" name="submit" class="login-btn">Send Reset Link</button>
                    <button type="submit" name="submit" class="auth-btn">Send Reset Link</button>
                </form>
            </div>
        </div>
    </div>
  </body>
</html>